<?php

namespace Tests\Feature\Billing;

use App\Livewire\Admin\Users as AdminUsers;
use App\Models\User;
use App\Services\PlanService;
use Livewire\Livewire;

class PlanServiceTest extends BillingTestCase
{
    // ── Stripe-only users: identical to the pre-IAP webhook listener ──────

    public function test_stripe_webhook_active_makes_free_user_pro(): void
    {
        $user = $this->stripeUser('incomplete');
        $this->assertFalse($user->isPro());

        $this->stripeWebhook($user, 'active', 'customer.subscription.created');

        $user->refresh();
        $this->assertTrue($user->isPro());
        $this->assertSame('stripe', $user->plan_source);
    }

    public function test_stripe_webhook_terminal_statuses_downgrade_and_pause_features(): void
    {
        foreach (['canceled', 'unpaid', 'incomplete_expired'] as $status) {
            $user = $this->stripeUser('active');
            $book = $this->withActiveProFeatures($user);

            $this->stripeWebhook($user, $status, 'customer.subscription.deleted');

            $user->refresh();
            $this->assertFalse($user->isPro(), $status);
            $this->assertNull($user->plan_source);
            $this->assertProFeaturesPaused($book);
        }
    }

    public function test_stripe_webhook_other_statuses_change_nothing(): void
    {
        foreach (['past_due', 'trialing', 'incomplete'] as $status) {
            $user = $this->stripeUser('active');
            $book = $this->withActiveProFeatures($user);

            $this->stripeWebhook($user, $status);

            $this->assertTrue($user->fresh()->isPro(), $status);
            $this->assertProFeaturesActive($book);
        }
    }

    public function test_stripe_webhook_ignores_unrelated_event_types_and_unknown_customers(): void
    {
        $user = $this->stripeUser('active');

        event(new \Laravel\Cashier\Events\WebhookReceived([
            'type' => 'invoice.paid',
            'data' => ['object' => ['customer' => $user->stripe_id, 'status' => 'canceled']],
        ]));
        event(new \Laravel\Cashier\Events\WebhookReceived([
            'type' => 'customer.subscription.deleted',
            'data' => ['object' => ['customer' => 'cus_unknown', 'status' => 'canceled']],
        ]));

        $this->assertTrue($user->fresh()->isPro());
    }

    public function test_stripe_cancel_on_legacy_pro_user_without_plan_source_still_downgrades(): void
    {
        $user = $this->stripeUser('active');
        $user->plan_source = null; // pre-migration Pro user
        $user->save();

        $this->stripeWebhook($user, 'canceled', 'customer.subscription.deleted');

        $this->assertFalse($user->fresh()->isPro());
    }

    // ── Unions: Stripe + store ────────────────────────────────────────────

    public function test_stripe_cancel_with_active_store_entitlement_stays_pro_without_side_effects(): void
    {
        $user = $this->stripeUser('active');
        $this->giveStoreEntitlement($user, now()->addDays(10), 'play_store');
        $book = $this->withActiveProFeatures($user);

        $this->stripeWebhook($user, 'canceled', 'customer.subscription.deleted');

        $user->refresh();
        $this->assertTrue($user->isPro());
        $this->assertSame('play_store', $user->plan_source);
        $this->assertProFeaturesActive($book);
    }

    public function test_store_expiry_downgrades_only_when_no_stripe(): void
    {
        $plans = app(PlanService::class);

        // Store expired, Stripe still active → Pro via Stripe.
        $withStripe = $this->stripeUser('active');
        $this->giveStoreEntitlement($withStripe, now()->subMinute());
        $plans->recompute($withStripe);
        $this->assertTrue($withStripe->fresh()->isPro());
        $this->assertSame('stripe', $withStripe->fresh()->plan_source);

        // Store expired, no Stripe → Free.
        $storeOnly = $this->makeUser(pro: true);
        $storeOnly->plan_source = 'app_store';
        $this->giveStoreEntitlement($storeOnly, now()->subMinute());
        $book = $this->withActiveProFeatures($storeOnly);
        $plans->recompute($storeOnly);
        $this->assertFalse($storeOnly->fresh()->isPro());
        $this->assertProFeaturesPaused($book);
    }

    public function test_stripe_grace_period_counts_as_pro(): void
    {
        $user = $this->stripeUser('active', now()->addDays(5)); // cancel at period end
        app(PlanService::class)->recompute($user);

        $this->assertTrue($user->fresh()->isPro());
        $this->assertSame('stripe', $user->fresh()->plan_source);
    }

    public function test_admin_source_wins_and_legacy_pro_is_never_auto_downgraded(): void
    {
        $plans = app(PlanService::class);

        $admin = $this->makeUser();
        $plans->forcePro($admin);
        $plans->recompute($admin);
        $this->assertTrue($admin->fresh()->isPro());
        $this->assertSame('admin', $admin->fresh()->plan_source);

        $legacy = $this->makeUser(pro: true); // plan_source null, no Stripe, no store
        $plans->recompute($legacy);
        $this->assertTrue($legacy->fresh()->isPro());
    }

    public function test_downgrade_side_effects_run_once_on_transition(): void
    {
        $plans = app(PlanService::class);
        $user = $this->makeUser(pro: true);
        $user->plan_source = 'app_store';
        $this->giveStoreEntitlement($user, now()->subMinute());
        $book = $this->withActiveProFeatures($user);

        $plans->recompute($user);
        $this->assertProFeaturesPaused($book);

        // Owner re-enables things while Free (or data changes); a second recompute
        // (no transition) must not touch them again.
        \App\Models\RecurringEntry::where('book_id', $book->id)->update(['status' => 'active']);
        $plans->recompute($user->fresh());
        $this->assertSame('active', \App\Models\RecurringEntry::where('book_id', $book->id)->value('status'));
    }

    public function test_free_to_pro_via_store_has_no_side_effects(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook($this->makeBusiness($user));
        $book->recurringEntries()->create([
            'type' => 'out', 'amount' => '1.00', 'description' => 'x', 'frequency' => 'weekly',
            'starts_at' => '2026-03-01', 'next_run_at' => '2026-03-08', 'status' => 'paused',
        ]);

        $this->giveStoreEntitlement($user, now()->addMonth());
        app(PlanService::class)->recompute($user);

        $this->assertTrue($user->fresh()->isPro());
        $this->assertSame('paused', \App\Models\RecurringEntry::where('book_id', $book->id)->value('status'));
    }

    // ── Admin force-pro / force-free ──────────────────────────────────────

    public function test_admin_force_pro_and_force_free(): void
    {
        $admin = User::factory()->create();
        $admin->is_admin = true;
        $admin->save();
        $target = $this->makeUser();
        $book = $this->withActiveProFeatures($target);

        $this->actingAs($admin);

        Livewire::test(AdminUsers::class)->call('forcePro', $target->id);
        $target->refresh();
        $this->assertTrue($target->isPro());
        $this->assertSame('admin', $target->plan_source);

        Livewire::test(AdminUsers::class)->call('forceFree', $target->id);
        $target->refresh();
        $this->assertFalse($target->isPro());
        $this->assertNull($target->plan_source);
        $this->assertProFeaturesPaused($book);
    }

    public function test_billing_columns_are_not_mass_assignable(): void
    {
        $user = $this->makeUser();
        $user->fill([
            'plan_source'          => 'admin',
            'store_pro_expires_at' => now()->addYear(),
            'store_product_id'     => 'x',
            'store_platform'       => 'app_store',
        ])->save();

        $user->refresh();
        $this->assertNull($user->plan_source);
        $this->assertNull($user->store_pro_expires_at);
        $this->assertNull($user->store_product_id);
    }
}
