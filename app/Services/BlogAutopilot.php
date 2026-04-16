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

        // Resolve category — use queued category or fall back to first category
        $category = $item->category_id
            ? BlogCategory::find($item->category_id)
            : BlogCategory::orderBy('name')->first();

        if (! $category) {
            throw new \RuntimeException('No blog categories exist — create at least one first.');
        }

        $ai = $this->generateWithClaude($item->title, $category);
        $clean = $this->validate($ai, $item->title);

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

    private function generateWithClaude(string $titleSeed, BlogCategory $category): array
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('ANTHROPIC_API_KEY is not configured.');
        }

        // Ask for a comfortable range inside the validator window
        $minWords = 1000;
        $maxWords = 2000;
        $appName  = config('app.name', 'TheCashFox');
        $appUrl   = rtrim(config('app.url', 'https://thecashfox.com'), '/');

        // JSON-encode user-controlled strings to neutralise prompt injection
        $titleJson    = json_encode($titleSeed,       JSON_UNESCAPED_UNICODE);
        $categoryJson = json_encode($category->name,  JSON_UNESCAPED_UNICODE);

        $prompt = <<<PROMPT
You are a senior content writer for {$appName}, a cash-flow tracking SaaS at {$appUrl}.
Write ONE practical, SEO-optimised blog post that small-business owners and freelancers will find genuinely useful — not generic AI fluff.

Post brief:
- Seed title (you may refine for SEO, keep the same topic): {$titleJson}
- Category: {$categoryJson}

Rules:
1. Final display title ≤ 60 chars. Preserve the seed title's subject and primary keyword.
2. Meta description ≤ 155 chars. Include the primary keyword naturally.
3. Excerpt ≤ 220 chars. Must hook the reader — a concrete promise, not a generic summary.
4. Body: {$minWords}–{$maxWords} words, plain markdown. Structure:
   - Short intro (2–3 sentences, NO greeting, NO "in this post we'll cover").
   - 4–6 H2 sections (## headings). Each H2 should contain a related keyword phrase.
   - Use bullet lists where helpful. Use concrete numbers, not vague adjectives.
   - Include 1–3 internal links to {$appUrl} where natural, using markdown link syntax. Examples:
     * [AI receipt scanning]({$appUrl}/#features)
     * [sign up free]({$appUrl}/register)
     * [our blog]({$appUrl}/blog)
   - End with ONE final call-to-action sentence pointing to {$appUrl}/register.
5. Do NOT mention you are an AI. Do NOT use: "in today's fast-paced world", "in conclusion", "unlock", "leverage", "delve", "elevate", "synergy", "harness", "embark", "journey".
6. Do NOT include an H1 (#) — the title is rendered separately.
7. Do NOT wrap body in code fences.
8. No emojis. No table of contents.
9. Slug: lowercase, hyphenated, 3–6 words, must contain the primary keyword from the title.
10. SEO title may differ slightly from display title if it improves keyword density — both ≤ 60 chars.

Return ONLY this JSON object (no markdown fences, no prose outside JSON):

{
  "title":           "display title",
  "slug":            "primary-keyword-slug",
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

        return [
            'title'           => $title,
            'slug'            => $slug,
            'excerpt'         => $excerpt,
            'body_markdown'   => $body,
            'seo_title'       => $seoTitle,
            'seo_description' => $seoDescription,
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
