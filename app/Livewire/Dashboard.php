<?php

namespace App\Livewire;

use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        $user = auth()->user();

        $businesses = $user->businesses()
            ->withCount(['books', 'members'])
            ->with('owner')
            ->latest('businesses.created_at')
            ->get();

        $ownedBusinesses  = $businesses->where('pivot.role', 'owner')->values();
        $sharedBusinesses = $businesses->whereIn('pivot.role', ['editor', 'viewer'])->values();

        $totalBooks = $businesses->sum('books_count');

        // Free plan: only the oldest owned business is accessible
        $firstOwnedId = $user->ownedBusinesses()->oldest()->value('id');

        return view('livewire.dashboard', [
            'ownedBusinesses'  => $ownedBusinesses,
            'sharedBusinesses' => $sharedBusinesses,
            'totalBooks'       => $totalBooks,
            'firstOwnedId'     => $firstOwnedId,
        ]);
    }
}
