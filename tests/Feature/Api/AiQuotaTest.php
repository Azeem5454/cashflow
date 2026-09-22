<?php

namespace Tests\Feature\Api;

use App\Livewire\Book\Show as BookShow;
use App\Models\AiUsageLog;
use App\Models\Book;
use App\Models\Business;
use App\Models\User;
use App\Services\AiQuota;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/**
 * AI entry quota (App\Services\AiQuota): Free = 10 AI entries/month shared
 * between receipt scans and typed entries; Pro = 200 scans/month + 30 typed
 * entries/day. Category suggestions are free for everyone.
 */
class AiQuotaTest extends ApiTestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // ── helpers ─────────────────────────────────────────────────

    /** @return array{0: User, 1: Business, 2: Book} */
    private function setupBook(bool $pro = false): array
    {
        $owner    = $this->makeUser(pro: $pro);
        $business = $this->makeBusiness($owner);
        $book     = $this->makeBook($business);

        return [$owner, $business, $book];
    }

    private function log(User $user, string $type, int $n = 1, ?Carbon $at = null): void
    {
        for ($i = 0; $i < $n; $i++) {
            AiUsageLog::forceCreate([
                'user_id'    => $user->id,
                'type'       => $type,
                'tokens_in'  => 1,
                'tokens_out' => 1,
                'cost_usd'   => 0,
                'created_at' => $at ?? now(),
            ]);
        }
    }

    private function fakeClaude(array $json, int $in = 1000, int $out = 100): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['type' => 'text', 'text' => json_encode($json)]],
                'usage'   => ['input_tokens' => $in, 'output_tokens' => $out],
            ]),
        ]);
    }

    private function fakeParse(): void
    {
        $this->fakeClaude([
            'type' => 'out', 'amount' => 120, 'date' => now()->format('Y-m-d'),
            'description' => 'Fuel', 'category' => 'Transport', 'payment_mode' => 'Cash',
        ]);
    }

    private function clearBursts(User $user): void
    {
        RateLimiter::clear('nlp-burst:' . $user->id);
        RateLimiter::clear('ocr:' . $user->id);
    }

    // ── Free allowance: 10 combined, then 403 ───────────────────

    public function test_free_user_gets_exactly_ten_combined_ai_entries_then_403(): void
    {
        Storage::fake('local');
        [$owner, , $book] = $this->setupBook();
        $this->actingAsUser($owner);

        // 5 scans
        $this->fakeClaude(['type' => 'out', 'amount' => 45, 'description' => 'Supplies']);
        for ($i = 1; $i <= 5; $i++) {
            $this->post("/api/v1/books/{$book->id}/scan", [
                'receipt' => UploadedFile::fake()->image("r{$i}.jpg"),
            ], ['Accept' => 'application/json'])
                ->assertOk()
                ->assertJsonPath('quota.plan', 'free')
                ->assertJsonPath('quota.used', $i)
                ->assertJsonPath('quota.remaining', 10 - $i);
        }

        // 5 typed entries
        $this->fakeParse();
        for ($i = 6; $i <= 10; $i++) {
            $this->postJson("/api/v1/books/{$book->id}/parse", ['text' => 'Paid 120 for fuel today'])
                ->assertOk()
                ->assertJsonPath('quota.remaining', 10 - $i);
        }

        $this->clearBursts($owner);
        Http::fake(); // anything further must not reach Claude

        $this->postJson("/api/v1/books/{$book->id}/parse", ['text' => 'Paid 120 for fuel today'])
            ->assertForbidden()
            ->assertJsonPath('code', 'ai_quota_exhausted')
            ->assertJsonPath('message', "You've used your 10 free AI entries this month.")
            ->assertJsonPath('resetsAt', now('UTC')->startOfMonth()->addMonth()->toIso8601String())
            ->assertJsonPath('quota.exhausted', true);

        $this->post("/api/v1/books/{$book->id}/scan", [
            'receipt' => UploadedFile::fake()->image('r.jpg'),
        ], ['Accept' => 'application/json'])
            ->assertForbidden()
            ->assertJsonPath('code', 'ai_quota_exhausted');

        Http::assertNothingSent();
        $this->assertSame(10, AiUsageLog::count());
    }

    public function test_free_web_user_gets_upgrade_modal_after_ten_entries(): void
    {
        [$owner, $business, $book] = $this->setupBook();
        $this->fakeParse();

        $page = Livewire::actingAs($owner)->test(BookShow::class, ['business' => $business, 'book' => $book]);
        for ($i = 0; $i < 10; $i++) {
            $page->set('nlpInput', 'Paid 120 for fuel today')->call('parseEntryText')
                ->assertSet('upgradeModalFeature', '')
                ->assertSet('entryAmount', '120');
        }
        $this->assertSame(10, AiUsageLog::where('type', 'nlp')->count());

        $page->set('entryAmount', '')
            ->set('nlpInput', 'Paid 120 for fuel today')->call('parseEntryText')
            ->assertSet('upgradeModalFeature', 'ai')
            ->assertSet('entryAmount', '');
        $page->set('upgradeModalFeature', '')->call('prepareScan')
            ->assertSet('upgradeModalFeature', 'ai')
            ->assertNotDispatched('open-ocr-picker');

        $this->assertSame(10, AiUsageLog::count());
    }

    public function test_free_web_user_can_scan_within_quota(): void
    {
        [$owner, $business, $book] = $this->setupBook();
        $this->fakeClaude(['type' => 'out', 'amount' => 45, 'description' => 'Supplies']);

        Livewire::actingAs($owner)->test(BookShow::class, ['business' => $business, 'book' => $book])
            ->call('prepareScan')
            ->assertDispatched('open-ocr-picker')
            ->set('ocrFile', UploadedFile::fake()->image('receipt.jpg'))
            ->assertSet('upgradeModalFeature', '')
            ->assertSet('entryAmount', '45');

        $this->assertSame(1, AiUsageLog::where('type', 'ocr')->count());
    }

    public function test_non_owner_sees_ask_the_owner_copy_when_exhausted(): void
    {
        [, $business, $book] = $this->setupBook();
        $editor = $this->makeUser();
        $this->addMember($business, $editor, 'editor');
        $this->log($editor, 'nlp', 10);

        Livewire::actingAs($editor)->test(BookShow::class, ['business' => $business, 'book' => $book])
            ->call('openAddEntry')
            ->assertSee('ask the owner to upgrade')
            ->call('prepareScan')
            ->assertSet('upgradeModalFeature', 'ai')
            ->assertSee("You've used your free AI entries");
    }

    // ── Month rollover ──────────────────────────────────────────

    public function test_month_rollover_resets_the_free_allowance(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-30 23:59:00', 'UTC'));
        [$owner, , $book] = $this->setupBook();
        $this->log($owner, 'ocr', 6);
        $this->log($owner, 'nlp', 4);
        $this->actingAsUser($owner);

        $this->getJson("/api/v1/books/{$book->id}/ai-quota")
            ->assertOk()
            ->assertJsonPath('quota.remaining', 0)
            ->assertJsonPath('quota.exhausted', true)
            ->assertJsonPath('quota.resetsAt', '2026-10-01T00:00:00+00:00')
            ->assertJsonPath('quota.resetsInDays', 1);

        Carbon::setTestNow(Carbon::parse('2026-10-01 00:00:01', 'UTC'));

        $this->getJson("/api/v1/books/{$book->id}/ai-quota")
            ->assertOk()
            ->assertJsonPath('quota.used', 0)
            ->assertJsonPath('quota.remaining', 10)
            ->assertJsonPath('quota.exhausted', false)
            ->assertJsonPath('quota.resetsAt', '2026-11-01T00:00:00+00:00');
    }

    // ── Pro unaffected ──────────────────────────────────────────

    public function test_pro_is_unaffected_by_the_free_allowance(): void
    {
        [$owner, , $book] = $this->setupBook(pro: true);
        $this->log($owner, 'ocr', 15);
        $this->log($owner, 'nlp', 5);
        $this->fakeParse();
        $this->actingAsUser($owner);

        $this->postJson("/api/v1/books/{$book->id}/parse", ['text' => 'Paid 120 for fuel today'])
            ->assertOk()
            ->assertJsonPath('quota.plan', 'pro')
            ->assertJsonPath('quota.scans.remaining', 185)
            ->assertJsonPath('quota.typed.used', 6)
            ->assertJsonPath('quota.typed.period', 'day')
            ->assertJsonPath('quota.combined', null);
    }

    public function test_pro_keeps_its_scan_and_daily_typed_caps(): void
    {
        [$owner, , $book] = $this->setupBook(pro: true);
        $this->log($owner, 'ocr', 200);
        $this->log($owner, 'nlp', 30);
        Http::fake();
        $this->actingAsUser($owner);

        $this->post("/api/v1/books/{$book->id}/scan", [
            'receipt' => UploadedFile::fake()->image('r.jpg'),
        ], ['Accept' => 'application/json'])
            ->assertStatus(429)
            ->assertJsonPath('code', 'ai_scan_limit');

        $this->postJson("/api/v1/books/{$book->id}/parse", ['text' => 'Paid 120 for fuel today'])
            ->assertStatus(429)
            ->assertJsonPath('code', 'ai_typed_daily_limit');

        Http::assertNothingSent();
    }

    public function test_pro_typed_cap_is_daily(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-22 12:00:00', 'UTC'));
        [$owner, $business] = $this->setupBook(pro: true);
        $this->log($owner, 'nlp', 30, Carbon::parse('2026-09-21 23:00:00', 'UTC'));

        $q = AiQuota::remaining($owner, $business);
        $this->assertSame(0, $q['typed']['used']);
        $this->assertSame(30, $q['typed']['remaining']);
        $this->assertSame('2026-09-23T00:00:00+00:00', $q['typed']['resetsAt']);
    }

    // ── Category suggestions are free ───────────────────────────

    public function test_suggestions_are_free_for_free_businesses_and_not_counted(): void
    {
        [$owner, $business, $book] = $this->setupBook();
        $this->log($owner, 'ocr', 10); // quota exhausted — suggestions still work
        $this->fakeClaude(['category' => 'Transport', 'confidence' => 0.9], 120, 15);
        $this->actingAsUser($owner);

        $this->postJson("/api/v1/books/{$book->id}/suggest-category", ['description' => 'Fuel for van'])
            ->assertOk()
            ->assertJsonPath('category', 'Transport');

        Livewire::actingAs($owner)->test(BookShow::class, ['business' => $business, 'book' => $book])
            ->set('entryDescription', 'Fuel for van')
            ->call('suggestCategory')
            ->assertSet('showCategoryChip', true)
            ->assertSet('aiCategorySuggestion', 'Transport');

        $q = AiQuota::remaining($owner, $business);
        $this->assertSame(10, $q['used'], 'suggestions do not use the AI entry allowance');
        $this->assertSame(2, AiUsageLog::where('type', 'categorize')->count());
    }

    // ── Roles ───────────────────────────────────────────────────

    public function test_viewer_cannot_parse_or_scan_but_can_read_quota(): void
    {
        [, $business, $book] = $this->setupBook();
        $viewer = $this->makeUser();
        $this->addMember($business, $viewer, 'viewer');
        Http::fake();
        $this->actingAsUser($viewer);

        $this->postJson("/api/v1/books/{$book->id}/parse", ['text' => 'Paid 120 for fuel'])->assertForbidden()
            ->assertJsonMissingPath('code');
        $this->post("/api/v1/books/{$book->id}/scan", [
            'receipt' => UploadedFile::fake()->image('r.jpg'),
        ], ['Accept' => 'application/json'])->assertForbidden();
        $this->getJson("/api/v1/books/{$book->id}/ai-quota")->assertOk()->assertJsonPath('quota.plan', 'free');

        Http::assertNothingSent();
        $this->assertSame(0, AiUsageLog::count());
    }

    public function test_non_member_gets_404(): void
    {
        [, , $book] = $this->setupBook();
        $this->actingAsUser($this->makeUser());

        $this->postJson("/api/v1/books/{$book->id}/parse", ['text' => 'Paid 120'])->assertNotFound();
        $this->getJson("/api/v1/books/{$book->id}/ai-quota")->assertNotFound();
    }

    // ── Parse endpoint: validation + sanitisation ───────────────

    public function test_parse_validation(): void
    {
        [$owner, , $book] = $this->setupBook();
        Http::fake();
        $this->actingAsUser($owner);

        $this->postJson("/api/v1/books/{$book->id}/parse", [])->assertStatus(422)->assertJsonValidationErrors('text');
        $this->postJson("/api/v1/books/{$book->id}/parse", ['text' => 'ab'])->assertStatus(422)->assertJsonValidationErrors('text');
        $this->postJson("/api/v1/books/{$book->id}/parse", ['text' => str_repeat('a', 301)])->assertStatus(422)->assertJsonValidationErrors('text');
        $this->postJson("/api/v1/books/{$book->id}/parse", ['text' => ['x']])->assertStatus(422)->assertJsonValidationErrors('text');

        Http::assertNothingSent();
    }

    public function test_parse_sanitises_model_output(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-22 10:00:00', 'UTC'));
        [$owner, , $book] = $this->setupBook();
        $this->fakeClaude([
            'type'         => 'sideways',               // invalid → dropped
            'amount'       => '1234.567',               // rounded to 2dp, string
            'date'         => '2030-01-01',             // future → clamped to today
            'description'  => str_repeat('x', 400),     // capped at 255
            'category'     => '  Fuel  ',
            'payment_mode' => 'Cash',
            'reference'    => 'INV-9',
            'is_admin'     => true,                     // unknown key → dropped
        ]);
        $this->actingAsUser($owner);

        $res = $this->postJson("/api/v1/books/{$book->id}/parse", ['text' => 'Paid 1234.567 for fuel'])
            ->assertOk()
            ->assertJsonPath('fields.type', null)
            ->assertJsonPath('fields.amount', '1234.57')
            ->assertJsonPath('fields.date', '2026-09-22')
            ->assertJsonPath('fields.category', 'Fuel')
            ->assertJsonPath('fields.paymentMode', 'Cash')
            ->assertJsonPath('fields.reference', 'INV-9')
            ->assertJsonPath('quota.used', 1);

        $this->assertSame(255, mb_strlen($res->json('fields.description')));
        $this->assertSame(
            ['type', 'amount', 'description', 'category', 'paymentMode', 'date', 'reference'],
            array_keys($res->json('fields'))
        );

        // The text is sent to Claude as a JSON-encoded string (prompt-injection hardening).
        Http::assertSent(fn ($req) => str_contains($req['messages'][0]['content'], '"Paid 1234.567 for fuel"')
            && str_contains($req['messages'][0]['content'], 'Book currency: USD'));

        // Parse doesn't create categories — saving the entry does.
        $this->assertSame(0, $book->categories()->count());
    }

    public function test_parse_failure_returns_422_with_quota(): void
    {
        [$owner, , $book] = $this->setupBook();
        Http::fake(['api.anthropic.com/*' => Http::response([
            'content' => [['type' => 'text', 'text' => 'null']],
            'usage'   => ['input_tokens' => 300, 'output_tokens' => 2],
        ])]);
        $this->actingAsUser($owner);

        $this->postJson("/api/v1/books/{$book->id}/parse", ['text' => 'hello there friend'])
            ->assertStatus(422)
            ->assertJsonPath('code', 'parse_failed')
            ->assertJsonPath('quota.used', 1);
    }

    public function test_usage_cost_uses_haiku_4_5_pricing(): void
    {
        [$owner, , $book] = $this->setupBook();
        $this->fakeClaude(['type' => 'out', 'amount' => 5], 2000, 100);
        $this->actingAsUser($owner);

        $this->postJson("/api/v1/books/{$book->id}/parse", ['text' => 'Paid 5 for tea'])->assertOk();

        Http::assertSent(fn ($req) => $req['model'] === 'claude-haiku-4-5-20251001');
        // $1 / MTok in, $5 / MTok out → 2000 * 1e-6 + 100 * 5e-6 = 0.0025
        $this->assertEqualsWithDelta(0.0025, (float) AiUsageLog::first()->cost_usd, 0.0000001);
        $this->assertSame($owner->id, AiUsageLog::first()->user_id);
    }

    // ── Quota object shape ──────────────────────────────────────

    public function test_quota_shape_for_free(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-22 08:00:00', 'UTC'));
        [$owner, , $book] = $this->setupBook();
        $this->log($owner, 'ocr', 2);
        $this->log($owner, 'nlp', 1);
        $this->log($owner, 'categorize', 5); // not counted
        $this->log($owner, 'ocr', 3, Carbon::parse('2026-08-31 23:59:59', 'UTC')); // last month
        $this->actingAsUser($owner);

        $this->getJson("/api/v1/books/{$book->id}/ai-quota")->assertOk()->assertExactJson(['quota' => [
            'plan'         => 'free',
            'isPro'        => false,
            'used'         => 3,
            'limit'        => 10,
            'remaining'    => 7,
            'scans'        => ['used' => 2, 'limit' => 10, 'remaining' => 7],
            'typed'        => ['used' => 1, 'limit' => 10, 'remaining' => 7, 'period' => 'month', 'resetsAt' => '2026-10-01T00:00:00+00:00'],
            'combined'     => ['used' => 3, 'limit' => 10, 'remaining' => 7],
            'resetsAt'     => '2026-10-01T00:00:00+00:00',
            'resetsInDays' => 9,
            'exhausted'    => false,
            'nearLimit'    => false,
        ]]);
    }

    public function test_quota_near_limit_and_exhausted_flags(): void
    {
        [$owner, $business] = $this->setupBook();

        $this->log($owner, 'nlp', 8);
        $q = AiQuota::remaining($owner, $business);
        $this->assertSame(2, $q['remaining']);
        $this->assertTrue($q['nearLimit']);
        $this->assertFalse($q['exhausted']);

        $this->log($owner, 'ocr', 5); // over the limit (e.g. concurrent calls) never goes negative
        $q = AiQuota::remaining($owner, $business);
        $this->assertSame(0, $q['remaining']);
        $this->assertSame(13, $q['used']);
        $this->assertTrue($q['exhausted']);

        [$pro, $proBiz] = $this->setupBook(pro: true);
        $this->log($pro, 'ocr', 160);
        $q = AiQuota::remaining($pro, $proBiz);
        $this->assertSame('pro', $q['plan']);
        $this->assertSame(40, $q['scans']['remaining']);
        $this->assertTrue($q['nearLimit']);
        $this->assertFalse($q['exhausted']);
    }

    public function test_user_quota_uses_own_plan_and_book_quota_uses_business_plan(): void
    {
        [, , $proBook] = $this->setupBook(pro: true);
        $editor = $this->makeUser(); // free user, editor in a Pro business
        $this->addMember($proBook->business, $editor, 'editor');
        $this->actingAsUser($editor);

        $this->getJson('/api/v1/ai-quota')->assertOk()->assertJsonPath('quota.plan', 'free');
        $this->getJson("/api/v1/books/{$proBook->id}/ai-quota")->assertOk()->assertJsonPath('quota.plan', 'pro');
    }

    public function test_billing_page_shows_ai_usage_card(): void
    {
        $owner = $this->makeUser();
        $this->log($owner, 'ocr', 3);

        $this->actingAs($owner)->get('/settings/billing')
            ->assertOk()
            ->assertSee('AI usage')
            ->assertSee('AI entries this month')
            ->assertSee('Get 200 scans a month with Pro');
    }
}
