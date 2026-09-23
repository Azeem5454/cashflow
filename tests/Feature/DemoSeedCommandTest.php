<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Business;
use App\Models\Entry;
use App\Models\RecurringEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * The reviewer account is what App Store / Play reviewers log into, so the
 * seeder has to stay working — a broken demo login is an automatic rejection.
 */
class DemoSeedCommandTest extends TestCase
{
    use RefreshDatabase;

    private function seedDemo(string $password = 'FoxDemo!Test2026'): void
    {
        $this->artisan('demo:seed', ['--password' => $password])->assertSuccessful();
    }

    public function test_it_creates_a_pro_owner_with_a_working_password(): void
    {
        $this->seedDemo();

        $owner = User::where('email', 'demo-reviewer@thecashfox.com')->firstOrFail();

        $this->assertTrue(Hash::check('FoxDemo!Test2026', $owner->password));
        $this->assertTrue($owner->isPro(), 'Reviewers must see Pro features without paying.');
        $this->assertNotNull($owner->email_verified_at, 'A verification wall would block the reviewer.');
    }

    public function test_it_builds_two_businesses_with_books_team_and_a_pending_invite(): void
    {
        $this->seedDemo();

        $studio = Business::where('name', 'Brightside Studio')->firstOrFail();

        $this->assertSame(3, $studio->members()->count());
        $this->assertSame(1, $studio->pendingInvitations()->count());
        $this->assertSame(2, $studio->books()->count());
        $this->assertSame(1, Business::where('name', 'Harbor Street Café')->firstOrFail()->books()->count());
    }

    public function test_the_hero_book_carries_the_previous_closing_balance_forward(): void
    {
        $this->seedDemo();

        $books = Book::whereHas('business', fn ($q) => $q->where('name', 'Brightside Studio'))
            ->orderBy('period_starts_at')
            ->get();

        $this->assertSame($books[0]->closingBalance(), (string) $books[1]->opening_balance);
    }

    public function test_it_never_seeds_a_transaction_dated_in_the_future(): void
    {
        $this->seedDemo();

        $this->assertSame(0, Entry::whereDate('date', '>', now())->count());
    }

    public function test_it_seeds_gated_features_reviewers_need_to_see(): void
    {
        $this->seedDemo();

        $hero = Book::whereHas('business', fn ($q) => $q->where('name', 'Brightside Studio'))
            ->orderByDesc('period_starts_at')
            ->firstOrFail();

        $this->assertSame(1, RecurringEntry::where('book_id', $hero->id)->where('status', 'active')->count());
        $this->assertSame(1, RecurringEntry::where('book_id', $hero->id)->where('status', 'paused')->count());
        $this->assertNotNull($hero->reportSchedule);

        // The cleaning entries already on the ledger belong to the active rule,
        // so the recurring marker shows on those rows.
        $this->assertGreaterThan(0, $hero->entries()->whereNotNull('recurring_entry_id')->count());

        // Two people in the ledger + a comment thread.
        $editor = User::where('email', 'maya.demo@thecashfox.com')->firstOrFail();
        $this->assertGreaterThan(0, Entry::where('created_by', $editor->id)->count());
        $this->assertGreaterThanOrEqual(2, \App\Models\EntryComment::count());
    }

    public function test_it_refuses_to_run_twice_without_fresh(): void
    {
        $this->seedDemo();

        $this->artisan('demo:seed')->assertFailed();
        $this->artisan('demo:seed', ['--fresh' => true])->assertSuccessful();

        $this->assertSame(1, User::where('email', 'demo-reviewer@thecashfox.com')->count());
    }
}
