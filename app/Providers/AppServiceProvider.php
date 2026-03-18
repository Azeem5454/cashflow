<?php

namespace App\Providers;

use App\Helpers\Setting;
use App\Models\RecurringEntry;
use App\Models\User;
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
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        // Override app name and mail config from settings table.
        // Wrapped in try-catch: during Railway build, the DB isn't reachable yet
        // (postgres.railway.internal only resolves at runtime, not build time).
        try {
            if (Schema::hasTable('settings')) {
                $appName = Setting::get('app.name');
                if ($appName) {
                    Config::set('app.name', $appName);
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

            $status = $object['status'] ?? '';

            if ($status === 'active') {
                $user->update(['plan' => 'pro']);
            } elseif (in_array($status, ['canceled', 'unpaid', 'incomplete_expired'])) {
                $user->update(['plan' => 'free']);

                // Pause all recurring entries in books owned by this user
                $businessIds = $user->ownedBusinesses()->pluck('id');

                if ($businessIds->isNotEmpty()) {
                    $bookIds = \App\Models\Book::whereIn('business_id', $businessIds)->pluck('id');
                    if ($bookIds->isNotEmpty()) {
                        RecurringEntry::whereIn('book_id', $bookIds)
                            ->where('status', 'active')
                            ->update(['status' => 'paused']);
                    }
                }
            }
        });
    }
}
