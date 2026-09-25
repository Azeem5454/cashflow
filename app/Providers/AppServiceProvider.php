<?php

namespace App\Providers;

use App\Helpers\Setting;
use App\Models\User;
use App\Services\PlanService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
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
        if (! app()->isLocal()) {
            URL::forceScheme('https');
        }

        // Brand + mail overrides from the settings table.
        //
        // This used to run Schema::hasTable() plus five Setting::get() calls on
        // EVERY request. With the database cache driver that is six round trips
        // before routing even starts, on the landing page and every API call
        // alike. They are now one cached array, refreshed when an admin saves.
        //
        // Wrapped in try/catch: during a Railway build the DB isn't reachable
        // (postgres.railway.internal only resolves at runtime).
        try {
            foreach (Setting::branding() as $key => $value) {
                Config::set($key, $value);
            }
        } catch (\Throwable $e) {
            // DB unavailable (build step) — defaults apply.
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

            // Plan + downgrade side-effects live in PlanService so the App Store /
            // Google Play (RevenueCat) path shares them. For users without a store
            // entitlement or admin grant the behaviour is unchanged.
            app(PlanService::class)->applyStripeStatus($user, (string) ($object['status'] ?? ''));
        });
    }
}
