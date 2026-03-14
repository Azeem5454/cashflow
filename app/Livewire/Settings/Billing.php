<?php

namespace App\Livewire\Settings;

use Livewire\Component;

class Billing extends Component
{
    public string $flash = ''; // 'success' | 'canceled' | 'resumed' | ''

    public function mount(): void
    {
        $user  = auth()->user();
        $query = request()->query('checkout');

        if ($query === 'success') {
            // Stripe only redirects here on successful payment — update plan immediately.
            // The webhook will also fire async and confirm via AppServiceProvider.
            $user->update(['plan' => 'pro']);
            $user->refresh();
            $this->flash = 'success';
        } elseif ($query === 'canceled') {
            $this->flash = 'canceled';
        }
    }

    public function subscribe(): void
    {
        $user    = auth()->user();
        $priceId = config('services.stripe.pro_price_id');

        if (! $priceId) {
            $this->addError('stripe', 'Stripe price ID is not configured. Set STRIPE_PRO_PRICE_ID in .env.');
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
            $this->addError('stripe', 'Could not open billing portal. Please try again.');
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
