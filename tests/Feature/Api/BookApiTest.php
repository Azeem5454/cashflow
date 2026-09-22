<?php

namespace Tests\Feature\Api;

use App\Models\AiUsageLog;
use App\Models\BookActivityLog;
use App\Models\RecurringEntry;
use Illuminate\Support\Facades\Http;

class BookApiTest extends ApiTestCase
{
    private function makeRule($book, array $attrs = []): RecurringEntry
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

    public function test_recurring_update_toggle_delete_require_editor(): void
    {
        $owner    = $this->makeUser(pro: true);
        $business = $this->makeBusiness($owner);
        $book     = $this->makeBook($business);
        $rule     = $this->makeRule($book);

        $viewer = $this->makeUser();
        $editor = $this->makeUser();
        $this->addMember($business, $viewer, 'viewer');
        $this->addMember($business, $editor, 'editor');

        $this->actingAsUser($viewer);
        $this->putJson("/api/v1/recurring/{$rule->id}", ['amount' => '20'])->assertForbidden();
        $this->putJson("/api/v1/recurring/{$rule->id}/toggle")->assertForbidden();
        $this->deleteJson("/api/v1/recurring/{$rule->id}")->assertForbidden();
        $this->assertSame('active', $rule->fresh()->status);

        // Viewer can still list
        $this->getJson("/api/v1/books/{$book->id}/recurring")->assertOk()
            ->assertJsonPath('data.0.id', $rule->id)
            ->assertJsonPath('data.0.startsAt', '2026-03-01')
            ->assertJsonPath('data.0.nextRunAt', '2026-03-08')
            ->assertJsonPath('data.0.status', 'active')
            ->assertJsonPath('data.0.reference', null);

        $this->actingAsUser($editor);
        $this->putJson("/api/v1/recurring/{$rule->id}", ['amount' => '20', 'category' => 'IT', 'endsAt' => '2026-12-31'])
            ->assertOk()
            ->assertJsonPath('data.amount', '20.00')
            ->assertJsonPath('data.category', 'IT')
            ->assertJsonPath('data.endsAt', '2026-12-31')
            ->assertJsonPath('data.frequency', 'weekly');

        $this->putJson("/api/v1/recurring/{$rule->id}", ['frequency' => 'monthly'])->assertStatus(422);

        $this->putJson("/api/v1/recurring/{$rule->id}/toggle")->assertOk()->assertJsonPath('status', 'paused');
        $this->putJson("/api/v1/recurring/{$rule->id}/toggle")->assertOk()->assertJsonPath('data.status', 'active');

        $this->deleteJson("/api/v1/recurring/{$rule->id}")->assertOk();
        $this->assertNull($rule->fresh());

        $this->assertSame(
            ['recurring_updated', 'recurring_paused', 'recurring_resumed', 'recurring_deleted'],
            BookActivityLog::where('book_id', $book->id)->get()->pluck('action')
                ->sortBy(fn ($a) => array_search($a, ['recurring_updated', 'recurring_paused', 'recurring_resumed', 'recurring_deleted']))
                ->values()->all()
        );
        $this->assertSame(['description' => 'Hosting'], BookActivityLog::where('action', 'recurring_deleted')->first()->meta);

        // Non-member → 404
        $rule2 = $this->makeRule($book);
        $this->actingAsUser($this->makeUser());
        $this->putJson("/api/v1/recurring/{$rule2->id}/toggle")->assertNotFound();
    }

    public function test_recurring_update_requires_pro(): void
    {
        $owner = $this->makeUser();
        $book  = $this->makeBook($this->makeBusiness($owner));
        $rule  = $this->makeRule($book);
        $this->actingAsUser($owner);

        $this->putJson("/api/v1/recurring/{$rule->id}", ['amount' => '20'])->assertForbidden();
    }

    public function test_locked_business_returns_403_on_free_plan(): void
    {
        $owner  = $this->makeUser();
        $first  = $this->makeBusiness($owner, ['name' => 'First']);
        $second = $this->makeBusiness($owner, ['name' => 'Second']);
        $first->created_at  = now()->subDay();
        $first->save();

        $book  = $this->makeBook($second);
        $entry = $this->makeEntry($book, 'in', '5.00', '2026-01-01');
        $okBook = $this->makeBook($first);

        $this->actingAsUser($owner);

        $list = collect($this->getJson('/api/v1/businesses')->assertOk()->json('data'))->keyBy('name');
        $this->assertFalse($list['First']['isLocked']);
        $this->assertTrue($list['Second']['isLocked']);

        $locked = ['message' => 'This business is locked on the Free plan.', 'code' => 'business_locked'];
        $this->getJson("/api/v1/businesses/{$second->id}")->assertForbidden()->assertExactJson($locked);
        $this->getJson("/api/v1/businesses/{$second->id}/books")->assertForbidden()->assertJsonPath('code', 'business_locked');
        $this->getJson("/api/v1/books/{$book->id}/entries")->assertForbidden()->assertJsonPath('code', 'business_locked');
        $this->getJson("/api/v1/entries/{$entry->id}")->assertForbidden()->assertJsonPath('code', 'business_locked');
        $this->postJson("/api/v1/books/{$book->id}/entries", [
            'type' => 'in', 'amount' => '1', 'description' => 'x', 'date' => '2026-01-01',
        ])->assertForbidden()->assertJsonPath('code', 'business_locked');

        $this->getJson("/api/v1/books/{$okBook->id}/entries")->assertOk();
        $recent = collect($this->getJson('/api/v1/books/recent')->assertOk()->json('data'))->pluck('id');
        $this->assertContains($okBook->id, $recent);
        $this->assertNotContains($book->id, $recent);

        // A member who is not the owner is never locked by their own plan
        $editor = $this->makeUser();
        $this->addMember($second, $editor, 'editor');
        $this->actingAsUser($editor);
        $this->getJson("/api/v1/books/{$book->id}/entries")->assertOk();

        // Upgrading unlocks
        $owner->plan = 'pro';
        $owner->save();
        $this->actingAsUser($owner->fresh());
        $this->getJson("/api/v1/businesses/{$second->id}")->assertOk()->assertJsonPath('data.isLocked', false);
    }

    public function test_business_books_sort_null_periods_last(): void
    {
        $owner    = $this->makeUser();
        $business = $this->makeBusiness($owner);
        $noPeriod = $this->makeBook($business, ['name' => 'No period']);
        $older    = $this->makeBook($business, ['name' => 'Jan', 'period_starts_at' => '2026-01-01', 'period_ends_at' => '2026-01-31']);
        $newer    = $this->makeBook($business, ['name' => 'Feb', 'period_starts_at' => '2026-02-01', 'period_ends_at' => '2026-02-28']);
        $this->actingAsUser($owner);

        $res = $this->getJson("/api/v1/businesses/{$business->id}/books")->assertOk();
        $this->assertSame(['Feb', 'Jan', 'No period'], array_column($res->json('data'), 'name'));
        $res->assertJsonPath('data.2.totalIn', '0.00')->assertJsonPath('data.2.balance', '0.00');
    }

    public function test_comment_body_text_and_mention_notification_message(): void
    {
        $owner    = $this->makeUser(pro: true, attrs: ['name' => 'Omar']);
        $business = $this->makeBusiness($owner);
        $book     = $this->makeBook($business, ['name' => 'March']);
        $entry    = $this->makeEntry($book, 'out', '300.00', '2026-03-01', ['description' => 'Office rent']);
        $sara     = $this->makeUser(attrs: ['name' => 'Sara']);
        $outsider = $this->makeUser(attrs: ['name' => 'Eve']);
        $this->addMember($business, $sara, 'editor');

        $this->actingAsUser($owner);
        $body = "Please check @[Sara]{{$sara->id}} and @[Eve]{{$outsider->id}}";
        $res  = $this->postJson("/api/v1/entries/{$entry->id}/comments", ['body' => $body])->assertCreated();
        $res->assertJsonPath('body', $body)->assertJsonPath('bodyText', 'Please check @Sara and @Eve');

        $this->getJson("/api/v1/entries/{$entry->id}/comments")->assertOk()
            ->assertJsonPath('data.0.bodyText', 'Please check @Sara and @Eve');

        $this->assertSame(['entry_description' => 'Office rent'],
            BookActivityLog::where('action', 'comment_added')->firstOrFail()->meta);

        // Only real members get notified
        $this->assertSame(0, $outsider->notifications()->count());

        $this->actingAsUser($sara);
        $this->getJson('/api/v1/notifications')->assertOk()
            ->assertJsonPath('data.0.message', "Omar mentioned you in a comment on 'Office rent' in March.")
            ->assertJsonPath('data.0.title', 'New mention')
            ->assertJsonPath('unreadCount', 1);

        // Sara (not author, not owner) cannot delete; owner can
        $commentId = $res->json('id');
        $this->deleteJson("/api/v1/comments/{$commentId}")->assertForbidden();
        $this->actingAsUser($owner);
        $this->deleteJson("/api/v1/comments/{$commentId}")->assertOk();
        $this->assertSame(1, BookActivityLog::where('action', 'comment_deleted')->count());
    }

    public function test_csv_export_running_balance_includes_opening_balance(): void
    {
        $owner = $this->makeUser(pro: true);
        $book  = $this->makeBook($this->makeBusiness($owner), ['opening_balance' => '1000.00']);
        $this->makeEntry($book, 'in', '200.00', '2026-01-01');
        $this->makeEntry($book, 'out', '50.00', '2026-01-02');
        $this->actingAsUser($owner);

        $csv = $this->get("/api/v1/books/{$book->id}/export/csv")->assertOk()->streamedContent();
        $rows = array_map('str_getcsv', array_values(array_filter(explode("\n", trim($csv)))));

        $this->assertSame('1200.00', $rows[1][7]);
        $this->assertSame('1150.00', $rows[2][7]);
    }

    public function test_insights_not_enough_data_limits_cache_and_generation(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['type' => 'text', 'text' => json_encode([
                    'sentiment' => 'concern', 'sentiment_reason' => 'Spending exceeds income',
                    'bullets' => ['a', 'b', 'c'], 'tip' => 'Cut costs',
                ])]],
                'usage' => ['input_tokens' => 100, 'output_tokens' => 50],
            ]),
        ]);

        $owner = $this->makeUser(pro: true);
        $book  = $this->makeBook($this->makeBusiness($owner));
        $this->actingAsUser($owner);

        $this->makeEntry($book, 'in', '10.00', '2026-01-01');
        $this->getJson("/api/v1/books/{$book->id}/insights")->assertOk()
            ->assertExactJson([
                'data' => null, 'status' => 'not_enough_data',
                'message' => 'Add at least 3 entries to generate insights.',
            ]);
        Http::assertNothingSent();

        $this->makeEntry($book, 'out', '20.00', '2026-01-02', ['category' => 'Rent']);
        $this->makeEntry($book, 'out', '5.00', '2026-01-03');

        $this->getJson("/api/v1/books/{$book->id}/insights")->assertOk()
            ->assertJsonPath('data.sentiment', 'Concern')
            ->assertJsonPath('data.sentimentReason', 'Spending exceeds income')
            ->assertJsonPath('data.bullets', ['a', 'b', 'c'])
            ->assertJsonPath('data.tip', 'Cut costs')
            ->assertJsonPath('data.cached', false);
        Http::assertSentCount(1);
        $this->assertSame(1, AiUsageLog::where('type', 'insights')->where('user_id', $owner->id)->count());
        // Stored in the same snake_case format the web reads
        $this->assertSame('concern', json_decode($book->fresh()->ai_insights_cache, true)['sentiment']);

        // Second call served from cache
        $this->getJson("/api/v1/books/{$book->id}/insights")->assertOk()->assertJsonPath('data.cached', true);
        Http::assertSentCount(1);

        // Forced refresh within a minute → burst limit, cached data still returned
        $this->getJson("/api/v1/books/{$book->id}/insights?refresh=1")->assertOk()
            ->assertJsonPath('status', 'limit_reached')
            ->assertJsonPath('data.sentiment', 'Concern');
        Http::assertSentCount(1);
    }

    public function test_insights_maps_web_cache_daily_limit_and_free_gate(): void
    {
        $owner = $this->makeUser(pro: true);
        $book  = $this->makeBook($this->makeBusiness($owner));
        foreach (range(1, 3) as $i) {
            $this->makeEntry($book, 'in', '10.00', "2026-01-0{$i}");
        }
        $this->actingAsUser($owner);

        // Web-written cache (snake_case, lowercase sentiment)
        $book->update([
            'ai_insights_cache'        => json_encode(['sentiment' => 'healthy', 'sentiment_reason' => 'Positive net', 'bullets' => ['x'], 'tip' => 'Keep going']),
            'ai_insights_generated_at' => now()->subHours(2),
        ]);
        $this->getJson("/api/v1/books/{$book->id}/insights")->assertOk()
            ->assertJsonPath('data.sentiment', 'Healthy')
            ->assertJsonPath('data.sentimentReason', 'Positive net')
            ->assertJsonPath('data.cached', true);

        // Stale cache + daily cap hit → limit_reached with stale data
        $book->update(['ai_insights_generated_at' => now()->subDays(2)]);
        foreach (range(1, 10) as $i) {
            AiUsageLog::create(['user_id' => $owner->id, 'type' => 'insights', 'tokens_in' => 1, 'tokens_out' => 1, 'cost_usd' => 0]);
        }
        $this->getJson("/api/v1/books/{$book->id}/insights")->assertOk()
            ->assertJsonPath('status', 'limit_reached')
            ->assertJsonPath('data.sentiment', 'Healthy');
        Http::assertNothingSent();

        // Free → 403
        $free     = $this->makeUser();
        $freeBook = $this->makeBook($this->makeBusiness($free));
        $this->actingAsUser($free);
        $this->getJson("/api/v1/books/{$freeBook->id}/insights")->assertForbidden();
    }

    public function test_insights_failed_status_when_ai_errors(): void
    {
        Http::fake(['api.anthropic.com/*' => Http::response(['error' => ['message' => 'boom']], 500)]);

        $owner = $this->makeUser(pro: true);
        $book  = $this->makeBook($this->makeBusiness($owner));
        foreach (range(1, 3) as $i) {
            $this->makeEntry($book, 'out', '10.00', "2026-01-0{$i}");
        }
        $this->actingAsUser($owner);

        $this->getJson("/api/v1/books/{$book->id}/insights")->assertOk()
            ->assertJsonPath('data', null)
            ->assertJsonPath('status', 'failed');
    }

    public function test_unverified_users_can_use_data_endpoints_but_not_email_others(): void
    {
        $user = $this->makeUser(attrs: ['email_verified_at' => null]);
        $this->actingAsUser($user);

        // Soft verification: the product works right away…
        $this->getJson('/api/v1/businesses')->assertOk();
        $this->getJson('/api/v1/notifications')->assertOk();
        $this->getJson('/api/v1/user')->assertOk();
        $this->putJson('/api/v1/profile', ['name' => 'New Name', 'email' => $user->email])->assertOk();
        $this->postJson('/api/v1/auth/email/resend')->assertSuccessful();

        // …only actions that email other people need a verified address.
        $business = $this->makeBusiness($user);
        $this->postJson("/api/v1/businesses/{$business->id}/invitations", ['email' => 'm@example.com', 'role' => 'viewer'])
            ->assertForbidden()
            ->assertExactJson(['message' => 'Please verify your email address first.', 'code' => 'email_unverified']);
    }

    public function test_report_schedule_viewer_gets_clear_message(): void
    {
        $owner    = $this->makeUser(pro: true);
        $business = $this->makeBusiness($owner);
        $book     = $this->makeBook($business);
        $viewer   = $this->makeUser();
        $this->addMember($business, $viewer, 'viewer');
        $this->actingAsUser($viewer);

        $this->getJson("/api/v1/books/{$book->id}/report-schedule")->assertForbidden()
            ->assertJsonPath('message', 'Only owners and editors can manage email reports.');
    }
}
