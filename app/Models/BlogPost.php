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

    /**
     * How long an admin "Feature" pin keeps a post in the blog hero slot.
     * After this the hero falls back to the newest published post, so a
     * forgotten pin can never bury fresh (autopilot) posts.
     */
    public const FEATURE_PIN_DAYS = 14;

    protected $fillable = [
        'slug', 'title', 'excerpt', 'body_markdown', 'body_html',
        'featured_image_key', 'featured_image_alt',
        'category_id', 'author_id',
        'status', 'is_featured', 'featured_at', 'published_at',
        'seo_title', 'seo_description', 'auto_topic_key',
        'reading_time', 'view_count',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'featured_at'  => 'datetime',
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

    /**
     * Posts with an ACTIVE hero pin: flagged is_featured and pinned within
     * the last FEATURE_PIN_DAYS days. A flag without featured_at (legacy
     * rows) or an older pin is ignored.
     */
    public function scopeFeatured(Builder $q): Builder
    {
        return $q->where('is_featured', true)
            ->whereNotNull('featured_at')
            ->where('featured_at', '>=', now()->subDays(self::FEATURE_PIN_DAYS));
    }

    /** Newest first, with a stable tiebreaker for identical publish times. */
    public function scopeLatestFirst(Builder $q): Builder
    {
        return $q->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    /**
     * The post shown as the big hero on /blog: an actively pinned post if
     * there is one (most recently pinned wins), otherwise the newest
     * published post.
     */
    public static function heroPost(): ?self
    {
        return static::published()->featured()->with(['category', 'author'])
                ->orderByDesc('featured_at')->first()
            ?? static::published()->with(['category', 'author'])->latestFirst()->first();
    }

    /** Is this post currently holding an active hero pin? */
    public function hasActivePin(): bool
    {
        return $this->is_featured
            && $this->featured_at
            && $this->featured_at->gte(now()->subDays(self::FEATURE_PIN_DAYS));
    }

    /** Pin (or unpin) this post as the blog hero. Caller saves. */
    public function setPinned(bool $pinned): void
    {
        $this->is_featured = $pinned;
        $this->featured_at = $pinned ? now() : null;
    }

    /**
     * Count a public view without touching updated_at. updated_at is the
     * "real edit" timestamp — it feeds sitemap <lastmod>, JSON-LD
     * dateModified and article:modified_time — so it must never move on a
     * page view. Query-builder increment (not Eloquent's, which adds
     * updated_at automatically).
     */
    public static function recordView(string $id): void
    {
        \Illuminate\Support\Facades\DB::table((new static)->getTable())
            ->where('id', $id)
            ->increment('view_count');
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

    /**
     * schema.org BlogPosting for the post page, built in PHP and emitted
     * with json_encode (never hand-written JSON inside Blade).
     */
    public function articleSchema(?string $fallbackImage = null, ?string $logoUrl = null): array
    {
        $appName = config('app.name', 'TheCashFox');
        $appUrl  = rtrim(config('app.url', 'https://thecashfox.com'), '/');
        $image   = $this->featuredImageUrl() ?: $fallbackImage;

        $publisher = [
            '@type' => 'Organization',
            'name'  => $appName,
            'url'   => $appUrl,
        ];
        if ($logoUrl) {
            $publisher['logo'] = ['@type' => 'ImageObject', 'url' => $logoUrl];
        }

        $schema = [
            '@context'         => 'https://schema.org',
            '@type'            => 'BlogPosting',
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $this->url()],
            'url'              => $this->url(),
            'headline'         => Str::limit($this->title, 110, ''),
            'description'      => $this->seoDescription(),
            'datePublished'    => ($this->published_at ?? $this->created_at)?->toIso8601String(),
            'dateModified'     => ($this->updated_at ?? $this->published_at)?->toIso8601String(),
            // Autopilot posts have no human author — attribute them to the
            // organisation rather than inventing a person.
            'author'           => $this->author
                ? ['@type' => 'Person', 'name' => $this->author->name]
                : ['@type' => 'Organization', 'name' => $appName, 'url' => $appUrl],
            'publisher'        => $publisher,
            'inLanguage'       => 'en',
            'wordCount'        => static::wordCount($this->body_markdown),
        ];
        if ($image) {
            $schema['image'] = [$image];
        }
        if ($this->category) {
            $schema['articleSection'] = $this->category->name;
        }

        return $schema;
    }

    /** JSON for a <script type="application/ld+json"> block — safe to echo raw. */
    public function articleSchemaJson(?string $fallbackImage = null, ?string $logoUrl = null): string
    {
        return json_encode(
            $this->articleSchema($fallbackImage, $logoUrl),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP
        ) ?: '{}';
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
        $words = max(1, static::wordCount($md));
        return max(1, (int) ceil($words / 230));
    }

    /**
     * Words a reader actually reads in a markdown body. Link URLs, image
     * sources, HTML tags and markdown punctuation are dropped; numbers and
     * amounts ("$5", "1,200", "30%") count as words (str_word_count()
     * ignored them and split URLs into several "words").
     */
    public static function wordCount(?string $md): int
    {
        if (! $md) return 0;

        $text = preg_replace('/!\[([^\]]*)\]\([^)]*\)/u', '$1', $md) ?? $md;   // images → alt text
        $text = preg_replace('/\[([^\]]*)\]\([^)]*\)/u', '$1', $text) ?? $text; // links → anchor text
        $text = preg_replace('/https?:\/\/\S+/u', ' ', $text) ?? $text;            // bare URLs
        $text = strip_tags($text);

        $tokens = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return count(array_filter($tokens, fn ($t) => preg_match('/[\p{L}\p{N}]/u', $t)));
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
            // Fresh lookup, not $p->category — the loaded relation can be the
            // OLD category when category_id just changed.
            if ($p->category_id) {
                BlogCategory::find($p->category_id)?->refreshPostCount();
            }
            if ($p->wasChanged('category_id')) {
                // Old category needs a refresh too.
                $oldId = $p->getOriginal('category_id');
                if ($oldId) {
                    BlogCategory::find($oldId)?->refreshPostCount();
                }
            }
        });

        static::deleted(function (self $p) {
            if ($p->category_id) {
                BlogCategory::find($p->category_id)?->refreshPostCount();
            }
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
