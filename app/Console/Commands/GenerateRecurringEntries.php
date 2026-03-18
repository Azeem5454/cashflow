<?php

namespace App\Console\Commands;

use App\Models\RecurringEntry;
use Illuminate\Console\Command;

class GenerateRecurringEntries extends Command
{
    protected $signature = 'entries:generate-recurring';
    protected $description = 'Create entries from active recurring entry rules (book-scoped)';

    public function handle(): int
    {
        $today = now()->toDateString();

        $recurring = RecurringEntry::where('status', 'active')
            ->where('next_run_at', '<=', $today)
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $today))
            ->with('book.business.owner')
            ->get();

        $count = 0;

        foreach ($recurring as $rec) {
            // Skip if the book's business owner is no longer Pro
            if (! $rec->book->business->isPro()) {
                continue;
            }

            $bookPeriodEnd = $rec->book->period_ends_at;

            // Catch-up loop: generate all missed runs up to today
            while ($rec->next_run_at->toDateString() <= $today) {
                $entryDate = $rec->next_run_at->toDateString();

                // Respect the book's period end as a hard stop
                if ($bookPeriodEnd && $rec->next_run_at->gt($bookPeriodEnd)) {
                    $rec->status = 'completed';
                    break;
                }

                // Respect explicit ends_at
                if ($rec->ends_at && $rec->next_run_at->gt($rec->ends_at)) {
                    $rec->status = 'completed';
                    break;
                }

                $rec->book->entries()->create([
                    'type'               => $rec->type,
                    'amount'             => $rec->amount,
                    'description'        => $rec->description,
                    'date'               => $entryDate,
                    'reference'          => $rec->reference,
                    'category'           => $rec->category,
                    'payment_mode'       => $rec->payment_mode,
                    'recurring_entry_id' => $rec->id,
                ]);

                $rec->book->touch();
                $rec->advanceNextRun();
                $count++;
            }

            $rec->save();
        }

        $this->info("Generated {$count} recurring entries.");

        return self::SUCCESS;
    }
}
