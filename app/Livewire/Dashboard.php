<?php

namespace App\Livewire;

use App\Models\Book;
use App\Models\Entry;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Component;

class Dashboard extends Component
{
    /** How many books the "Change" picker lists before the user searches. */
    public const PICKER_RECENT = 10;

    /** Hard cap on books sent to the picker (search runs client-side over these). */
    public const PICKER_MAX = 60;

    /**
     * "Change ▾" on the Continue-in card. Persists the choice so the card keeps
     * pointing at this book on the next visit. The id must be a book in a
     * business the user belongs to that is NOT Free-plan locked — anything else
     * is ignored (no error surface for a tampered id).
     */
    public function selectBook(string $bookId): void
    {
        $user = auth()->user();

        $allowed = $this->unlockedBusinesses($user)
            ->flatMap(fn ($biz) => $biz->books->pluck('id'))
            ->contains($bookId);

        if (! $allowed) {
            return;
        }

        $user->last_book_id = $bookId;
        $user->save();
    }

    public function render()
    {
        $user = auth()->user();

        $businesses = $this->loadBusinesses($user);

        $ownedBusinesses  = $businesses->where('pivot.role', 'owner')->values();
        $sharedBusinesses = $businesses->whereIn('pivot.role', ['editor', 'viewer'])->values();
        $firstOwnedId     = $user->ownedBusinesses()->oldest()->value('id');

        $isPro    = $user->isPro();
        $unlocked = $this->filterUnlocked($businesses, $isPro, $firstOwnedId);

        // Recent entries across all accessible books (activity feed). Free-plan
        // locked businesses are left out (same rule as the API's recentBooks) —
        // their books redirect to billing and their data shouldn't surface here.
        $accessibleBookIds = Book::whereIn('business_id', $unlocked->pluck('id'))->pluck('id');

        $recentEntries = Entry::whereIn('book_id', $accessibleBookIds)
            ->with([
                'book:id,name,business_id',
                'book.business:id,name,currency',
            ])
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        // ── Balance per currency across unlocked businesses. Never summed across
        // currencies — one row per currency code. Includes opening balances.
        $totals = $unlocked
            ->groupBy(fn ($b) => $b->currency ?: 'USD')
            ->map(function ($group, $currency) {
                $in = $out = $opening = 0.0;
                foreach ($group as $biz) {
                    foreach ($biz->books as $book) {
                        $in      += (float) ($book->cash_in ?? 0);
                        $out     += (float) ($book->cash_out ?? 0);
                        $opening += (float) ($book->opening_balance ?? 0);
                    }
                }

                return [
                    'currency' => $currency,
                    'symbol'   => $group->first()->currencySymbol(),
                    'in'       => $in,
                    'out'      => $out,
                    'balance'  => $opening + $in - $out,
                ];
            })
            ->sortByDesc(fn ($t) => $t['in'] + $t['out'])
            ->values();

        // ── Continue-in card: every book in an unlocked business, most recent first.
        $bookChoices = $this->bookChoices($unlocked);
        $currentBook = $this->resolveCurrentBook($bookChoices, $user->last_book_id);

        // Where "Create your first book" goes when there are businesses but no books.
        $createBookBusiness = $unlocked
            ->first(fn ($b) => in_array($b->pivot?->role, ['owner', 'editor'], true));

        return view('livewire.dashboard', [
            'totals'             => $totals,
            'currentBook'        => $currentBook,
            'bookChoices'        => $bookChoices->take(self::PICKER_MAX)->values(),
            'createBookBusiness' => $createBookBusiness,
            'unlockedCount'      => $unlocked->count(),
            'ownedBusinesses'    => $ownedBusinesses,
            'sharedBusinesses'   => $sharedBusinesses,
            'firstOwnedId'       => $firstOwnedId,
            'recentEntries'      => $recentEntries,
            // Free plan includes 1 owned business — "New Business" opens the upgrade modal.
            'businessLimitReached' => ! $isPro && $ownedBusinesses->isNotEmpty(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────

    private function loadBusinesses($user): Collection
    {
        return $user->businesses()
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
    }

    private function unlockedBusinesses($user): Collection
    {
        return $this->filterUnlocked(
            $this->loadBusinesses($user),
            $user->isPro(),
            $user->ownedBusinesses()->oldest()->value('id'),
        );
    }

    /** BusinessLock's rule, using the already-fetched oldest owned id. */
    private function filterUnlocked(Collection $businesses, bool $isPro, ?string $firstOwnedId): Collection
    {
        return $businesses
            ->reject(fn ($b) => ! $isPro && $b->pivot?->role === 'owner' && $b->id !== $firstOwnedId)
            ->values();
    }

    /**
     * Flat list of every book the card can point at, with its business,
     * the user's role and precomputed money figures. Most recently active first
     * (latest entry, or the book's own updated_at — Book is touched on writes).
     */
    private function bookChoices(Collection $unlocked): Collection
    {
        return $unlocked
            ->flatMap(function ($biz) {
                $role = $biz->pivot?->role ?? 'viewer';

                return $biz->books->map(function ($book) use ($biz, $role) {
                    $in  = (float) ($book->cash_in ?? 0);
                    $out = (float) ($book->cash_out ?? 0);

                    $recency = max(
                        $book->last_entry_at ? Carbon::parse($book->last_entry_at)->getTimestamp() : 0,
                        $book->updated_at?->getTimestamp() ?? 0,
                        $book->created_at?->getTimestamp() ?? 0,
                    );

                    return [
                        'id'       => $book->id,
                        'book'     => $book,
                        'business' => $biz,
                        'role'     => $role,
                        'canEdit'  => in_array($role, ['owner', 'editor'], true),
                        'symbol'   => $biz->currencySymbol(),
                        'in'       => $in,
                        'out'      => $out,
                        'net'      => (float) ($book->opening_balance ?? 0) + $in - $out,
                        'recency'  => $recency,
                    ];
                });
            })
            ->sortByDesc('recency')
            ->values();
    }

    /**
     * Card target: the book the user last picked (if still accessible), else the
     * most recently active book they can edit, else (viewer everywhere) the most
     * recent book at all.
     */
    private function resolveCurrentBook(Collection $choices, ?string $lastBookId): ?array
    {
        if ($lastBookId && ($picked = $choices->firstWhere('id', $lastBookId))) {
            return $picked;
        }

        return $choices->firstWhere('canEdit', true) ?? $choices->first();
    }
}
