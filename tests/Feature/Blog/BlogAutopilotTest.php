<?php

namespace Tests\Feature\Blog;

use App\Exceptions\BlogAutopilotSkipped;
use App\Helpers\Setting;
use App\Models\AiUsageLog;
use App\Models\BlogAutopilotQueueItem;
use App\Models\BlogPost;
use App\Models\User;
use App\Services\BlogAutopilot;
use App\Services\BlogImageRenderer;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class BlogAutopilotTest extends BlogTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(BlogImageRenderer::class, function ($m) {
            $m->shouldReceive('renderForPost')->andReturnUsing(fn ($id) => "blog-post-{$id}-featured");
        });

        Setting::set('blog_autopilot.enabled', '1');
        $this->travelTo(Carbon::parse('2026-09-22 09:00:00', 'UTC'));
    }

    private function claudeReply(array $overrides = [], int $words = 1300): array
    {
        $payload = array_merge([
            'title'           => 'Excel vs. Cash Book App',
            'slug'            => 'excel-vs-cash-book-app',
            'category_slug'   => 'bookkeeping-101',
            'excerpt'         => 'Spreadsheets cost more than you think.',
            'body_markdown'   => $this->body($words),
            'seo_title'       => 'Excel vs Cash Book App',
            'seo_description' => 'Compare spreadsheets and a cash book app.',
        ], $overrides);

        return [
            'content'     => [['type' => 'text', 'text' => json_encode($payload)]],
            'stop_reason' => 'end_turn',
            'usage'       => ['input_tokens' => 3000, 'output_tokens' => 2500],
        ];
    }

    private function queue(string $title, int $position = 10): BlogAutopilotQueueItem
    {
        return BlogAutopilotQueueItem::create(['title' => $title, 'position' => $position]);
    }

    public function test_publishes_post_with_todays_date_and_current_facts_in_prompt(): void
    {
        $this->queue('Excel vs. Cash Book App: Which Works for Small Business?');
        $this->queue('Second title', 20);
        $this->queue('Third title', 30);
        $this->queue('Fourth title', 40);
        Http::fake(['api.anthropic.com/*' => Http::response($this->claudeReply())]);

        $post = app(BlogAutopilot::class)->run();

        $this->assertSame('published', $post->status);
        $this->assertSame('excel-vs-cash-book-app', $post->slug);
        $this->assertSame('bookkeeping-101', $post->category->slug);
        $this->assertNotNull($post->featured_image_key);
        $this->assertSame((int) ceil(BlogPost::wordCount($post->body_markdown) / 230), $post->reading_time);
        $this->assertSame(3, BlogAutopilotQueueItem::count());

        Http::assertSentCount(1);
        Http::assertSent(function (Request $req) {
            $prompt = $req['messages'][0]['content'][0]['text'];

            return str_contains($prompt, "Today's date is Tuesday, 22 September 2026")
                && str_contains($prompt, 'The current year is 2026')
                && str_contains($prompt, 'Do NOT cite or invent statistics')
                && str_contains($prompt, '$5/month')
                && str_contains($prompt, 'daily, weekly or every 2 weeks')
                && str_contains($prompt, 'NOT available yet')
                && ! str_contains($prompt, '{pro_price}')
                && ! preg_match('/PKR|rupee/i', $prompt);
        });

        $log = AiUsageLog::where('type', 'blog_autopilot')->sole();
        $this->assertNull($log->user_id);
        $this->assertEquals(0.0155, (float) $log->cost_usd); // 3000×$1/M + 2500×$5/M
    }

    public function test_short_body_gets_one_expansion_retry_then_publishes(): void
    {
        $this->queue('Cash flow basics for freelancers');
        Http::fake(['api.anthropic.com/*' => Http::sequence()
            ->push($this->claudeReply([], 820))
            ->push($this->claudeReply([], 1350)),
        ]);

        $post = app(BlogAutopilot::class)->run();

        $this->assertGreaterThanOrEqual(BlogAutopilot::MIN_WORDS, BlogPost::wordCount($post->body_markdown));
        Http::assertSentCount(2);
        $recorded = Http::recorded();
        $followUp = $recorded[1][0]['messages'];
        $this->assertCount(3, $followUp);
        $this->assertSame('assistant', $followUp[1]['role']);
        $this->assertStringContainsString('minimum is 1000 words', $followUp[2]['content'][0]['text']);
        $this->assertSame(2, AiUsageLog::where('type', 'blog_autopilot')->count());
    }

    public function test_still_too_short_after_retry_fails_keeps_queue_row_and_records_error(): void
    {
        $item = $this->queue('Cash flow basics for freelancers');
        Http::fake(['api.anthropic.com/*' => Http::sequence()
            ->push($this->claudeReply([], 820))
            ->push($this->claudeReply([], 900)),
        ]);

        try {
            app(BlogAutopilot::class)->run();
            $this->fail('Expected failure');
        } catch (\RuntimeException $e) {
            $this->assertNotInstanceOf(BlogAutopilotSkipped::class, $e);
            $this->assertStringContainsString('Body too short', $e->getMessage());
        }

        $this->assertSame(0, BlogPost::count());
        $this->assertTrue(BlogAutopilotQueueItem::whereKey($item->id)->exists());
        $this->assertStringContainsString('Body too short', (string) Setting::get('blog_autopilot.last_error'));

        $this->artisan('blog:generate')->assertFailed();
    }

    public function test_queue_title_already_published_is_dropped_case_insensitively(): void
    {
        $this->makePost(['title' => 'Excel vs. Cash Book App', 'slug' => 'excel-existing', 'published_at' => now()->subMonth()]);
        $this->queue('EXCEL VS. CASH BOOK APP', 10);
        $this->queue('How to price freelance work', 20);

        Http::fake(['api.anthropic.com/*' => Http::response($this->claudeReply([
            'title' => 'How to Price Freelance Work',
            'slug'  => 'price-freelance-work',
        ]))]);

        $post = app(BlogAutopilot::class)->run();

        $this->assertSame('How to Price Freelance Work', $post->title);
        $this->assertSame(0, BlogAutopilotQueueItem::count());
        Http::assertSent(fn (Request $r) => str_contains($r['messages'][0]['content'][0]['text'], 'How to price freelance work'));
    }

    public function test_generated_title_matching_existing_post_falls_back_to_seed_title(): void
    {
        $this->makePost(['title' => 'Excel vs. Cash Book App', 'slug' => 'excel-existing', 'published_at' => now()->subMonth()]);
        $this->queue('Spreadsheets vs apps for small shops');
        Http::fake(['api.anthropic.com/*' => Http::response($this->claudeReply())]); // returns the duplicate title

        $post = app(BlogAutopilot::class)->run();

        $this->assertSame('Spreadsheets vs apps for small shops', $post->title);
        $this->assertNotSame('excel-existing', $post->slug);
    }

    public function test_cooldown_prevents_second_post_within_20_hours(): void
    {
        $this->queue('First title');
        $this->queue('Second title', 20);
        Http::fake(['api.anthropic.com/*' => Http::response($this->claudeReply())]);

        app(BlogAutopilot::class)->run();

        $this->travel(2)->hours();
        $this->expectException(BlogAutopilotSkipped::class);
        app(BlogAutopilot::class)->run();
    }

    public function test_empty_queue_is_a_clean_skip_and_emails_admins_once_a_day(): void
    {
        Mail::fake();
        $admin = User::factory()->create();
        $admin->is_admin = true;
        $admin->save();
        Http::fake();

        $this->artisan('blog:generate')->assertSuccessful();
        $this->artisan('blog:generate')->assertSuccessful();

        Http::assertNothingSent();
        $this->assertNull(Setting::get('blog_autopilot.last_error'));
        $this->assertSame(now()->toDateString(), Setting::get('blog_autopilot.low_queue_notified_on'));
    }

    public function test_low_queue_after_publishing_triggers_notification(): void
    {
        Mail::fake();
        $admin = User::factory()->create();
        $admin->is_admin = true;
        $admin->save();

        $this->queue('First title');
        $this->queue('Second title', 20);
        Http::fake(['api.anthropic.com/*' => Http::response($this->claudeReply())]);

        app(BlogAutopilot::class)->run();

        $this->assertSame(1, BlogAutopilotQueueItem::count());
        $this->assertSame(now()->toDateString(), Setting::get('blog_autopilot.low_queue_notified_on'));
    }

    public function test_api_error_is_recorded_and_command_fails(): void
    {
        $this->queue('First title');
        Http::fake(['api.anthropic.com/*' => Http::response(['error' => 'overloaded'], 529)]);

        $this->artisan('blog:generate')->assertFailed();

        $this->assertSame(1, BlogAutopilotQueueItem::count());
        $this->assertStringContainsString('HTTP 529', (string) Setting::get('blog_autopilot.last_error'));
    }

    public function test_custom_brief_placeholder_is_filled_and_core_facts_always_appended(): void
    {
        Setting::set('blog_autopilot.product_brief', 'Old brief: Pro is {pro_price}/month. Recurring: monthly, yearly.');
        $this->queue('First title');
        Http::fake(['api.anthropic.com/*' => Http::response($this->claudeReply())]);

        app(BlogAutopilot::class)->run();

        Http::assertSent(function (Request $r) {
            $prompt = $r['messages'][0]['content'][0]['text'];

            return str_contains($prompt, 'Pro is $5/month')
                && str_contains($prompt, 'Always true (these override anything above if they conflict)')
                && str_contains($prompt, 'never say monthly or yearly');
        });
    }

    public function test_default_brief_has_no_stale_claims(): void
    {
        $brief = BlogAutopilot::productBrief();

        $this->assertStringContainsString('$5/month', $brief);
        $this->assertStringContainsString('every 2 weeks', $brief);
        $this->assertStringContainsString('200 scans per month', $brief);
        $this->assertStringContainsString('coming soon', $brief);
        $this->assertDoesNotMatchRegularExpression('/PKR|rupee|daily\/weekly\/monthly\/yearly|backed up automatically|30-day projection|Offline-capable/i', $brief);
    }

    public function test_admin_pages_render_error_and_low_queue_banners_and_reject_duplicate_titles(): void
    {
        $admin = User::factory()->create();
        $admin->is_admin = true;
        $admin->save();

        $post = $this->makePost(['title' => 'Already Live Title', 'slug' => 'already-live']);
        Setting::set('blog_autopilot.last_error', 'Body too short (820 < 1000 words). @ 2026-09-22T09:00:00+00:00');

        \Livewire\Livewire::actingAs($admin)->test(\App\Livewire\Admin\Blog\Autopilot::class)
            ->assertSee('Last autopilot run failed')
            ->assertSee('The queue is empty')
            ->set('newTitle', 'already live title')
            ->call('addSingle')
            ->assertHasErrors('newTitle')
            ->set('bulkTitles', "Already Live Title\nA brand new topic title")
            ->call('addBulk')
            ->call('clearRunError');

        $this->assertSame(['A brand new topic title'], BlogAutopilotQueueItem::pluck('title')->all());
        $this->assertNull(Setting::get('blog_autopilot.last_error'));

        \Livewire\Livewire::actingAs($admin)->test(\App\Livewire\Admin\Blog\Edit::class, ['id' => $post->id])
            ->assertSee('Pin as blog hero')
            ->set('is_featured', true)
            ->call('save');
        $this->assertTrue($post->fresh()->hasActivePin());
    }
}
