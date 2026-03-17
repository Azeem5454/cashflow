<?php

namespace App\Console\Commands;

use App\Models\RecurringEntry;
use Illuminate\Console\Command;

class GenerateRecurringEntries extends Command
{
    protected $signature = 'entries:generate-recurring';
    protected $description = 'Create entries from active recurring entry rules';

    public function handle(): int
    {
        $today = now()->format('Y-m-d');

        $recurring = RecurringEntry::where('is_active', true)
            ->where('next_run_at', '<=', $today)
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $today))
            ->with('book.business.owner')
            ->get();

        $count = 0;

        foreach ($recurring as $rec) {
            // Skip if business owner is no longer Pro
            if (! $rec->book->business->isPro()) {
                continue;
            }

            // Catch up: generate entries for all missed runs
            while ($rec->next_run_at->format('Y-m-d') <= $today) {
                $rec->book->entries()->create([
                    'type'               => $rec->type,
                    'amount'             => $rec->amount,
                    'description'        => $rec->description,
                    'date'               => $rec->next_run_at,
                    'reference'          => $rec->reference,
                    'category'           => $rec->category,
                    'payment_mode'       => $rec->payment_mode,
                    'recurring_entry_id' => $rec->id,
                ]);

                $rec->advanceNextRun();
                $count++;

                // Deactivate if past end date
                if ($rec->ends_at && $rec->next_run_at->format('Y-m-d') > $rec->ends_at->format('Y-m-d')) {
                    $rec->is_active = false;
                    break;
                }
            }

            $rec->save();
            $rec->book->touch();
        }

        $this->info("Generated {$count} recurring entries.");

        return self::SUCCESS;
    }
}
