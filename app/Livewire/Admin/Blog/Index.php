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
        // Only one post can be featured at a time — flip others off first.
        if (! $post->is_featured) {
            BlogPost::where('is_featured', true)->update(['is_featured' => false]);
        }
        $post->is_featured = ! $post->is_featured;
        $post->save();
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
            $key = app(\App\Services\BlogImageRenderer::class)
                ->renderForPost($post->id, $post->title, $post->category);
            $post->update(['featured_image_key' => $key]);
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
