<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ResyncStripeUser extends Command
{
    /**
     * Usage:
     *   php artisan stripe:resync {email}
     *
     * Looks up a user by email, queries Stripe (using current keys — live or test)
     * for a matching customer, and resyncs the user's stripe_id + plan based on
     * real Stripe state. If no live customer exists the test stripe_id is cleared
     * and plan is reset to free so the user can do a fresh checkout.
     */
    protected $signature = 'stripe:resync {email : The user email to resync}';

    protected $description = 'Resync a user\'s Stripe customer ID + plan against current Stripe mode';

    public function handle(): int
    {
        $email = $this->argument('email');
        $user  = User::where('email', $email)->first();

        if (! $user) {
            $this->error("No user found with email: {$email}");
            return self::FAILURE;
        }

        $this->info("User: {$user->name} <{$user->email}>");
        $this->line("Current stripe_id: " . ($user->stripe_id ?: 'null'));
        $this->line("Current plan:      {$user->plan}");

        // Search Stripe for a customer with this email in current mode
        try {
            $stripe = app(\Laravel\Cashier\Cashier::class)::stripe();
        } catch (\Throwable) {
            // Fallback: Cashier exposes ->stripe() on the user model
            $stripe = $user->stripe();
        }

        $customers = $stripe->customers->all([
            'email' => $email,
            'limit' => 10,
        ]);

        if (empty($customers->data)) {
            $this->warn("No Stripe customer found in current mode for {$email}.");
            $this->warn("Clearing stale stripe_id and resetting plan to 'free'.");

            // Also drop any cashier subscription rows referencing the old customer.
            // stripe_id + pm_* are not in User::$fillable, so set directly.
            $user->subscriptions()->delete();
            $user->stripe_id     = null;
            $user->pm_type       = null;
            $user->pm_last_four  = null;
            $user->trial_ends_at = null;
            $user->plan          = 'free';
            $user->save();

            $this->info("User reset. They can now subscribe fresh via /settings/billing.");
            return self::SUCCESS;
        }

        // Pick the most recent customer (usually just one)
        $customer = collect($customers->data)->sortByDesc('created')->first();
        $this->info("Found Stripe customer: {$customer->id} (created " . date('Y-m-d', $customer->created) . ")");

        // Check active subscriptions on that customer
        $subs = $stripe->subscriptions->all([
            'customer' => $customer->id,
            'status'   => 'all',
            'limit'    => 5,
        ]);

        $activeSub = collect($subs->data)->first(fn ($s) => in_array($s->status, ['active', 'trialing', 'past_due']));

        // Update user.stripe_id to point at the real customer (not in $fillable).
        $user->stripe_id = $customer->id;
        $user->plan      = $activeSub ? 'pro' : 'free';
        $user->save();

        // Drop stale cashier subscription rows that don't match the current customer
        $user->subscriptions()->where('stripe_id', '!=', $activeSub?->id)->delete();

        if ($activeSub) {
            $this->info("Active subscription found: {$activeSub->id} (status: {$activeSub->status})");
            $this->line("User set to Pro. Cashier subscription rows will sync via the next webhook.");
        } else {
            $this->warn("No active subscription on this customer. User set to Free.");
        }

        $this->newLine();
        $this->info("✅ Resync complete.");
        $this->line("New stripe_id: {$customer->id}");
        $this->line("New plan:      " . ($activeSub ? 'pro' : 'free'));

        return self::SUCCESS;
    }
}
