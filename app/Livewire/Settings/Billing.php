<?php

namespace App\Livewire\Settings;

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

            if ($hasActive && $user->plan !== 'pro') {
                $user->update(['plan' => 'pro']);
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

        try {
            $checkout = $user
                ->newSubscription('default', $priceId)
                ->checkout([
                    'success_url' => route('billing') . '?checkout=success',
                    'cancel_url'  => route('billing') . '?checkout=canceled',
                ]);

            $this->redirect($checkout->url);
        } catch (\Exception $e) {
            $this->addError('stripe', 'Could not start checkout. Please try again.');
        }
    }

    public function resume(): void
    {
        $user         = auth()->user();
        $subscription = $user->subscription('default');

        if ($subscription && $subscription->onGracePeriod()) {
            $subscription->resume();
            $user->update(['plan' => 'pro']);
            $user->refresh();
            $this->flash = 'resumed';
            return;
        }

        // Subscription already ended — start fresh checkout
        $this->subscribe();
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

            // Show the actual Stripe error to help diagnose (temporary — revert once stable).
            $this->addError('stripe', 'Could not open billing portal: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $user         = auth()->user()->fresh();
        $subscription = $user->subscription('default');

        return view('livewire.settings.billing', [
            'user'         => $user,
            'subscription' => $subscription,
        ]);
    }
}
