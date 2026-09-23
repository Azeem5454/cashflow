<?php

namespace Tests\Feature;

use App\Livewire\Search;
use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GlobalSearchPageTest extends TestCase
{
    use RefreshDatabase;

    private function business(User $owner, string $name, string $currency = 'USD'): Business
    {
        $business = Business::create([
            'owner_id' => $owner->id,
            'name'     => $name,
            'currency' => $currency,
        ]);
        $business->members()->attach($owner->id, ['role' => 'owner']);

        return $business;
    }

    public function test_search_page_finds_entries_across_businesses(): void
    {
        $user = User::factory()->create();
        $user->plan = 'pro';
        $user->save();

        $bizA  = $this->business($user, 'Acme');
        $bizB  = $this->business($user, 'Beta');
        $bookA = $bizA->books()->create(['name' => 'March', 'opening_balance' => 0]);
        $bookB = $bizB->books()->create(['name' => 'April', 'opening_balance' => 0]);

        $bookA->entries()->create(['type' => 'out', 'amount' => '450.00', 'date' => '2026-03-01', 'description' => 'Office rent']);
        $bookB->entries()->create(['type' => 'in', 'amount' => '900.00', 'date' => '2026-04-02', 'description' => 'Rent refund']);
        $bookA->entries()->create(['type' => 'out', 'amount' => '20.00', 'date' => '2026-03-03', 'description' => 'Coffee']);

        $this->actingAs($user);

        // Idle state: nothing is searched until there are criteria.
        Livewire::test(Search::class)
            ->assertSee('Find any entry')
            ->assertDontSee('Office rent')
            ->set('q', 'rent')
            ->assertSee('Office rent')
            ->assertSee('Rent refund')
            ->assertSee('Acme')
            ->assertSee('Beta')
            ->assertDontSee('Coffee')
            // Filters narrow it down
            ->call('setType', 'out')
            ->assertSee('Office rent')
            ->assertDontSee('Rent refund')
            ->call('setType', 'out')            // toggles back off
            ->assertSee('Rent refund')
            ->call('setBusiness', $bizB->id)
            ->assertDontSee('Office rent')
            ->call('clearFilters')
            ->assertSee('Office rent');
    }

    public function test_locked_and_foreign_businesses_are_not_searchable(): void
    {
        $user = User::factory()->create(); // free plan
        $user->plan = 'free';
        $user->save();

        $first = $this->business($user, 'First');
        $extra = $this->business($user, 'Extra');   // locked on Free
        $first->books()->create(['name' => 'B1', 'opening_balance' => 0])
            ->entries()->create(['type' => 'out', 'amount' => '10.00', 'date' => '2026-03-01', 'description' => 'Rent one']);
        $extra->books()->create(['name' => 'B2', 'opening_balance' => 0])
            ->entries()->create(['type' => 'out', 'amount' => '20.00', 'date' => '2026-03-02', 'description' => 'Rent two']);

        $stranger = User::factory()->create();
        $foreign  = $this->business($stranger, 'Foreign');
        $foreign->books()->create(['name' => 'B3', 'opening_balance' => 0])
            ->entries()->create(['type' => 'out', 'amount' => '30.00', 'date' => '2026-03-03', 'description' => 'Rent three']);

        $this->actingAs($user);

        Livewire::test(Search::class)
            ->set('q', 'rent')
            ->assertSee('Rent one')
            ->assertDontSee('Rent two')
            ->assertDontSee('Rent three');
    }

    public function test_load_more_grows_the_result_list(): void
    {
        $user = User::factory()->create();
        $user->plan = 'pro';
        $user->save();

        $business = $this->business($user, 'Acme');
        $book     = $business->books()->create(['name' => 'March', 'opening_balance' => 0]);

        for ($i = 1; $i <= 30; $i++) {
            $book->entries()->create([
                'type'        => 'in',
                'amount'      => '10.00',
                'date'        => '2026-03-' . str_pad((string) min($i, 28), 2, '0', STR_PAD_LEFT),
                'description' => "Rent {$i}",
            ]);
        }

        $this->actingAs($user);

        Livewire::test(Search::class)
            ->set('q', 'rent')
            ->assertViewHas('shown', 25)
            ->assertViewHas('total', 30)
            ->assertViewHas('hasMore', true)
            ->call('loadMore')
            ->assertViewHas('shown', 30)
            ->assertViewHas('hasMore', false)
            // Changing a filter resets back to the first page.
            ->call('setType', 'in')
            ->assertViewHas('shown', 25);
    }

    public function test_route_is_reachable_and_sidebar_links_to_it(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('search'))
            ->assertOk()
            ->assertSee('Find any entry');

        $this->actingAs($user)->get(route('dashboard'))->assertSee(route('search'));
    }

    public function test_guests_are_redirected(): void
    {
        $this->get(route('search'))->assertRedirect(route('login'));
    }
}
