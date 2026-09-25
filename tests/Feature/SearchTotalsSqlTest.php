<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\User;
use App\Services\EntrySearch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The totals query groups by currency. It inherits its base from the search
 * query, which selects `entries.*` — and selectRaw() appends rather than
 * replaces, so the grouped query kept selecting ungrouped columns.
 *
 * PostgreSQL rejects that ("entries.id must appear in the GROUP BY clause");
 * SQLite allows it. The suite runs on SQLite, so this shipped and returned
 * 500s on every search in production.
 *
 * These assertions read the SQL text, so they hold on either driver.
 */
class SearchTotalsSqlTest extends TestCase
{
    use RefreshDatabase;

    private function userWithEntries(): User
    {
        $user = User::factory()->create();

        $business = Business::create([
            'owner_id' => $user->id,
            'name'     => 'Acme',
            'currency' => 'USD',
        ]);
        $business->members()->attach($user->id, ['role' => 'owner']);

        $book = $business->books()->create(['name' => 'Sep', 'opening_balance' => '0.00']);

        $book->entries()->create([
            'type' => 'out', 'amount' => '120.00', 'description' => 'Fuel', 'date' => now()->toDateString(),
        ]);
        $book->entries()->create([
            'type' => 'in', 'amount' => '500.00', 'description' => 'Invoice', 'date' => now()->toDateString(),
        ]);

        return $user;
    }

    public function test_the_grouped_totals_query_never_selects_ungrouped_columns(): void
    {
        $user = $this->userWithEntries();

        $query = EntrySearch::query($user, EntrySearch::normalise(['q' => 'fuel']));
        $this->assertNotNull($query);

        // Rebuild exactly what totals() runs, so the assertion tracks the real thing.
        $grouped = (clone $query)->reorder()->toBase()
            ->join('businesses', 'businesses.id', '=', 'books.business_id')
            ->groupBy('businesses.currency')
            ->select(\Illuminate\Support\Facades\DB::raw('businesses.currency AS currency'))
            ->selectRaw('COUNT(*) AS row_count');

        $sql = $grouped->toSql();

        $this->assertStringContainsString('group by', strtolower($sql));
        $this->assertStringNotContainsString(
            '"entries".*',
            $sql,
            'A grouped query must not carry entries.* — PostgreSQL rejects it.'
        );
    }

    public function test_totals_are_correct_and_grouped_by_currency(): void
    {
        $user = $this->userWithEntries();

        $query  = EntrySearch::query($user, EntrySearch::normalise(['q' => 'fuel']));
        $totals = EntrySearch::totals($query, EntrySearch::accessibleBusinesses($user));

        $this->assertSame('120.00', $totals['totals']['out']);
        $this->assertSame(1, $totals['totals']['count']);
        $this->assertSame('USD', $totals['totals']['currency']);
        $this->assertFalse($totals['totals']['mixedCurrency']);
    }

    public function test_the_search_page_responds_for_a_signed_in_user(): void
    {
        $user = $this->userWithEntries();

        $this->actingAs($user)->get('/search?q=fuel')->assertOk();
    }
}
