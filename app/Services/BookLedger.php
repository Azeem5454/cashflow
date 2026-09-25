<?php

namespace App\Services;

use App\Support\LikeSearch;

use App\Models\Book;
use App\Models\Entry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Ledger helpers for the mobile API, mirroring App\Livewire\Book\Show::render():
 *  - running balance is computed over ALL entries of the book, in stable
 *    chronological order (date → created_at → id), starting from the book's
 *    opening balance;
 *  - filters are applied AFTER the running balance, so a filtered row keeps
 *    the balance it has in the full ledger (same as the web);
 *  - search matches description, reference, category or amount (same as web).
 *
 * All money math uses bcmath on strings — never floats.
 */
class BookLedger
{
    public const FILTER_KEYS = ['type', 'from', 'to', 'search', 'category', 'paymentMode'];

    /**
     * All entries of the book, oldest first, each with a `running_balance`
     * attribute (string, 2 dp) that includes the opening balance.
     */
    public static function chronological(Book $book, array $with = [], bool $withCommentsCount = false): Collection
    {
        $entries = $book->entries()
            ->with($with)
            ->when($withCommentsCount, fn ($q) => $q->withCount('comments'))
            ->orderBy('date', 'asc')
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $running = self::money($book->opening_balance);
        foreach ($entries as $entry) {
            $running = $entry->type === 'in'
                ? bcadd($running, self::money($entry->amount), 2)
                : bcsub($running, self::money($entry->amount), 2);
            $entry->running_balance = $running;
        }

        return $entries;
    }

    /**
     * @param  array{type?:?string,from?:?string,to?:?string,search?:?string,category?:string|array|null,paymentMode?:string|array|null}  $filters
     */
    public static function applyFilters(Collection $entries, array $filters): Collection
    {
        $type = $filters['type'] ?? null;
        if (in_array($type, ['in', 'out'], true)) {
            $entries = $entries->where('type', $type);
        }

        if (! empty($filters['from'])) {
            $from    = $filters['from'];
            $entries = $entries->filter(fn ($e) => $e->date->format('Y-m-d') >= $from);
        }

        if (! empty($filters['to'])) {
            $to      = $filters['to'];
            $entries = $entries->filter(fn ($e) => $e->date->format('Y-m-d') <= $to);
        }

        $categories = self::listFilter($filters['category'] ?? null);
        if ($categories !== []) {
            $entries = $entries->filter(fn ($e) => in_array($e->category, $categories, true));
        }

        $modes = self::listFilter($filters['paymentMode'] ?? null);
        if ($modes !== []) {
            $entries = $entries->filter(fn ($e) => in_array($e->payment_mode, $modes, true));
        }

        $search = isset($filters['search']) ? trim((string) $filters['search']) : '';
        if ($search !== '') {
            $term    = mb_strtolower($search);
            $entries = $entries->filter(fn ($e) =>
                str_contains(mb_strtolower($e->description ?? ''), $term)
                || str_contains(mb_strtolower($e->reference ?? ''), $term)
                || str_contains(mb_strtolower($e->category ?? ''), $term)
                || str_contains((string) $e->amount, $term)
            );
        }

        return $entries->values();
    }

    public static function hasFilters(array $filters): bool
    {
        foreach (self::FILTER_KEYS as $key) {
            $value = $filters[$key] ?? null;
            if ($key === 'type' && ! in_array($value, ['in', 'out'], true)) {
                continue;
            }
            if (is_array($value) ? self::listFilter($value) !== [] : ($value !== null && trim((string) $value) !== '')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{totalIn:string,totalOut:string,inCount:int,outCount:int}
     */
    public static function totals(Collection $entries): array
    {
        $in = $out = '0.00';
        $inCount = $outCount = 0;

        foreach ($entries as $entry) {
            if ($entry->type === 'in') {
                $in = bcadd($in, self::money($entry->amount), 2);
                $inCount++;
            } else {
                $out = bcadd($out, self::money($entry->amount), 2);
                $outCount++;
            }
        }

        return ['totalIn' => $in, 'totalOut' => $out, 'inCount' => $inCount, 'outCount' => $outCount];
    }

    /** Normalise any stored amount (string/int/null) to a bcmath-safe string. */
    public static function money($value): string
    {
        if ($value === null || $value === '') {
            return '0.00';
        }

        return bcadd((string) $value, '0', 2);
    }

    // ─────────────────────────────────────────────────────────────────────
    //  SQL-side ledger (web ledger page — paginated)
    //
    //  The running balance is a window function over the WHOLE book in the
    //  stable order date → created_at → id, computed in an inner query, and
    //  filters are applied on the outer query — so a filtered row keeps the
    //  balance it has in the full ledger (same semantics as chronological()
    //  + applyFilters()), but only one page of rows is ever hydrated.
    //  Works on PostgreSQL and SQLite ≥ 3.25 (window functions).
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Builder over the book's entries (aliased `entries`) exposing a
     * `running_delta` column = signed cumulative sum up to and including
     * the row. Add opening balance via withRunningBalance().
     */
    public static function runningQuery(Book $book): Builder
    {
        $inner = Entry::query()
            ->select('entries.*')
            ->selectRaw(
                "SUM(CASE WHEN type = 'in' THEN amount ELSE -amount END) "
                . 'OVER (ORDER BY date ASC, created_at ASC, id ASC ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW) AS running_delta'
            )
            ->where('book_id', $book->id);

        return Entry::query()->fromSub($inner, 'entries')->select('entries.*');
    }

    /**
     * Apply the ledger filters to an entries query (works on both the
     * plain relation and runningQuery()).
     *
     * @param  array{type?:?string,from?:?string,to?:?string,category?:string|array|null,paymentMode?:string|array|null,search?:?string}  $filters
     */
    public static function applyQueryFilters($query, array $filters)
    {
        $type = $filters['type'] ?? null;
        if (in_array($type, ['in', 'out'], true)) {
            $query->where('entries.type', $type);
        }

        if (! empty($filters['from'])) {
            $query->whereDate('entries.date', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $query->whereDate('entries.date', '<=', $filters['to']);
        }

        $categories = self::listFilter($filters['category'] ?? null);
        if ($categories !== []) {
            $query->whereIn('entries.category', $categories);
        }

        $modes = self::listFilter($filters['paymentMode'] ?? null);
        if ($modes !== []) {
            $query->whereIn('entries.payment_mode', $modes);
        }

        $search = isset($filters['search']) ? trim((string) $filters['search']) : '';
        if ($search !== '') {
            $like = LikeSearch::contains($search);
            $esc  = LikeSearch::CLAUSE;
            $query->where(function ($q) use ($like, $esc) {
                $q->whereRaw("LOWER(COALESCE(entries.description, '')) LIKE ? {$esc}", [$like])
                  ->orWhereRaw("LOWER(COALESCE(entries.reference, '')) LIKE ? {$esc}", [$like])
                  ->orWhereRaw("LOWER(COALESCE(entries.category, '')) LIKE ? {$esc}", [$like])
                  ->orWhereRaw("CAST(entries.amount AS TEXT) LIKE ? {$esc}", [$like]);
            });
        }

        return $query;
    }

    /**
     * Totals for a (filtered) entries query in one aggregate round-trip.
     *
     * @return array{totalIn:string,totalOut:string,inCount:int,outCount:int,count:int}
     */
    public static function queryTotals($query): array
    {
        $row = (clone $query)->reorder()->toBase()
            ->selectRaw("COALESCE(SUM(CASE WHEN entries.type = 'in' THEN entries.amount ELSE 0 END), 0) AS total_in")
            ->selectRaw("COALESCE(SUM(CASE WHEN entries.type = 'out' THEN entries.amount ELSE 0 END), 0) AS total_out")
            ->selectRaw("SUM(CASE WHEN entries.type = 'in' THEN 1 ELSE 0 END) AS in_count")
            ->selectRaw("SUM(CASE WHEN entries.type = 'out' THEN 1 ELSE 0 END) AS out_count")
            ->first();

        $inCount  = (int) ($row->in_count ?? 0);
        $outCount = (int) ($row->out_count ?? 0);

        return [
            'totalIn'  => self::dbMoney($row->total_in ?? 0),
            'totalOut' => self::dbMoney($row->total_out ?? 0),
            'inCount'  => $inCount,
            'outCount' => $outCount,
            'count'    => $inCount + $outCount,
        ];
    }

    /**
     * Stamp `running_balance` (opening balance + running_delta, 2 dp string)
     * on rows fetched from runningQuery().
     */
    public static function withRunningBalance(Collection $entries, Book $book): Collection
    {
        $opening = self::money($book->opening_balance);

        foreach ($entries as $entry) {
            $entry->running_balance = bcadd($opening, self::dbMoney($entry->running_delta ?? 0), 2);
        }

        return $entries;
    }

    /**
     * Normalise an aggregate coming back from the DB. PostgreSQL returns
     * exact numeric strings; SQLite may return floats — round those to
     * cents rather than letting bcmath truncate 0.30000000000000004.
     */
    public static function dbMoney($value): string
    {
        if (is_float($value) || is_int($value)) {
            return number_format(round((float) $value, 2), 2, '.', '');
        }
        if (! is_numeric($value)) {
            return '0.00';
        }
        if (stripos((string) $value, 'e') !== false) {
            return number_format(round((float) $value, 2), 2, '.', '');
        }

        return bcadd((string) $value, '0', 2);
    }

    private static function listFilter($value): array
    {
        if ($value === null) {
            return [];
        }

        $values = is_array($value) ? $value : [$value];

        return array_values(array_filter(
            array_map(fn ($v) => is_scalar($v) ? trim((string) $v) : '', $values),
            fn ($v) => $v !== ''
        ));
    }
}
