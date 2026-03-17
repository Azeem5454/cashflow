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
        if ($this->user->is_admin) {
            return;
        }

        session(['impersonating_admin_id' => auth()->id()]);
        Auth::login($this->user);

        $this->redirect(route('dashboard'));
    }

    public function forcePro(): void
    {
        $this->user->update(['plan' => 'pro']);
        $this->user->refresh();
    }

    public function forceFree(): void
    {
        $this->user->update(['plan' => 'free']);

        // Pause all recurring entries for this user's businesses
        $bookIds = $this->user->ownedBusinesses()->with('books')->get()
            ->pluck('books')->flatten()->pluck('id');

        if ($bookIds->isNotEmpty()) {
            RecurringEntry::whereIn('book_id', $bookIds)
                ->where('is_active', true)
                ->update(['is_active' => false]);
        }

        $this->user->refresh();
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
