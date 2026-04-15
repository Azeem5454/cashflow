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

        // Best-effort view counter. Skips bot-like requests (no referer + HEAD etc.)
        // and intentionally doesn't deduplicate per-user — this is a rough signal,
        // not an analytics system. Real numbers come from GA4.
        if (request()->isMethod('get') && ! $this->looksLikeBot()) {
            // Increment via raw update to avoid touching updated_at.
            BlogPost::where('id', $this->post->id)->increment('view_count');
        }
    }

    private function looksLikeBot(): bool
    {
        $ua = strtolower((string) request()->header('User-Agent', ''));
        foreach (['bot', 'crawler', 'spider', 'headless', 'curl', 'wget'] as $needle) {
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
                ->orderByDesc('published_at')
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
