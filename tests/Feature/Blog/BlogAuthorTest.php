<?php

namespace Tests\Feature\Blog;

use App\Helpers\Setting;
use App\Models\BlogPost;
use App\Models\User;
use App\Services\BlogAutopilot;

/**
 * Posts attributed to an organisation give Google nothing to assess. A named
 * author with a stated background, linked from every byline, is what the
 * helpful-content guidance asks for.
 */
class BlogAuthorTest extends BlogTestCase
{
    private function author(array $attrs = []): User
    {
        $user = User::factory()->create(['name' => 'Azeem Amin']);
        $user->forceFill(array_merge([
            'author_slug' => 'azeem-amin',
            'author_bio'  => 'Builds TheCashFox. Writes about the money admin small businesses actually face.',
            'author_role' => 'Founder',
        ], $attrs))->save();

        return $user;
    }

    public function test_the_author_page_lists_their_posts(): void
    {
        $author = $this->author();
        $mine = $this->makePost(['title' => 'Mine', 'slug' => 'mine', 'author_id' => $author->id]);
        $this->makePost(['title' => 'Someone elses', 'slug' => 'theirs']);

        $this->get('/blog/author/azeem-amin')
            ->assertOk()
            ->assertSee('Azeem Amin')
            ->assertSee('Founder')
            ->assertSee($mine->title)
            ->assertDontSee('Someone elses');
    }

    public function test_it_emits_person_and_profilepage_schema(): void
    {
        $this->author();

        $html = $this->get('/blog/author/azeem-amin')->assertOk()->getContent();

        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);
        $blocks = collect($m[1])->map(fn ($j) => json_decode($j, true));

        $profile = $blocks->firstWhere('@type', 'ProfilePage');
        $this->assertNotNull($profile, 'Author pages need ProfilePage schema');
        $this->assertSame('Person', $profile['mainEntity']['@type']);
        $this->assertSame('Azeem Amin', $profile['mainEntity']['name']);
        $this->assertSame('Founder', $profile['mainEntity']['jobTitle']);

        // A profile page is not the site root.
        $this->assertFalse($blocks->contains(fn ($b) => ($b['@type'] ?? null) === 'WebSite'));
    }

    public function test_a_post_byline_links_to_the_profile_and_names_it_in_schema(): void
    {
        $author = $this->author();
        $post = $this->makePost(['slug' => 'with-author', 'author_id' => $author->id]);

        $html = $this->get($post->url())->assertOk()->getContent();

        $this->assertStringContainsString('href="' . route('blog.author', 'azeem-amin') . '"', $html);
        $this->assertStringContainsString('rel="author"', $html);

        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);
        $article = collect($m[1])->map(fn ($j) => json_decode($j, true))->firstWhere('@type', 'BlogPosting');

        $this->assertSame('Person', $article['author']['@type']);
        $this->assertSame('Azeem Amin', $article['author']['name']);
        $this->assertSame(route('blog.author', 'azeem-amin'), $article['author']['url']);
    }

    public function test_a_user_without_a_bio_has_no_public_page(): void
    {
        // An admin account is not automatically a published author.
        $this->author(['author_bio' => null]);

        $this->get('/blog/author/azeem-amin')->assertNotFound();
    }

    public function test_an_unknown_slug_is_a_404(): void
    {
        $this->get('/blog/author/nobody')->assertNotFound();
    }

    public function test_autopilot_attributes_posts_to_the_configured_author(): void
    {
        $author = $this->author();

        $this->assertNull(BlogAutopilot::defaultAuthorId(), 'No setting means no attribution');

        Setting::set('blog.author_id', $author->id);
        $this->assertSame($author->id, BlogAutopilot::defaultAuthorId());

        // A stale id must not break generation.
        Setting::set('blog.author_id', '019cedd4-0000-0000-0000-000000000000');
        $this->assertNull(BlogAutopilot::defaultAuthorId());
    }

    public function test_the_byline_stays_plain_text_when_there_is_no_profile(): void
    {
        $user = User::factory()->create(['name' => 'Ghost Writer']);
        $post = $this->makePost(['slug' => 'no-profile', 'author_id' => $user->id]);

        $html = $this->get($post->url())->assertOk()->getContent();

        $this->assertStringContainsString('Ghost Writer', $html);
        $this->assertStringNotContainsString('rel="author"', $html);

        $this->assertSame(
            1,
            BlogPost::where('slug', 'no-profile')->count()
        );
    }
}
