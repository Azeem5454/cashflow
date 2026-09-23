<?php

namespace Tests\Feature;

use App\Livewire\Dashboard;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\Feature\Api\ApiTestCase;

/**
 * Dashboard business rows carry a 7-day sparkline and a "vs last month" chip.
 */
class DashboardBusinessSignalTest extends ApiTestCase
{
    public function test_dashboard_renders_the_sparkline_and_the_change_chip(): void
    {
        Carbon::setTestNow('2026-09-23 10:00:00');

        $owner    = $this->makeUser();
        $business = $this->makeBusiness($owner, ['name' => 'Acme']);
        $book     = $this->makeBook($business);

        $this->makeEntry($book, 'in', '100.00', '2026-08-10'); // last month
        $this->makeEntry($book, 'in', '150.00', '2026-09-22'); // this month, inside the sparkline window

        $component = Livewire::actingAs($owner)->test(Dashboard::class);

        $trends = $component->viewData('trends');
        $this->assertSame(50.0, $trends[$business->id]['monthChangePct']);

        $component
            ->assertSee('Daily net for the last 7 days in Acme')
            ->assertSee('50% vs last month');

        Carbon::setTestNow();
    }

    public function test_the_chip_is_replaced_with_a_note_when_last_month_was_empty(): void
    {
        Carbon::setTestNow('2026-09-23 10:00:00');

        $owner    = $this->makeUser();
        $business = $this->makeBusiness($owner);
        $book     = $this->makeBook($business);
        $this->makeEntry($book, 'in', '150.00', '2026-09-22');

        Livewire::actingAs($owner)->test(Dashboard::class)
            ->assertSee('No activity last month')
            ->assertDontSee('vs last month');

        Carbon::setTestNow();
    }

    public function test_a_business_with_no_recent_entries_shows_no_sparkline(): void
    {
        Carbon::setTestNow('2026-09-23 10:00:00');

        $owner    = $this->makeUser();
        $business = $this->makeBusiness($owner);
        $this->makeBook($business);

        Livewire::actingAs($owner)->test(Dashboard::class)
            ->assertDontSee('Daily net for the last 7 days')
            ->assertDontSee('No activity last month');

        Carbon::setTestNow();
    }
}
