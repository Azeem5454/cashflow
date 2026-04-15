<?php

namespace App\Livewire\Blog;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public ?string $categorySlug = null;
    public string $search = '';

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    public function mount(?string $categorySlug = null): void
    {
        $this->categorySlug = $categorySlug;
    }

    public function updatingSearch(): void { $this->resetPage(); }

    public function render()
    {
        $category = $this->categorySlug
            ? BlogCategory::where('slug', $this->categorySlug)->firstOrFail()
            : null;

        // The big featured-hero layout only makes sense when there are enough
        // posts to justify it. If there are 3 or fewer total, showing one as
        // a full-width hero leaves the grid below sparse/empty. Below that
        // threshold, every post goes into the equal-weight 3-column grid.
        $totalPublished = BlogPost::published()->count();
        $useFeaturedHero = ! $category && $this->search === '' && $totalPublished >= 4;

        $featured = $useFeaturedHero
            ? BlogPost::published()->featured()->with(['category', 'author'])->first()
            : null;

        $query = BlogPost::published()
            ->with(['category', 'author'])
            ->when($category, fn ($q) => $q->where('category_id', $category->id))
            ->when($this->search !== '', function ($q) {
                $like = '%' . $this->search . '%';
                $q->where(function ($q2) use ($like) {
                    $q2->where('title', 'ilike', $like)
                       ->orWhere('excerpt', 'ilike', $like);
                });
            })
            // Exclude the featured one from the main grid so it doesn't appear twice.
            ->when($featured, fn ($q) => $q->where('id', '!=', $featured->id))
            ->orderByDesc('published_at');

        return view('livewire.blog.index', [
            'featured'       => $featured,
            'posts'          => $query->paginate(12),
            'allCategories'  => BlogCategory::where('post_count', '>', 0)->orderBy('name')->get(),
            'currentCategory' => $category,
        ])->layout('layouts.blog', [
            'pageTitle'       => $category ? ($category->name . ' — Blog') : 'Blog',
            'pageDescription' => $category
                ? ($category->description ?: 'Articles tagged ' . $category->name)
                : 'Practical advice, cash flow insights, and product updates from the TheCashFox team.',
            'canonical'       => $category
                ? route('blog.category', $category->slug)
                : route('blog.index'),
        ]);
    }
}
