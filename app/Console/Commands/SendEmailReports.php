<?php

namespace App\Console\Commands;

use App\Mail\BookEmailReport;
use App\Models\ReportSchedule;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendEmailReports extends Command
{
    protected $signature = 'reports:send';
    protected $description = 'Send scheduled email reports for all active book report schedules';

    public function handle(): int
    {
        $schedules = ReportSchedule::where('is_active', true)
            ->with('book.business.owner', 'book.entries')
            ->get();

        $sent = 0;

        foreach ($schedules as $schedule) {
            if (! $schedule->isDue()) {
                continue;
            }

            // Skip if the business owner is no longer Pro
            if (! $schedule->book->business->isPro()) {
                continue;
            }

            try {
                $reportData = $schedule->buildReportData();

                foreach ($schedule->recipients as $email) {
                    Mail::to($email)->queue(
                        new BookEmailReport($schedule->book, $reportData, $schedule->frequency)
                    );
                }

                $schedule->update(['last_sent_at' => now()]);
                $sent++;
            } catch (\Throwable $e) {
                Log::error('Email report failed', [
                    'schedule_id' => $schedule->id,
                    'book_id'     => $schedule->book_id,
                    'error'       => $e->getMessage(),
                ]);
            }
        }

        $this->info("Sent {$sent} email reports.");

        return self::SUCCESS;
    }
}
