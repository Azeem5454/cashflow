<?php

namespace Tests\Feature\Api;

use App\Models\BookActivityLog;
use App\Models\Entry;
use App\Models\RecurringEntry;
use Illuminate\Support\Facades\Storage;

class EntryApiTest extends ApiTestCase
{
    private function seedLedger(): array
    {
        $owner    = $this->makeUser(pro: true);
        $business = $this->makeBusiness($owner);
        $book     = $this->makeBook($business, ['opening_balance' => '100.00']);

        $a = $this->makeEntry($book, 'in', '50.00', '2026-01-01', ['category' => 'Sales']);
        $b = $this->makeEntry($book, 'out', '30.00', '2026-01-02', ['category' => 'Rent', 'reference' => 'INV-9']);
        $c = $this->makeEntry($book, 'in', '10.00', '2026-01-02', ['category' => 'Sales', 'payment_mode' => 'Cash']);

        // Deterministic created_at so same-date ordering is stable.
        foreach ([[$a, 1], [$b, 2], [$c, 3]] as [$e, $min]) {
            $e->created_at = now()->subHour()->addMinutes($min);
            $e->saveQuietly();
        }

        return compact('owner', 'business', 'book', 'a', 'b', 'c');
    }

    public function test_entries_list_is_newest_first_with_running_balance_from_opening_balance(): void
    {
        ['owner' => $owner, 'book' => $book, 'a' => $a, 'b' => $b, 'c' => $c] = $this->seedLedger();
        $this->actingAsUser($owner);

        $res = $this->getJson("/api/v1/books/{$book->id}/entries")->assertOk();

        $this->assertSame([$c->id, $b->id, $a->id], array_column($res->json('data'), 'id'));
        $this->assertSame(['130.00', '120.00', '150.00'], array_column($res->json('data'), 'runningBalance'));
        $res->assertJsonPath('meta.currentPage', 1)
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('meta.perPage', 50)
            ->assertJsonPath('meta.lastPage', 1);

        // Filtered rows keep their full-ledger running balance (same as web).
        $res = $this->getJson("/api/v1/books/{$book->id}/entries?type=out")->assertOk();
        $this->assertSame([$b->id], array_column($res->json('data'), 'id'));
        $res->assertJsonPath('data.0.runningBalance', '120.00');

        // Pagination + perPage cap
        $res = $this->getJson("/api/v1/books/{$book->id}/entries?perPage=2&page=2")->assertOk();
        $this->assertSame([$a->id], array_column($res->json('data'), 'id'));
        $res->assertJsonPath('meta.lastPage', 2);

        $this->getJson("/api/v1/books/{$book->id}/entries?perPage=1000")
            ->assertOk()->assertJsonPath('meta.perPage', 100);

        // Search matches reference / category like the web.
        $res = $this->getJson("/api/v1/books/{$book->id}/entries?search=inv-9")->assertOk();
        $this->assertSame([$b->id], array_column($res->json('data'), 'id'));
    }

    public function test_entry_show_has_contract_shape_and_hides_creator_private_fields(): void
    {
        $owner    = $this->makeUser();
        $business = $this->makeBusiness($owner);
        $book     = $this->makeBook($business);
        $entry    = $this->makeEntry($book, 'in', '12.50', '2026-02-01', ['created_by' => $owner->id]);

        $this->actingAsUser($owner);

        $res = $this->getJson("/api/v1/entries/{$entry->id}")->assertOk();
        $res->assertJsonPath('data.id', $entry->id)
            ->assertJsonPath('data.amount', '12.50')
            ->assertJsonPath('data.createdBy', ['id' => $owner->id, 'name' => $owner->name])
            ->assertJsonPath('data.commentsCount', 0)
            ->assertJsonPath('data.isFlagged', false)
            ->assertJsonPath('data.flagReason', null)
            ->assertJsonPath('data.recurringEntryId', null)
            ->assertJsonPath('data.hasAttachment', false);
        $this->assertArrayHasKey('updatedAt', $res->json('data'));
        $this->assertArrayNotHasKey('email', $res->json('data.createdBy'));

        // Non-member → 404
        $this->actingAsUser($this->makeUser());
        $this->getJson("/api/v1/entries/{$entry->id}")->assertNotFound();
        $this->getJson('/api/v1/entries/not-a-uuid')->assertNotFound();
    }

    public function test_summary_supports_filters_and_formats_zero(): void
    {
        ['owner' => $owner, 'book' => $book] = $this->seedLedger();
        $this->actingAsUser($owner);

        $this->getJson("/api/v1/books/{$book->id}/summary")->assertOk()
            ->assertJsonPath('totalIn', '60.00')
            ->assertJsonPath('totalOut', '30.00')
            ->assertJsonPath('balance', '130.00')
            ->assertJsonPath('openingBalance', '100.00')
            ->assertJsonPath('entryCount', 3);

        $this->getJson("/api/v1/books/{$book->id}/summary?type=in")->assertOk()
            ->assertJsonPath('totalIn', '60.00')
            ->assertJsonPath('totalOut', '0.00')
            ->assertJsonPath('entryCount', 2)
            ->assertJsonPath('filtered', true);

        $this->getJson("/api/v1/books/{$book->id}/summary?from=2026-01-02&to=2026-01-02&paymentMode=Cash")->assertOk()
            ->assertJsonPath('totalIn', '10.00')
            ->assertJsonPath('totalOut', '0.00')
            ->assertJsonPath('entryCount', 1);

        $this->getJson("/api/v1/books/{$book->id}/summary?category=Rent")->assertOk()
            ->assertJsonPath('totalOut', '30.00')
            ->assertJsonPath('totalIn', '0.00');

        // Empty book → "0.00" strings everywhere
        $empty = $this->makeBook($book->business);
        $this->getJson("/api/v1/books/{$empty->id}/summary")->assertOk()
            ->assertJsonPath('totalIn', '0.00')
            ->assertJsonPath('totalOut', '0.00')
            ->assertJsonPath('balance', '0.00')
            ->assertJsonPath('openingBalance', '0.00')
            ->assertJsonPath('entryCount', 0);

        $this->getJson("/api/v1/books/{$book->id}/summary?from=not-a-date")->assertStatus(422);
    }

    public function test_create_entry_with_recurring_as_pro(): void
    {
        $owner    = $this->makeUser(pro: true);
        $business = $this->makeBusiness($owner);
        $book     = $this->makeBook($business);
        $this->actingAsUser($owner);

        $res = $this->postJson("/api/v1/books/{$book->id}/entries", [
            'type'               => 'out',
            'amount'             => '45.00',
            'description'        => 'Office rent',
            'date'               => '2026-03-10',
            'category'           => 'Rent',
            'paymentMode'        => 'Bank',
            'recurringFrequency' => 'weekly',
            'recurringEndsAt'    => '2026-06-30',
        ])->assertCreated();

        $rule = RecurringEntry::firstOrFail();
        $this->assertSame('weekly', $rule->frequency);
        $this->assertSame('2026-03-10', $rule->starts_at->toDateString());
        $this->assertSame('2026-03-17', $rule->next_run_at->toDateString());
        $this->assertSame('2026-06-30', $rule->ends_at->toDateString());
        $this->assertSame('active', $rule->status);

        $res->assertJsonPath('data.recurringEntryId', $rule->id)
            ->assertJsonPath('data.createdBy.id', $owner->id);

        $this->assertSame(
            ['entry_created', 'recurring_created'],
            BookActivityLog::where('book_id', $book->id)->orderBy('created_at')->orderBy('action')->pluck('action')->sort()->values()->all()
        );
        $log = BookActivityLog::where('action', 'entry_created')->first();
        $this->assertSame(['type' => 'out', 'amount' => '45.00', 'description' => 'Office rent'], $log->meta);
        $this->assertSame($res->json('data.id'), $log->entry_id);
        $this->assertSame(['description' => 'Office rent', 'frequency' => 'weekly'],
            BookActivityLog::where('action', 'recurring_created')->first()->meta);

        // New category / payment mode added to the book's lists
        $this->assertSame(['Rent'], $book->categories()->pluck('name')->all());
        $this->assertSame(['Bank'], $book->paymentModes()->pluck('name')->all());

        // Unsupported frequency → 422
        $this->postJson("/api/v1/books/{$book->id}/entries", [
            'type' => 'out', 'amount' => '1', 'description' => 'x', 'date' => '2026-03-10',
            'recurringFrequency' => 'hourly',
        ])->assertStatus(422);

        // recurringEndsAt before date → 422
        $this->postJson("/api/v1/books/{$book->id}/entries", [
            'type' => 'out', 'amount' => '1', 'description' => 'x', 'date' => '2026-03-10',
            'recurringFrequency' => 'daily', 'recurringEndsAt' => '2026-03-01',
        ])->assertStatus(422);
    }

    public function test_create_entry_with_recurring_forbidden_for_free_and_viewer(): void
    {
        $owner    = $this->makeUser();
        $business = $this->makeBusiness($owner);
        $book     = $this->makeBook($business);
        $this->actingAsUser($owner);

        $payload = [
            'type' => 'in', 'amount' => '10', 'description' => 'Retainer', 'date' => '2026-03-10',
            'recurringFrequency' => 'weekly',
        ];

        $this->postJson("/api/v1/books/{$book->id}/entries", $payload)
            ->assertForbidden()
            ->assertJsonStructure(['message']);
        $this->assertSame(0, Entry::count());
        $this->assertSame(0, RecurringEntry::count());

        // Free without recurring is fine
        $this->postJson("/api/v1/books/{$book->id}/entries", array_diff_key($payload, ['recurringFrequency' => 1]))
            ->assertCreated();

        // Viewer on a Pro business → 403
        $proOwner = $this->makeUser(pro: true);
        $proBiz   = $this->makeBusiness($proOwner);
        $proBook  = $this->makeBook($proBiz);
        $viewer   = $this->makeUser();
        $this->addMember($proBiz, $viewer, 'viewer');
        $this->actingAsUser($viewer);

        $this->postJson("/api/v1/books/{$proBook->id}/entries", $payload)->assertForbidden();
        $this->assertSame(0, $proBook->entries()->count());
    }

    public function test_description_is_optional_and_blank_is_stored_as_null(): void
    {
        $owner = $this->makeUser();
        $book  = $this->makeBook($this->makeBusiness($owner));
        $this->actingAsUser($owner);

        $id = $this->postJson("/api/v1/books/{$book->id}/entries", [
            'type' => 'in', 'amount' => '10', 'date' => '2026-03-10',
        ])->assertCreated()->assertJsonPath('data.description', null)->json('data.id');
        $this->assertNull(Entry::findOrFail($id)->description);

        $id = $this->postJson("/api/v1/books/{$book->id}/entries", [
            'type' => 'out', 'amount' => '5', 'date' => '2026-03-10', 'description' => '   ',
        ])->assertCreated()->json('data.id');
        $this->assertNull(Entry::findOrFail($id)->description);

        $this->putJson("/api/v1/entries/{$id}", ['description' => '  Taxi  '])
            ->assertOk()->assertJsonPath('data.description', 'Taxi');
        $this->putJson("/api/v1/entries/{$id}", ['description' => null])
            ->assertOk()->assertJsonPath('data.description', null);
        $this->assertNull(Entry::findOrFail($id)->description);

        $this->postJson("/api/v1/books/{$book->id}/entries", [
            'type' => 'in', 'amount' => '10', 'date' => '2026-03-10', 'description' => str_repeat('x', 256),
        ])->assertStatus(422)->assertJsonValidationErrors('description');
    }

    public function test_recurring_rule_without_description_is_created(): void
    {
        $owner = $this->makeUser(pro: true);
        $book  = $this->makeBook($this->makeBusiness($owner));
        $this->actingAsUser($owner);

        $this->postJson("/api/v1/books/{$book->id}/entries", [
            'type' => 'out', 'amount' => '12', 'date' => '2026-03-10', 'recurringFrequency' => 'weekly',
        ])->assertCreated();

        $this->assertNull(RecurringEntry::where('book_id', $book->id)->firstOrFail()->description);
    }

    public function test_categories_and_payment_modes_post(): void
    {
        $owner    = $this->makeUser();
        $business = $this->makeBusiness($owner);
        $book     = $this->makeBook($business);
        $this->actingAsUser($owner);

        $this->postJson("/api/v1/books/{$book->id}/categories", ['name' => 'Rent'])
            ->assertOk()->assertExactJson(['data' => ['Rent']]);
        $this->postJson("/api/v1/books/{$book->id}/categories", ['name' => '  rent '])
            ->assertOk()->assertExactJson(['data' => ['Rent']]);
        $this->postJson("/api/v1/books/{$book->id}/categories", ['name' => 'Food'])
            ->assertOk()->assertExactJson(['data' => ['Food', 'Rent']]);
        $this->postJson("/api/v1/books/{$book->id}/categories", ['name' => ''])->assertStatus(422);

        $this->postJson("/api/v1/books/{$book->id}/payment-modes", ['name' => 'Cash'])
            ->assertOk()->assertExactJson(['data' => ['Cash']]);
        $this->postJson("/api/v1/books/{$book->id}/payment-modes", ['name' => 'CASH'])
            ->assertOk()->assertExactJson(['data' => ['Cash']]);

        $viewer = $this->makeUser();
        $this->addMember($business, $viewer, 'viewer');
        $this->actingAsUser($viewer);
        $this->postJson("/api/v1/books/{$book->id}/categories", ['name' => 'X'])->assertForbidden();
        $this->postJson("/api/v1/books/{$book->id}/payment-modes", ['name' => 'X'])->assertForbidden();
    }

    public function test_update_and_delete_write_activity_and_follow_null_semantics(): void
    {
        $owner = $this->makeUser();
        $book  = $this->makeBook($this->makeBusiness($owner));
        $entry = $this->makeEntry($book, 'out', '20.00', '2026-03-01', ['category' => 'Food', 'reference' => 'R1']);
        $this->actingAsUser($owner);

        $this->putJson("/api/v1/entries/{$entry->id}", ['category' => null, 'amount' => '25.00', 'paymentMode' => 'Card'])
            ->assertOk()
            ->assertJsonPath('data.category', null)
            ->assertJsonPath('data.reference', 'R1')
            ->assertJsonPath('data.amount', '25.00')
            ->assertJsonPath('data.paymentMode', 'Card');
        $this->assertSame(['Card'], $book->paymentModes()->pluck('name')->all());

        $this->putJson("/api/v1/entries/{$entry->id}", ['description' => str_repeat('x', 256)])->assertStatus(422);

        $log = BookActivityLog::where('action', 'entry_updated')->firstOrFail();
        $this->assertSame($entry->id, $log->entry_id);
        $this->assertSame(['type' => 'out', 'amount' => '25.00', 'description' => $entry->description], $log->meta);

        $this->deleteJson("/api/v1/entries/{$entry->id}")->assertOk();
        $log = BookActivityLog::where('action', 'entry_deleted')->firstOrFail();
        $this->assertNull($log->entry_id);
        $this->assertSame('out', $log->meta['type']);
        $this->assertSame('deleted a Cash Out entry', $log->describe());
    }

    public function test_bulk_operations_write_activity(): void
    {
        $owner    = $this->makeUser();
        $business = $this->makeBusiness($owner);
        $book     = $this->makeBook($business);
        $target   = $this->makeBook($business, ['name' => 'Target']);
        $e1 = $this->makeEntry($book, 'in', '10.00', '2026-03-01');
        $e2 = $this->makeEntry($book, 'out', '5.00', '2026-03-02');
        $e3 = $this->makeEntry($book, 'out', '7.00', '2026-03-03');
        $this->actingAsUser($owner);

        $this->postJson("/api/v1/books/{$book->id}/entries/bulk-update", [
            'ids' => [$e1->id, $e2->id], 'category' => 'Misc', 'paymentMode' => null, 'flipType' => true,
        ])->assertOk();

        $this->assertSame(['count' => 2, 'category' => 'Misc'],
            BookActivityLog::where('action', 'bulk_change_category')->firstOrFail()->meta);
        $this->assertSame(['count' => 2, 'payment_mode' => 'None'],
            BookActivityLog::where('action', 'bulk_change_payment_mode')->firstOrFail()->meta);
        $this->assertSame(['count' => 2], BookActivityLog::where('action', 'bulk_flip_type')->firstOrFail()->meta);
        $this->assertSame('out', $e1->fresh()->type);

        $this->postJson("/api/v1/books/{$book->id}/entries/bulk-move", [
            'ids' => [$e1->id], 'targetBookId' => $target->id, 'copy' => true,
        ])->assertOk();
        $this->assertSame(['count' => 1, 'target_book' => 'Target'],
            BookActivityLog::where('action', 'bulk_copy')->firstOrFail()->meta);

        $this->postJson("/api/v1/books/{$book->id}/entries/bulk-move", [
            'ids' => [$e2->id], 'targetBookId' => $target->id,
        ])->assertOk();
        $this->assertSame(['count' => 1, 'target_book' => 'Target'],
            BookActivityLog::where('action', 'bulk_move')->firstOrFail()->meta);
        $this->assertSame($target->id, $e2->fresh()->book_id);

        $this->postJson("/api/v1/books/{$book->id}/entries/bulk-delete", ['ids' => [$e3->id]])->assertOk();
        $meta = BookActivityLog::where('action', 'bulk_delete')->firstOrFail()->meta;
        $this->assertSame(1, $meta['count']);
        $this->assertSame('out', $meta['type']);

        // Cross-book ids rejected
        $foreign = $this->makeEntry($target, 'in', '1.00', '2026-03-01');
        $this->postJson("/api/v1/books/{$book->id}/entries/bulk-delete", ['ids' => [$foreign->id]])->assertForbidden();
        $this->assertNotNull($foreign->fresh());

        $this->assertSame(0, BookActivityLog::where('user_id', '!=', $owner->id)->count());
    }

    public function test_scan_attachment_path_is_attached_only_when_valid(): void
    {
        Storage::fake('local');

        $owner    = $this->makeUser(pro: true);
        $business = $this->makeBusiness($owner);
        $book     = $this->makeBook($business);
        $other    = $this->makeBook($business);
        $this->actingAsUser($owner);

        $good = "attachments/{$business->id}/{$book->id}/" . str_repeat('a', 40) . '.jpg';
        Storage::disk('local')->put($good, 'img');
        $foreign = "attachments/{$business->id}/{$other->id}/" . str_repeat('b', 40) . '.jpg';
        Storage::disk('local')->put($foreign, 'img');

        $base = ['type' => 'out', 'amount' => '9.99', 'description' => 'Lunch', 'date' => '2026-03-10'];

        foreach ([
            $foreign,
            "attachments/{$business->id}/{$book->id}/../{$other->id}/" . str_repeat('b', 40) . '.jpg',
            "attachments/{$business->id}/{$book->id}/" . str_repeat('c', 40) . '.jpg', // does not exist
            "attachments/{$business->id}/{$book->id}/evil.php",
            '/etc/passwd',
        ] as $bad) {
            $this->postJson("/api/v1/books/{$book->id}/entries", $base + ['scanAttachmentPath' => $bad])
                ->assertStatus(422)->assertJsonValidationErrors('scanAttachmentPath');
        }
        $this->assertSame(0, Entry::count());

        $res = $this->postJson("/api/v1/books/{$book->id}/entries", $base + ['scanAttachmentPath' => $good])
            ->assertCreated()
            ->assertJsonPath('data.hasAttachment', true);
        $this->assertSame($good, Entry::find($res->json('data.id'))->attachment_path);
        $this->assertSame(1, BookActivityLog::where('action', 'attachment_added')->count());

        // Same file can't be attached twice
        $this->postJson("/api/v1/books/{$book->id}/entries", $base + ['scanAttachmentPath' => $good])
            ->assertStatus(422);
    }
}
