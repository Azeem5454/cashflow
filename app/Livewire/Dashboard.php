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

        // Recent entries across all accessible books (activity feed). Free-plan
        // locked businesses are left out (same rule as the API's recentBooks) —
        // their books redirect to billing and their data shouldn't surface here.
        // (BusinessLock's rule, using the already-fetched oldest owned id.)
        $isPro             = $user->isPro();
        $businessIds       = $businesses
            ->reject(fn ($b) => ! $isPro && $b->pivot?->role === 'owner' && $b->id !== $firstOwnedId)
            ->pluck('id');
        $accessibleBookIds = Book::whereIn('business_id', $businessIds)->pluck('id');

        $recentEntries = Entry::whereIn('book_id', $accessibleBookIds)
            ->with([
                'book:id,name,business_id',
                'book.business:id,name,currency',
            ])
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        // ── Header totals: balance per currency across unlocked businesses.
        // Books are already loaded with in/out sums — no extra queries.
        $unlocked = $businesses->whereIn('id', $businessIds)->values();
        $totals   = $unlocked
            ->groupBy(fn ($b) => $b->currency ?: 'USD')
            ->map(function ($group) {
                $in = $out = $opening = 0.0;
                foreach ($group as $biz) {
                    foreach ($biz->books as $book) {
                        $in      += (float) ($book->cash_in ?? 0);
                        $out     += (float) ($book->cash_out ?? 0);
                        $opening += (float) ($book->opening_balance ?? 0);
                    }
                }

                return [
                    'symbol'  => $group->first()->currencySymbol(),
                    'in'      => $in,
                    'out'     => $out,
                    'balance' => $opening + $in - $out,
                ];
            })
            ->sortByDesc(fn ($t) => $t['in'] + $t['out'])
            ->values();

        // ── Primary CTA target: most recently active book the user can edit.
        $quickAddBook = $unlocked
            ->filter(fn ($b) => in_array($b->pivot?->role, ['owner', 'editor'], true))
            ->flatMap(fn ($biz) => $biz->books->map(fn ($book) => ['business' => $biz, 'book' => $book]))
            ->sortByDesc(fn ($row) => (string) ($row['book']->last_entry_at ?? $row['book']->updated_at ?? $row['book']->created_at))
            ->first();

        return view('livewire.dashboard', [
            'totals'           => $totals,
            'quickAddBook'     => $quickAddBook,
            'ownedBusinesses'  => $ownedBusinesses,
            'sharedBusinesses' => $sharedBusinesses,
            'firstOwnedId'     => $firstOwnedId,
            'recentEntries'    => $recentEntries,
            // Free plan includes 1 owned business — "New Business" opens the upgrade modal.
            'businessLimitReached' => ! $isPro && $ownedBusinesses->isNotEmpty(),
        ]);
    }
}
