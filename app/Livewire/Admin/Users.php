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
        User::where('id', $userId)->update(['plan' => 'pro']);
    }

    public function forceFree(string $userId): void
    {
        $user = User::findOrFail($userId);
        $user->update(['plan' => 'free']);

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
