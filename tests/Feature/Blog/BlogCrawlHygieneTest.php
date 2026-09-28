<?php

namespace Tests\Feature\Blog;

use App\Models\User;

/**
 * Search Console flagged a soft 404 and a duplicate homepage. Both were
 * invisible locally because they only show up in how a crawler reads the
 * response, not in whether the page renders.
 */
class BlogCrawlHygieneTest extends BlogTestCase
{
    private function author(): User
    {
        $user = User::factory()->create(['name' => 'Azeem Amin']);
        $user->forceFill([
            'author_slug' => 'azeem-amin',
            'author_bio'  => 'Builds TheCashFox.',
        ])->save();

        return $user;
    }

    public function test_a_page_past_the_last_blog_page_is_a_404(): void
    {
        $this->makePost();

        // An empty 200 here reads as a soft 404 and counts against the
        // whole /blog section, not just the one URL.
        $this->get('/blog?page=99')->assertNotFound();
    }

    public function test_an_empty_blog_still_serves_page_one(): void
    {
        // No posts at all is a legitimate 200, not a broken URL.
        $this->get('/blog')->assertOk();
        $this->get('/blog?page=1')->assertOk();
    }

    public function test_a_page_past_the_last_author_page_is_a_404(): void
    {
        $author = $this->author();
        $this->makePost(['slug' => 'theirs', 'author_id' => $author->id]);

        $this->get('/blog/author/azeem-amin')->assertOk();
        $this->get('/blog/author/azeem-amin?page=99')->assertNotFound();
    }

    public function test_deeper_blog_pages_canonical_to_themselves(): void
    {
        // 13 posts so page 2 exists (12 per page, and the hero is pulled
        // out of the grid once there are 4+).
        for ($i = 0; $i < 15; $i++) {
            $this->makePost();
        }

        $html = $this->get('/blog?page=2')->assertOk()->getContent();

        // Pointing page 2 back at page 1 marks it duplicate, and the posts
        // only reachable from it stop being crawled.
        preg_match('#<link rel="canonical" href="([^"]+)">#', $html, $m);
        $this->assertNotEmpty($m, 'Every blog page needs a canonical');
        $this->assertStringEndsWith('/blog?page=2', $m[1]);
    }

    public function test_the_front_controller_path_redirects_to_the_homepage(): void
    {
        $this->get('/index.php')->assertRedirect('/')->assertStatus(301);
    }
}
