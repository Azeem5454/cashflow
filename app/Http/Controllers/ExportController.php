<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Business;
use App\Models\Entry;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    private function authorise(Business $business, Book $book, bool $requirePro = true): void
    {
        abort_unless(
            auth()->user()->businesses()->where('businesses.id', $business->id)->exists(),
            403
        );
        if ($requirePro) {
            abort_unless($business->isPro(), 403);
        }
        abort_unless($book->business_id === $business->id, 404);
    }

    private function entriesWithRunningBalance(Book $book)
    {
        $entries = $book->entries()
            ->orderBy('date', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        $running = '0.00';
        foreach ($entries as $entry) {
            $running = $entry->type === 'in'
                ? bcadd($running, (string) $entry->amount, 2)
                : bcsub($running, (string) $entry->amount, 2);
            $entry->running_balance = $running;
        }

        return $entries;
    }

    public function pdf(Business $business, Book $book): \Illuminate\Http\Response
    {
        $this->authorise($business, $book);

        $entries  = $this->entriesWithRunningBalance($book);
        $totalIn  = $book->totalIn();
        $totalOut = $book->totalOut();
        $balance  = $book->balance();

        $pdf = Pdf::loadView('exports.book-pdf', compact(
            'business', 'book', 'entries', 'totalIn', 'totalOut', 'balance'
        ))->setPaper('a4', 'landscape');

        $filename = str()->slug($business->name) . '-' . str()->slug($book->name) . '.pdf';

        return $pdf->download($filename);
    }

    public function csv(Business $business, Book $book): StreamedResponse
    {
        $this->authorise($business, $book);

        $entries  = $this->entriesWithRunningBalance($book);
        $filename = str()->slug($business->name) . '-' . str()->slug($book->name) . '.csv';

        return response()->streamDownload(function () use ($entries) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM so Excel opens it correctly
            fputs($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Date', 'Description', 'Reference',
                'Category', 'Payment Mode',
                'Cash In', 'Cash Out', 'Running Balance',
            ]);

            foreach ($entries as $entry) {
                fputcsv($handle, [
                    $entry->date->format('Y-m-d'),
                    $entry->description,
                    $entry->reference ?? '',
                    $entry->category ?? '',
                    $entry->payment_mode ?? '',
                    $entry->type === 'in'  ? number_format((float) $entry->amount, 2, '.', '') : '',
                    $entry->type === 'out' ? number_format((float) $entry->amount, 2, '.', '') : '',
                    number_format((float) $entry->running_balance, 2, '.', ''),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function attachment(Business $business, Book $book, Entry $entry): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $this->authorise($business, $book, requirePro: false);
        abort_unless($entry->book_id === $book->id, 404);
        abort_unless($entry->attachment_path, 404);
        abort_unless(Storage::disk('local')->exists($entry->attachment_path), 404);

        $mime = Storage::disk('local')->mimeType($entry->attachment_path);

        // Strict MIME whitelist — never serve unexpected file types
        $allowedMimes = ['image/png', 'image/jpeg', 'image/jpg', 'application/pdf'];
        abort_unless(in_array($mime, $allowedMimes), 403);

        $path = Storage::disk('local')->path($entry->attachment_path);

        // PDFs and images served inline for preview; all others force-download (belt-and-suspenders)
        return response()->file($path, [
            'Content-Type'              => $mime,
            'X-Content-Type-Options'    => 'nosniff',
            'Content-Security-Policy'   => "default-src 'none'",
        ]);
    }
}
