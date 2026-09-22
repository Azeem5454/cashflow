<?php

namespace App\Livewire\Settings;

use App\Services\PlanService;
use Livewire\Attributes\On;
use Livewire\Component;

class Billing extends Component
{
    public string $flash = ''; // 'success' | 'canceled' | 'resumed' | 'processing' | ''

    public function mount(): void
    {
        $user  = auth()->user();
        $query = request()->query('checkout');

        if ($query === 'success') {
            // Stripe redirected us here after successful payment. The webhook
            // will also fire async and update the plan via AppServiceProvider.
            //
            // SECURITY: we do NOT blindly mark the user as Pro based on the
            // query string — that would let anyone flip themselves to Pro by
            // visiting this URL manually. Instead we verify directly against
            // Stripe that an active subscription exists on this customer.
            $this->syncPlanFromStripe($user);
            $user->refresh();
            $this->flash = $user->isPro() ? 'success' : 'processing';
        } elseif ($query === 'canceled') {
            $this->flash = 'canceled';
        }

        // Auto-trigger checkout when redirected from Pro-intent signup.
        if (request()->boolean('auto') && ! $user->isPro() && ! $user->subscribed('default')) {
            $this->subscribe();
        }
    }

    /**
     * Check Stripe directly for an active subscription and sync the local plan.
     * Returns early if no Stripe customer exists yet (first-time signup).
     */
    private function syncPlanFromStripe($user): void
    {
        if (! $user->hasStripeId()) {
            return;
        }

        try {
            $stripeSubs = $user->stripe()->subscriptions->all([
                'customer' => $user->stripe_id,
                'status'   => 'active',
                'limit'    => 1,
            ]);

            $hasActive = ! empty($stripeSubs->data);

            if ($hasActive && ($user->plan !== 'pro' || $user->plan_source !== PlanService::SOURCE_STRIPE)) {
                app(PlanService::class)->applyStripeStatus($user, 'active');
            }
        } catch (\Throwable) {
            // Fall through — webhook will update eventually.
        }
    }

    public function subscribe(): void
    {
        $user    = auth()->user();
        $priceId = config('services.stripe.pro_price_id');

        if (! $priceId) {
            $this->addError('stripe', 'Payment processing is temporarily unavailable. Please try again later.');
            return;
        }

        if ($user->subscribed('default')) {
            return;
        }

        // Already Pro through the App Store / Google Play — a Stripe checkout
        // would bill them twice. Enforced here, not only by hiding the button.
        if ($this->refuseIfStoreBilled($user)) {
            return;
        }

        try {
            $checkout = $user
                ->newSubscription('default', $priceId)
                ->allowPromotionCodes()
                ->checkout([
                    'success_url' => route('billing') . '?checkout=success',
                    'cancel_url'  => route('billing') . '?checkout=canceled',
                ]);

            $this->redirect($checkout->url);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Stripe checkout start failed', [
                'user_id'   => $user->id,
                'stripe_id' => $user->stripe_id,
                'price_id'  => $priceId,
                'message'   => $e->getMessage(),
                'class'     => get_class($e),
            ]);
            $this->addError('stripe', 'Payment processing is temporarily unavailable. Please try again in a few minutes or contact support.');
        }
    }

    public function resume(): void
    {
        $user         = auth()->user();
        $subscription = $user->subscription('default');

        if ($this->refuseIfStoreBilled($user)) {
            return;
        }

        if ($subscription && $subscription->onGracePeriod()) {
            $subscription->resume();
            app(PlanService::class)->applyStripeStatus($user, 'active');
            $user->refresh();
            $this->flash = 'resumed';
            return;
        }

        // Subscription already ended — start fresh checkout
        $this->subscribe();
    }

    private function refuseIfStoreBilled($user): bool
    {
        if (! app(PlanService::class)->hasStoreEntitlement($user)) {
            return false;
        }

        $this->addError('stripe', 'Your Pro plan is billed through ' . self::storeName($user->store_platform) . '. Manage it on your device.');

        return true;
    }

    public static function storeName(?string $platform): string
    {
        return $platform === PlanService::SOURCE_PLAY_STORE ? 'Google Play' : 'the App Store';
    }

    public function openPortal(): void
    {
        try {
            $url = auth()->user()->billingPortalUrl(route('billing'));
            $this->redirect($url);
        } catch (\Exception $e) {
            // Log the full Stripe error for debugging
            \Illuminate\Support\Facades\Log::error('Billing portal error', [
                'user_id'   => auth()->id(),
                'stripe_id' => auth()->user()->stripe_id,
                'message'   => $e->getMessage(),
                'class'     => get_class($e),
            ]);

            $this->addError('stripe', 'Could not open billing portal right now. Please try again in a few minutes or contact support.');
        }
    }

    public function render()
    {
        $user         = auth()->user()->fresh();
        $subscription = $user->subscription('default');

        $plans = app(PlanService::class);

        return view('livewire.settings.billing', [
            'user'         => $user,
            'subscription' => $subscription,
            // Pro via App Store / Google Play: hide Stripe checkout + portal.
            'storeBilled'  => $plans->isStoreBilled($user),
            // Active store entitlement (even if another source wins): never offer Stripe checkout.
            'hasStoreEntitlement' => $plans->hasStoreEntitlement($user),
            'storeName'    => self::storeName($user->store_platform),
            // AI entry allowance on the user's OWN plan.
            'aiQuota'      => \App\Services\AiQuota::remaining($user),
        ]);
    }
}
