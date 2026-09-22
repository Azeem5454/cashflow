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

        // Override app name and mail config from settings table.
        // Wrapped in try-catch: during Railway build, the DB isn't reachable yet
        // (postgres.railway.internal only resolves at runtime, not build time).
        // Keys preloaded here can then be read via config() in views — avoids
        // hitting the DB on every public request (including the / healthcheck).
        try {
            if (Schema::hasTable('settings')) {
                $appName = Setting::get('app.name');
                if ($appName) {
                    Config::set('app.name', $appName);
                }

                $appTagline = Setting::get('app.tagline');
                if ($appTagline) {
                    Config::set('app.tagline', $appTagline);
                }

                $appSupportEmail = Setting::get('app.support_email');
                if ($appSupportEmail) {
                    Config::set('app.support_email', $appSupportEmail);
                }

                $mailName = Setting::get('mail.from_name');
                if ($mailName) {
                    Config::set('mail.from.name', $mailName);
                }

                $mailAddress = Setting::get('mail.from_address');
                if ($mailAddress) {
                    Config::set('mail.from.address', $mailAddress);
                }
            }
        } catch (\Exception $e) {
            // DB unavailable during build — skip silently, defaults apply
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
