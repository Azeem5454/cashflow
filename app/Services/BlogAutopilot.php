<?php

namespace App\Services;

use App\Helpers\Setting;
use App\Models\AiUsageLog;
use App\Models\BlogAutopilotQueueItem;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Daily auto-publishing blog pipeline.
 *
 * Source of truth: the `blog_autopilot_queue` table, managed by admin at
 * /admin/blog/autopilot. Each row is one title + optional category. The
 * row with the lowest `position` is the next to publish; on success the
 * row is deleted (the queue is consumed, not a log).
 *
 * Pipeline (all synchronous, all in one command run):
 *   1. pickNextQueueItem() — top-of-queue row
 *   2. generateWithClaude() — title → full post via Claude Haiku
 *   3. validate() — length / forbidden-tag checks
 *   4. BlogImageRenderer — draw 1200×630 featured image
 *   5. BlogPost::create() + delete queue row
 *
 * Safety rails:
 *   - Setting 'blog_autopilot.enabled' toggles the whole thing (admin UI)
 *   - min_hours_between_posts prevents cron double-fires from duplicating
 *   - All Claude output is whitelisted through validate() before saving
 *
 * Logs cost + tokens to ai_usage_logs with type='blog_autopilot' and
 * user_id=null (system-generated — requires the 2026_04_17 migration).
 */
class BlogAutopilot
{
    // Minimum hours between autopilot runs — double-fire protection
    public const MIN_HOURS_BETWEEN_POSTS = 20;

    // Word-count guard for the markdown body. The prompt requests 1000-2000
    // words — the validator allows 800-2400 to absorb Claude's ±10% variance
    // so we don't bin a perfectly good 950-word post over a 50-word miss.
    public const MIN_WORDS = 800;
    public const MAX_WORDS = 2400;

    /**
     * Default product brief baked into the autopilot prompt so every generated
     * post can reference real features (not hallucinated ones). Stored in the
     * `settings` table under 'blog_autopilot.product_brief' — the admin can
     * edit it from /admin/blog/autopilot without a deploy.
     *
     * Rules baked into the prompt prevent over-stuffing: max 2–3 product
     * mentions per post, only when genuinely relevant, never forced.
     */
    public const DEFAULT_PRODUCT_BRIEF = <<<'BRIEF'
**What it is:** TheCashFox is a cash-flow tracking SaaS for small business owners, freelancers, and their finance teams. Live at https://thecashfox.com.

**Who it's for:** Solopreneurs, freelancers, small agencies, and small business teams who want a clean, AI-powered alternative to spreadsheets and heavyweight accounting software like QuickBooks.

**Data model:** Users create Businesses → Books (e.g. by month/quarter/project) → Entries (Cash In / Cash Out). Live balance summary updates in real time.

**Pricing:**
- Free: 1 business, unlimited books + entries, 2 team members, entry attachments, book audit log
- Pro: **$5 / month** — unlimited businesses, unlimited team, PDF + CSV export, book reports & charts, recurring entries, email reports, date range comparison, entry notes/comments, **all AI features**

**Pro-only AI features:**
- AI receipt OCR — snap a photo, AI auto-fills type / amount / date / description / category (200 scans/month)
- AI auto-categorization — suggests the category as you type a description
- AI cash flow insights — 3-bullet insight card with Healthy / Watch / Concern sentiment + 24h cache
- Natural language entry — type "paid 5000 for rent yesterday" → AI parses into full entry
- Voice input — dictate transactions on the go (Web Speech API, no cost)
- Cash flow forecast — 30-day projection on Reports tab (trailing 90-day avg + active recurring entries)
- Anomaly detection — flags entries ≥ 3× the trailing mean for the same (book, type, category)

**Other Pro features:** recurring entries with catch-up (daily/weekly/monthly/yearly), auto-pause on downgrade, book-level reports (cash flow trend / category breakdown / payment mode breakdown), PDF + CSV export, weekly/monthly branded email reports, date-range comparison (vs previous period or same period last year), entry comments/notes for team collaboration, multi-business switcher, book audit log (who did what), bulk entry operations.

**Mobile app:** iOS + Android via React Native + Expo. ~90% parity with web. Token-based auth via Laravel Sanctum. Camera + gallery receipt scanning. Offline-capable for read flows.

**Trust & security:** Laravel 11 backend, PostgreSQL, Redis, Stripe live-mode billing, Google OAuth, Cloudflare Turnstile on signup, Sentry error monitoring, admin audit trail, role-based access (owner / editor / viewer), per-business data isolation, HSTS + CSP headers in production.

**Brand voice:** Confident, accessible, honest, global. Short direct sentences. No accounting jargon. No filler words ("in today's fast-paced world", "unlock", "leverage", "delve"). No region-specific currency references in public copy.
BRIEF;

    private string $apiKey;
    private string $model = 'claude-haiku-4-5-20251001';

    private float $inputCostPerToken  = 0.0000008;   // $0.80 / 1M
    private float $outputCostPerToken = 0.000004;    // $4.00 / 1M

    public function __construct(private BlogImageRenderer $renderer)
    {
        $this->apiKey = config('services.anthropic.key', '');
    }

    /**
     * Is the autopilot enabled? Stored in the `settings` table (admin UI toggle).
     * Default false so a fresh deploy never auto-publishes until the operator
     * explicitly turns it on.
     */
    public static function isEnabled(): bool
    {
        return Setting::get('blog_autopilot.enabled') === '1';
    }

    /**
     * The product brief that gets injected into every autopilot prompt.
     * Admin can override via /admin/blog/autopilot; falls back to
     * DEFAULT_PRODUCT_BRIEF so fresh installs still produce grounded posts.
     */
    public static function productBrief(): string
    {
        $custom = Setting::get('blog_autopilot.product_brief');
        $custom = is_string($custom) ? trim($custom) : '';
        return $custom !== '' ? $custom : self::DEFAULT_PRODUCT_BRIEF;
    }

    /**
     * Run the full pipeline. Returns the created BlogPost, or throws on
     * anything worth surfacing (no eligible queue item, API error,
     * validation failure, etc.).
     *
     * @param  bool  $force  If true, skip the "enabled" + "recently ran" guards.
     *                       Used by the admin "Generate Now" button.
     */
    public function run(bool $force = false): BlogPost
    {
        if (! $force && ! self::isEnabled()) {
            throw new \RuntimeException('Autopilot is disabled. Turn it on in /admin/blog/autopilot.');
        }

        if (! $force) {
            $this->assertNotRecentlyPublished();
        }

        $item = $this->pickNextQueueItem();
        if (! $item) {
            throw new \RuntimeException('Queue is empty — add titles at /admin/blog/autopilot.');
        }

        $allCategories = BlogCategory::orderBy('name')->get();
        if ($allCategories->isEmpty()) {
            throw new \RuntimeException('No blog categories exist — create at least one first.');
        }

        // Admin's choice (set on the queue row) overrides AI picking.
        // If blank, Claude picks the best-fitting category as part of the
        // generation call — same request, no extra cost.
        $preselected = $item->category_id
            ? $allCategories->firstWhere('id', $item->category_id)
            : null;

        $ai = $this->generateWithClaude($item->title, $allCategories, $preselected);
        $clean = $this->validate($ai, $item->title);

        $category = $preselected
            ?? $allCategories->firstWhere('slug', $clean['category_slug'] ?? null)
            ?? $allCategories->first();

        $clean['slug'] = $this->uniqueSlug($clean['slug']);

        $post = BlogPost::create([
            'title'              => $clean['title'],
            'slug'               => $clean['slug'],
            'excerpt'            => $clean['excerpt'],
            'body_markdown'      => $clean['body_markdown'],
            'seo_title'          => $clean['seo_title'],
            'seo_description'    => $clean['seo_description'],
            'featured_image_alt' => $clean['title'],
            'category_id'        => $category->id,
            'author_id'          => null,
            'status'             => 'published',
            'is_featured'        => false,
            'auto_topic_key'     => 'q-' . $item->id,
        ]);

        // Featured image — failure here shouldn't block the post going live.
        try {
            $key = $this->renderer->renderForPost($post->id, $post->title, $category);
            $post->update(['featured_image_key' => $key]);
        } catch (\Throwable $e) {
            Log::warning('BlogAutopilot: image render failed', [
                'post_id' => $post->id,
                'err'     => $e->getMessage(),
            ]);
        }

        // Consume the queue row + stamp last-run timestamp
        $item->delete();
        Setting::set('blog_autopilot.last_run_at', now()->toIso8601String());

        return $post->fresh();
    }

    /** @return BlogAutopilotQueueItem|null */
    public function pickNextQueueItem()
    {
        return BlogAutopilotQueueItem::orderBy('position')->orderBy('created_at')->first();
    }

    // ─── Claude call ───────────────────────────────────────────────────

    private function generateWithClaude(
        string $titleSeed,
        \Illuminate\Support\Collection $allCategories,
        ?BlogCategory $preselected = null
    ): array {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('ANTHROPIC_API_KEY is not configured.');
        }

        // Ask for a comfortable range inside the validator window
        $minWords = 1000;
        $maxWords = 2000;
        $appName  = config('app.name', 'TheCashFox');
        $appUrl   = rtrim(config('app.url', 'https://thecashfox.com'), '/');

        // JSON-encode user-controlled strings to neutralise prompt injection
        $titleJson = json_encode($titleSeed, JSON_UNESCAPED_UNICODE);

        // Build the category instruction + JSON-schema addendum depending on
        // whether the admin pre-selected a category on the queue row.
        if ($preselected) {
            $categoryBlock = '- Category (fixed, do not change): ' .
                json_encode($preselected->name, JSON_UNESCAPED_UNICODE);
            $categoryJsonField = ''; // not asked for; server uses preselected
            $categoryPickRule  = '';
        } else {
            $list = $allCategories->map(function ($c) {
                return '  * "' . $c->slug . '" — ' . $c->name
                    . ($c->description ? ' (' . $c->description . ')' : '');
            })->implode("\n");

            $categoryBlock = "- Category: pick the single best-fitting category by slug from this list:\n{$list}";
            $categoryJsonField = "\n  \"category_slug\":  \"one slug from the list above\",";
            $categoryPickRule  = "13. category_slug MUST be exactly one of the slugs listed above — no inventions, no renaming.\n";
        }

        $brief = self::productBrief();

        $prompt = <<<PROMPT
You are a senior content writer for {$appName}, a cash-flow tracking SaaS at {$appUrl}.
Write ONE practical, SEO-optimised blog post that small-business owners and freelancers will find genuinely useful — not generic AI fluff.

=== PRODUCT FACTS (reference these when relevant, never invent features) ===
{$brief}
=== END PRODUCT FACTS ===

Post brief:
- Seed title (you may refine for SEO, keep the same topic): {$titleJson}
{$categoryBlock}

Rules:
1. Final display title ≤ 60 chars. Preserve the seed title's subject and primary keyword.
2. Meta description ≤ 155 chars. Include the primary keyword naturally.
3. Excerpt ≤ 220 chars. Must hook the reader — a concrete promise, not a generic summary.
4. Body: {$minWords}–{$maxWords} words, plain markdown. Structure:
   - Short intro (2–3 sentences, NO greeting, NO "in this post we'll cover").
   - 4–6 H2 sections (## headings). Each H2 should contain a related keyword phrase.
   - Use bullet lists where helpful. Use concrete numbers, not vague adjectives.
   - End with ONE final call-to-action sentence pointing to {$appUrl}/register.
5. Product mentions — CRITICAL:
   - Reference product features ONLY when they're genuinely relevant to the topic. Max 2–3 references per post. NEVER force them.
   - When referenced, use concrete details from PRODUCT FACTS (e.g. "200 scans/month on Pro", not "some scans").
   - NEVER invent features, numbers, or capabilities not in PRODUCT FACTS.
   - For purely educational topics (e.g. "What is cash flow?"), you may write the post without any product mentions at all — a single CTA at the end is enough.
6. Include 1–3 internal links to {$appUrl} where natural, using markdown link syntax. Examples:
   * [AI receipt scanning]({$appUrl}/#features)
   * [sign up free]({$appUrl}/register)
   * [our blog]({$appUrl}/blog)
7. Do NOT mention you are an AI. Do NOT use: "in today's fast-paced world", "in conclusion", "unlock", "leverage", "delve", "elevate", "synergy", "harness", "embark", "journey".
8. Do NOT include an H1 (#) — the title is rendered separately.
9. Do NOT wrap body in code fences.
10. No emojis. No table of contents.
11. Slug: lowercase, hyphenated, 3–6 words, must contain the primary keyword from the title.
12. SEO title may differ slightly from display title if it improves keyword density — both ≤ 60 chars.
{$categoryPickRule}
Return ONLY this JSON object (no markdown fences, no prose outside JSON):

{
  "title":           "display title",
  "slug":            "primary-keyword-slug",{$categoryJsonField}
  "excerpt":         "≤ 220 chars hook",
  "body_markdown":   "full markdown body",
  "seo_title":       "≤ 60 chars",
  "seo_description": "≤ 155 chars meta description"
}
PROMPT;

        $response = Http::withHeaders([
            'x-api-key'         => $this->apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ])->timeout(180)->post('https://api.anthropic.com/v1/messages', [
            'model'      => $this->model,
            'max_tokens' => 4000,
            'messages'   => [
                [
                    'role'    => 'user',
                    'content' => [['type' => 'text', 'text' => $prompt]],
                ],
            ],
        ]);

        if (! $response->successful()) {
            Log::warning('BlogAutopilot: Claude API error', ['status' => $response->status()]);
            throw new \RuntimeException('Claude API error: HTTP ' . $response->status());
        }

        $json = $response->json();
        $text = $json['content'][0]['text'] ?? '';

        // Log usage (user_id null = system-generated)
        $tokensIn  = (int) ($json['usage']['input_tokens']  ?? 0);
        $tokensOut = (int) ($json['usage']['output_tokens'] ?? 0);
        try {
            AiUsageLog::create([
                'user_id'    => null,
                'type'       => 'blog_autopilot',
                'tokens_in'  => $tokensIn,
                'tokens_out' => $tokensOut,
                'cost_usd'   => round(
                    ($tokensIn  * $this->inputCostPerToken) +
                    ($tokensOut * $this->outputCostPerToken),
                    6
                ),
            ]);
        } catch (\Throwable $e) {
            Log::warning('BlogAutopilot: ai_usage_logs insert failed', ['err' => $e->getMessage()]);
        }

        // Parse JSON (strip defensive code fences)
        $text = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', trim($text));
        $decoded = json_decode($text, true);
        if (! is_array($decoded)) {
            throw new \RuntimeException('Claude returned unparseable JSON.');
        }

        return $decoded;
    }

    // ─── Validation + sanitisation ─────────────────────────────────────

    private function validate(array $data, string $seedTitle): array
    {
        $required = ['title', 'slug', 'excerpt', 'body_markdown', 'seo_title', 'seo_description'];
        foreach ($required as $field) {
            if (empty($data[$field]) || ! is_string($data[$field])) {
                throw new \RuntimeException("Claude output missing/invalid field: {$field}");
            }
        }

        $title          = trim($data['title']);
        $slug           = Str::slug(trim($data['slug']));
        $excerpt        = trim($data['excerpt']);
        $body           = trim($data['body_markdown']);
        $seoTitle       = trim($data['seo_title']);
        $seoDescription = trim($data['seo_description']);

        // Fall back to seed title if Claude returned something empty
        if ($title === '')          $title = $seedTitle;
        if ($slug === '')           $slug = Str::slug($seedTitle);
        if ($seoTitle === '')       $seoTitle = mb_substr($title, 0, 60);
        if ($seoDescription === '') $seoDescription = mb_substr($excerpt, 0, 155);

        // Length guards (hard caps matching DB schema)
        if (mb_strlen($title)          > 200) $title          = mb_substr($title,          0, 200);
        if (mb_strlen($excerpt)        > 400) $excerpt        = mb_substr($excerpt,        0, 400);
        if (mb_strlen($seoTitle)       > 160) $seoTitle       = mb_substr($seoTitle,       0, 160);
        if (mb_strlen($seoDescription) > 280) $seoDescription = mb_substr($seoDescription, 0, 280);

        // Word count guard on body
        $wc = str_word_count(strip_tags($body));
        if ($wc < self::MIN_WORDS) {
            throw new \RuntimeException("Body too short ({$wc} < " . self::MIN_WORDS . " words).");
        }
        if ($wc > self::MAX_WORDS) {
            throw new \RuntimeException("Body too long ({$wc} > " . self::MAX_WORDS . " words).");
        }

        // Forbidden content — defence against script/iframe/style injection
        // (CommonMark already escapes HTML, but defence in depth)
        $forbidden = ['<script', '<iframe', '<style', '<object', '<embed', 'javascript:'];
        $lower = strtolower($body);
        foreach ($forbidden as $needle) {
            if (str_contains($lower, $needle)) {
                throw new \RuntimeException("Body contained forbidden token: {$needle}");
            }
        }

        // Strip any stray leading H1 — title renders separately
        $body = preg_replace('/^\s*#\s+.*$/m', '', $body, 1) ?? $body;
        $body = trim($body);

        // Optional category_slug — only present when admin didn't pre-select.
        // Normalised to a simple slug string; run() resolves it to a model or
        // falls back to the first category if Claude invented something.
        $categorySlug = null;
        if (isset($data['category_slug']) && is_string($data['category_slug'])) {
            $cs = Str::slug(trim($data['category_slug']));
            if ($cs !== '' && mb_strlen($cs) <= 120) {
                $categorySlug = $cs;
            }
        }

        return [
            'title'           => $title,
            'slug'            => $slug,
            'excerpt'         => $excerpt,
            'body_markdown'   => $body,
            'seo_title'       => $seoTitle,
            'seo_description' => $seoDescription,
            'category_slug'   => $categorySlug,
        ];
    }

    private function uniqueSlug(string $slug): string
    {
        $base = $slug;
        $i = 2;
        while (BlogPost::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i;
            if (++$i > 20) {
                $slug = $base . '-' . Str::lower(Str::random(4));
                break;
            }
        }
        return $slug;
    }

    // ─── Guards ────────────────────────────────────────────────────────

    private function assertNotRecentlyPublished(): void
    {
        $lastAuto = BlogPost::whereNotNull('auto_topic_key')
            ->latest('created_at')
            ->first();

        if ($lastAuto && $lastAuto->created_at->gt(now()->subHours(self::MIN_HOURS_BETWEEN_POSTS))) {
            throw new \RuntimeException(
                'Last auto-post was ' . $lastAuto->created_at->diffForHumans() .
                ' — within the ' . self::MIN_HOURS_BETWEEN_POSTS . 'h cooldown.'
            );
        }
    }
}
