<?php

namespace App\Livewire\Admin;

use App\Models\RecurringEntry;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class Users extends Component
{
    use WithPagination;

    public string $search    = '';
    public string $planFilter = ''; // '' | 'free' | 'pro'

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingPlanFilter(): void
    {
        $this->resetPage();
    }

    public function forcePro(string $userId): void
    {
        abort_unless(auth()->check() && auth()->user()->is_admin, 403);

        $user = User::findOrFail($userId);
        $user->plan = 'pro';
        $user->save();
    }

    public function forceFree(string $userId): void
    {
        abort_unless(auth()->check() && auth()->user()->is_admin, 403);

        $user = User::findOrFail($userId);

        // Cancel Stripe subscription first so the customer stops getting billed.
        try {
            $activeSub = $user->subscription('default');
            if ($activeSub && ($activeSub->active() || $activeSub->onTrial())) {
                $activeSub->cancelNow();
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Admin Users::forceFree Stripe cancel failed', [
                'admin_id'  => auth()->id(),
                'target_id' => $user->id,
                'message'   => $e->getMessage(),
            ]);
        }

        $user->plan = 'free';
        $user->save();

        // Pause all recurring entries and email report schedules in books owned by this user
        $businessIds = $user->ownedBusinesses()->pluck('id');

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
    }

    public function deleteUser(string $userId): void
    {
        $user = User::findOrFail($userId);

        if ($user->is_admin) {
            return;
        }

        // Cascade: delete owned businesses + their books, entries, members, invitations
        foreach ($user->ownedBusinesses as $business) {
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

        // Remove from other businesses
        $user->businesses()->detach();

        $user->delete();
    }

    public function render()
    {
        $users = User::query()
            ->withCount('ownedBusinesses')
            ->when($this->search, fn ($q) =>
                $q->where(fn ($q2) =>
                    $q2->where('name', 'ilike', '%' . $this->search . '%')
                       ->orWhere('email', 'ilike', '%' . $this->search . '%')
                ))
            ->when($this->planFilter === 'pro',  fn ($q) => $q->where('plan', 'pro'))
            ->when($this->planFilter === 'free', fn ($q) => $q->where('plan', 'free'))
            ->latest()
            ->paginate(25);

        return view('livewire.admin.users', compact('users'))
            ->layout('layouts.admin');
    }
}
