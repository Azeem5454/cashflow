<?php

namespace Tests\Feature\Api;

use App\Models\User;

class SearchApiTest extends ApiTestCase
{
    /** @return array{0:User,1:\App\Models\Business,2:\App\Models\Book} */
    private function scenario(): array
    {
        $user     = $this->makeUser(pro: true);
        $business = $this->makeBusiness($user, ['name' => 'Acme', 'currency' => 'USD']);
        $book     = $this->makeBook($business, ['name' => 'March']);

        return [$user, $business, $book];
    }

    public function test_finds_entries_across_businesses_and_books(): void
    {
        [$user, $bizA, $bookA] = $this->scenario();
        $bizB  = $this->makeBusiness($user, ['name' => 'Beta', 'currency' => 'USD']);
        $bookB = $this->makeBook($bizB, ['name' => 'April']);

        $this->makeEntry($bookA, 'out', '450.00', '2026-03-01', ['description' => 'Office rent']);
        $this->makeEntry($bookB, 'in', '900.00', '2026-04-02', ['description' => 'Rent refund']);
        $this->makeEntry($bookA, 'out', '20.00', '2026-03-03', ['description' => 'Coffee']);

        $this->actingAsUser($user);
        $res = $this->getJson('/api/v1/search?q=rent')->assertOk();

        $res->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.currentPage', 1)
            ->assertJsonPath('meta.perPage', 25)
            // Newest date first
            ->assertJsonPath('data.0.description', 'Rent refund')
            ->assertJsonPath('data.0.amount', '900.00')
            ->assertJsonPath('data.0.book.name', 'April')
            ->assertJsonPath('data.0.business.name', 'Beta')
            ->assertJsonPath('data.0.business.currency', 'USD')
            ->assertJsonPath('data.0.business.currencySymbol', '$')
            ->assertJsonPath('data.1.description', 'Office rent')
            ->assertJsonPath('data.1.book.name', 'March')
            ->assertJsonPath('data.1.business.name', 'Acme');

        // Totals cover the whole match set, single currency.
        $res->assertJsonPath('totals.in', '900.00')
            ->assertJsonPath('totals.out', '450.00')
            ->assertJsonPath('totals.net', '450.00')
            ->assertJsonPath('totals.count', 2)
            ->assertJsonPath('totals.currency', 'USD')
            ->assertJsonPath('totals.mixedCurrency', false)
            ->assertJsonPath('totalsByCurrency.USD.count', 2);
    }

    public function test_matches_text_columns_and_amount(): void
    {
        [$user, , $book] = $this->scenario();

        $this->makeEntry($book, 'out', '450.00', '2026-03-01', ['description' => null, 'category' => 'Supplies']);
        $this->makeEntry($book, 'in', '12.00', '2026-03-02', ['description' => 'Sale', 'payment_mode' => 'Bank Transfer']);
        $this->makeEntry($book, 'out', '77.00', '2026-03-03', ['description' => 'Fuel', 'reference' => 'INV-9912']);

        $this->actingAsUser($user);

        // category
        $this->getJson('/api/v1/search?q=suppl')->assertOk()->assertJsonPath('meta.total', 1)
            // description is null → label falls back to the category
            ->assertJsonPath('data.0.description', null)
            ->assertJsonPath('data.0.label', 'Supplies');

        // payment mode
        $this->getJson('/api/v1/search?q=bank')->assertOk()->assertJsonPath('meta.total', 1);

        // reference
        $this->getJson('/api/v1/search?q=inv-99')->assertOk()->assertJsonPath('meta.total', 1);

        // amount — "450" matches 450.00
        $this->getJson('/api/v1/search?q=450')->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.amount', '450.00');

        // case-insensitive
        $this->getJson('/api/v1/search?q=FUEL')->assertOk()->assertJsonPath('meta.total', 1);
    }

    public function test_non_member_entries_are_never_returned(): void
    {
        [, , $book] = $this->scenario();
        $this->makeEntry($book, 'out', '450.00', '2026-03-01', ['description' => 'Office rent']);

        $stranger = $this->makeUser();
        $this->actingAsUser($stranger);

        $this->getJson('/api/v1/search?q=rent')->assertOk()
            ->assertJsonPath('meta.total', 0)
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('totals.count', 0);
    }

    public function test_free_plan_locked_businesses_are_excluded(): void
    {
        $user  = $this->makeUser(); // free
        $first = $this->makeBusiness($user, ['name' => 'First']);
        $extra = $this->makeBusiness($user, ['name' => 'Extra']);

        $this->makeEntry($this->makeBook($first), 'out', '10.00', '2026-03-01', ['description' => 'Rent one']);
        $this->makeEntry($this->makeBook($extra), 'out', '20.00', '2026-03-02', ['description' => 'Rent two']);

        $this->actingAsUser($user);
        $this->getJson('/api/v1/search?q=rent')->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.description', 'Rent one')
            ->assertJsonPath('totals.out', '10.00');
    }

    public function test_viewer_can_search_shared_business(): void
    {
        [$owner, $business, $book] = $this->scenario();
        $this->makeEntry($book, 'in', '99.00', '2026-03-05', ['description' => 'Retainer']);

        $viewer = $this->makeUser();
        $this->addMember($business, $viewer, 'viewer');

        $this->actingAsUser($viewer);
        $this->getJson('/api/v1/search?q=retainer')->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.business.id', $business->id);
    }

    public function test_filters_type_dates_business_and_amount_range(): void
    {
        [$user, $bizA, $bookA] = $this->scenario();
        $bizB  = $this->makeBusiness($user, ['name' => 'Beta', 'currency' => 'USD']);
        $bookB = $this->makeBook($bizB);

        $this->makeEntry($bookA, 'in',  '100.00', '2026-01-10', ['description' => 'Rent in']);
        $this->makeEntry($bookA, 'out', '200.00', '2026-02-10', ['description' => 'Rent out']);
        $this->makeEntry($bookB, 'out', '300.00', '2026-03-10', ['description' => 'Rent other']);

        $this->actingAsUser($user);

        $this->getJson('/api/v1/search?q=rent&type=out')->assertOk()->assertJsonPath('meta.total', 2);
        $this->getJson('/api/v1/search?q=rent&from=2026-02-01')->assertOk()->assertJsonPath('meta.total', 2);
        $this->getJson('/api/v1/search?q=rent&to=2026-02-01')->assertOk()->assertJsonPath('meta.total', 1);
        $this->getJson('/api/v1/search?q=rent&from=2026-02-01&to=2026-02-28')->assertOk()->assertJsonPath('meta.total', 1);
        $this->getJson("/api/v1/search?q=rent&businessId={$bizB->id}")->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.description', 'Rent other');
        $this->getJson('/api/v1/search?q=rent&minAmount=200')->assertOk()->assertJsonPath('meta.total', 2);
        $this->getJson('/api/v1/search?q=rent&minAmount=150&maxAmount=250')->assertOk()->assertJsonPath('meta.total', 1);

        // Filters alone (no q) are a valid search.
        $this->getJson('/api/v1/search?type=in')->assertOk()->assertJsonPath('meta.total', 1);

        // Another member's business cannot be smuggled in via businessId.
        $other = $this->makeBusiness($this->makeUser());
        $this->getJson("/api/v1/search?q=rent&businessId={$other->id}")->assertOk()
            ->assertJsonPath('meta.total', 0);

        $this->getJson('/api/v1/search?type=sideways')->assertStatus(422);
        $this->getJson('/api/v1/search?businessId=not-a-uuid')->assertStatus(422);
    }

    public function test_short_or_missing_query_returns_empty_set(): void
    {
        [$user, , $book] = $this->scenario();
        $this->makeEntry($book, 'out', '450.00', '2026-03-01', ['description' => 'Office rent']);

        $this->actingAsUser($user);

        foreach (['', 'r', '   '] as $q) {
            $this->getJson('/api/v1/search?q=' . urlencode($q))->assertOk()
                ->assertJsonPath('meta.total', 0)
                ->assertJsonCount(0, 'data')
                ->assertJsonPath('totals.count', 0)
                ->assertJsonPath('totalsByCurrency', []);
        }
    }

    public function test_pagination(): void
    {
        [$user, , $book] = $this->scenario();
        for ($i = 1; $i <= 7; $i++) {
            $this->makeEntry($book, 'in', '10.00', '2026-03-' . str_pad((string) $i, 2, '0', STR_PAD_LEFT), [
                'description' => "Rent {$i}",
            ]);
        }

        $this->actingAsUser($user);

        $this->getJson('/api/v1/search?q=rent&perPage=3')->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.total', 7)
            ->assertJsonPath('meta.lastPage', 3)
            ->assertJsonPath('data.0.description', 'Rent 7')
            // Totals always cover the whole match set, not the page.
            ->assertJsonPath('totals.in', '70.00')
            ->assertJsonPath('totals.count', 7);

        $this->getJson('/api/v1/search?q=rent&perPage=3&page=3')->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.description', 'Rent 1');

        // perPage is capped at 100.
        $this->getJson('/api/v1/search?q=rent&perPage=5000')->assertOk()
            ->assertJsonPath('meta.perPage', 100);
    }

    public function test_totals_are_split_per_currency(): void
    {
        $user  = $this->makeUser(pro: true);
        $usd   = $this->makeBusiness($user, ['name' => 'US', 'currency' => 'USD']);
        $pkr   = $this->makeBusiness($user, ['name' => 'PK', 'currency' => 'PKR']);

        $this->makeEntry($this->makeBook($usd), 'in',  '100.00', '2026-03-01', ['description' => 'Rent a']);
        $this->makeEntry($this->makeBook($usd), 'out', '40.00',  '2026-03-02', ['description' => 'Rent b']);
        $this->makeEntry($this->makeBook($pkr), 'in',  '5000.00', '2026-03-03', ['description' => 'Rent c']);

        $this->actingAsUser($user);
        $res = $this->getJson('/api/v1/search?q=rent')->assertOk();

        $res->assertJsonPath('totals.mixedCurrency', true)
            ->assertJsonPath('totals.in', null)
            ->assertJsonPath('totals.net', null)
            ->assertJsonPath('totals.count', 3)
            ->assertJsonPath('totalsByCurrency.USD.in', '100.00')
            ->assertJsonPath('totalsByCurrency.USD.out', '40.00')
            ->assertJsonPath('totalsByCurrency.USD.net', '60.00')
            ->assertJsonPath('totalsByCurrency.USD.currencySymbol', '$')
            ->assertJsonPath('totalsByCurrency.PKR.in', '5000.00')
            ->assertJsonPath('totalsByCurrency.PKR.net', '5000.00')
            ->assertJsonPath('totalsByCurrency.PKR.count', 1);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/api/v1/search?q=rent')->assertUnauthorized();
    }
}
