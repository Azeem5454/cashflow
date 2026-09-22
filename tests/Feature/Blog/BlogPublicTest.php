<?php

namespace Tests\Feature\Blog;

use App\Livewire\Admin\Blog\Index as AdminBlogIndex;
use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

class BlogPublicTest extends BlogTestCase
{
    private function fourPosts(): array
    {
        $old = $this->makePost(['title' => 'April guide', 'slug' => 'april-guide', 'published_at' => now()->subMonths(5)]);
        $b   = $this->makePost(['title' => 'WhatsApp post', 'slug' => 'whatsapp-post', 'published_at' => now()->subMonths(5)->addHour()]);
        $c   = $this->makePost(['title' => 'June post', 'slug' => 'june-post', 'published_at' => now()->subMonths(3)]);
        $new = $this->makePost(['title' => 'Excel vs cash book', 'slug' => 'excel-vs-cash-book', 'published_at' => now()->subHours(2)]);

        return [$old, $b, $c, $new];
    }

    public function test_hero_is_newest_post_and_legacy_featured_flag_without_featured_at_is_ignored(): void
    {
        [$old, , , $new] = $this->fourPosts();

        // The production situation: an April post flagged is_featured with no
        // featured_at (pre-migration row).
        BlogPost::whereKey($old->id)->update(['is_featured' => true, 'featured_at' => null]);

        $this->assertSame($new->id, BlogPost::heroPost()->id);

        $this->get('/blog')
            ->assertOk()
            ->assertSeeInOrder(['Latest', 'Excel vs cash book', 'June post', 'WhatsApp post', 'April guide']);
    }

    public function test_recent_pin_overrides_newest_but_expires_after_14_days(): void
    {
        [$old, , , $new] = $this->fourPosts();

        BlogPost::whereKey($old->id)->update(['is_featured' => true, 'featured_at' => now()->subDays(3)]);
        $this->assertSame($old->id, BlogPost::heroPost()->id);
        $this->get('/blog')->assertOk()->assertSee('★ Featured');

        BlogPost::whereKey($old->id)->update(['featured_at' => now()->subDays(BlogPost::FEATURE_PIN_DAYS + 1)]);
        $this->assertSame($new->id, BlogPost::heroPost()->id);
    }

    public function test_admin_feature_toggle_sets_featured_at_and_does_not_touch_updated_at(): void
    {
        [$old] = $this->fourPosts();
        $admin = User::factory()->create();
        $admin->is_admin = true;
        $admin->save();

        $before = $old->fresh()->updated_at;
        $this->travel(5)->minutes();

        Livewire::actingAs($admin)->test(AdminBlogIndex::class)->call('toggleFeatured', $old->id);

        $fresh = $old->fresh();
        $this->assertTrue($fresh->is_featured);
        $this->assertNotNull($fresh->featured_at);
        $this->assertTrue($fresh->hasActivePin());
        $this->assertEquals($before, $fresh->updated_at);

        Livewire::actingAs($admin)->test(AdminBlogIndex::class)->call('toggleFeatured', $old->id);
        $this->assertFalse($old->fresh()->is_featured);
        $this->assertNull($old->fresh()->featured_at);
    }

    public function test_hero_is_not_repeated_on_page_two_and_is_excluded_from_grid(): void
    {
        for ($i = 1; $i <= 14; $i++) {
            $this->makePost(['title' => "Paged post {$i}", 'slug' => "paged-post-{$i}", 'published_at' => now()->subDays(30 - $i)]);
        }

        // Newest = "Paged post 14" → hero on page 1; page-1 grid = posts 13..2; page 2 = post 1.
        $this->get('/blog')->assertOk()->assertSee('Latest')->assertSee('Paged post 14')->assertSee('Paged post 2');
        $page2 = $this->get('/blog?page=2')->assertOk();
        $page2->assertSee('Paged post 1')->assertDontSee('Paged post 14')->assertDontSee('Latest');
        $page2->assertSee('<link rel="canonical" href="' . route('blog.index') . '?page=2">', false);
    }

    public function test_view_counting_skips_bots_dedupes_per_session_and_never_touches_updated_at(): void
    {
        $post = $this->makePost(['slug' => 'counted-post']);
        $updatedAt = $post->fresh()->updated_at;
        $this->travel(1)->hours();

        $browser = ['User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_0) AppleWebKit/605.1.15 Safari/605.1.15'];

        $this->withHeaders($browser)->get('/blog/counted-post')->assertOk();
        $this->withHeaders($browser)->get('/blog/counted-post')->assertOk(); // refresh, same session

        $this->assertSame(1, (int) $post->fresh()->view_count);
        $this->assertEquals($updatedAt, $post->fresh()->updated_at);

        foreach ([
            'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
            'facebookexternalhit/1.1',
            'WhatsApp/2.23',
            'curl/8.4.0',
            '',
        ] as $ua) {
            $this->flushSession();
            $this->withHeaders(['User-Agent' => $ua])->get('/blog/counted-post')->assertOk();
        }
        $this->assertSame(1, (int) $post->fresh()->view_count);

        // New session, real browser → counts again
        $this->flushSession();
        $this->withHeaders($browser)->get('/blog/counted-post')->assertOk();
        $this->assertSame(2, (int) $post->fresh()->view_count);
        $this->assertEquals($updatedAt, $post->fresh()->updated_at);
    }

    public function test_post_page_has_valid_article_json_ld_and_seo_meta(): void
    {
        $post = $this->makePost([
            'title'           => 'Cash "flow" </script> basics',
            'slug'            => 'cash-flow-basics',
            'seo_title'       => 'Cash flow basics for owners',
            'seo_description' => null,
            'excerpt'         => 'A short hook about cash flow.',
            'published_at'    => Carbon::parse('2026-09-22 09:00:21', 'UTC'),
        ]);

        $html = $this->get('/blog/cash-flow-basics')->assertOk()->getContent();

        $this->assertStringContainsString('<title>Cash flow basics for owners — ', $html);
        $this->assertStringContainsString('<meta name="description" content="A short hook about cash flow.">', $html);
        $this->assertStringContainsString('<link rel="canonical" href="' . $post->url() . '">', $html);
        $this->assertStringContainsString('href="' . route('register') . '"', $html);

        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);
        $this->assertCount(1, $m[1]);
        $schema = json_decode($m[1][0], true);
        $this->assertIsArray($schema, 'JSON-LD must be valid JSON');
        $this->assertSame('BlogPosting', $schema['@type']);
        $this->assertSame('https://schema.org', $schema['@context']);
        $this->assertSame('Cash "flow" </script> basics', $schema['headline']);
        $this->assertSame('2026-09-22T09:00:21+00:00', $schema['datePublished']);
        $this->assertArrayHasKey('dateModified', $schema);
        $this->assertSame('Organization', $schema['author']['@type']); // autopilot posts: no human author
        $this->assertSame('Organization', $schema['publisher']['@type']);
        $this->assertNotEmpty($schema['publisher']['logo']['url']);
    }

    public function test_sitemap_lists_blog_posts_and_categories_with_real_lastmod(): void
    {
        $this->travelTo(Carbon::parse('2026-06-10 12:00:00', 'UTC'));
        $a = $this->makePost(['slug' => 'first-post', 'category_id' => $this->category('growing-your-business')->id]);
        $this->travelTo(Carbon::parse('2026-09-22 09:00:00', 'UTC'));
        $b = $this->makePost(['slug' => 'second-post']);
        $this->makePost(['slug' => 'draft-post', 'status' => 'draft']);

        // A later view must not change any lastmod.
        $this->travelTo(Carbon::parse('2026-09-23 10:00:00', 'UTC'));
        BlogPost::recordView($a->id);

        $xml = $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->getContent();

        $doc = simplexml_load_string($xml);
        $this->assertNotFalse($doc);

        $urls = [];
        foreach ($doc->url as $u) {
            $urls[(string) $u->loc] = isset($u->lastmod) ? (string) $u->lastmod : null;
        }
        $base = 'https://thecashfox.test';

        $this->assertArrayHasKey($base . '/blog', $urls);
        $this->assertStringStartsWith('2026-09-22', $urls[$base . '/blog']);
        $this->assertStringStartsWith('2026-06-10', $urls[$base . '/blog/first-post']);
        $this->assertStringStartsWith('2026-09-22', $urls[$base . '/blog/second-post']);
        $this->assertArrayNotHasKey($base . '/blog/draft-post', $urls);
        $this->assertStringStartsWith('2026-06-10', $urls[$base . '/blog/category/growing-your-business']);
        $this->assertArrayHasKey($base . '/blog/category/bookkeeping-101', $urls);
        $this->assertArrayNotHasKey($base . '/blog/category/product-updates', $urls);
        $this->assertNull($urls[$base . '/terms']); // no fake "today" lastmod

        $this->assertStringContainsString('Allow: /blog', file_get_contents(public_path('robots.txt')));
    }

    public function test_reading_time_and_category_post_count_stay_in_sync(): void
    {
        $post = $this->makePost(['body_markdown' => $this->body(1000)]);
        $this->assertSame((int) ceil(BlogPost::wordCount($post->body_markdown) / 230), $post->reading_time);
        $this->assertSame(1, $this->category()->fresh()->post_count);

        // Moving it to another category updates both counts.
        $post->category_id = $this->category('growing-your-business')->id;
        $post->save();
        $this->assertSame(0, $this->category()->fresh()->post_count);
        $this->assertSame(1, $this->category('growing-your-business')->fresh()->post_count);

        $post->update(['status' => 'draft']);
        $this->assertSame(0, $this->category('growing-your-business')->fresh()->post_count);
    }
}
