<?php

namespace Tests\Feature;

use App\Livewire\Book\Show;
use App\Models\Book;
use App\Services\BookLedger;
use Livewire\Livewire;
use Tests\Feature\Api\ApiTestCase;

class LedgerPaginationTest extends ApiTestCase
{
    /** Reference balances computed in PHP over the full book (same semantics as the old ledger). */
    private function referenceBalances(Book $book): array
    {
        return BookLedger::chronological($book)->mapWithKeys(fn ($e) => [$e->id => $e->running_balance])->all();
    }

    private function seedBook(int $count = 120): array
    {
        $user     = $this->makeUser(pro: true);
        $business = $this->makeBusiness($user);
        $book     = $this->makeBook($business, ['opening_balance' => '1000.00']);

        for ($i = 0; $i < $count; $i++) {
            $this->makeEntry(
                $book,
                $i % 3 === 0 ? 'out' : 'in',
                number_format(10 + ($i * 7) % 250 + 0.25, 2, '.', ''),
                now()->subDays(200 - intdiv($i, 2))->format('Y-m-d'), // two entries per day → exercises tie-breaks
                ['category' => $i % 4 === 0 ? 'Food' : 'Rent']
            );
        }

        return [$user, $business, $book];
    }

    public function test_first_page_is_50_newest_rows_with_correct_running_balances(): void
    {
        [$user, $business, $book] = $this->seedBook();
        $reference = $this->referenceBalances($book);

        $component = Livewire::actingAs($user)->test(Show::class, ['business' => $business, 'book' => $book]);

        $entries = $component->viewData('entries');
        $this->assertCount(50, $entries);
        $this->assertTrue($component->viewData('hasMoreEntries'));
        $this->assertSame(120, $component->viewData('filteredCount'));

        // Newest first: the first row is the last entry of the full chronological ledger.
        $this->assertSame(array_key_last($reference), $entries->first()->id);

        foreach ($entries as $entry) {
            $this->assertSame($reference[$entry->id], $entry->running_balance, "Running balance mismatch for {$entry->id}");
        }

        // Last row's running balance equals the book balance (opening + in − out).
        $this->assertSame(end($reference), $entries->first()->running_balance);
        $this->assertSame(end($reference), $component->viewData('balance'));
    }

    public function test_load_more_returns_the_next_50_and_stops_at_the_end(): void
    {
        [$user, $business, $book] = $this->seedBook();
        $reference = $this->referenceBalances($book);
        $newestFirst = array_reverse(array_keys($reference));

        $component = Livewire::actingAs($user)->test(Show::class, ['business' => $business, 'book' => $book]);

        $component->call('loadMore');
        $entries = $component->viewData('entries');
        $this->assertCount(100, $entries);
        $this->assertSame(array_slice($newestFirst, 50, 50), $entries->slice(50)->pluck('id')->values()->all());
        foreach ($entries->slice(50) as $entry) {
            $this->assertSame($reference[$entry->id], $entry->running_balance);
        }

        $component->call('loadMore');
        $this->assertCount(120, $component->viewData('entries'));
        $this->assertFalse($component->viewData('hasMoreEntries'));
        $component->assertDontSee('Load 50 more');
    }

    public function test_filters_keep_full_ledger_balances_and_totals_cover_the_whole_filtered_set(): void
    {
        [$user, $business, $book] = $this->seedBook();
        $reference = $this->referenceBalances($book);

        $all      = BookLedger::chronological($book);
        $expected = BookLedger::applyFilters($all, ['type' => 'out', 'category' => ['Food']]);
        $totals   = BookLedger::totals($expected);

        $component = Livewire::actingAs($user)->test(Show::class, ['business' => $business, 'book' => $book])
            ->call('loadMore') // page size grows…
            ->set('filterType', 'out')
            ->set('filterCategories', ['Food']); // …and a filter change resets it

        $this->assertSame(Show::LEDGER_PAGE_SIZE, $component->get('perPage'));

        $entries = $component->viewData('entries');
        $this->assertSame($expected->count(), $component->viewData('filteredCount'));
        $this->assertCount(min(50, $expected->count()), $entries);
        $this->assertSame(
            $expected->reverse()->take(50)->pluck('id')->values()->all(),
            $entries->pluck('id')->values()->all()
        );
        foreach ($entries as $entry) {
            $this->assertSame('out', $entry->type);
            $this->assertSame('Food', $entry->category);
            $this->assertSame($reference[$entry->id], $entry->running_balance);
        }

        // Summary strip reflects the whole filtered set, not just the visible page.
        $this->assertSame($totals['totalIn'], $component->viewData('totalIn'));
        $this->assertSame($totals['totalOut'], $component->viewData('totalOut'));
        $this->assertTrue($component->viewData('hasFilters'));
        $component->assertSee('Showing:')->assertSee('Cash out only · Food');
    }

    public function test_search_is_server_side_and_matches_description_category_and_amount(): void
    {
        $user     = $this->makeUser(pro: true);
        $business = $this->makeBusiness($user);
        $book     = $this->makeBook($business, ['opening_balance' => '0.00']);
        $a = $this->makeEntry($book, 'in', '100.00', '2026-09-01', ['description' => 'Client invoice 42']);
        $b = $this->makeEntry($book, 'out', '37.50', '2026-09-02', ['description' => 'Lunch', 'category' => 'Meals']);
        $this->makeEntry($book, 'out', '12.00', '2026-09-03', ['description' => 'Parking']);

        $component = Livewire::actingAs($user)->test(Show::class, ['business' => $business, 'book' => $book]);

        $component->set('search', 'INVOICE');
        $this->assertSame([$a->id], $component->viewData('entries')->pluck('id')->all());

        $component->set('search', 'meals');
        $this->assertSame([$b->id], $component->viewData('entries')->pluck('id')->all());
        $this->assertSame('62.50', $component->viewData('entries')->first()->running_balance);

        $component->set('search', '37.5');
        $this->assertSame([$b->id], $component->viewData('entries')->pluck('id')->all());
    }

    public function test_rows_without_a_description_fall_back_to_category_or_direction(): void
    {
        $user     = $this->makeUser(pro: true);
        $business = $this->makeBusiness($user);
        $book     = $this->makeBook($business);
        $this->makeEntry($book, 'out', '80.00', '2026-09-01', ['description' => null, 'category' => 'Rent']);
        $this->makeEntry($book, 'out', '15.00', '2026-09-02', ['description' => '', 'category' => null]);
        $this->makeEntry($book, 'in', '20.00', '2026-09-03', ['description' => null, 'category' => null]);

        Livewire::actingAs($user)
            ->test(Show::class, ['business' => $business, 'book' => $book])
            ->assertSee('Rent')
            ->assertSee('Cash out')
            ->assertSee('Cash in')
            ->assertSee('Edit Rent', false)
            ->assertSee('Edit Cash out', false)
            ->assertSee('Edit Cash in', false);
    }

    public function test_reports_tab_shows_the_four_plain_language_blocks_only(): void
    {
        [$user, $business, $book] = $this->seedBook(12);

        $component = Livewire::actingAs($user)
            ->test(Show::class, ['business' => $business, 'book' => $book])
            ->set('activeTab', 'reports');

        $component->assertSee('At a glance')
            ->assertSee('Money in vs money out')
            ->assertSee('Where your money went')
            ->assertSee('What stands out')
            ->assertDontSee('Health Score')
            ->assertDontSee('Burn Rate')
            ->assertDontSee('Income Reliability')
            ->assertDontSee('Spending Velocity')
            ->assertDontSee('Forecast');

        $report = $component->viewData('reportData');
        $this->assertSame(['periodSummary', 'trendChart', 'categoryBreakdown'], array_keys($report));
        $this->assertSame(12, $report['periodSummary']['inCount'] + $report['periodSummary']['outCount']);
    }
}
