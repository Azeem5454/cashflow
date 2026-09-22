<?php

namespace App\Services;

use App\Models\Book;
use App\Models\RecurringEntry;
use App\Models\ReportSchedule;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Single place that decides whether a user is on Pro and which source grants it.
 *
 * A user is Pro when ANY of these hold:
 *   - Stripe: an active `default` Cashier subscription (incl. cancel-at-period-end
 *     grace period and past_due, which the Stripe webhook never downgraded on)
 *   - Store:  store_pro_expires_at is in the future AND store_platform is
 *             app_store / play_store (App Store / Google Play via RevenueCat)
 *   - Admin:  plan_source === 'admin' (admin "Force Pro")
 *   - Sticky: plan === 'pro' with plan_source 'stripe' or null (null = Pro from
 *             before plan_source existed, source unknown). recompute() NEVER
 *             downgrades these: the local Cashier row can lag behind Stripe
 *             (plan_source='stripe' is set from the webhook payload / checkout
 *             success before Cashier writes the row). Only applyStripeStatus()
 *             with a terminal status (or admin Force Free) downgrades them —
 *             exactly the pre-IAP behaviour.
 *
 * plan_source precedence when several hold: admin > stripe > legacy(null) > store.
 *
 * Pro → Free transitions pause recurring entries + email report schedules
 * (marking them paused_by_system_at); Free → Pro transitions resume ONLY the
 * rows the system paused.
 */
class PlanService
{
    public const SOURCE_STRIPE = 'stripe';
    public const SOURCE_APP_STORE = 'app_store';
    public const SOURCE_PLAY_STORE = 'play_store';
    public const SOURCE_ADMIN = 'admin';

    public const STORE_SOURCES = [self::SOURCE_APP_STORE, self::SOURCE_PLAY_STORE];

    /** Stripe statuses the webhook treats as "Pro". */
    private const STRIPE_ACTIVE_STATUSES = ['active'];

    /** Stripe statuses the webhook treats as "no longer Pro via Stripe". */
    private const STRIPE_TERMINAL_STATUSES = ['canceled', 'unpaid', 'incomplete_expired'];

    /**
     * Recompute plan + plan_source from current local state (store events,
     * billing sync, expiry sweep). Never downgrades a Stripe-sourced or legacy
     * Pro user — see class doc.
     *
     * @param  string|null  $reason  short label for logs ('revenuecat:RENEWAL', 'billing_sync', ...)
     */
    public function recompute(User $user, ?string $reason = null): User
    {
        $wasPro = $user->isPro();
        // Recompute may KEEP Stripe Pro but never GRANT it: only Stripe's own
        // signals (the webhook via applyStripeStatus, or the checkout-success
        // sync) upgrade through Stripe. A stale local Cashier row — e.g. an old
        // test-mode subscription, or a failed cancelNow during admin Force Free —
        // must not silently turn a Free user into Pro.
        $stripe = $wasPro
            && ($user->plan_source === self::SOURCE_STRIPE || $this->stripeActive($user));

        return $this->apply($user, $stripe, $reason, keepLegacy: true);
    }

    /**
     * Called by the Stripe webhook listener with the status from the event
     * payload (Cashier fires WebhookReceived BEFORE it updates the local
     * subscription row, so the payload is the source of truth here).
     *
     * Behaviour for users without a store entitlement is identical to the
     * pre-IAP listener:
     *   - 'active'                                     → pro (Stripe takes over as the source,
     *                                                    even from an admin grant)
     *   - 'canceled' | 'unpaid' | 'incomplete_expired' → free + downgrade side-effects
     *   - anything else                                → no change
     */
    public function applyStripeStatus(User $user, string $status, bool $sideEffects = true): ?User
    {
        if (in_array($status, self::STRIPE_ACTIVE_STATUSES, true)) {
            if ($this->hasStoreEntitlement($user)) {
                Log::warning('Possible double billing: Stripe subscription active while a store subscription is active', [
                    'user_id'        => $user->id,
                    'store_platform' => $user->store_platform,
                    'store_expires'  => $user->store_pro_expires_at?->toIso8601String(),
                ]);
            }

            // Stripe now pays for Pro: it replaces an admin grant as the source,
            // so a later Stripe cancellation downgrades as it always did.
            if ($user->plan_source === self::SOURCE_ADMIN) {
                $user->plan_source = self::SOURCE_STRIPE;
            }

            return $this->apply($user, true, 'stripe:' . $status, keepLegacy: false);
        }

        if (in_array($status, self::STRIPE_TERMINAL_STATUSES, true)) {
            $this->apply($user, false, 'stripe:' . $status, keepLegacy: false, sideEffects: false);

            // Legacy semantics: a terminal Stripe status always (re)ran the
            // pause side-effects when the user ends up Free — they are
            // idempotent (they only touch active rows).
            if ($sideEffects && ! $user->isPro()) {
                $this->applyDowngradeSideEffects($user);
            }

            return $user;
        }

        return null;
    }

    /** Admin "Force Pro". */
    public function forcePro(User $user): User
    {
        $wasPro = $user->isPro();

        $user->plan = 'pro';
        $user->plan_source = self::SOURCE_ADMIN;
        $user->save();

        if (! $wasPro) {
            $this->resumeSystemPaused($user);
        }

        return $user;
    }

    /**
     * Admin "Force Free": clears the admin grant and forces the local plan to
     * Free (the caller cancels Stripe first). A still-active store
     * subscription can't be cancelled from here; if it renews, the next
     * RevenueCat event restores Pro.
     */
    public function forceFree(User $user): User
    {
        $user->plan = 'free';
        $user->plan_source = null;
        $user->save();

        $this->applyDowngradeSideEffects($user);

        return $user;
    }

    public function hasStoreEntitlement(User $user): bool
    {
        return $user->store_pro_expires_at !== null
            && $user->store_pro_expires_at->isFuture()
            && in_array($user->store_platform, self::STORE_SOURCES, true);
    }

    /** Pro granted by the App Store / Google Play (the source that currently wins). */
    public function isStoreBilled(User $user): bool
    {
        return $user->isPro() && in_array($user->plan_source, self::STORE_SOURCES, true);
    }

    public function stripeActive(User $user): bool
    {
        if ($user->subscribed('default')) {
            return true;
        }

        // The Stripe webhook never downgraded on past_due (Stripe is still
        // retrying the card), so keep treating it as Pro here too.
        $sub = $user->subscription('default');

        return $sub !== null && $sub->stripe_status === 'past_due' && $sub->ends_at === null;
    }

    /**
     * Pause recurring entries + email report schedules in every book the user owns,
     * marking them as paused by the system. Idempotent — only touches active rows.
     */
    public function applyDowngradeSideEffects(User $user): void
    {
        $bookIds = $this->ownedBookIds($user);
        if ($bookIds === []) {
            return;
        }

        $now = now();

        RecurringEntry::whereIn('book_id', $bookIds)
            ->where('status', 'active')
            ->update(['status' => 'paused', 'paused_by_system_at' => $now]);

        ReportSchedule::whereIn('book_id', $bookIds)
            ->where('is_active', true)
            ->update(['is_active' => false, 'paused_by_system_at' => $now]);
    }

    /**
     * Back on Pro: resume only what an automatic downgrade paused. A resumed
     * rule's next run is moved to today or later so the generator doesn't
     * back-fill the months the owner wasn't on Pro.
     */
    public function resumeSystemPaused(User $user): void
    {
        $bookIds = $this->ownedBookIds($user);
        if ($bookIds === []) {
            return;
        }

        $today = now()->startOfDay();

        RecurringEntry::whereIn('book_id', $bookIds)
            ->where('status', 'paused')
            ->whereNotNull('paused_by_system_at')
            ->get()
            ->each(function (RecurringEntry $rule) use ($today) {
                try {
                    $guard = 0;
                    while ($rule->next_run_at && $rule->next_run_at->lt($today) && $guard++ < 2000) {
                        $rule->advanceNextRun();
                    }
                } catch (\UnhandledMatchError) {
                    // Unknown legacy frequency — leave next_run_at as it is.
                }

                $rule->status = 'active';
                $rule->paused_by_system_at = null;
                $rule->save();
            });

        ReportSchedule::whereIn('book_id', $bookIds)
            ->where('is_active', false)
            ->whereNotNull('paused_by_system_at')
            ->update(['is_active' => true, 'paused_by_system_at' => null]);
    }

    /** @return array<int, string> */
    private function ownedBookIds(User $user): array
    {
        $businessIds = $user->ownedBusinesses()->pluck('id');

        if ($businessIds->isEmpty()) {
            return [];
        }

        return Book::whereIn('business_id', $businessIds)->pluck('id')->all();
    }

    private function apply(User $user, bool $stripe, ?string $reason, bool $keepLegacy, bool $sideEffects = true): User
    {
        $wasPro = $user->isPro();
        $store = $this->hasStoreEntitlement($user);
        $admin = $user->plan_source === self::SOURCE_ADMIN && $wasPro;
        $legacy = $keepLegacy && $wasPro && $user->plan_source === null;

        if ($admin) {
            $plan = 'pro';
            $source = self::SOURCE_ADMIN;
        } elseif ($stripe) {
            $plan = 'pro';
            $source = self::SOURCE_STRIPE;
        } elseif ($legacy) {
            // Legacy (source unknown) stays sticky even if a store purchase is
            // added, so a later store lapse can't downgrade them.
            $plan = 'pro';
            $source = null;
        } elseif ($store) {
            $plan = 'pro';
            $source = $user->store_platform;
        } else {
            $plan = 'free';
            $source = null;
        }

        $user->plan = $plan;
        $user->plan_source = $source;

        if ($user->isDirty(['plan', 'plan_source'])) {
            $user->save();

            if ($wasPro !== ($plan === 'pro')) {
                Log::info('Plan changed', [
                    'user_id' => $user->id,
                    'plan'    => $plan,
                    'source'  => $source,
                    'reason'  => $reason,
                ]);
            }
        }

        if ($sideEffects && $wasPro && $plan === 'free') {
            $this->applyDowngradeSideEffects($user);
        }

        if (! $wasPro && $plan === 'pro') {
            $this->resumeSystemPaused($user);
        }

        return $user;
    }
}
