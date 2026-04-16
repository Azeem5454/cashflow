<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Autolink\AutolinkExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\MarkdownConverter;

class BlogPost extends Model
{
    use HasUuids;

    protected $fillable = [
        'slug', 'title', 'excerpt', 'body_markdown', 'body_html',
        'featured_image_key', 'featured_image_alt',
        'category_id', 'author_id',
        'status', 'is_featured', 'published_at',
        'seo_title', 'seo_description', 'auto_topic_key',
        'reading_time', 'view_count',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'is_featured'  => 'boolean',
        ];
    }

    // ─── Relationships ─────────────────────────────────────────────────

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // ─── Scopes ────────────────────────────────────────────────────────

    public function scopePublished(Builder $q): Builder
    {
        // status='published' is the single source of truth for visibility.
        // published_at is metadata only (for display + ordering).
        //
        // Why not honour future published_at as "scheduled"? Because the
        // datetime-local input in the admin has no timezone, Laravel stores
        // it raw, and the server is UTC — so an admin in a +5 timezone
        // entering "3 AM" accidentally schedules the post 5 hours into
        // the future and it silently disappears. If we ever want true
        // scheduled publishing, we'll add a dedicated status='scheduled'
        // + a cron that flips it, not this foot-gun.
        return $q->where('status', 'published');
    }

    public function scopeFeatured(Builder $q): Builder
    {
        return $q->where('is_featured', true);
    }

    // ─── Derived values for rendering ──────────────────────────────────

    public function seoTitle(): string
    {
        return $this->seo_title ?: $this->title;
    }

    public function seoDescription(): string
    {
        return $this->seo_description ?: ($this->excerpt ?: Str::limit(strip_tags($this->body_html ?? ''), 160));
    }

    /**
     * Absolute URL for the post. Works at render time across all routes.
     */
    public function url(): string
    {
        return route('blog.show', $this->slug);
    }

    public function featuredImageUrl(): ?string
    {
        if (! $this->featured_image_key) {
            return null;
        }
        if (! UploadedAsset::has($this->featured_image_key)) {
            return null;
        }
        return route('brand-asset', $this->featured_image_key)
             . '?v=' . UploadedAsset::cacheBuster($this->featured_image_key);
    }

    // ─── Markdown rendering ────────────────────────────────────────────

    /**
     * Shared CommonMark converter. Safe HTML output (dangerous HTML escaped
     * rather than rendered), autolinks, GitHub-flavoured tables.
     */
    public static function markdownConverter(): MarkdownConverter
    {
        static $converter = null;
        if ($converter) return $converter;

        $env = new Environment([
            'html_input'         => 'escape',   // never allow raw <script>/etc. — content is trusted-ish but defence-in-depth
            'allow_unsafe_links' => false,
            'max_nesting_level'  => 20,
            'renderer'           => [
                'soft_break' => "<br>\n",
            ],
        ]);
        $env->addExtension(new CommonMarkCoreExtension());
        $env->addExtension(new AutolinkExtension());
        $env->addExtension(new TableExtension());

        return $converter = new MarkdownConverter($env);
    }

    public static function renderMarkdown(?string $md): string
    {
        if (! $md) return '';
        return (string) static::markdownConverter()->convert($md);
    }

    /**
     * Words per minute used for reading time estimates. 230 is the typical
     * adult silent-reading rate; SEO tools (Medium, HubSpot) use 200–250.
     */
    public static function calcReadingTime(?string $md): int
    {
        if (! $md) return 1;
        $words = max(1, str_word_count(strip_tags($md)));
        return max(1, (int) ceil($words / 230));
    }

    // ─── Lifecycle ─────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::saving(function (self $p) {
            // Auto-slug from title if blank.
            if (empty($p->slug) && ! empty($p->title)) {
                $p->slug = static::uniqueSlugFrom($p->title, $p->id);
            }

            // Always keep body_html + reading_time in sync with body_markdown.
            if ($p->isDirty('body_markdown') || empty($p->body_html)) {
                $p->body_html     = static::renderMarkdown($p->body_markdown);
                $p->reading_time  = static::calcReadingTime($p->body_markdown);
            }

            // Auto-stamp published_at on first publish.
            if ($p->status === 'published' && empty($p->published_at)) {
                $p->published_at = now();
            }
        });

        // Refresh denormalised category.post_count on save/delete.
        static::saved(function (self $p) {
            $p->category?->refreshPostCount();
            if ($p->wasChanged('category_id')) {
                // Old category needs a refresh too.
                $oldId = $p->getOriginal('category_id');
                if ($oldId) {
                    BlogCategory::find($oldId)?->refreshPostCount();
                }
            }
        });

        static::deleted(function (self $p) {
            $p->category?->refreshPostCount();
        });
    }

    private static function uniqueSlugFrom(string $title, ?string $excludeId = null): string
    {
        $base = Str::slug($title) ?: 'post';
        $slug = $base;
        $n = 2;
        while (static::where('slug', $slug)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->exists()) {
            $slug = $base . '-' . $n++;
        }
        return $slug;
    }
}
