<?php

namespace App\Livewire;

use App\Models\Book;
use App\Models\Entry;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        $user = auth()->user();

        $businesses = $user->businesses()
            ->with([
                'books' => function ($q) {
                    $q->withSum(['entries as cash_in' => fn ($q) => $q->where('type', 'in')], 'amount')
                      ->withSum(['entries as cash_out' => fn ($q) => $q->where('type', 'out')], 'amount')
                      ->withCount('entries')
                      ->withMax('entries as last_entry_at', 'created_at')
                      ->orderByDesc('period_starts_at')
                      ->orderByDesc('created_at');
                },
                'owner:id,name',
            ])
            ->withCount(['books', 'members'])
            ->latest('businesses.created_at')
            ->get();

        $ownedBusinesses  = $businesses->where('pivot.role', 'owner')->values();
        $sharedBusinesses = $businesses->whereIn('pivot.role', ['editor', 'viewer'])->values();
        $firstOwnedId     = $user->ownedBusinesses()->oldest()->value('id');

        // Recent entries across all accessible books (activity feed)
        $businessIds       = $businesses->pluck('id');
        $accessibleBookIds = Book::whereIn('business_id', $businessIds)->pluck('id');

        $recentEntries = Entry::whereIn('book_id', $accessibleBookIds)
            ->with([
                'book:id,name,business_id',
                'book.business:id,name,currency',
            ])
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        return view('livewire.dashboard', [
            'ownedBusinesses'  => $ownedBusinesses,
            'sharedBusinesses' => $sharedBusinesses,
            'firstOwnedId'     => $firstOwnedId,
            'recentEntries'    => $recentEntries,
        ]);
    }
}
