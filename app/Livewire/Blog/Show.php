<?php

namespace App\Livewire\Blog;

use App\Models\BlogPost;
use Livewire\Component;

class Show extends Component
{
    public BlogPost $post;

    public function mount(string $slug): void
    {
        $this->post = BlogPost::published()
            ->where('slug', $slug)
            ->with(['category', 'author'])
            ->firstOrFail();

        // Best-effort view counter (real numbers come from GA4):
        //  - GET only, skip crawlers / link-preview bots / scripts / admins
        //  - once per post per session, so refreshes don't inflate it
        //  - never touches updated_at (see BlogPost::recordView)
        if (request()->isMethod('get') && ! $this->looksLikeBot() && ! auth()->user()?->is_admin) {
            $seen = (array) session('blog_viewed', []);
            if (! in_array($this->post->id, $seen, true)) {
                BlogPost::recordView($this->post->id);
                $seen[] = $this->post->id;
                session(['blog_viewed' => array_slice($seen, -200)]);
            }
        }
    }

    /** Crawlers, link unfurlers, monitors and scripted clients. */
    public const BOT_UA_NEEDLES = [
        'bot', 'crawl', 'spider', 'slurp', 'mediapartners', 'facebookexternalhit',
        'facebookcatalog', 'embedly', 'quora link preview', 'whatsapp', 'telegram',
        'skypeuripreview', 'discord', 'preview', 'headless', 'phantomjs', 'lighthouse',
        'pagespeed', 'pingdom', 'uptime', 'monitor', 'statuscake', 'curl', 'wget',
        'python', 'go-http-client', 'java/', 'okhttp', 'axios', 'node-fetch',
        'libwww', 'httpclient', 'guzzle', 'scrapy', 'feedfetcher', 'rss',
    ];

    private function looksLikeBot(): bool
    {
        $ua = strtolower(trim((string) request()->header('User-Agent', '')));
        if ($ua === '') {
            return true;
        }
        foreach (self::BOT_UA_NEEDLES as $needle) {
            if (str_contains($ua, $needle)) return true;
        }
        return false;
    }

    public function render()
    {
        $related = $this->post->category_id
            ? BlogPost::published()
                ->where('category_id', $this->post->category_id)
                ->where('id', '!=', $this->post->id)
                ->latestFirst()
                ->limit(3)
                ->with('category')
                ->get()
            : collect();

        return view('livewire.blog.show', [
            'related' => $related,
        ])->layout('layouts.blog', [
            'pageTitle'       => $this->post->seoTitle(),
            'pageDescription' => $this->post->seoDescription(),
            'canonical'       => $this->post->url(),
            'ogType'          => 'article',
            'ogImage'         => $this->post->featuredImageUrl(),
            'ogImageAlt'      => $this->post->featured_image_alt ?: $this->post->title,
            'articleMeta'     => [
                'published_time' => $this->post->published_at?->toIso8601String(),
                'modified_time'  => $this->post->updated_at?->toIso8601String(),
                'author'         => $this->post->author?->name,
                'section'        => $this->post->category?->name,
            ],
            'postForSchema'   => $this->post,
        ]);
    }
}
