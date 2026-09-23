<?php

namespace App\Livewire\Admin\Blog;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'all';     // all | published | draft
    public string $categoryFilter = 'all';
    public string $sort = 'newest';          // newest | oldest | most_viewed | title

    protected $queryString = [
        'search'         => ['except' => ''],
        'statusFilter'   => ['except' => 'all'],
        'categoryFilter' => ['except' => 'all'],
        'sort'           => ['except' => 'newest'],
    ];

    public function updatingSearch():         void { $this->resetPage(); }
    public function updatingStatusFilter():   void { $this->resetPage(); }
    public function updatingCategoryFilter(): void { $this->resetPage(); }
    public function updatingSort():           void { $this->resetPage(); }

    public function togglePublish(string $id): void
    {
        $post = BlogPost::findOrFail($id);
        $post->status = $post->status === 'published' ? 'draft' : 'published';
        if ($post->status === 'published' && ! $post->published_at) {
            $post->published_at = now();
        }
        $post->save();
    }

    public function toggleFeatured(string $id): void
    {
        $post = BlogPost::findOrFail($id);
        // "Feature" pins the post as the blog hero for BlogPost::FEATURE_PIN_DAYS
        // days (featured_at = now). An expired pin counts as unpinned, so
        // clicking the star again re-pins it for a fresh window.
        $pin = ! $post->hasActivePin();

        // Only one pin at a time — clear the others first. Query-builder
        // update so other posts' updated_at (sitemap lastmod) isn't bumped.
        if ($pin) {
            \Illuminate\Support\Facades\DB::table('blog_posts')
                ->where('id', '!=', $post->id)
                ->where('is_featured', true)
                ->update(['is_featured' => false, 'featured_at' => null]);
        }

        $post->setPinned($pin);
        // Pinning isn't a content edit — don't move updated_at.
        $post->timestamps = false;
        $post->save();
        $post->timestamps = true;

        $this->dispatch('blog-toast', message: $pin
            ? 'Pinned as the blog hero for ' . BlogPost::FEATURE_PIN_DAYS . ' days.'
            : 'Unpinned — the newest post is the hero again.');
    }

    public function deletePost(string $id): void
    {
        BlogPost::findOrFail($id)->delete();
    }

    /**
     * Re-render the branded featured image for a single post via the GD
     * renderer. Same code path the autopilot + CLI command use, just
     * invoked per-row from the admin table.
     */
    public function regenerateImage(string $id): void
    {
        $post = BlogPost::with('category')->findOrFail($id);

        try {
            $renderer = app(\App\Services\BlogImageRenderer::class);
            $key = $renderer->renderForPost($post->id, $post->title, $post->category, $post->image_query);
            $post->update([
                'featured_image_key'    => $key,
                'featured_image_credit' => $renderer->lastPhotoCredit(),
            ]);
            $this->dispatch('blog-toast', message: 'Image regenerated.');
        } catch (\Throwable $e) {
            report($e);
            $this->dispatch('blog-toast', message: 'Failed: ' . $e->getMessage(), error: true);
        }
    }

    public function render()
    {
        $query = BlogPost::query()->with(['category', 'author']);

        if ($this->search !== '') {
            $like = '%' . $this->search . '%';
            $query->where(function ($q) use ($like) {
                $q->where('title', 'ilike', $like)
                  ->orWhere('excerpt', 'ilike', $like)
                  ->orWhere('slug', 'ilike', $like);
            });
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->categoryFilter !== 'all') {
            $query->where('category_id', $this->categoryFilter);
        }

        match ($this->sort) {
            'oldest'      => $query->orderBy('created_at', 'asc'),
            'most_viewed' => $query->orderBy('view_count', 'desc'),
            'title'       => $query->orderBy('title', 'asc'),
            default       => $query->orderByDesc('created_at'),
        };

        return view('livewire.admin.blog.index', [
            'posts'      => $query->paginate(15),
            'categories' => BlogCategory::orderBy('name')->get(),
            'counts'     => [
                'all'       => BlogPost::count(),
                'published' => BlogPost::where('status', 'published')->count(),
                'draft'     => BlogPost::where('status', 'draft')->count(),
            ],
        ])->layout('layouts.admin');
    }
}
