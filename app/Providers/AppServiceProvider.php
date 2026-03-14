<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Events\WebhookReceived;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        // Sync user.plan when Stripe subscription status changes
        Event::listen(WebhookReceived::class, function (WebhookReceived $event) {
            $type   = $event->payload['type'] ?? '';
            $object = $event->payload['data']['object'] ?? [];

            if (! in_array($type, [
                'customer.subscription.created',
                'customer.subscription.updated',
                'customer.subscription.deleted',
            ])) {
                return;
            }

            $customerId = $object['customer'] ?? null;
            if (! $customerId) {
                return;
            }

            $user = User::where('stripe_id', $customerId)->first();
            if (! $user) {
                return;
            }

            $status = $object['status'] ?? '';

            if ($status === 'active') {
                $user->update(['plan' => 'pro']);
            } elseif (in_array($status, ['canceled', 'unpaid', 'incomplete_expired'])) {
                $user->update(['plan' => 'free']);
            }
        });
    }
}
