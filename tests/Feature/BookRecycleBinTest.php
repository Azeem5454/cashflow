<?php

namespace Tests\Feature;

use App\Livewire\Book\Show as BookShow;
use App\Livewire\Business\Show as BusinessShow;
use App\Livewire\Dashboard;
use App\Models\Book;
use App\Models\Business;
use App\Models\Entry;
use App\Models\RecurringEntry;
use App\Models\ReportSchedule;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Feature\Api\ApiTestCase;

/**
 * 30-day recycle bin for BOOKS.
 *
 * Deleting a book soft-deletes it: it vanishes from every list, summary,
 * search, export, report and API endpoint, while its entries stay untouched so
 * a restore brings the ledger back exactly as it was. Permanent deletion (by
 * the owner, or by `books:purge-deleted` after 30 days) is what actually
 * removes the rows and the attachment files.
 */
class BookRecycleBinTest extends ApiTestCase
{
    private function binBook(Business $business, Book $book, User $owner): void
    {
        Livewire::actingAs($owner)
            ->test(BusinessShow::class, ['business' => $business])
            ->call('openDeleteBook', $book->id)
            ->set('deleteConfirmName', $book->name)
            ->call('deleteBook')
            ->assertHasNoErrors();
    }

    // ── Soft delete ──────────────────────────────────────────────────────

    public function test_deleting_a_book_bins_it_and_keeps_its_entries(): void
    {
        $owner    = $this->makeUser();
        $business = $this->makeBusiness($owner);
        $book     = $this->makeBook($business, ['name' => 'March', 'opening_balance' => '100.00']);
        $this->makeEntry($book, 'in',  '250.00', '2026-03-01');
        $this->makeEntry($book, 'out', '50.00',  '2026-03-02');

        $this->binBook($business, $book, $owner);

        $this->assertSoftDeleted('books', ['id' => $book->id]);
        $this->assertNull(Book::find($book->id));
        $this->assertNotNull(Book::withTrashed()->find($book->id));
        // Entries, categories and everything else stay exactly where they are.
        $this->assertSame(2, Entry::where('book_id', $book->id)->count());
    }

    public function test_the_ledger_page_delete_also_only_bins_the_book(): void
    {
        $owner    = $this->makeUser();
        $business = $this->makeBusiness($owner);
        $book     = $this->makeBook($business, ['name' => 'April']);
        $this->makeEntry($book, 'in', '10.00', '2026-04-01');

        Livewire::actingAs($owner)
            ->test(BookShow::class, ['business' => $business, 'book' => $book])
            ->set('deleteConfirmName', 'April')
            ->call('deleteBook')
            ->assertRedirect(route('businesses.show', $business->id));

        $this->assertSoftDeleted('books', ['id' => $book->id]);
        $this->assertSame(1, Entry::where('book_id', $book->id)->count());
    }

    // ── Hidden everywhere ────────────────────────────────────────────────

    public function test_a_binned_book_is_gone_from_every_listing_and_summary(): void
    {
        $owner    = $this->makeUser(pro: true);
        $business = $this->makeBusiness($owner);

        $kept   = $this->makeBook($business, ['name' => 'Kept',   'opening_balance' => '100.00']);
        $binned = $this->makeBook($business, ['name' => 'Binned', 'opening_balance' => '500.00']);
        $this->makeEntry($kept,   'in', '20.00',  now()->toDateString());
        $this->makeEntry($binned, 'in', '900.00', now()->toDateString(), ['description' => 'Needle payment']);

        $this->binBook($business, $binned, $owner);

        // Business page book list
        $listed = Livewire::actingAs($owner)
            ->test(BusinessShow::class, ['business' => $business])
            ->viewData('books');
        $this->assertSame(['Kept'], $listed->pluck('name')->all());

        // Business aggregates: netBalances + trends
        $this->assertSame('120.00', Business::netBalances([$business->id])[$business->id]);
        $trend = Business::trends([$business->id])[$business->id]['trend'];
        $this->assertSame(20.0, array_sum($trend));

        // Relationship counts / hasManyThrough
        $this->assertSame(1, $business->books()->count());
        $this->assertSame(1, $business->entries()->count());

        // Dashboard totals + book picker
        $dashboard = Livewire::actingAs($owner)->test(Dashboard::class);
        $this->assertSame(120.0, $dashboard->viewData('totals')[0]['balance']);
        $this->assertNotContains($binned->id, collect($dashboard->viewData('bookChoices'))->pluck('id')->all());

        // Global search
        $this->actingAsUser($owner);
        $this->getJson('/api/v1/search?q=Needle')->assertOk()->assertJsonCount(0, 'data');

        // API: the book itself and everything hanging off it
        $this->getJson("/api/v1/books/{$binned->id}")->assertNotFound();
        $this->getJson("/api/v1/books/{$binned->id}/entries")->assertNotFound();
        $this->getJson("/api/v1/books/{$binned->id}/summary")->assertNotFound();
        $this->getJson("/api/v1/books/{$binned->id}/activity")->assertNotFound();
        $this->putJson("/api/v1/books/{$binned->id}", ['name' => 'Nope'])->assertNotFound();
        $this->deleteJson("/api/v1/books/{$binned->id}")->assertNotFound();

        $this->getJson("/api/v1/businesses/{$business->id}/books")
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Kept');

        $this->getJson('/api/v1/books/recent')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Kept');

        // Web routes: ledger page + exports 404 on a binned book
        $this->actingAs($owner);
        $this->get(route('businesses.books.show', [$business->id, $binned->id]))->assertNotFound();
        $this->get(route('businesses.books.export.csv', [$business->id, $binned->id]))->assertNotFound();
        $this->get(route('businesses.books.export.pdf', [$business->id, $binned->id]))->assertNotFound();
    }

    public function test_an_entry_in_a_binned_book_is_not_reachable_through_the_api(): void
    {
        $owner    = $this->makeUser();
        $business = $this->makeBusiness($owner);
        $book     = $this->makeBook($business);
        $entry    = $this->makeEntry($book, 'in', '10.00', '2026-03-01');

        $this->binBook($business, $book, $owner);

        $this->actingAsUser($owner);
        $this->putJson("/api/v1/entries/{$entry->id}", ['amount' => 20])->assertNotFound();
        $this->deleteJson("/api/v1/entries/{$entry->id}")->assertNotFound();
        $this->getJson("/api/v1/entries/{$entry->id}/comments")->assertNotFound();
    }

    public function test_the_continue_in_card_falls_back_when_its_book_is_binned(): void
    {
        $owner    = $this->makeUser();
        $business = $this->makeBusiness($owner);
        $keep     = $this->makeBook($business, ['name' => 'Keep']);
        $pinned   = $this->makeBook($business, ['name' => 'Pinned']);

        Livewire::actingAs($owner)->test(Dashboard::class)->call('selectBook', $pinned->id);
        $this->assertSame($pinned->id, $owner->fresh()->last_book_id);

        $this->binBook($business, $pinned, $owner);

        $current = Livewire::actingAs($owner)->test(Dashboard::class)->viewData('currentBook');
        $this->assertSame($keep->id, $current['id']);
    }

    // ── Restore ──────────────────────────────────────────────────────────

    public function test_restoring_brings_the_book_and_its_balances_back(): void
    {
        $owner    = $this->makeUser();
        $business = $this->makeBusiness($owner);
        $book     = $this->makeBook($business, ['name' => 'March', 'opening_balance' => '100.00']);
        $this->makeEntry($book, 'in',  '250.00', '2026-03-01');
        $this->makeEntry($book, 'out', '50.00',  '2026-03-02');

        $this->binBook($business, $book, $owner);
        $this->assertSame('0.00', Business::netBalances([$business->id])[$business->id]);

        Livewire::actingAs($owner)
            ->test(BusinessShow::class, ['business' => $business])
            ->assertSee('Recently deleted')
            ->call('toggleBin')
            ->assertSee('March')
            ->assertSee('Restore')
            ->assertSee('Delete permanently')
            ->call('restoreBook', $book->id)
            ->assertHasNoErrors();

        $restored = Book::find($book->id);
        $this->assertNotNull($restored);
        $this->assertSame('300.00', $restored->balance());
        $this->assertSame('300.00', Business::netBalances([$business->id])[$business->id]);
        $this->assertSame(2, $restored->entries()->count());
    }

    public function test_deleted_books_list_restore_and_force_delete_over_the_api(): void
    {
        $owner    = $this->makeUser();
        $business = $this->makeBusiness($owner);
        $book     = $this->makeBook($business, ['name' => 'Binned']);
        $this->makeEntry($book, 'in', '10.00', '2026-03-01');

        $this->actingAsUser($owner);
        $this->deleteJson("/api/v1/books/{$book->id}")
            ->assertOk()
            ->assertJsonPath('restorable', true)
            ->assertJsonPath('binDays', Book::BIN_DAYS);

        $this->getJson("/api/v1/businesses/{$business->id}/books/deleted")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $book->id)
            ->assertJsonPath('data.0.entriesCount', 1)
            ->assertJsonPath('data.0.binDaysLeft', Book::BIN_DAYS);

        $this->postJson("/api/v1/books/{$book->id}/restore")->assertOk();
        $this->assertNotNull(Book::find($book->id));
        $this->getJson("/api/v1/businesses/{$business->id}/books/deleted")->assertOk()->assertJsonCount(0, 'data');

        // Restoring something that isn't in the bin is a 404.
        $this->postJson("/api/v1/books/{$book->id}/restore")->assertNotFound();

        $this->deleteJson("/api/v1/books/{$book->id}")->assertOk();
        $this->deleteJson("/api/v1/books/{$book->id}/force")->assertOk();
        $this->assertDatabaseMissing('books', ['id' => $book->id]);
        $this->assertSame(0, Entry::where('book_id', $book->id)->count());
    }

    // ── Permanent delete ─────────────────────────────────────────────────

    public function test_force_delete_removes_the_book_entries_and_attachment_files(): void
    {
        Storage::fake('local');

        $owner    = $this->makeUser();
        $business = $this->makeBusiness($owner);
        $book     = $this->makeBook($business, ['name' => 'Gone']);

        $path = "attachments/{$business->id}/{$book->id}/receipt.png";
        Storage::disk('local')->put($path, 'png-bytes');
        $this->makeEntry($book, 'out', '15.00', '2026-03-01', ['attachment_path' => $path]);
        $book->categories()->create(['name' => 'Supplies']);
        ReportSchedule::create([
            'book_id'    => $book->id,
            'frequency'  => 'weekly',
            'recipients' => [$owner->email],
            'is_active'  => true,
        ]);

        $this->binBook($business, $book, $owner);
        Storage::disk('local')->assertExists($path);

        Livewire::actingAs($owner)
            ->test(BusinessShow::class, ['business' => $business])
            ->call('openPurgeBook', $book->id)
            ->set('purgeConfirmName', 'Gone')
            ->call('purgeBook')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('books', ['id' => $book->id]);
        $this->assertSame(0, Entry::where('book_id', $book->id)->count());
        $this->assertDatabaseMissing('report_schedules', ['book_id' => $book->id]);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_permanent_delete_requires_the_exact_book_name(): void
    {
        $owner    = $this->makeUser();
        $business = $this->makeBusiness($owner);
        $book     = $this->makeBook($business, ['name' => 'Careful']);

        $this->binBook($business, $book, $owner);

        Livewire::actingAs($owner)
            ->test(BusinessShow::class, ['business' => $business])
            ->call('openPurgeBook', $book->id)
            ->set('purgeConfirmName', 'careful')
            ->call('purgeBook')
            ->assertHasErrors('purgeConfirmName');

        $this->assertNotNull(Book::withTrashed()->find($book->id));
    }

    // ── Owner-only rules ─────────────────────────────────────────────────

    public function test_only_the_owner_can_delete_restore_or_purge(): void
    {
        $owner    = $this->makeUser();
        $business = $this->makeBusiness($owner);
        $book     = $this->makeBook($business, ['name' => 'Ledger']);

        $editor = $this->makeUser();
        $this->addMember($business, $editor, 'editor');
        $outsider = $this->makeUser();

        // Editor cannot delete.
        $this->actingAsUser($editor);
        $this->deleteJson("/api/v1/books/{$book->id}")->assertForbidden();

        $this->binBook($business, $book, $owner);

        // Editor: 403 on the bin listing, restore and force delete.
        $this->actingAsUser($editor);
        $this->getJson("/api/v1/businesses/{$business->id}/books/deleted")->assertForbidden();
        $this->postJson("/api/v1/books/{$book->id}/restore")->assertForbidden();
        $this->deleteJson("/api/v1/books/{$book->id}/force")->assertForbidden();

        // Non-member: 404 everywhere (existence isn't leaked).
        $this->actingAsUser($outsider);
        $this->getJson("/api/v1/businesses/{$business->id}/books/deleted")->assertNotFound();
        $this->postJson("/api/v1/books/{$book->id}/restore")->assertNotFound();
        $this->deleteJson("/api/v1/books/{$book->id}/force")->assertNotFound();

        // Editor's Livewire calls are blocked too, and the bin stays hidden.
        Livewire::actingAs($editor)
            ->test(BusinessShow::class, ['business' => $business])
            ->assertViewHas('deletedBooks', fn ($books) => $books->isEmpty())
            ->call('restoreBook', $book->id)
            ->assertForbidden();

        $this->assertNotNull(Book::withTrashed()->find($book->id));
    }

    public function test_a_locked_free_business_cannot_use_the_bin(): void
    {
        $owner = $this->makeUser();
        $first = $this->makeBusiness($owner);            // the one Free business
        $extra = $this->makeBusiness($owner);            // locked on Free
        $book  = $this->makeBook($extra, ['name' => 'Locked']);
        $book->delete();

        $this->actingAsUser($owner);
        $this->getJson("/api/v1/businesses/{$extra->id}/books/deleted")
            ->assertForbidden()->assertJsonPath('code', 'business_locked');
        $this->postJson("/api/v1/books/{$book->id}/restore")
            ->assertForbidden()->assertJsonPath('code', 'business_locked');

        // The unlocked business is unaffected.
        $this->getJson("/api/v1/businesses/{$first->id}/books/deleted")->assertOk();
    }

    // ── Purge command ────────────────────────────────────────────────────

    public function test_the_purge_command_respects_the_thirty_day_cut_off(): void
    {
        $owner    = $this->makeUser();
        $business = $this->makeBusiness($owner);

        $fresh = $this->makeBook($business, ['name' => 'Fresh']);
        $stale = $this->makeBook($business, ['name' => 'Stale']);
        $this->makeEntry($stale, 'in', '10.00', '2026-03-01');

        $fresh->delete();
        $stale->delete();
        Book::withTrashed()->whereKey($stale->id)
            ->update(['deleted_at' => now()->subDays(Book::BIN_DAYS + 1)]);

        $this->artisan('books:purge-deleted')->assertSuccessful();

        $this->assertDatabaseMissing('books', ['id' => $stale->id]);
        $this->assertSame(0, Entry::where('book_id', $stale->id)->count());
        $this->assertNotNull(Book::withTrashed()->find($fresh->id));

        // Idempotent: a second run is a no-op and still succeeds.
        $this->artisan('books:purge-deleted')->assertSuccessful();
        $this->assertNotNull(Book::withTrashed()->find($fresh->id));

        // A live (never deleted) book is never touched, whatever the window.
        $live = $this->makeBook($business, ['name' => 'Live']);
        $this->artisan('books:purge-deleted', ['--days' => 0])->assertSuccessful();
        $this->assertNotNull(Book::find($live->id));
        $this->assertDatabaseMissing('books', ['id' => $fresh->id]);
    }

    public function test_a_book_past_the_window_is_no_longer_offered_as_restorable(): void
    {
        $owner    = $this->makeUser();
        $business = $this->makeBusiness($owner);
        $book     = $this->makeBook($business, ['name' => 'Expired']);
        $book->delete();
        Book::withTrashed()->whereKey($book->id)
            ->update(['deleted_at' => now()->subDays(Book::BIN_DAYS + 2)]);

        Livewire::actingAs($owner)
            ->test(BusinessShow::class, ['business' => $business])
            ->assertViewHas('deletedBooks', fn ($books) => $books->isEmpty());

        $this->actingAsUser($owner);
        $this->getJson("/api/v1/businesses/{$business->id}/books/deleted")
            ->assertOk()->assertJsonCount(0, 'data');
    }

    // ── Scheduled work ───────────────────────────────────────────────────

    public function test_the_recurring_cron_skips_binned_books(): void
    {
        $owner    = $this->makeUser(pro: true);
        $business = $this->makeBusiness($owner);
        $book     = $this->makeBook($business, ['name' => 'Recurring']);

        $rule = RecurringEntry::create([
            'book_id'     => $book->id,
            'type'        => 'out',
            'amount'      => '99.00',
            'description' => 'Rent',
            'frequency'   => 'weekly',
            'starts_at'   => now()->subDay()->toDateString(),
            'next_run_at' => now()->subDay()->toDateString(),
            'status'      => 'active',
        ]);

        $book->delete();

        $this->artisan('entries:generate-recurring')->assertSuccessful();

        $this->assertSame(0, Entry::where('book_id', $book->id)->count());
        // next_run_at untouched, so restoring resumes the schedule as it was.
        $this->assertSame(
            $rule->next_run_at->toDateString(),
            $rule->fresh()->next_run_at->toDateString()
        );
    }

    public function test_the_email_report_schedule_is_skipped_while_the_book_is_binned(): void
    {
        Mail::fake();

        $owner    = $this->makeUser(pro: true);
        $business = $this->makeBusiness($owner);
        $book     = $this->makeBook($business, ['name' => 'Reported']);
        $this->makeEntry($book, 'in', '10.00', now()->toDateString());

        $schedule = ReportSchedule::create([
            'book_id'      => $book->id,
            'frequency'    => 'weekly',
            'recipients'   => [$owner->email],
            'is_active'    => true,
            'last_sent_at' => null,
        ]);

        $book->delete();

        $this->artisan('reports:send')->assertSuccessful();

        Mail::assertNothingQueued();
        $this->assertNull($schedule->fresh()->last_sent_at);
        // The schedule survives the bin so a restore resumes it.
        $this->assertDatabaseHas('report_schedules', ['id' => $schedule->id, 'is_active' => true]);
    }

    // ── Admin ────────────────────────────────────────────────────────────

    public function test_admin_business_counts_exclude_binned_books(): void
    {
        $admin = $this->makeUser();
        $admin->is_admin = true;
        $admin->save();

        $owner    = $this->makeUser();
        $business = $this->makeBusiness($owner);
        $kept     = $this->makeBook($business, ['name' => 'Kept']);
        $binned   = $this->makeBook($business, ['name' => 'Binned']);
        $this->makeEntry($kept,   'in', '1.00', '2026-03-01');
        $this->makeEntry($binned, 'in', '2.00', '2026-03-01');

        $binned->delete();

        $row = Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\Businesses::class)
            ->viewData('businesses')
            ->first();

        $this->assertSame(1, $row->books_count);
        $this->assertSame(1, $row->books_in_bin_count);
        $this->assertSame(1, $row->entries_count);
    }
}
