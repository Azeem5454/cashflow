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

        // The big hero layout only makes sense when there are enough posts
        // to justify it. With 3 or fewer, a full-width hero leaves the grid
        // below sparse, so every post goes into the equal-weight grid.
        $totalPublished = BlogPost::published()->count();
        $useHero = ! $category && $this->search === '' && $totalPublished >= 4;

        // Hero = newest published post, unless an admin pinned one within
        // the last BlogPost::FEATURE_PIN_DAYS days (see BlogPost::heroPost).
        $hero = $useHero ? BlogPost::heroPost() : null;

        $query = BlogPost::published()
            ->with(['category', 'author'])
            ->when($category, fn ($q) => $q->where('category_id', $category->id))
            ->when($this->search !== '', function ($q) {
                // Portable case-insensitive match (ILIKE is Postgres-only).
                $like = '%' . mb_strtolower($this->search) . '%';
                $q->where(function ($q2) use ($like) {
                    $q2->whereRaw('LOWER(title) LIKE ?', [$like])
                       ->orWhereRaw('LOWER(excerpt) LIKE ?', [$like]);
                });
            })
            // The hero is excluded from the grid on every page so pagination
            // stays stable; it is only rendered on page 1.
            ->when($hero, fn ($q) => $q->where('id', '!=', $hero->id))
            ->latestFirst();

        $posts = $query->paginate(12);
        $page  = $posts->currentPage();

        $canonical = $category
            ? route('blog.category', $category->slug)
            : route('blog.index');
        if ($page > 1) {
            $canonical .= '?page=' . $page;
        }

        return view('livewire.blog.index', [
            'featured'       => $page === 1 ? $hero : null,
            'heroIsPinned'   => $hero?->hasActivePin() ?? false,
            'posts'          => $posts,
            'allCategories'  => BlogCategory::where('post_count', '>', 0)->orderBy('name')->get(),
            'currentCategory' => $category,
        ])->layout('layouts.blog', [
            'pageTitle'       => $category ? ($category->name . ' — Blog') : 'Blog',
            'pageDescription' => $category
                ? ($category->description ?: 'Articles tagged ' . $category->name)
                : 'Practical advice, cash flow insights, and product updates from the ' . config('app.name', 'TheCashFox') . ' team.',
            'canonical'       => $canonical,
            // Search result pages are thin/duplicate content — keep them out of the index.
            'robots'          => $this->search !== '' ? 'noindex,follow' : 'index,follow',
            'breadcrumbs'     => array_values(array_filter([
                ['name' => 'Blog', 'url' => route('blog.index')],
                $category ? ['name' => $category->name, 'url' => route('blog.category', $category->slug)] : null,
            ])),
        ]);
    }
}
