<?php

namespace Tests\Feature;

use App\Livewire\Dashboard;
use App\Models\Book;
use App\Models\User;
use Livewire\Livewire;
use Tests\Feature\Api\ApiTestCase;

/**
 * Dashboard "Continue in" card — book-scoped balance + quick-add target.
 */
class DashboardContinueCardTest extends ApiTestCase
{
    private function dashboard(User $user)
    {
        return Livewire::actingAs($user)->test(Dashboard::class);
    }

    private function currentBookId(User $user): ?string
    {
        return $this->dashboard($user)->viewData('currentBook')['id'] ?? null;
    }

    private function age(Book $book, int $minutesAgo): Book
    {
        $book->timestamps = false;
        $book->created_at = now()->subMinutes($minutesAgo);
        $book->updated_at = now()->subMinutes($minutesAgo);
        $book->save();

        return $book;
    }

    public function test_defaults_to_most_recent_editable_book(): void
    {
        $user     = $this->makeUser();
        $business = $this->makeBusiness($user);
        $old      = $this->age($this->makeBook($business, ['name' => 'Old']), 120);
        $recent   = $this->age($this->makeBook($business, ['name' => 'Recent']), 5);

        $this->assertSame($recent->id, $this->currentBookId($user));

        // A newer entry makes the older book the most recent one.
        $this->makeEntry($old, 'in', '10.00', '2026-09-01');
        $this->assertSame($old->id, $this->currentBookId($user));
    }

    public function test_fallback_prefers_editable_over_more_recent_view_only_book(): void
    {
        $user     = $this->makeUser();
        $own      = $this->makeBusiness($user);
        $mine     = $this->age($this->makeBook($own), 60);

        $other    = $this->makeBusiness($this->makeUser(), ['name' => 'Shared Co']);
        $this->addMember($other, $user, 'viewer');
        $this->age($this->makeBook($other), 1);

        $this->assertSame($mine->id, $this->currentBookId($user));
    }

    public function test_fallback_skips_locked_businesses(): void
    {
        $user   = $this->makeUser(); // Free
        $first  = $this->makeBusiness($user, ['created_at' => now()->subDay()]);
        $locked = $this->makeBusiness($user);
        $open   = $this->age($this->makeBook($first), 60);
        $hidden = $this->age($this->makeBook($locked), 1);

        $component = $this->dashboard($user);
        $this->assertSame($open->id, $component->viewData('currentBook')['id']);
        $this->assertNotContains($hidden->id, $component->viewData('bookChoices')->pluck('id'));

        // A persisted pointer to a (now) locked book is ignored.
        $user->last_book_id = $hidden->id;
        $user->save();
        $this->assertSame($open->id, $this->currentBookId($user->fresh()));
    }

    public function test_last_chosen_book_wins_and_change_persists(): void
    {
        $user     = $this->makeUser();
        $business = $this->makeBusiness($user);
        $older    = $this->age($this->makeBook($business, ['name' => 'Older']), 120);
        $this->age($this->makeBook($business, ['name' => 'Newer']), 1);

        $component = $this->dashboard($user)->call('selectBook', $older->id);

        $this->assertSame($older->id, $user->fresh()->last_book_id);
        $this->assertSame($older->id, $component->viewData('currentBook')['id']);
        $component->assertSeeText('Older · ' . $business->name);

        // Survives a fresh visit.
        $this->assertSame($older->id, $this->currentBookId($user->fresh()));
    }

    public function test_change_rejects_books_the_user_cannot_access(): void
    {
        $user     = $this->makeUser();
        $mine     = $this->makeBook($this->makeBusiness($user));
        $foreign  = $this->makeBook($this->makeBusiness($this->makeUser()));

        $this->dashboard($user)->call('selectBook', $foreign->id);
        $this->assertNull($user->fresh()->last_book_id);

        $locked = $this->makeBook($this->makeBusiness($user, ['created_at' => now()->addDay()]));
        $this->dashboard($user)->call('selectBook', $locked->id);
        $this->assertNull($user->fresh()->last_book_id);

        $this->assertSame($mine->id, $this->currentBookId($user->fresh()));
    }

    public function test_last_book_id_is_not_mass_assignable(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook($this->makeBusiness($user));

        $user->fill(['last_book_id' => $book->id])->save();

        $this->assertNull($user->fresh()->last_book_id);
    }

    public function test_same_book_names_are_disambiguated_by_business(): void
    {
        $user  = $this->makeUser(pro: true);
        $alpha = $this->makeBusiness($user, ['name' => 'Alpha Traders']);
        $beta  = $this->makeBusiness($user, ['name' => 'Beta Studio']);
        $this->age($this->makeBook($alpha, ['name' => 'June 2026']), 10);
        $this->age($this->makeBook($beta, ['name' => 'June 2026']), 5);

        $this->dashboard($user)
            ->assertSeeText('June 2026 · Beta Studio')
            ->assertSee('Adds to <span class="font-medium text-gray-700 dark:text-slate-300">Beta Studio › June 2026</span>', false)
            ->assertSeeInOrder(['Beta Studio', '›', 'June 2026', 'Alpha Traders', '›', 'June 2026'])
            ->assertSee('aria-label="Add cash in to Beta Studio › June 2026"', false);
    }

    public function test_card_shows_book_balance_with_opening_and_negative_emphasis(): void
    {
        $user     = $this->makeUser();
        $business = $this->makeBusiness($user, ['currency' => 'USD']);
        $book     = $this->makeBook($business, ['opening_balance' => 100]);
        $this->makeEntry($book, 'out', '250.00', '2026-09-01');

        $component = $this->dashboard($user);
        $this->assertEqualsWithDelta(-150.0, $component->viewData('currentBook')['net'], 0.001);
        $component->assertSee('below zero')->assertSee('$150.00');
    }

    public function test_viewer_everywhere_sees_balance_but_no_buttons(): void
    {
        $owner    = $this->makeUser();
        $business = $this->makeBusiness($owner, ['name' => 'Owner Co']);
        $book     = $this->makeBook($business, ['name' => 'Ledger']);
        $this->makeEntry($book, 'in', '40.00', '2026-09-01');

        $viewer = $this->makeUser();
        $this->addMember($business, $viewer, 'viewer');

        $this->dashboard($viewer)
            ->assertSeeText('Ledger · Owner Co')
            ->assertSee('data-testid="continue-view-only"', false)
            ->assertDontSee('data-testid="continue-cash-in"', false)
            ->assertDontSee('data-testid="continue-cash-out"', false)
            ->assertDontSee('?addEntry=in', false);
    }

    public function test_editor_sees_buttons_targeting_the_card_book(): void
    {
        $user     = $this->makeUser();
        $business = $this->makeBusiness($user);
        $book     = $this->makeBook($business);

        $url = route('businesses.books.show', [$business, $book]);

        $this->dashboard($user)
            ->assertSee('data-testid="continue-cash-in"', false)
            ->assertSee(e($url . '?addEntry=in'), false)
            ->assertSee(e($url . '?addEntry=out'), false);
    }

    public function test_no_books_shows_create_first_book_cta(): void
    {
        $user     = $this->makeUser();
        $business = $this->makeBusiness($user);

        $this->dashboard($user)
            ->assertViewHas('currentBook', null)
            ->assertSee('data-testid="continue-empty"', false)
            ->assertSee('Create your first book')
            ->assertSee(e(route('businesses.show', $business) . '?createBook=1'), false);
    }

    public function test_no_businesses_shows_onboarding_and_no_card(): void
    {
        $this->dashboard($this->makeUser())
            ->assertDontSee('data-testid="continue-card"', false)
            ->assertSee('Create Your First Business');
    }

    public function test_total_balance_hero_is_gone(): void
    {
        $user = $this->makeUser();
        $this->makeBook($this->makeBusiness($user));

        $this->dashboard($user)->assertDontSee('Total balance');
    }

    public function test_totals_are_never_summed_across_currencies(): void
    {
        $user = $this->makeUser(pro: true);
        $usd  = $this->makeBusiness($user, ['currency' => 'USD', 'name' => 'Dollar Co']);
        $pkr  = $this->makeBusiness($user, ['currency' => 'PKR', 'name' => 'Rupee Co']);
        $this->makeEntry($this->makeBook($usd), 'in', '100.00', '2026-09-01');
        $this->makeEntry($this->makeBook($pkr), 'in', '5000.00', '2026-09-01');

        $component = $this->dashboard($user);
        $totals    = $component->viewData('totals')->keyBy('currency');

        $this->assertCount(2, $totals);
        $this->assertEqualsWithDelta(100.0, $totals['USD']['balance'], 0.001);
        $this->assertEqualsWithDelta(5000.0, $totals['PKR']['balance'], 0.001);

        $component->assertSee('Across all businesses, by currency:')
            ->assertSee('$100.00')
            ->assertSee('5,000.00')
            ->assertDontSee('5,100.00');
    }

    public function test_single_currency_shows_one_combined_line(): void
    {
        $user = $this->makeUser(pro: true);
        $a    = $this->makeBusiness($user, ['currency' => 'USD']);
        $b    = $this->makeBusiness($user, ['currency' => 'USD']);
        $this->makeEntry($this->makeBook($a), 'in', '100.00', '2026-09-01');
        $this->makeEntry($this->makeBook($b), 'out', '30.00', '2026-09-01');

        $this->dashboard($user)
            ->assertSee('Across all businesses:')
            ->assertSee('$70.00');
    }

    public function test_single_business_has_no_across_all_line(): void
    {
        $user = $this->makeUser();
        $this->makeEntry($this->makeBook($this->makeBusiness($user)), 'in', '10.00', '2026-09-01');

        $this->dashboard($user)->assertDontSee('data-testid="across-all"', false);
    }
}
