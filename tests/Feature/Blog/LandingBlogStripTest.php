<?php

namespace Tests\Feature\Blog;

/**
 * The homepage carries more weight than any other page on the domain, and
 * for a while it linked to the blog exactly once, from the footer. New posts
 * inherited almost nothing and Googlebot had no reason to come back daily.
 */
class LandingBlogStripTest extends BlogTestCase
{
    public function test_the_landing_page_links_to_the_newest_posts(): void
    {
        $old = $this->makePost(['title' => 'Oldest post', 'slug' => 'oldest']);
        $old->forceFill(['published_at' => now()->subMonth()])->save();

        $recent = collect(range(1, 3))->map(fn ($i) => $this->makePost([
            'title' => "Recent post {$i}",
            'slug'  => "recent-{$i}",
        ]));

        $html = $this->get('/')->assertOk()->getContent();

        foreach ($recent as $post) {
            $this->assertStringContainsString('/blog/' . $post->slug, $html);
        }

        // Only three slots, so the month-old post stays off.
        $this->assertStringNotContainsString('/blog/oldest', $html);
    }

    public function test_the_landing_page_renders_with_no_posts_at_all(): void
    {
        // An empty blog hides the strip rather than showing an empty heading,
        // and must never take the landing page down with it.
        $this->get('/')
            ->assertOk()
            ->assertDontSee('From the blog', false)
            ->assertSee('Get started free', false);
    }

    public function test_the_organisation_schema_disambiguates_the_brand(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);
        $blocks = collect($m[1])->map(fn ($j) => json_decode($j, true));

        // A JSON-LD block that fails to parse is worth nothing, and Blade's
        // directive scanner has silently eaten these before.
        $this->assertFalse(
            $blocks->contains(null),
            'Every JSON-LD block on the landing page must be valid JSON'
        );

        $org = $blocks->firstWhere('@type', 'Organization');
        $this->assertNotNull($org);

        // "CashFox" is shared with a rewards app, a budgeting app and an AI
        // tool. Without these, Google has nothing to tell the entities apart.
        $this->assertSame('CashFox', $org['alternateName']);
        $this->assertNotEmpty($org['description']);
        $this->assertStringContainsString('cash book', strtolower($org['description']));
    }
}
