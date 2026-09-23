<?php

namespace Tests\Feature;

use App\Mail\BookEmailReport;
use App\Models\Book;
use App\Models\BookActivityLog;
use App\Models\Business;
use App\Models\Entry;
use App\Models\RecurringEntry;
use App\Models\ReportSchedule;
use App\Models\User;
use Tests\Feature\Api\ApiTestCase;

/**
 * Entry descriptions are optional. Everywhere an entry is shown outside the
 * ledger (exports, email reports, activity feed) a blank description falls
 * back to the category, then "Cash in" / "Cash out".
 */
class NullDescriptionFallbackTest extends ApiTestCase
{
    /** @return array{0: User, 1: Business, 2: Book} */
    private function proBook(): array
    {
        $owner    = $this->makeUser(pro: true);
        $business = $this->makeBusiness($owner);
        $book     = $this->makeBook($business);

        return [$owner, $business, $book];
    }

    /** Seed: one null-description entry with a category, one empty, one with a description. */
    private function seedEntries(Book $book): void
    {
        $this->makeEntry($book, 'out', '40.00', '2026-03-01', ['description' => null, 'category' => 'Fuel']);
        $this->makeEntry($book, 'out', '15.00', '2026-03-02', ['description' => '']);
        $this->makeEntry($book, 'in', '99.00', '2026-03-03', ['description' => null]);
        $this->makeEntry($book, 'in', '500.00', '2026-03-04', ['description' => 'Invoice 42']);
    }

    public function test_label_for_fallback_order(): void
    {
        $this->assertSame('Rent', Entry::labelFor('Rent', 'Housing', 'out'));
        $this->assertSame('Housing', Entry::labelFor('  ', 'Housing', 'out'));
        $this->assertSame('Cash out', Entry::labelFor(null, null, 'out'));
        $this->assertSame('Cash in', Entry::labelFor('', '', 'in'));
    }

    public function test_csv_export_uses_fallback_label(): void
    {
        [$owner, $business, $book] = $this->proBook();
        $this->seedEntries($book);

        $csv = $this->actingAs($owner)
            ->get(route('businesses.books.export.csv', [$business, $book]))
            ->assertOk()
            ->streamedContent();

        // array_values: array_filter preserves keys, which would desync the
        // header index from array_slice's positional offset.
        $rows = array_map('str_getcsv', array_values(array_filter(explode("\n", trim($csv)))));

        // The export opens with business/book metadata rows; entries start
        // after the column-header row.
        $headerIndex = null;
        foreach ($rows as $i => $row) {
            if (($row[0] ?? null) === 'Date' && ($row[1] ?? null) === 'Description') {
                $headerIndex = $i;
                break;
            }
        }
        $this->assertNotNull($headerIndex, 'CSV is missing its column header row.');

        $descriptions = array_column(array_slice($rows, $headerIndex + 1), 1);

        $this->assertSame(['Fuel', 'Cash out', 'Cash in', 'Invoice 42'], $descriptions);
    }

    public function test_pdf_export_view_renders_fallback_label(): void
    {
        [, $business, $book] = $this->proBook();
        $this->seedEntries($book);

        $entries = $book->entries()->orderBy('date')->get()
            ->each(fn ($e) => $e->running_balance = '0.00');

        $html = view('exports.book-pdf', [
            'business' => $business,
            'book'     => $book,
            'entries'  => $entries,
            'totalIn'  => $book->totalIn(),
            'totalOut' => $book->totalOut(),
            'balance'  => $book->balance(),
        ])->render();

        $this->assertStringContainsString('<td>Fuel</td>', $html);
        $this->assertStringContainsString('<td>Cash out</td>', $html);
        $this->assertStringContainsString('<td>Cash in</td>', $html);
        $this->assertStringContainsString('<td>Invoice 42</td>', $html);
    }

    public function test_email_report_data_and_view_use_fallback_label(): void
    {
        [, , $book] = $this->proBook();
        $this->seedEntries($book);

        $schedule = ReportSchedule::create([
            'book_id'    => $book->id,
            'frequency'  => 'weekly',
            'recipients' => ['owner@example.com'],
            'is_active'  => true,
        ]);

        $data = $schedule->buildReportData();
        $labels = array_column($data['recentEntries'], 'description');

        $this->assertEqualsCanonicalizing(['Fuel', 'Cash out', 'Cash in', 'Invoice 42'], $labels);

        $html = (new BookEmailReport($book, $data, 'weekly'))->render();

        $this->assertStringContainsString('Fuel', $html);
        $this->assertStringContainsString('Cash out', $html);
        $this->assertStringContainsString('Invoice 42', $html);
    }

    public function test_activity_log_describe_falls_back(): void
    {
        [$owner, , $book] = $this->proBook();

        $log = fn (string $action, array $meta) => new BookActivityLog([
            'book_id' => $book->id, 'user_id' => $owner->id, 'action' => $action, 'meta' => $meta,
        ]);

        // Stored description still wins.
        $this->assertSame('commented on "Rent"', $log('comment_added', ['entry_description' => 'Rent'])->describe());

        // Null / empty description with category + type in meta.
        $this->assertSame(
            'paused the recurring rule for "Hosting"',
            $log('recurring_paused', ['description' => null, 'category' => 'Hosting', 'type' => 'out'])->describe()
        );
        $this->assertSame(
            'deleted the recurring rule for "Cash out"',
            $log('recurring_deleted', ['description' => '', 'category' => null, 'type' => 'out'])->describe()
        );

        // Nothing usable in meta → generic wording, never an empty "".
        $this->assertSame('commented on "an entry"', $log('comment_added', ['entry_description' => null])->describe());
        $this->assertSame('attached a file to "an entry"', $log('attachment_added', [])->describe());

        // Missing type key no longer throws on entry_created.
        $this->assertSame('added a Cash Out entry', $log('entry_created', ['description' => null])->describe());
    }

    public function test_recurring_entry_display_label(): void
    {
        [, , $book] = $this->proBook();

        $rule = fn (array $attrs) => new RecurringEntry(array_merge([
            'book_id' => $book->id, 'type' => 'in', 'amount' => '10.00', 'frequency' => 'weekly',
        ], $attrs));

        $this->assertSame('Salary', $rule(['description' => 'Salary'])->displayLabel());
        $this->assertSame('Sales', $rule(['description' => null, 'category' => 'Sales'])->displayLabel());
        $this->assertSame('Cash in', $rule(['description' => ''])->displayLabel());
    }

    public function test_api_recurring_update_accepts_blank_description_and_logs_fallback(): void
    {
        [$owner, , $book] = $this->proBook();
        $this->actingAsUser($owner);

        $rule = $book->recurringEntries()->create([
            'type' => 'out', 'amount' => '15.00', 'description' => 'Hosting', 'category' => 'Servers',
            'frequency' => 'weekly', 'starts_at' => '2026-03-01', 'next_run_at' => '2026-03-08', 'status' => 'active',
        ]);

        $this->putJson("/api/v1/recurring/{$rule->id}", ['description' => '   '])->assertOk();

        $this->assertNull($rule->fresh()->description);

        $log = BookActivityLog::where('book_id', $book->id)->where('action', 'recurring_updated')->firstOrFail();
        $this->assertSame('edited the recurring rule for "Servers"', $log->describe());
    }
}
