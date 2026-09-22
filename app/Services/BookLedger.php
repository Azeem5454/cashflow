<?php

namespace App\Services;

use App\Models\Book;
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
