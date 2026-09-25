<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Entry;
use App\Models\User;
use App\Support\LikeSearch;
use App\Support\BusinessLock;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Global entry search — "find any entry across all my businesses and books".
 *
 * Shared by the web (App\Livewire\Search) and the API
 * (GET /api/v1/search) so both apply exactly the same rules:
 *
 *  - only entries in businesses the user is a MEMBER of (any role, viewer
 *    included) are ever visible;
 *  - Free-plan locked businesses are excluded (same rule as
 *    App\Support\BusinessLock / the web businesses.show gate);
 *  - text matching is case-insensitive and portable (LOWER(...) LIKE, never
 *    Postgres-only ILIKE) because tests run on SQLite and prod on Postgres;
 *  - totals are computed with one aggregate over the WHOLE match set and
 *    grouped per currency — money from two currencies is never summed.
 *
 * All money is handled as strings (bcmath / DB numerics), never floats.
 */
class EntrySearch
{
    /** A query shorter than this is ignored (treated as "no text filter"). */
    public const MIN_QUERY_LENGTH = 2;

    public const DEFAULT_PER_PAGE = 25;
    public const MAX_PER_PAGE     = 100;

    /**
     * Businesses the user may search: every business they belong to, minus
     * the ones locked by the Free-plan rule. Keyed by id.
     *
     * @return Collection<string, Business>
     */
    public static function accessibleBusinesses(User $user): Collection
    {
        return $user->businesses()
            ->get()
            ->reject(fn (Business $b) => BusinessLock::isLocked($user, $b, $b->pivot?->role))
            ->keyBy('id');
    }

    /**
     * Normalise raw request input into the filter array used below.
     *
     * @param  array<string, mixed>  $input
     * @return array{q:string,type:?string,from:?string,to:?string,businessId:?string,minAmount:?string,maxAmount:?string}
     */
    public static function normalise(array $input): array
    {
        $str = static function ($v): string {
            return is_scalar($v) ? trim((string) $v) : '';
        };

        $q    = $str($input['q'] ?? '');
        $type = $str($input['type'] ?? '');

        return [
            'q'          => mb_strlen($q) >= self::MIN_QUERY_LENGTH ? $q : '',
            'type'       => in_array($type, ['in', 'out'], true) ? $type : null,
            'from'       => $str($input['from'] ?? '') ?: null,
            'to'         => $str($input['to'] ?? '') ?: null,
            'businessId' => $str($input['businessId'] ?? '') ?: null,
            'minAmount'  => is_numeric($input['minAmount'] ?? null) ? (string) $input['minAmount'] : null,
            'maxAmount'  => is_numeric($input['maxAmount'] ?? null) ? (string) $input['maxAmount'] : null,
        ];
    }

    /**
     * True when the user asked for *something*. A blank/too-short q with no
     * other filter means "don't search" — we return an empty set rather than
     * dumping every entry the user owns.
     *
     * @param  array<string, mixed>  $filters  output of normalise()
     */
    public static function hasCriteria(array $filters): bool
    {
        foreach (['q', 'type', 'from', 'to', 'businessId', 'minAmount', 'maxAmount'] as $key) {
            if (($filters[$key] ?? null) !== null && ($filters[$key] ?? '') !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * The search query: entries joined to their book + business, already
     * scoped to what this user may see, ordered newest first.
     *
     * Returns null when the user has no searchable business or supplied no
     * criteria — callers then render an empty result set without a round-trip.
     *
     * @param  array<string, mixed>  $filters  output of normalise()
     */
    public static function query(User $user, array $filters, ?Collection $businesses = null): ?Builder
    {
        if (! self::hasCriteria($filters)) {
            return null;
        }

        $businesses = $businesses ?? self::accessibleBusinesses($user);

        if ($filters['businessId'] ?? null) {
            $businesses = $businesses->filter(fn ($b) => $b->id === $filters['businessId']);
        }

        if ($businesses->isEmpty()) {
            return null;
        }

        $query = Entry::query()
            ->join('books', 'books.id', '=', 'entries.book_id')
            ->whereIn('books.business_id', $businesses->keys()->all())
            // Books in the recycle bin are invisible until restored.
            ->whereNull('books.deleted_at')
            ->select('entries.*')
            // Stable, deterministic ordering: newest date first, then the row's
            // own creation order, then the UUID as the final tiebreaker.
            ->orderBy('entries.date', 'desc')
            ->orderBy('entries.created_at', 'desc')
            ->orderBy('entries.id', 'desc');

        if ($filters['type'] ?? null) {
            $query->where('entries.type', $filters['type']);
        }

        if ($filters['from'] ?? null) {
            $query->whereDate('entries.date', '>=', $filters['from']);
        }

        if ($filters['to'] ?? null) {
            $query->whereDate('entries.date', '<=', $filters['to']);
        }

        if (($filters['minAmount'] ?? null) !== null) {
            $query->where('entries.amount', '>=', $filters['minAmount']);
        }

        if (($filters['maxAmount'] ?? null) !== null) {
            $query->where('entries.amount', '<=', $filters['maxAmount']);
        }

        $q = (string) ($filters['q'] ?? '');
        if ($q !== '') {
            self::applyText($query, $q);
        }

        return $query;
    }

    /**
     * Case-insensitive partial match over description, category, payment mode,
     * reference and the amount. LOWER(...) LIKE keeps this identical on
     * PostgreSQL (prod) and SQLite (tests) — ILIKE is Postgres-only.
     */
    private static function applyText(Builder $query, string $term): void
    {
        $like = LikeSearch::contains($term);
        $esc  = LikeSearch::CLAUSE;

        $query->where(function ($q) use ($like, $term, $esc) {
            foreach (['description', 'category', 'payment_mode', 'reference'] as $column) {
                $q->orWhereRaw("LOWER(COALESCE(entries.{$column}, '')) LIKE ? {$esc}", [$like]);
            }

            // "450" should find 450.00. CAST-to-text handles partials on
            // Postgres ('450.00'); the numeric equality covers SQLite, where
            // the same value may come back as '450'.
            $q->orWhereRaw("CAST(entries.amount AS TEXT) LIKE ? {$esc}", [$like]);

            if (is_numeric($term)) {
                $q->orWhere('entries.amount', '=', $term);
            }
        });
    }

    /**
     * Totals over the WHOLE match set (not just the current page), grouped by
     * the owning business's currency.
     *
     * @param  Collection<string, Business>  $businesses  keyed by id (for symbols)
     * @return array{totals:array,totalsByCurrency:array<string,array>}
     */
    public static function totals(?Builder $query, Collection $businesses): array
    {
        $empty = [
            'totals' => [
                'in' => '0.00', 'out' => '0.00', 'net' => '0.00',
                'count' => 0, 'currency' => null, 'currencySymbol' => null,
                'mixedCurrency' => false,
            ],
            'totalsByCurrency' => [],
        ];

        if (! $query) {
            return $empty;
        }

        $rows = (clone $query)->reorder()->toBase()
            ->join('businesses', 'businesses.id', '=', 'books.business_id')
            ->groupBy('businesses.currency')
            ->selectRaw('businesses.currency AS currency')
            ->selectRaw("COALESCE(SUM(CASE WHEN entries.type = 'in' THEN entries.amount ELSE 0 END), 0) AS total_in")
            ->selectRaw("COALESCE(SUM(CASE WHEN entries.type = 'out' THEN entries.amount ELSE 0 END), 0) AS total_out")
            ->selectRaw('COUNT(*) AS row_count')
            ->get();

        if ($rows->isEmpty()) {
            return $empty;
        }

        // Currency symbol per code — reuse the businesses we already loaded.
        $symbols = $businesses->mapWithKeys(fn (Business $b) => [$b->currency => $b->currencySymbol()]);

        $byCurrency = [];
        $count      = 0;

        foreach ($rows as $row) {
            $in  = BookLedger::dbMoney($row->total_in);
            $out = BookLedger::dbMoney($row->total_out);

            $byCurrency[$row->currency] = [
                'currency'       => $row->currency,
                'currencySymbol' => $symbols[$row->currency] ?? ($row->currency . ' '),
                'in'             => $in,
                'out'            => $out,
                'net'            => bcsub($in, $out, 2),
                'count'          => (int) $row->row_count,
            ];

            $count += (int) $row->row_count;
        }

        ksort($byCurrency);

        $mixed = count($byCurrency) > 1;
        $only  = $mixed ? null : reset($byCurrency);

        return [
            'totals' => [
                // Summing across currencies is forbidden — when the match set
                // spans several, the money fields are null and the caller must
                // read totalsByCurrency.
                'in'             => $mixed ? null : $only['in'],
                'out'            => $mixed ? null : $only['out'],
                'net'            => $mixed ? null : $only['net'],
                'count'          => $count,
                'currency'       => $mixed ? null : $only['currency'],
                'currencySymbol' => $mixed ? null : $only['currencySymbol'],
                'mixedCurrency'  => $mixed,
            ],
            'totalsByCurrency' => $byCurrency,
        ];
    }

    public static function perPage($value): int
    {
        $perPage = (int) ($value ?: self::DEFAULT_PER_PAGE);

        return min(self::MAX_PER_PAGE, max(1, $perPage));
    }
}
