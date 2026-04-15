<?php

namespace App\Livewire\Admin;

use App\Models\RecurringEntry;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class UserDetail extends Component
{
    public User $user;

    public function mount(User $user): void
    {
        $this->user = $user;
    }

    public function impersonate(): void
    {
        abort_unless(auth()->check() && auth()->user()->is_admin, 403);

        if ($this->user->is_admin) {
            return;
        }

        session(['impersonating_admin_id' => auth()->id()]);
        Auth::login($this->user);

        $this->redirect(route('dashboard'));
    }

    public function forcePro(): void
    {
        abort_unless(auth()->check() && auth()->user()->is_admin, 403);

        $this->user->plan = 'pro';
        $this->user->save();
        $this->user->refresh();
    }

    public function forceFree(): void
    {
        abort_unless(auth()->check() && auth()->user()->is_admin, 403);

        // Cancel any active Stripe subscription FIRST — otherwise we'd flip
        // the local plan to free while Stripe keeps charging the customer.
        try {
            $activeSub = $this->user->subscription('default');
            if ($activeSub && ($activeSub->active() || $activeSub->onTrial())) {
                $activeSub->cancelNow();
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Admin forceFree: Stripe cancel failed', [
                'admin_id'  => auth()->id(),
                'target_id' => $this->user->id,
                'message'   => $e->getMessage(),
            ]);
            // Continue anyway — admin still wants the user downgraded locally.
        }

        $this->user->plan = 'free';
        $this->user->save();

        // Pause all recurring entries and email report schedules in books owned by this user
        $businessIds = $this->user->ownedBusinesses()->pluck('id');

        if ($businessIds->isNotEmpty()) {
            $bookIds = \App\Models\Book::whereIn('business_id', $businessIds)->pluck('id');
            if ($bookIds->isNotEmpty()) {
                RecurringEntry::whereIn('book_id', $bookIds)
                    ->where('status', 'active')
                    ->update(['status' => 'paused']);

                \App\Models\ReportSchedule::whereIn('book_id', $bookIds)
                    ->where('is_active', true)
                    ->update(['is_active' => false]);
            }
        }

        $this->user->refresh();
    }

    /**
     * Resync this user's Stripe customer + subscription state against the
     * current Stripe keys. Fixes stale test customer IDs left over from
     * switching between Stripe test/live modes.
     */
    public string $resyncMessage = '';

    public function resyncStripe(): void
    {
        abort_unless(auth()->check() && auth()->user()->is_admin, 403);

        try {
            $stripe = $this->user->stripe();

            $customers = $stripe->customers->all([
                'email' => $this->user->email,
                'limit' => 10,
            ]);

            if (empty($customers->data)) {
                // No customer in this mode — clear stale ID and reset to free.
                // Cashier columns (stripe_id, pm_*, trial_ends_at) are NOT in
                // User::$fillable for security, so we have to set them directly.
                $this->user->subscriptions()->delete();
                $this->user->stripe_id     = null;
                $this->user->pm_type       = null;
                $this->user->pm_last_four  = null;
                $this->user->trial_ends_at = null;
                $this->user->plan          = 'free';
                $this->user->save();
                $this->user->refresh();
                $this->resyncMessage = "No Stripe customer found — cleared stale ID, plan reset to Free. User can now subscribe fresh.";
                return;
            }

            // Newest customer wins
            $customer = collect($customers->data)->sortByDesc('created')->first();

            $subs = $stripe->subscriptions->all([
                'customer' => $customer->id,
                'status'   => 'all',
                'limit'    => 5,
            ]);

            $activeSub = collect($subs->data)->first(
                fn ($s) => in_array($s->status, ['active', 'trialing', 'past_due'])
            );

            // stripe_id isn't in User::$fillable — set directly.
            $this->user->stripe_id = $customer->id;
            $this->user->plan      = $activeSub ? 'pro' : 'free';
            $this->user->save();

            // Drop cashier subscription rows that don't match the current active sub
            $this->user->subscriptions()
                ->where('stripe_id', '!=', $activeSub?->id)
                ->delete();

            $this->user->refresh();

            $this->resyncMessage = $activeSub
                ? "Synced → customer {$customer->id}, active subscription {$activeSub->id} (status: {$activeSub->status}). Plan: Pro."
                : "Synced → customer {$customer->id}, no active subscription. Plan: Free.";
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Admin stripe resync failed', [
                'admin_id'  => auth()->id(),
                'target_id' => $this->user->id,
                'message'   => $e->getMessage(),
            ]);
            $this->resyncMessage = "Error: " . $e->getMessage();
        }
    }

    public function deleteUser(): void
    {
        if ($this->user->is_admin) {
            return;
        }

        // Cascade delete owned businesses
        foreach ($this->user->ownedBusinesses as $business) {
            foreach ($business->books as $book) {
                $book->entries()->delete();
                $book->categories()->delete();
                $book->paymentModes()->delete();
            }
            $business->books()->delete();
            $business->invitations()->delete();
            $business->members()->detach();
            $business->delete();
        }

        $this->user->businesses()->detach();
        $this->user->delete();

        $this->redirect(route('admin.users'));
    }

    public function render()
    {
        $subscription = $this->user->subscriptions()->latest()->first();

        $businesses = $this->user->businesses()
            ->withCount('books')
            ->withPivot('role')
            ->get();

        $ownedIds    = $this->user->ownedBusinesses()->pluck('id');
        $invitations = \App\Models\Invitation::whereIn('business_id', $ownedIds)
            ->latest()
            ->take(10)
            ->get();

        return view('livewire.admin.user-detail', compact('subscription', 'businesses', 'invitations'))
            ->layout('layouts.admin');
    }
}
