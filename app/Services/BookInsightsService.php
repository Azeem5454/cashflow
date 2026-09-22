<?php

namespace App\Services;

use App\Models\AiUsageLog;
use App\Models\Book;

/**
 * Shared data-building for AI cash flow insights.
 *
 * Extracted verbatim from App\Livewire\Book\Show so the web Reports tab and
 * the mobile API (GET /api/v1/books/{id}/insights) send the exact same
 * aggregated payload to AiService::generateInsights() and share the same
 * daily cap. Only aggregated numbers are sent — never raw descriptions.
 */
class BookInsightsService
{
    public const DAILY_LIMIT        = 10;
    public const MIN_ENTRIES        = 3;
    public const CACHE_HOURS        = 24;
    public const BURST_KEY_PREFIX   = 'ai_insights_burst:';

    public function dailyLimitReached(string $userId): bool
    {
        return AiUsageLog::where('user_id', $userId)
            ->where('type', 'insights')
            ->whereDate('created_at', today())
            ->count() >= self::DAILY_LIMIT;
    }

    public function buildBookAggregates(Book $book, $entries): array
    {
        $totalIn  = (float) $entries->where('type', 'in')->sum('amount');
        $totalOut = (float) $entries->where('type', 'out')->sum('amount');
        $balance  = $totalIn - $totalOut + (float) ($book->opening_balance ?? 0);

        $topOut = $entries->where('type', 'out')->whereNotNull('category')
            ->groupBy('category')
            ->map(fn ($g) => $g->sum('amount'))
            ->sortDesc()->take(5)
            ->map(fn ($amt, $cat) => "{$cat} (" . number_format($amt, 0) . ")")
            ->values()->toArray();

        $topIn = $entries->where('type', 'in')->whereNotNull('category')
            ->groupBy('category')
            ->map(fn ($g) => $g->sum('amount'))
            ->sortDesc()->take(3)
            ->map(fn ($amt, $cat) => "{$cat} (" . number_format($amt, 0) . ")")
            ->values()->toArray();

        $period = ($book->period_starts_at && $book->period_ends_at)
            ? $book->period_starts_at->format('d M Y') . ' to ' . $book->period_ends_at->format('d M Y')
            : 'Custom period';

        return [
            'name'              => $book->name,
            'period'            => $period,
            'totalIn'           => number_format($totalIn, 2),
            'totalOut'          => number_format($totalOut, 2),
            'balance'           => number_format($balance, 2),
            'entryCount'        => $entries->count(),
            'topCategoriesOut'  => $topOut,
            'topCategoriesIn'   => $topIn,
        ];
    }

    public function buildPreviousBookAggregates(Book $book): ?array
    {
        // Require period dates on the current book — without them we cannot
        // reliably determine which other book represents an earlier period.
        if (! $book->period_starts_at) {
            return null;
        }

        $prevBook = $book->business->books()
            ->where('id', '!=', $book->id)
            ->whereNotNull('period_ends_at')
            ->where('period_ends_at', '<', $book->period_starts_at)
            ->orderByDesc('period_ends_at')
            ->first();

        if (! $prevBook) {
            return null;
        }

        $entries = $prevBook->entries()->get();

        if ($entries->count() < 2) {
            return null;
        }

        return $this->buildBookAggregates($prevBook, $entries);
    }

    /**
     * Call the AI with the book's aggregates. Returns the sanitised
     * snake_case insights array (sentiment lowercase) or null on failure.
     * AiService logs the usage row to ai_usage_logs itself.
     */
    public function generate(Book $book, $entries): ?array
    {
        $current        = $this->buildBookAggregates($book, $entries);
        $previous       = $this->buildPreviousBookAggregates($book);
        $recurringCount = $book->recurringEntries()->where('status', 'active')->count();

        return app(AiService::class)->generateInsights(
            $current,
            $previous,
            $book->business->currency,
            $recurringCount
        );
    }
}
