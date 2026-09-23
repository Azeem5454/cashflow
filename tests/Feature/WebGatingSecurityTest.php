<?php

namespace Tests\Feature;

use App\Livewire\Book\Show as BookShow;
use App\Livewire\Business\Settings as BusinessSettings;
use App\Livewire\Business\Show as BusinessShow;
use App\Livewire\Dashboard;
use App\Models\AiUsageLog;
use App\Models\Book;
use App\Models\Business;
use App\Models\EntryComment;
use App\Models\RecurringEntry;
use App\Models\User;
use App\Services\AiQuota;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Tests\Feature\Api\ApiTestCase;

/**
 * Server-side plan & role gates on the web (Livewire) — the client can't be
 * trusted to hide buttons, so every mutating action re-checks the DB role,
 * the Free-plan business lock and the business plan.
 */
class WebGatingSecurityTest extends ApiTestCase
{
    /** @return array{0: User, 1: Business, 2: Book} */
    private function proBusinessWithBook(): array
    {
        $owner    = $this->makeUser(pro: true);
        $business = $this->makeBusiness($owner);
        $book     = $this->makeBook($business);

        return [$owner, $business, $book];
    }

    private function member(Business $business, string $role): User
    {
        $user = $this->makeUser();
        $this->addMember($business, $user, $role);

        return $user;
    }

    private function bookShow(User $user, Business $business, Book $book)
    {
        return Livewire::actingAs($user)->test(BookShow::class, ['business' => $business, 'book' => $book]);
    }

    private function makeRule(Book $book, array $attrs = []): RecurringEntry
    {
        return $book->recurringEntries()->create(array_merge([
            'type'        => 'out',
            'amount'      => '15.00',
            'description' => 'Hosting',
            'frequency'   => 'weekly',
            'starts_at'   => '2026-03-01',
            'next_run_at' => '2026-03-08',
            'status'      => 'active',
        ], $attrs));
    }

    // ── 1. Locked role property ─────────────────────────────────

    public function test_book_show_user_role_cannot_be_tampered(): void
    {
        [, $business, $book] = $this->proBusinessWithBook();
        $viewer = $this->member($business, 'viewer');

        $this->expectException(CannotUpdateLockedPropertyException::class);

        $this->bookShow($viewer, $business, $book)->set('userRole', 'editor');
    }

    public function test_business_show_user_role_cannot_be_tampered(): void
    {
        [, $business] = $this->proBusinessWithBook();
        $viewer = $this->member($business, 'viewer');

        $this->expectException(CannotUpdateLockedPropertyException::class);

        Livewire::actingAs($viewer)->test(BusinessShow::class, ['business' => $business])
            ->set('userRole', 'owner');
    }

    public function test_viewer_mutating_book_actions_are_forbidden_and_change_nothing(): void
    {
        [, $business, $book] = $this->proBusinessWithBook();
        $other  = $this->makeBook($business, ['name' => 'Other']);
        $entry  = $this->makeEntry($book, 'in', '10.00', '2026-09-01');
        $rule   = $this->makeRule($book);
        $viewer = $this->member($business, 'viewer');

        $entryForm = fn ($t) => $t->set('entryType', 'in')->set('entryAmount', '5')
            ->set('entryDescription', 'Sneaky')->set('entryDate', '2026-09-02');

        $calls = [
            'saveEntry'             => fn ($t) => $entryForm($t)->call('saveEntry'),
            'saveAndAddNew'         => fn ($t) => $entryForm($t)->call('saveAndAddNew'),
            'addCategory'           => fn ($t) => $t->set('newCategoryName', 'Hack')->call('addCategory'),
            'addPaymentMode'        => fn ($t) => $t->set('newPaymentModeName', 'Hack')->call('addPaymentMode'),
            'openBulkBookPicker'    => fn ($t) => $t->call('openBulkBookPicker', 'move'),
            'executeBulkBookAction' => fn ($t) => $t->set('bulkAction', 'move')->set('bulkTargetBookId', $other->id)
                ->call('executeBulkBookAction', [$entry->id]),
            'bulkDelete'            => fn ($t) => $t->call('bulkDelete', [$entry->id]),
            'bulkChangeCategory'    => fn ($t) => $t->set('bulkNewCategory', 'X')->call('bulkChangeCategory', [$entry->id]),
            'confirmDeleteEntry'    => fn ($t) => $t->call('confirmDeleteEntry', $entry->id),
            'deleteEntry'           => fn ($t) => $t->set('pendingDeleteEntryId', $entry->id)->call('deleteEntry'),
            'saveEditBook'          => fn ($t) => $t->set('editBookName', 'Renamed')->call('saveEditBook'),
            'executeDuplicate'      => fn ($t) => $t->set('duplicateBookName', 'Dup')->call('executeDuplicate'),
            'deleteBook'            => fn ($t) => $t->set('deleteConfirmName', $book->name)->call('deleteBook'),
            'applyAiCategory'       => fn ($t) => $t->set('aiCategorySuggestion', 'Hack')->call('applyAiCategory'),
            'parseEntryText'        => fn ($t) => $t->set('nlpInput', 'Paid 5000 rent')->call('parseEntryText'),
            'toggleRecurringStatus' => fn ($t) => $t->call('toggleRecurringStatus', $rule->id),
            'deleteRecurring'       => fn ($t) => $t->call('deleteRecurring', $rule->id),
            'prepareScan'           => fn ($t) => $t->call('prepareScan'),
        ];

        foreach ($calls as $name => $call) {
            $call($this->bookShow($viewer, $business, $book))->assertForbidden();
        }

        $this->assertSame(1, $book->entries()->count(), 'no entries added/removed');
        $this->assertSame($book->id, $entry->fresh()->book_id, 'entry not moved');
        $this->assertNull($entry->fresh()->category);
        $this->assertSame(0, $book->categories()->count());
        $this->assertSame(0, $book->paymentModes()->count());
        $this->assertSame(0, $other->entries()->count());
        $this->assertNotSame('Renamed', $book->fresh()->name);
        $this->assertSame(2, $business->books()->count(), 'no book duplicated or deleted');
        $this->assertSame('active', $rule->fresh()->status);
        Http::assertNothingSent();
    }

    public function test_viewer_cannot_bulk_copy_to_another_book(): void
    {
        [, $business, $book] = $this->proBusinessWithBook();
        $other  = $this->makeBook($business);
        $entry  = $this->makeEntry($book, 'in', '10.00', '2026-09-01');
        $viewer = $this->member($business, 'viewer');

        foreach (['copy', 'copy_opposite'] as $action) {
            $this->bookShow($viewer, $business, $book)
                ->set('bulkAction', $action)
                ->set('bulkTargetBookId', $other->id)
                ->call('executeBulkBookAction', [$entry->id])
                ->assertForbidden();
        }

        $this->assertSame(0, $other->entries()->count());
    }

    public function test_editor_bulk_move_stays_within_the_business(): void
    {
        [, $business, $book] = $this->proBusinessWithBook();
        $entry  = $this->makeEntry($book, 'in', '10.00', '2026-09-01');
        $editor = $this->member($business, 'editor');

        // Target book in a business the editor also belongs to, but a different one.
        $foreignOwner = $this->makeUser(pro: true);
        $foreignBiz   = $this->makeBusiness($foreignOwner);
        $this->addMember($foreignBiz, $editor, 'editor');
        $foreignBook  = $this->makeBook($foreignBiz);

        $this->bookShow($editor, $business, $book)
            ->set('bulkAction', 'move')
            ->set('bulkTargetBookId', $foreignBook->id)
            ->call('executeBulkBookAction', [$entry->id]);

        $this->assertSame($book->id, $entry->fresh()->book_id);

        $sameBizBook = $this->makeBook($business);
        $this->bookShow($editor, $business, $book)
            ->set('bulkAction', 'move')
            ->set('bulkTargetBookId', $sameBizBook->id)
            ->call('executeBulkBookAction', [$entry->id]);

        $this->assertSame($sameBizBook->id, $entry->fresh()->book_id);
    }

    public function test_viewer_business_show_actions_are_forbidden(): void
    {
        [, $business, $book] = $this->proBusinessWithBook();
        $viewer = $this->member($business, 'viewer');

        $calls = [
            fn ($t) => $t->set('bookName', 'New')->call('createBook'),
            fn ($t) => $t->set('editingBookId', $book->id)->set('editBookName', 'Renamed')->call('saveEditBook'),
            fn ($t) => $t->set('duplicatingBookId', $book->id)->set('duplicateBookName', 'Dup')->call('executeDuplicate'),
            fn ($t) => $t->set('deletingBookId', $book->id)->set('deletingBookName', $book->name)
                ->set('deleteConfirmName', $book->name)->call('deleteBook'),
            fn ($t) => $t->call('renameBook', $book->id, 'Renamed'),
        ];

        foreach ($calls as $call) {
            $call(Livewire::actingAs($viewer)->test(BusinessShow::class, ['business' => $business]))->assertForbidden();
        }

        // Opening modals is a silent no-op for viewers.
        Livewire::actingAs($viewer)->test(BusinessShow::class, ['business' => $business])
            ->call('openCreateBook')->assertSet('showCreateBook', false);

        $this->assertSame(1, $business->books()->count());
        $this->assertNotSame('Renamed', $book->fresh()->name);
    }

    // ── 11. Book delete = owner only ────────────────────────────

    public function test_editor_cannot_delete_a_book_on_the_web(): void
    {
        [$owner, $business, $book] = $this->proBusinessWithBook();
        $editor = $this->member($business, 'editor');

        $this->bookShow($editor, $business, $book)
            ->call('openDeleteBook')->assertSet('showDeleteBook', false)
            ->set('deleteConfirmName', $book->name)
            ->call('deleteBook')
            ->assertForbidden();

        Livewire::actingAs($editor)->test(BusinessShow::class, ['business' => $business])
            ->set('deletingBookId', $book->id)
            ->set('deletingBookName', $book->name)
            ->set('deleteConfirmName', $book->name)
            ->call('deleteBook')
            ->assertForbidden();

        $this->assertNotNull($book->fresh());

        // Editors keep edit + duplicate.
        Livewire::actingAs($editor)->test(BusinessShow::class, ['business' => $business])
            ->call('openDuplicateBook', $book->id)
            ->call('executeDuplicate')
            ->assertHasNoErrors();
        $this->assertSame(2, $business->books()->count());

        // Owner can delete.
        $this->bookShow($owner, $business, $book)
            ->set('deleteConfirmName', $book->name)
            ->call('deleteBook')
            ->assertRedirect(route('businesses.show', $business->id));
        // Deleting now bins the book for 30 days rather than erasing it.
        $this->assertSoftDeleted('books', ['id' => $book->id]);
        $this->assertNull(\App\Models\Book::find($book->id));
    }

    public function test_duplicate_book_keeps_opening_balance_on_the_web(): void
    {
        [$owner, $business] = $this->proBusinessWithBook();
        $source = $this->makeBook($business, ['opening_balance' => '250.00']);

        $this->bookShow($owner, $business, $source)
            ->call('openDuplicateBook')
            ->call('executeDuplicate');

        Livewire::actingAs($owner)->test(BusinessShow::class, ['business' => $business])
            ->call('openDuplicateBook', $source->id)
            ->call('executeDuplicate');

        $copies = $business->books()->where('id', '!=', $source->id)->where('name', 'like', '%(Copy)')->get();
        $this->assertCount(2, $copies);
        foreach ($copies as $copy) {
            $this->assertSame('250.00', (string) $copy->opening_balance);
        }
    }

    // ── Locked (Free extra) business ────────────────────────────

    public function test_locked_business_book_actions_are_forbidden_over_livewire(): void
    {
        $owner  = $this->makeUser();
        $this->makeBusiness($owner, ['created_at' => now()->subDay()]);
        $locked = $this->makeBusiness($owner);
        $book   = $this->makeBook($locked);

        $this->bookShow($owner, $locked, $book)
            ->set('newCategoryName', 'X')
            ->call('addCategory')
            ->assertForbidden();

        $this->assertSame(0, $book->categories()->count());
    }

    // ── 3. OCR is quota-gated (Free: 10 AI entries/month) ───────

    public function test_free_ocr_upload_is_rejected_without_calling_ai_once_quota_is_used(): void
    {
        Http::fake();

        $owner    = $this->makeUser();
        $business = $this->makeBusiness($owner);
        $book     = $this->makeBook($business);
        for ($i = 0; $i < \App\Services\AiQuota::FREE_MONTHLY_LIMIT; $i++) {
            AiUsageLog::create(['user_id' => $owner->id, 'type' => $i % 2 ? 'ocr' : 'nlp', 'tokens_in' => 1, 'tokens_out' => 1, 'cost_usd' => 0, 'created_at' => now()]);
        }

        $this->bookShow($owner, $business, $book)
            ->set('ocrFile', UploadedFile::fake()->image('receipt.jpg'))
            ->assertSet('upgradeModalFeature', 'ai')
            ->assertSet('ocrFile', null)
            ->assertSet('aiFilledFields', []);

        Http::assertNothingSent();
        $this->assertSame(AiQuota::FREE_MONTHLY_LIMIT, AiUsageLog::count());
    }

    public function test_book_page_renders_the_ocr_input_until_free_quota_is_used(): void
    {
        $owner    = $this->makeUser();
        $business = $this->makeBusiness($owner);
        $book     = $this->makeBook($business);

        $this->bookShow($owner, $business, $book)
            ->call('openAddEntry')
            ->assertSeeHtml('wire:model="ocrFile"')
            ->assertSee('free AI entries left this month');

        for ($i = 0; $i < AiQuota::FREE_MONTHLY_LIMIT; $i++) {
            AiUsageLog::create(['user_id' => $owner->id, 'type' => 'ocr', 'tokens_in' => 1, 'tokens_out' => 1, 'cost_usd' => 0, 'created_at' => now()]);
        }
        $this->bookShow($owner, $business, $book)
            ->call('openAddEntry')
            ->assertDontSeeHtml('wire:model="ocrFile"');

        [$proOwner, $proBiz, $proBook] = $this->proBusinessWithBook();
        $this->bookShow($proOwner, $proBiz, $proBook)
            ->call('openAddEntry')
            ->assertSeeHtml('wire:model="ocrFile"')
            ->assertSee('scans left this month');
    }

    // ── 4. Comments scoped to this book ─────────────────────────

    public function test_comment_from_another_business_cannot_be_deleted(): void
    {
        // Attacker owns their own business…
        [$attacker, $attackerBiz, $attackerBook] = $this->proBusinessWithBook();

        // …victim's comment lives in a different business.
        [$victim, , $victimBook] = $this->proBusinessWithBook();
        $victimEntry = $this->makeEntry($victimBook, 'in', '10.00', '2026-09-01');
        $comment     = $victimEntry->comments()->create(['user_id' => $victim->id, 'body' => 'Private']);

        try {
            $this->bookShow($attacker, $attackerBiz, $attackerBook)
                ->call('confirmDeleteComment', $comment->id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            // expected
        }

        try {
            $this->bookShow($attacker, $attackerBiz, $attackerBook)
                ->set('pendingDeleteCommentId', $comment->id)
                ->call('deleteComment');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            // expected
        }

        $this->assertNotNull(EntryComment::find($comment->id));
    }

    public function test_owner_can_delete_any_comment_in_their_book_but_editor_only_their_own(): void
    {
        [$owner, $business, $book] = $this->proBusinessWithBook();
        $editor = $this->member($business, 'editor');
        $entry  = $this->makeEntry($book, 'in', '10.00', '2026-09-01');
        $ownerComment  = $entry->comments()->create(['user_id' => $owner->id, 'body' => 'Owner note']);
        $editorComment = $entry->comments()->create(['user_id' => $editor->id, 'body' => 'Editor note']);

        $this->bookShow($editor, $business, $book)
            ->set('pendingDeleteCommentId', $ownerComment->id)
            ->call('deleteComment');
        $this->assertNotNull($ownerComment->fresh());

        $this->bookShow($owner, $business, $book)
            ->call('confirmDeleteComment', $editorComment->id)
            ->call('deleteComment');
        $this->assertNull($editorComment->fresh());
    }

    public function test_free_can_read_existing_threads_but_not_post(): void
    {
        $owner    = $this->makeUser();
        $business = $this->makeBusiness($owner);
        $book     = $this->makeBook($business);
        $entry    = $this->makeEntry($book, 'in', '10.00', '2026-09-01');
        $empty    = $this->makeEntry($book, 'in', '11.00', '2026-09-02');
        $entry->comments()->create(['user_id' => $owner->id, 'body' => 'Old thread from Pro days']);

        $this->bookShow($owner, $business, $book)
            ->call('openComments', $entry->id)
            ->assertSet('showCommentPanel', true)
            ->assertSee('Old thread from Pro days')
            ->assertSee('Adding comments is available on the Pro plan.')
            ->set('commentBody', 'New one')
            ->call('addComment')
            ->assertSet('upgradeModalFeature', 'comments');

        $this->assertSame(1, $entry->comments()->count());

        // No thread to read → straight to the upgrade modal.
        $this->bookShow($owner, $business, $book)
            ->call('openComments', $empty->id)
            ->assertSet('showCommentPanel', false)
            ->assertSet('upgradeModalFeature', 'comments');
    }

    // ── 6. Resume recurring is Pro ──────────────────────────────

    public function test_free_can_pause_but_not_resume_recurring(): void
    {
        $owner    = $this->makeUser();
        $business = $this->makeBusiness($owner);
        $book     = $this->makeBook($business);
        $active   = $this->makeRule($book);
        $paused   = $this->makeRule($book, ['status' => 'paused']);

        $this->bookShow($owner, $business, $book)
            ->call('toggleRecurringStatus', $paused->id)
            ->assertSet('upgradeModalFeature', 'recurring');
        $this->assertSame('paused', $paused->fresh()->status);

        $this->bookShow($owner, $business, $book)->call('toggleRecurringStatus', $active->id);
        $this->assertSame('paused', $active->fresh()->status);

        $this->bookShow($owner, $business, $book)->call('deleteRecurring', $active->id);
        $this->assertNull($active->fresh());
    }

    // ── 7. suggestCategory ──────────────────────────────────────

    public function test_suggest_category_requires_editor_and_is_rate_limited(): void
    {
        Http::fake();

        [$owner, $business, $book] = $this->proBusinessWithBook();
        $viewer = $this->member($business, 'viewer');

        $this->bookShow($viewer, $business, $book)
            ->set('entryDescription', 'Office rent')
            ->call('suggestCategory')
            ->assertSet('showCategoryChip', false);
        Http::assertNothingSent();

        for ($i = 0; $i < BookShow::SUGGEST_RATE_LIMIT; $i++) {
            RateLimiter::hit(BookShow::SUGGEST_RATE_KEY . $owner->id, 60);
        }

        $this->bookShow($owner, $business, $book)
            ->set('entryDescription', 'Office rent')
            ->call('suggestCategory')
            ->assertSet('showCategoryChip', false);
        Http::assertNothingSent();
    }

    // ── 10. Custom date range is Pro ────────────────────────────

    public function test_custom_date_range_is_ignored_for_free_and_applied_for_pro(): void
    {
        $owner    = $this->makeUser();
        $business = $this->makeBusiness($owner);
        $book     = $this->makeBook($business);
        $this->makeEntry($book, 'in', '10.00', '2020-01-15');
        $this->makeEntry($book, 'in', '20.00', '2026-01-15');

        $free = $this->bookShow($owner, $business, $book)
            ->call('openCustomDateModal')
            ->assertSet('upgradeModalFeature', 'daterange')
            ->set('filterDuration', 'custom')
            ->set('filterCustomFrom', '2020-01-01')
            ->set('filterCustomTo', '2020-01-31');
        $this->assertCount(2, $free->viewData('entries'));

        $free->call('applyCustomDate')->assertSet('filterDuration', 'all_time');

        [$proOwner, $proBiz, $proBook] = $this->proBusinessWithBook();
        $this->makeEntry($proBook, 'in', '10.00', '2020-01-15');
        $this->makeEntry($proBook, 'in', '20.00', '2026-01-15');

        $pro = $this->bookShow($proOwner, $proBiz, $proBook)
            ->set('filterDuration', 'custom')
            ->set('filterCustomFrom', '2020-01-01')
            ->set('filterCustomTo', '2020-01-31');
        $this->assertCount(1, $pro->viewData('entries'));
    }

    // ── 5. Locked business settings ─────────────────────────────

    public function test_locked_business_settings_actions_are_blocked_but_delete_is_allowed(): void
    {
        $owner  = $this->makeUser();
        $this->makeBusiness($owner, ['created_at' => now()->subDay()]);
        $locked = $this->makeBusiness($owner, ['name' => 'Locked Co']);

        Livewire::actingAs($owner)->test(BusinessSettings::class, ['business' => $locked])
            ->set('name', 'Renamed')
            ->call('saveGeneral')
            ->assertForbidden();

        Livewire::actingAs($owner)->test(BusinessSettings::class, ['business' => $locked])
            ->set('inviteEmail', 'someone@example.com')
            ->call('sendInvite')
            ->assertForbidden();

        $this->assertSame('Locked Co', $locked->fresh()->name);
        $this->assertSame(0, $locked->invitations()->count());

        Livewire::actingAs($owner)->test(BusinessSettings::class, ['business' => $locked])
            ->set('deleteConfirmInput', 'Locked Co')
            ->call('deleteBusiness')
            ->assertRedirect(route('dashboard'));
        $this->assertNull(Business::find($locked->id));
    }

    // ── 9. Dashboard feed ───────────────────────────────────────

    public function test_dashboard_recent_entries_exclude_locked_businesses(): void
    {
        $owner  = $this->makeUser();
        $first  = $this->makeBusiness($owner, ['created_at' => now()->subDay()]);
        $locked = $this->makeBusiness($owner);
        $visible = $this->makeEntry($this->makeBook($first), 'in', '10.00', '2026-09-01');
        $hidden  = $this->makeEntry($this->makeBook($locked), 'in', '20.00', '2026-09-01');

        $ids = Livewire::actingAs($owner)->test(Dashboard::class)
            ->viewData('recentEntries')->pluck('id')->all();

        $this->assertContains($visible->id, $ids);
        $this->assertNotContains($hidden->id, $ids);
    }

    public function test_dashboard_new_business_opens_upgrade_for_free_owner_with_a_business(): void
    {
        $owner = $this->makeUser();
        $this->makeBusiness($owner);

        Livewire::actingAs($owner)->test(Dashboard::class)
            ->assertViewHas('businessLimitReached', true)
            ->assertSeeHtml("\$dispatch('open-business-upgrade')");
    }

    // ── 21. Invitation accept uses the plan limit ──────────────

    public function test_invitation_accept_respects_free_member_limit(): void
    {
        $owner    = $this->makeUser();
        $business = $this->makeBusiness($owner);
        $this->member($business, 'editor'); // 2 of 2 seats

        $invitee    = $this->makeUser();
        $invitation = $business->invitations()->create([
            'email'      => $invitee->email,
            'role'       => 'viewer',
            'token'      => \Illuminate\Support\Str::random(40),
            'invited_by' => $owner->id,
            'expires_at' => now()->addDay(),
        ]);

        Livewire::actingAs($invitee)->test(\App\Livewire\Invitation\Accept::class, ['invitation' => $invitation])
            ->assertSet('status', 'seat_limit');
    }
}
