<?php

namespace Tests\Feature\Blog;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

abstract class BlogTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Never hit the real Anthropic (or any external) API from tests.
        Http::preventStrayRequests();
        config(['services.anthropic.key' => 'test-key', 'app.url' => 'https://thecashfox.test']);
    }

    protected function category(string $slug = 'bookkeeping-101'): BlogCategory
    {
        return BlogCategory::where('slug', $slug)->firstOrFail();
    }

    protected function makePost(array $attrs = []): BlogPost
    {
        static $n = 0;
        $n++;

        return BlogPost::create(array_merge([
            'title'         => "Post number {$n}",
            'slug'          => "post-number-{$n}",
            'excerpt'       => "Excerpt {$n}",
            'body_markdown' => str_repeat('Cash flow words here. ', 50),
            'category_id'   => $this->category()->id,
            'status'        => 'published',
        ], $attrs));
    }

    /** A markdown body with roughly $words reader-visible words. */
    protected function body(int $words): string
    {
        $paragraph = 'Track every payment the day it happens so the balance stays honest and simple to read.'; // 16 words
        $parts = ['## Why it matters'];
        $count = 3;
        while ($count < $words) {
            $parts[] = $paragraph;
            $count += 16;
        }
        $parts[] = 'Ready to start? [Sign up free](https://thecashfox.com/register).';

        return implode("\n\n", $parts);
    }
}
