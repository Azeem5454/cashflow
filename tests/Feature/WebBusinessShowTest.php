<?php

namespace Tests\Feature;

use App\Livewire\Business\Show;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\Feature\Api\ApiTestCase;

/**
 * Business page: carry-forward opening balance on the create-book modal, and
 * books bucketed into collapsible year sections.
 */
class WebBusinessShowTest extends ApiTestCase
{
    public function test_create_book_modal_offers_the_previous_books_closing_balance(): void
    {
        $owner    = $this->makeUser();
        $business = $this->makeBusiness($owner);

        $previous = $this->makeBook($business, ['name' => 'February', 'opening_balance' => '100.00', 'period_ends_at' => '2026-02-28']);
        $this->makeEntry($previous, 'in',  '250.50', '2026-02-05');
        $this->makeEntry($previous, 'out', '50.25',  '2026-02-06');

        Livewire::actingAs($owner)
            ->test(Show::class, ['business' => $business])
            ->call('openCreateBook')
            ->assertSet('carryForwardEnabled', true)
            ->assertSet('carryForwardBookName', 'February')
            ->assertSet('carryForwardAmount', '300.25')
            ->assertSet('bookOpeningBalance', '300.25');
    }

    public function test_unchecking_carry_forward_clears_the_opening_balance(): void
    {
        $owner    = $this->makeUser();
        $business = $this->makeBusiness($owner);
        $previous = $this->makeBook($business, ['opening_balance' => '100.00']);
        $this->makeEntry($previous, 'in', '50.00', '2026-02-05');

        Livewire::actingAs($owner)
            ->test(Show::class, ['business' => $business])
            ->call('openCreateBook')
            ->assertSet('bookOpeningBalance', '150.00')
            ->set('carryForwardEnabled', false)
            ->assertSet('bookOpeningBalance', '')
            ->set('carryForwardEnabled', true)
            ->assertSet('bookOpeningBalance', '150.00');
    }

    public function test_the_first_book_in_a_business_gets_no_offer(): void
    {
        $owner    = $this->makeUser();
        $business = $this->makeBusiness($owner);

        Livewire::actingAs($owner)
            ->test(Show::class, ['business' => $business])
            ->call('openCreateBook')
            ->assertSet('carryForwardEnabled', false)
            ->assertSet('carryForwardAmount', null)
            ->assertSet('bookOpeningBalance', '');
    }

    public function test_creating_the_book_stores_the_carried_forward_balance(): void
    {
        $owner    = $this->makeUser();
        $business = $this->makeBusiness($owner);
        $previous = $this->makeBook($business, ['opening_balance' => '0.00']);
        $this->makeEntry($previous, 'out', '75.25', '2026-02-05');

        Livewire::actingAs($owner)
            ->test(Show::class, ['business' => $business])
            ->call('openCreateBook')
            ->set('bookName', 'March')
            ->call('createBook', '', '');

        $created = $business->books()->where('name', 'March')->firstOrFail();
        $this->assertSame('-75.25', bcadd((string) $created->opening_balance, '0', 2));
    }

    public function test_a_viewer_cannot_open_the_create_book_modal(): void
    {
        $owner    = $this->makeUser();
        $business = $this->makeBusiness($owner);
        $this->makeBook($business, ['opening_balance' => '100.00']);

        $viewer = $this->makeUser();
        $this->addMember($business, $viewer, 'viewer');

        Livewire::actingAs($viewer)
            ->test(Show::class, ['business' => $business])
            ->call('openCreateBook')
            ->assertSet('showCreateBook', false)
            ->assertSet('carryForwardAmount', null);
    }

    public function test_books_are_grouped_by_year_with_the_current_year_expanded(): void
    {
        Carbon::setTestNow('2026-09-23 10:00:00');

        $owner    = $this->makeUser();
        $business = $this->makeBusiness($owner);

        $this->makeBook($business, ['name' => 'Recent', 'period_starts_at' => '2026-03-01', 'opening_balance' => '10.00']);
        $this->makeBook($business, ['name' => 'Older',  'period_starts_at' => '2024-03-01', 'opening_balance' => '5.00']);

        $groups = Livewire::actingAs($owner)
            ->test(Show::class, ['business' => $business])
            ->viewData('bookGroups');

        $this->assertCount(2, $groups);
        $this->assertSame(2026, $groups[0]['year']);
        $this->assertTrue($groups[0]['open']);
        $this->assertSame('10.00', $groups[0]['net']);
        $this->assertSame(2024, $groups[1]['year']);
        $this->assertFalse($groups[1]['open']);

        Carbon::setTestNow();
    }

    public function test_searching_returns_one_flat_open_section(): void
    {
        Carbon::setTestNow('2026-09-23 10:00:00');

        $owner    = $this->makeUser();
        $business = $this->makeBusiness($owner);
        $this->makeBook($business, ['name' => 'Alpha ledger', 'period_starts_at' => '2024-03-01']);
        $this->makeBook($business, ['name' => 'Beta ledger',  'period_starts_at' => '2026-03-01']);

        $groups = Livewire::actingAs($owner)
            ->test(Show::class, ['business' => $business])
            ->set('search', 'ledger')
            ->viewData('bookGroups');

        $this->assertCount(1, $groups);
        $this->assertNull($groups[0]['year']);
        $this->assertTrue($groups[0]['open']);
        $this->assertSame(2, $groups[0]['count']);

        Carbon::setTestNow();
    }

    public function test_the_newest_section_opens_even_when_nothing_is_from_this_year(): void
    {
        Carbon::setTestNow('2026-09-23 10:00:00');

        $owner    = $this->makeUser();
        $business = $this->makeBusiness($owner);
        $this->makeBook($business, ['name' => 'Old A', 'period_starts_at' => '2024-03-01']);
        $this->makeBook($business, ['name' => 'Old B', 'period_starts_at' => '2023-03-01']);

        $groups = Livewire::actingAs($owner)
            ->test(Show::class, ['business' => $business])
            ->viewData('bookGroups');

        $this->assertSame(2024, $groups[0]['year']);
        $this->assertTrue($groups[0]['open']);
        $this->assertFalse($groups[1]['open']);

        Carbon::setTestNow();
    }
}
