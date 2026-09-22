<?php

namespace Tests\Feature\Api;

use App\Livewire\Book\Show as BookShow;
use App\Mail\BookEmailReport;
use App\Models\RecurringEntry;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Plan & role gates on /api/v1 (mirrors WebGatingSecurityTest).
 */
class GatingApiTest extends ApiTestCase
{
    public function test_book_delete_is_owner_only(): void
    {
        $owner    = $this->makeUser(pro: true);
        $business = $this->makeBusiness($owner);
        $book     = $this->makeBook($business);
        $editor   = $this->makeUser();
        $this->addMember($business, $editor, 'editor');

        $this->actingAsUser($editor);
        $this->deleteJson("/api/v1/books/{$book->id}")
            ->assertForbidden()
            ->assertJsonPath('message', 'Only the business owner can delete a book.');
        $this->assertNotNull($book->fresh());

        // Editors keep edit + duplicate.
        $this->putJson("/api/v1/books/{$book->id}", ['name' => 'Renamed'])->assertOk();
        $this->postJson("/api/v1/books/{$book->id}/duplicate", ['name' => 'Copy'])->assertCreated();

        $this->actingAsUser($owner);
        $this->deleteJson("/api/v1/books/{$book->id}")->assertOk();
        $this->assertNull($book->fresh());
    }

    public function test_free_cannot_resume_a_paused_recurring_rule(): void
    {
        $owner    = $this->makeUser();
        $business = $this->makeBusiness($owner);
        $book     = $this->makeBook($business);
        $rule     = $book->recurringEntries()->create([
            'type' => 'out', 'amount' => '15.00', 'description' => 'Hosting', 'frequency' => 'weekly',
            'starts_at' => '2026-03-01', 'next_run_at' => '2026-03-08', 'status' => 'active',
        ]);

        $this->actingAsUser($owner);

        // Pause is allowed on Free…
        $this->putJson("/api/v1/recurring/{$rule->id}/toggle")->assertOk()->assertJsonPath('status', 'paused');

        // …resume is not.
        $this->putJson("/api/v1/recurring/{$rule->id}/toggle")
            ->assertForbidden()
            ->assertJsonPath('code', 'pro_required');
        $this->assertSame('paused', $rule->fresh()->status);

        // Delete stays allowed.
        $this->deleteJson("/api/v1/recurring/{$rule->id}")->assertOk();
        $this->assertNull(RecurringEntry::find($rule->id));
    }

    public function test_suggest_category_requires_editor_and_is_rate_limited(): void
    {
        Http::fake();

        $owner    = $this->makeUser(pro: true);
        $business = $this->makeBusiness($owner);
        $book     = $this->makeBook($business);
        $viewer   = $this->makeUser();
        $this->addMember($business, $viewer, 'viewer');

        $this->actingAsUser($viewer);
        $this->postJson("/api/v1/books/{$book->id}/suggest-category", ['description' => 'Office rent'])
            ->assertForbidden();

        for ($i = 0; $i < BookShow::SUGGEST_RATE_LIMIT; $i++) {
            RateLimiter::hit(BookShow::SUGGEST_RATE_KEY . $owner->id, 60);
        }

        $this->actingAsUser($owner);
        $this->postJson("/api/v1/books/{$book->id}/suggest-category", ['description' => 'Office rent'])
            ->assertStatus(429)
            ->assertJsonPath('category', null);

        Http::assertNothingSent();
    }

    public function test_export_is_rate_limited_per_user(): void
    {
        $owner    = $this->makeUser(pro: true);
        $business = $this->makeBusiness($owner);
        $book     = $this->makeBook($business);
        $this->actingAsUser($owner);

        for ($i = 0; $i < 10; $i++) {
            $this->get("/api/v1/books/{$book->id}/export/csv")->assertOk();
        }

        $this->getJson("/api/v1/books/{$book->id}/export/csv")->assertStatus(429);
    }

    public function test_custom_date_range_is_ignored_for_free_but_presets_work(): void
    {
        $owner    = $this->makeUser();
        $business = $this->makeBusiness($owner);
        $book     = $this->makeBook($business);
        $this->makeEntry($book, 'in', '10.00', '2020-01-15');
        $this->makeEntry($book, 'in', '20.00', now()->toDateString());

        $this->actingAsUser($owner);

        // Arbitrary custom range → ignored (all-time).
        $this->getJson("/api/v1/books/{$book->id}/entries?from=2020-01-01&to=2020-01-31")
            ->assertOk()->assertJsonCount(2, 'data');
        $this->getJson("/api/v1/books/{$book->id}/summary?from=2020-01-01&to=2020-01-31")
            ->assertOk()->assertJsonPath('entryCount', 2);

        // Open-ended range → custom → ignored.
        $this->getJson("/api/v1/books/{$book->id}/entries?from=2026-01-01")
            ->assertOk()->assertJsonCount(2, 'data');

        // Preset window (last 7 days ending today) → honoured.
        $from = now()->subDays(6)->toDateString();
        $to   = now()->toDateString();
        $this->getJson("/api/v1/books/{$book->id}/entries?from={$from}&to={$to}")
            ->assertOk()->assertJsonCount(1, 'data');

        // Pro: any custom range is honoured.
        $owner->plan = 'pro';
        $owner->save();
        $this->getJson("/api/v1/books/{$book->id}/entries?from=2020-01-01&to=2020-01-31")
            ->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_first_report_is_sent_when_a_schedule_is_enabled(): void
    {
        Mail::fake();

        $owner    = $this->makeUser(pro: true);
        $business = $this->makeBusiness($owner);
        $book     = $this->makeBook($business);
        $this->actingAsUser($owner);

        $this->putJson("/api/v1/books/{$book->id}/report-schedule", [
            'frequency'  => 'weekly',
            'recipients' => ['a@example.com', 'b@example.com'],
            'isActive'   => true,
        ])->assertOk()->assertJsonPath('firstSent', true);

        Mail::assertQueued(BookEmailReport::class, 2);
        Mail::assertQueued(BookEmailReport::class, fn ($m) => $m->hasTo('a@example.com'));
        $this->assertNotNull($book->reportSchedule()->first()->last_sent_at);

        // Updating an existing schedule doesn't send again.
        $this->putJson("/api/v1/books/{$book->id}/report-schedule", [
            'frequency'  => 'monthly',
            'recipients' => ['a@example.com'],
            'isActive'   => true,
        ])->assertOk()->assertJsonPath('firstSent', false);

        Mail::assertQueued(BookEmailReport::class, 2);
    }

    public function test_paused_new_schedule_does_not_send(): void
    {
        Mail::fake();

        $owner    = $this->makeUser(pro: true);
        $business = $this->makeBusiness($owner);
        $book     = $this->makeBook($business);
        $this->actingAsUser($owner);

        $this->putJson("/api/v1/books/{$book->id}/report-schedule", [
            'frequency'  => 'weekly',
            'recipients' => ['a@example.com'],
            'isActive'   => false,
        ])->assertOk();

        Mail::assertNothingQueued();
    }

    public function test_free_ocr_scan_is_blocked_without_calling_ai(): void
    {
        Http::fake();

        $owner    = $this->makeUser();
        $business = $this->makeBusiness($owner);
        $book     = $this->makeBook($business);
        $this->actingAsUser($owner);

        $this->post("/api/v1/books/{$book->id}/scan", [
            'file' => \Illuminate\Http\UploadedFile::fake()->image('r.jpg'),
        ], ['Accept' => 'application/json'])->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_viewer_cannot_bulk_move_entries(): void
    {
        $owner    = $this->makeUser(pro: true);
        $business = $this->makeBusiness($owner);
        $book     = $this->makeBook($business);
        $target   = $this->makeBook($business);
        $entry    = $this->makeEntry($book, 'in', '10.00', '2026-09-01');
        $viewer   = $this->makeUser();
        $this->addMember($business, $viewer, 'viewer');

        $this->actingAsUser($viewer);
        $this->postJson("/api/v1/books/{$book->id}/entries/bulk-move", [
            'ids' => [$entry->id], 'targetBookId' => $target->id,
        ])->assertForbidden();

        $this->assertSame($book->id, $entry->fresh()->book_id);
    }
}
