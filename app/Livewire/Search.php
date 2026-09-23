<?php

namespace App\Livewire;

use App\Services\EntrySearch;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Global search — "find any entry across all my businesses and books".
 *
 * All access + matching rules live in App\Services\EntrySearch, shared with
 * GET /api/v1/search so the web page and the mobile app never drift.
 */
#[Layout('layouts.app')]
class Search extends Component
{
    public const PAGE_SIZE = 25;

    #[Url(as: 'q', except: '')]
    public string $q = '';

    #[Url(except: '')]
    public string $type = '';

    #[Url(except: '')]
    public string $from = '';

    #[Url(except: '')]
    public string $to = '';

    #[Url(as: 'business', except: '')]
    public string $businessId = '';

    /** How many rows are currently shown ("Load more" grows this). */
    public int $limit = self::PAGE_SIZE;

    /** Any filter change starts the result list over. */
    public function updated(string $property): void
    {
        if (in_array($property, ['q', 'type', 'from', 'to', 'businessId'], true)) {
            $this->limit = self::PAGE_SIZE;
        }
    }

    public function loadMore(): void
    {
        $this->limit += self::PAGE_SIZE;
    }

    public function setType(string $type): void
    {
        $this->type  = in_array($type, ['in', 'out'], true) && $this->type !== $type ? $type : '';
        $this->limit = self::PAGE_SIZE;
    }

    public function setBusiness(string $businessId): void
    {
        $this->businessId = $this->businessId === $businessId ? '' : $businessId;
        $this->limit      = self::PAGE_SIZE;
    }

    public function clearFilters(): void
    {
        $this->type = $this->from = $this->to = $this->businessId = '';
        $this->limit = self::PAGE_SIZE;
    }

    public function clearAll(): void
    {
        $this->q = '';
        $this->clearFilters();
    }

    public function render()
    {
        $user       = auth()->user();
        $businesses = EntrySearch::accessibleBusinesses($user);

        $filters = EntrySearch::normalise([
            'q'          => $this->q,
            'type'       => $this->type,
            'from'       => $this->from,
            'to'         => $this->to,
            'businessId' => $this->businessId,
        ]);

        $query  = EntrySearch::query($user, $filters, $businesses);
        $totals = EntrySearch::totals($query, $businesses);

        $results = collect();
        $total   = 0;

        if ($query) {
            $total   = (clone $query)->reorder()->toBase()->count();
            $results = $query->with(['book.business'])->limit($this->limit)->get();
        }

        return view('livewire.search', [
            'businesses'       => $businesses->values(),
            'grouped'          => $this->groupByDate($results),
            'total'            => $total,
            'shown'            => $results->count(),
            'hasMore'          => $results->count() < $total,
            'totals'           => $totals['totals'],
            'totalsByCurrency' => $totals['totalsByCurrency'],
            'searching'        => (bool) $query,
            // Told the user typed something too short to search on.
            'tooShort'         => $this->q !== '' && $filters['q'] === '',
            'hasFilters'       => $this->type !== '' || $this->from !== '' || $this->to !== '' || $this->businessId !== '',
        ]);
    }

    /** Results grouped by entry date, newest day first (the query is already sorted). */
    private function groupByDate(Collection $entries): Collection
    {
        return $entries->groupBy(fn ($entry) => $entry->date->format('Y-m-d'));
    }
}
