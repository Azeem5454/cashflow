<?php

namespace Tests\Feature\Blog;

/**
 * Search engines have to be able to reach every post by following links.
 * Livewire's default pagination renders wire:click buttons, which Googlebot
 * cannot follow — that silently strands everything past page one.
 */
class BlogCrawlabilityTest extends BlogTestCase
{
    public function test_pagination_renders_real_anchor_links(): void
    {
        // 12 per page + 1 hero pulled out of the grid.
        for ($i = 0; $i < 15; $i++) {
            $this->makePost();
        }

        $html = $this->get('/blog')->assertOk()->getContent();

        $this->assertStringContainsString('aria-label="Blog pagination"', $html);
        $this->assertMatchesRegularExpression(
            '#<a[^>]+href="[^"]*page=2"#',
            $html,
            'Page 2 must be reachable through a real href, not a wire:click button.'
        );
        $this->assertStringContainsString('rel="next"', $html);
    }

    public function test_page_two_links_back_and_is_canonical_to_itself(): void
    {
        for ($i = 0; $i < 15; $i++) {
            $this->makePost();
        }

        $html = $this->get('/blog?page=2')->assertOk()->getContent();

        $this->assertStringContainsString('rel="prev"', $html);
        $this->assertStringContainsString('<link rel="canonical" href="' . route('blog.index') . '?page=2">', $html);
    }

    public function test_listing_pages_carry_website_and_breadcrumb_schema(): void
    {
        $this->makePost();

        $html = $this->get('/blog')->assertOk()->getContent();

        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);
        $types = collect($m[1])->map(fn ($j) => json_decode($j, true))->pluck('@type');

        $this->assertTrue($types->contains('WebSite'), 'Listing pages should declare the site.');
        $this->assertFalse($types->contains('BlogPosting'), 'A listing page is not a single article.');
    }

    public function test_category_pages_carry_a_two_step_breadcrumb(): void
    {
        $category = $this->category('growing-your-business');
        $this->makePost(['category_id' => $category->id]);

        $html = $this->get('/blog/category/' . $category->slug)->assertOk()->getContent();

        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);
        $crumbs = collect($m[1])
            ->map(fn ($j) => json_decode($j, true))
            ->firstWhere('@type', 'BreadcrumbList');

        $this->assertNotNull($crumbs);
        $this->assertCount(2, $crumbs['itemListElement']);
        $this->assertSame('Blog', $crumbs['itemListElement'][0]['name']);
        $this->assertSame($category->name, $crumbs['itemListElement'][1]['name']);
    }

    public function test_the_page_title_stays_within_the_serp_budget(): void
    {
        $post = $this->makePost([
            'title'     => 'How to Track Business Expenses Without a Spreadsheet',
            'slug'      => 'track-business-expenses',
            'seo_title' => 'How to Track Business Expenses Without a Spreadsheet',
        ]);

        $html = $this->get($post->url())->assertOk()->getContent();

        preg_match('#<title>(.*?)</title>#s', $html, $m);
        $this->assertLessThanOrEqual(60, mb_strlen(html_entity_decode($m[1])));
    }

    public function test_short_titles_still_get_the_brand_suffix(): void
    {
        $post = $this->makePost(['title' => 'Cash Flow Basics', 'slug' => 'cash-flow-basics', 'seo_title' => 'Cash Flow Basics']);

        $html = $this->get($post->url())->assertOk()->getContent();

        preg_match('#<title>(.*?)</title>#s', $html, $m);
        $this->assertStringContainsString(config('app.name'), html_entity_decode($m[1]));
    }

    public function test_below_the_fold_images_are_lazy_loaded(): void
    {
        for ($i = 0; $i < 4; $i++) {
            $post = $this->makePost();
            $key  = 'blog-post-' . $post->id . '-featured';
            \App\Models\UploadedAsset::put($key, 'fake-png-bytes', 'image/png');
            $post->update(['featured_image_key' => $key]);
        }

        $html = $this->get('/blog')->assertOk()->getContent();

        $this->assertStringContainsString('loading="lazy"', $html);
        // The hero stays eager — lazy-loading the LCP image slows the page.
        $this->assertStringContainsString('fetchpriority="high"', $html);
        $this->assertStringContainsString('width="1200" height="630"', $html);
    }
}
