<?php

namespace App\Services;

use App\Models\Entry;
use Illuminate\Support\Facades\DB;

/**
 * Synchronous anomaly detection for entries. No queue dependency — runs
 * in-line during Entry saving. Pure SQL aggregates, no AI / no API calls.
 *
 * Heuristic: flag an entry if its amount is ≥ FLAG_MULTIPLIER × the
 * trailing-90-day mean for the same (book, type, category), provided
 * there are ≥ MIN_SAMPLE_SIZE historical entries for comparison. Below
 * that sample size we have no signal — don't flag.
 *
 * Performance notes:
 *   - One SELECT per save (mean + count by category/type/book)
 *   - Indexed on book_id; adds ~2-5ms per Entry save on a warm DB
 *   - Writes the flag fields via saveQuietly() on the entry *before* it
 *     returns to avoid a second DB round-trip
 */
class AnomalyDetector
{
    /** Flag if amount ≥ mean × this multiplier. */
    private const FLAG_MULTIPLIER = 3.0;

    /** Don't flag unless we've seen this many comparable entries. */
    private const MIN_SAMPLE_SIZE = 5;

    /** Rolling window to compute the baseline mean. */
    private const WINDOW_DAYS = 90;

    /**
     * Compute flag state for an entry and write it onto the model.
     * Safe to call during Entry::saved(). Swallows its own exceptions so
     * a transient DB hiccup can't block a save.
     */
    public function evaluate(Entry $entry): void
    {
        try {
            $result = $this->analyse($entry);

            // Only touch the DB if something actually changes.
            if (
                $result['is_flagged'] !== (bool) $entry->is_flagged
                || $result['flag_reason'] !== $entry->flag_reason
            ) {
                $entry->is_flagged  = $result['is_flagged'];
                $entry->flag_reason = $result['flag_reason'];
                $entry->flagged_at  = now();
                $entry->saveQuietly();
            }
        } catch (\Throwable $e) {
            // Never let anomaly detection break an entry save.
            \Illuminate\Support\Facades\Log::warning('Anomaly detection failed', [
                'entry_id' => $entry->id,
                'message'  => $e->getMessage(),
            ]);
        }
    }

    /**
     * @return array{is_flagged: bool, flag_reason: ?string}
     */
    private function analyse(Entry $entry): array
    {
        $amount = (float) $entry->amount;
        if ($amount <= 0) {
            return ['is_flagged' => false, 'flag_reason' => null];
        }

        // Baseline: trailing 90d for same book + type + category, excluding self.
        $category = $entry->category;
        if (empty($category)) {
            // Without a category we have no meaningful comparison set.
            return ['is_flagged' => false, 'flag_reason' => null];
        }

        $since = now()->subDays(self::WINDOW_DAYS)->toDateString();

        $stats = DB::table('entries')
            ->where('book_id', $entry->book_id)
            ->where('type', $entry->type)
            ->where('category', $category)
            ->where('id', '!=', $entry->id)
            ->where('date', '>=', $since)
            ->selectRaw('count(*)::int as n, avg(amount)::float as mean_amt')
            ->first();

        $count = (int) ($stats->n ?? 0);
        $mean  = (float) ($stats->mean_amt ?? 0);

        if ($count < self::MIN_SAMPLE_SIZE || $mean <= 0) {
            return ['is_flagged' => false, 'flag_reason' => null];
        }

        $ratio = $amount / $mean;
        if ($ratio < self::FLAG_MULTIPLIER) {
            return ['is_flagged' => false, 'flag_reason' => null];
        }

        $reason = sprintf(
            '%.1f× your typical %s %s (avg of last %d)',
            $ratio,
            $category,
            $entry->type === 'in' ? 'income' : 'expense',
            $count,
        );

        return ['is_flagged' => true, 'flag_reason' => $reason];
    }
}
