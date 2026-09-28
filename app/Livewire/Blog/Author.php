<?php

namespace App\Livewire\Blog;

use App\Models\BlogPost;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Public author profile: who writes the blog, and everything they've written.
 *
 * Exists for E-E-A-T. Posts attributed to an organisation give Google nothing
 * to assess; a named person with a stated background, linked from every post
 * they wrote, is what the helpful-content guidance asks for.
 *
 * Only users with BOTH a slug and a bio are public — an admin account without
 * a profile filled in stays unreachable rather than exposing a bare page.
 */
class Author extends Component
{
    use WithPagination;

    public User $author;

    public function mount(string $authorSlug): void
    {
        $author = User::where('author_slug', $authorSlug)->first();

        abort_if($author === null || ! $author->isPublicAuthor(), 404);

        $this->author = $author;
    }

    public function render()
    {
        $posts = BlogPost::published()
            ->where('author_id', $this->author->id)
            ->with('category')
            ->latestFirst()
            ->paginate(12);

        // See Blog\Index: an out-of-range ?page= must 404, not serve an
        // empty 200 that Google files as a soft 404.
        abort_if($posts->currentPage() > 1 && $posts->isEmpty(), 404);

        // Page 2+ canonicals to itself. Pointing it back at page 1 tells
        // Google the deeper pages are duplicates, and the posts only
        // reachable from them stop being crawled.
        $canonical = route('blog.author', $this->author->author_slug);
        if ($posts->currentPage() > 1) {
            $canonical .= '?page=' . $posts->currentPage();
        }

        return view('livewire.blog.author', [
            'posts' => $posts,
        ])->layout('layouts.blog', [
            'pageTitle'       => $this->author->name,
            'pageDescription' => \Illuminate\Support\Str::limit($this->author->author_bio, 155),
            'canonical'       => $canonical,
            'breadcrumbs'     => [
                ['name' => 'Blog', 'url' => route('blog.index')],
                ['name' => $this->author->name, 'url' => route('blog.author', $this->author->author_slug)],
            ],
            // Person + ProfilePage, so the byline on every post resolves to a
            // real entity rather than a bare string.
            'personForSchema' => $this->author,
        ]);
    }
}
