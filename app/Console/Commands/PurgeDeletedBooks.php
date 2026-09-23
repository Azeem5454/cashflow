<?php

namespace App\Console\Commands;

use App\Models\Book;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Empties the books recycle bin.
 *
 * Any book soft-deleted more than Book::BIN_DAYS ago is force-deleted: its
 * entry attachments leave storage, then the row goes and the ON DELETE CASCADE
 * foreign keys take entries, categories, payment modes, recurring rules,
 * comments, activity log and the report schedule with it.
 *
 * Idempotent: books within the window are never touched, and a book that fails
 * to purge is logged and skipped rather than aborting the run, so the next
 * daily pass retries it.
 */
class PurgeDeletedBooks extends Command
{
    protected $signature = 'books:purge-deleted
                            {--days= : Override the retention window (defaults to Book::BIN_DAYS)}
                            {--dry-run : List what would be purged without deleting anything}';

    protected $description = 'Permanently delete books that have been in the recycle bin for more than 30 days';

    public function handle(): int
    {
        // Careful: "--days=0" is a legitimate (test/manual) value, so a plain
        // `?:` fallback would silently turn it back into 30.
        $days   = $this->option('days') !== null ? max(0, (int) $this->option('days')) : Book::BIN_DAYS;
        $cutoff = now()->subDays($days);
        $dryRun = (bool) $this->option('dry-run');

        $due = Book::onlyTrashed()
            ->where('deleted_at', '<=', $cutoff)
            ->orderBy('deleted_at')
            ->get();

        if ($due->isEmpty()) {
            $this->info("No books past the {$days}-day bin window.");

            return self::SUCCESS;
        }

        $purged = 0;
        $failed = 0;

        foreach ($due as $book) {
            if ($dryRun) {
                $this->line("would purge: {$book->name} ({$book->id}) deleted {$book->deleted_at->toDateTimeString()}");
                $purged++;
                continue;
            }

            try {
                $book->purge();
                $purged++;
            } catch (\Throwable $e) {
                $failed++;
                report($e);
                Log::error('books:purge-deleted failed for a book', [
                    'book_id' => $book->id,
                    'error'   => $e->getMessage(),
                ]);
            }
        }

        $verb = $dryRun ? 'Would purge' : 'Purged';
        $this->info("{$verb} {$purged} book(s) deleted before {$cutoff->toDateTimeString()}."
            . ($failed ? " {$failed} failed — see the log." : ''));

        if (! $dryRun && $purged > 0) {
            Log::info('books:purge-deleted completed', [
                'purged' => $purged,
                'failed' => $failed,
                'cutoff' => $cutoff->toDateTimeString(),
            ]);
        }

        return self::SUCCESS;
    }
}
