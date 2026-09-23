<?php

namespace App\Services;

use App\Exceptions\BlogAutopilotSkipped;
use App\Helpers\Setting;
use App\Models\AiUsageLog;
use App\Models\BlogAutopilotQueueItem;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\User;
use App\Support\Pricing;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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
 *   1. pickNextQueueItem() — top-of-queue row (titles that already exist as
 *      posts are dropped, case-insensitive)
 *   2. generateWithClaude() — title → full post via Claude Haiku, with
 *      today's date in the prompt
 *   3. validate() — length / forbidden-tag checks; a too-short body gets ONE
 *      "expand it" follow-up call before the run fails
 *   4. BlogImageRenderer — draw 1200×630 featured image
 *   5. BlogPost::create() + delete queue row
 *
 * Safety rails:
 *   - Setting 'blog_autopilot.enabled' toggles the whole thing (admin UI)
 *   - MIN_HOURS_BETWEEN_POSTS cooldown + a cache lock prevent duplicates from
 *     cron double-fires or "Generate Now" racing the scheduler
 *   - All Claude output is whitelisted through validate() before saving
 *   - Real failures → report() (Sentry) + Setting 'blog_autopilot.last_error'
 *     (admin banner); queue running low → email to admins (once a day)
 *
 * Logs cost + tokens to ai_usage_logs with type='blog_autopilot' and
 * user_id=null (system-generated) — one row per Claude call.
 */
class BlogAutopilot
{
    // Minimum hours between autopilot runs — double-fire protection
    public const MIN_HOURS_BETWEEN_POSTS = 20;

    // Word-count guard for the markdown body (reader-visible words, see
    // BlogPost::wordCount). The prompt targets 1200–1800 so a normal run
    // lands comfortably inside the window.
    public const MIN_WORDS = 1000;
    public const MAX_WORDS = 2400;

    // Email the admins when this many titles (or fewer) are left in the queue.
    public const LOW_QUEUE_THRESHOLD = 2;

    /**
     * Default product brief baked into the autopilot prompt so every generated
     * post can reference real features (not hallucinated ones). Admins can
     * override it (Setting 'blog_autopilot.product_brief') from
     * /admin/blog/autopilot without a deploy.
     *
     * Placeholders replaced at prompt time: {pro_price} → App\Support\Pricing.
     *
     * Keep this in step with docs/FEATURE_GATES.md and the live product.
     * Last reviewed: 2026-09-22.
     */
    public const DEFAULT_PRODUCT_BRIEF = <<<'BRIEF'
**What it is:** TheCashFox is a simple cash book for small business owners, freelancers and small teams: record money in and money out, and always see the current balance. Web app at https://thecashfox.com.

**Who it's for:** Owners and freelancers anywhere in the world who have outgrown a notebook, a spreadsheet or a chat thread, but don't need full accounting software.

**How it's organised:** Account → Businesses → Books (e.g. one per month, quarter or project) → Entries (Cash In / Cash Out). Each entry has an amount and date, plus optional description, category, payment method, reference and attachment. The balance and running balance update instantly.

**Free plan ($0, no card needed):**
- 1 business (as owner), unlimited books and entries
- Up to 2 team members per business, with roles: owner, editor, viewer
- Receipt / invoice attachments on entries (photo or PDF)
- Book activity log (who added, changed or deleted what)
- Categories, payment methods, search and filters, bulk edit / move / copy / delete
- AI on the Free plan: 10 AI entries a month (receipt scans and typed/spoken entries share the 10), plus unlimited AI category suggestions
- You can start using it straight away; verifying your email is only needed to invite teammates or schedule email reports

**Pro plan — {pro_price}/month on the web (billed monthly, cancel anytime):**
- Unlimited businesses and unlimited team members
- PDF and CSV export
- Reports: period summary, cash in vs cash out over time, spending by category, plus AI insights
- Custom date ranges and comparison with the previous period or the same period last year
- Recurring entries: daily, weekly or every 2 weeks (no monthly/yearly option)
- Weekly or monthly email reports to chosen recipients
- Comments on entries for team discussion
- AI features (below)

**AI features (the headline — Free gets a taste, Pro gets the full amount):**
- Receipt scan: photograph or upload a receipt and the entry fills itself in (Free: counts toward 10 AI entries a month; Pro: 200 scans per month)
- Type or say an entry in plain words ("paid 5,000 for rent yesterday") and it becomes a filled-in entry (Free: counts toward the same 10 a month; Pro: fair-use daily cap; dictation works where speech input is supported)
- Category suggestions: suggests a category from the entry's description — free for everyone
- Cash flow insights (Pro only): three plain-English points on the period with a Healthy / Watch / Concern label
- Unusual-amount flags: marks an entry that is about 3× the usual amount for that category

**Mobile:** iOS and Android apps are coming soon — they are NOT available yet. Do not tell readers to download them. Face ID / fingerprint sign-in is planned for the apps.

**Trust & security:** HTTPS everywhere, role-based access per business, each business's data kept separate, card payments handled by Stripe (we never store card numbers), sign in with Google or email.

**What it does NOT do (never claim these):** bank feeds or bank sync, invoicing, payroll, tax filing, inventory, double-entry accounting or a general ledger, cash flow forecasting, offline mode, automatic backups you can restore, a free trial of Pro, annual billing.

**Brand voice:** Confident, plain and honest. Short, direct sentences. No accounting jargon (explain any term you must use). Written for a global audience: no country-specific currencies, taxes or laws unless the topic demands it; show amounts as plain numbers or in USD.
BRIEF;

    private string $apiKey;
    private string $model = 'claude-haiku-4-5-20251001';

    // Claude Haiku 4.5 list prices
    private float $inputCostPerToken  = 0.000001;   // $1.00 / 1M
    private float $outputCostPerToken = 0.000005;   // $5.00 / 1M

    public function __construct(private BlogImageRenderer $renderer)
    {
        $this->apiKey = (string) config('services.anthropic.key', '');
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

    /** The admin's custom brief if set, else the default — placeholders intact (for editing). */
    public static function rawProductBrief(): string
    {
        $custom = Setting::get('blog_autopilot.product_brief');
        $custom = is_string($custom) ? trim($custom) : '';
        return $custom !== '' ? $custom : self::DEFAULT_PRODUCT_BRIEF;
    }

    public static function hasCustomProductBrief(): bool
    {
        $custom = Setting::get('blog_autopilot.product_brief');
        return is_string($custom) && trim($custom) !== ''
            && trim($custom) !== trim(self::DEFAULT_PRODUCT_BRIEF);
    }

    /** The product brief injected into the prompt, placeholders filled in. */
    public static function productBrief(): string
    {
        return str_replace('{pro_price}', Pricing::proMonthly(), self::rawProductBrief());
    }

    /**
     * Facts that are always appended after the (possibly admin-edited) brief,
     * so a stale custom brief can't make posts claim the wrong price or
     * features. These win on any conflict.
     */
    public static function coreFacts(): string
    {
        $price = Pricing::proMonthly();

        return implode("\n", [
            "- Pro costs {$price}/month on the web, billed monthly. There is no annual plan and no Pro trial.",
            '- Free plan: 1 business, up to 2 team members, unlimited books and entries.',
            '- Recurring entries repeat daily, weekly or every 2 weeks only — never say monthly or yearly.',
            '- AI entries: Free gets 10 a month (receipt scans and typed entries combined); Pro gets 200 receipt scans a month plus typed entries within fair use.',
            '- AI category suggestions are free on every plan. AI cash flow insights are Pro only.',
            '- The iOS and Android apps are coming soon and are NOT available yet.',
            '- No bank sync, invoicing, payroll, tax filing, forecasting, offline mode or automatic backups.',
        ]);
    }

    /**
     * Run the full pipeline. Returns the created BlogPost.
     *
     * @param  bool  $force  If true, skip the "enabled" + "recently ran" guards.
     *                       Used by the admin "Generate Now" button.
     *
     * @throws BlogAutopilotSkipped  expected, non-error skips
     * @throws \Throwable            real failures (already reported + recorded)
     */
    public function run(bool $force = false): BlogPost
    {
        if (! $force && ! self::isEnabled()) {
            throw new BlogAutopilotSkipped('Autopilot is disabled. Turn it on in /admin/blog/autopilot.');
        }

        // One run at a time across processes/servers (scheduler + Generate Now).
        $lock = Cache::lock('blog-autopilot:run', 900);
        if (! $lock->get()) {
            throw new BlogAutopilotSkipped('Another autopilot run is already in progress.');
        }

        try {
            if (! $force) {
                $this->assertNotRecentlyPublished();
            }

            $post = $this->publishNext();

            Setting::forget('blog_autopilot.last_error');
            $this->notifyIfQueueLow();

            return $post;
        } catch (BlogAutopilotSkipped $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);
            Log::error('BlogAutopilot: run failed', ['err' => $e->getMessage(), 'force' => $force]);
            Setting::set('blog_autopilot.last_error', $e->getMessage() . ' @ ' . now()->toIso8601String());
            throw $e;
        } finally {
            $lock->release();
        }
    }

    private function publishNext(): BlogPost
    {
        $item = $this->pickNextQueueItem();
        if (! $item) {
            $this->notifyIfQueueLow();
            throw new BlogAutopilotSkipped('Queue is empty — add titles at /admin/blog/autopilot.');
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

        $clean = $this->generateValidated($item->title, $allCategories, $preselected);

        // Same-title guard (case-insensitive). If Claude's refined title
        // collides with an existing post, fall back to the seed title (already
        // checked unique); otherwise send the row to the back of the queue so
        // it can't block every future run.
        if (self::titleExists($clean['title'])) {
            if (! self::titleExists($item->title)) {
                $clean['title'] = $item->title;
            } else {
                $item->update(['position' => ((int) BlogAutopilotQueueItem::max('position')) + 10]);
                throw new \RuntimeException('Generated title duplicates an existing post: ' . $clean['title']);
            }
        }

        $category = $preselected
            ?? $allCategories->firstWhere('slug', $clean['category_slug'] ?? null)
            ?? $allCategories->first();

        $clean['slug'] = $this->uniqueSlug($clean['slug']);

        // reading_time is derived from body_markdown by the BlogPost saving hook.
        $post = BlogPost::create([
            'title'              => $clean['title'],
            'slug'               => $clean['slug'],
            'excerpt'            => $clean['excerpt'],
            'body_markdown'      => $clean['body_markdown'],
            'seo_title'          => $clean['seo_title'],
            'seo_description'    => $clean['seo_description'],
            'featured_image_alt' => $clean['image_alt'] ?? $clean['title'],
            'image_query'        => $clean['image_query'] ?? null,
            'category_id'        => $category->id,
            'author_id'          => null,
            'status'             => 'published',
            'is_featured'        => false,
            'auto_topic_key'     => 'q-' . $item->id,
        ]);

        // Featured image — failure here shouldn't block the post going live,
        // but it MUST be visible. report() sends the full exception to Sentry;
        // the Setting record lets the admin UI surface "last image error" on
        // the autopilot page without a separate log dive.
        try {
            $key = $this->renderer->renderForPost(
                $post->id,
                $post->title,
                $category,
                $clean['image_query'] ?? null
            );
            // Quiet save: attaching the image right after creation isn't an
            // edit worth a second updated_at.
            $post->featured_image_key = $key;
            $post->featured_image_credit = $this->renderer->lastPhotoCredit();
            $post->saveQuietly();
            Setting::forget('blog_autopilot.last_image_error');
        } catch (\Throwable $e) {
            report($e);
            Log::warning('BlogAutopilot: image render failed', [
                'post_id' => $post->id,
                'err'     => $e->getMessage(),
            ]);
            Setting::set(
                'blog_autopilot.last_image_error',
                $e->getMessage() . ' @ ' . now()->toIso8601String()
            );
        }

        // Consume the queue row + stamp last-run timestamp
        $item->delete();
        Setting::set('blog_autopilot.last_run_at', now()->toIso8601String());

        return $post->fresh();
    }

    /**
     * Next queue row whose title hasn't already been published. Rows whose
     * title matches an existing post (case-insensitive) are removed — the
     * topic is covered and re-running it would publish a duplicate.
     */
    public function pickNextQueueItem(): ?BlogAutopilotQueueItem
    {
        for ($i = 0; $i < 50; $i++) {
            $item = BlogAutopilotQueueItem::orderBy('position')->orderBy('created_at')->first();
            if (! $item) {
                return null;
            }
            if (! self::titleExists($item->title)) {
                return $item;
            }
            Log::info('BlogAutopilot: dropping queue title that already exists as a post', ['title' => $item->title]);
            $item->delete();
        }
        return null;
    }

    /** Case-insensitive "is there already a post with this title?" */
    public static function titleExists(string $title): bool
    {
        return BlogPost::whereRaw('LOWER(title) = ?', [mb_strtolower(trim($title))])->exists();
    }

    // ─── Claude call ───────────────────────────────────────────────────

    /**
     * Generate + validate, with one follow-up request if the body comes back
     * under MIN_WORDS.
     */
    private function generateValidated(string $titleSeed, Collection $allCategories, ?BlogCategory $preselected): array
    {
        $prompt   = $this->buildPrompt($titleSeed, $allCategories, $preselected);
        $messages = [['role' => 'user', 'content' => [['type' => 'text', 'text' => $prompt]]]];

        [$data, $rawText] = $this->callClaude($messages);

        $words = BlogPost::wordCount(is_string($data['body_markdown'] ?? null) ? $data['body_markdown'] : '');
        if ($words < self::MIN_WORDS) {
            Log::info('BlogAutopilot: body too short, asking for an expansion', ['words' => $words]);

            $messages[] = ['role' => 'assistant', 'content' => [['type' => 'text', 'text' => $rawText]]];
            $messages[] = ['role' => 'user', 'content' => [['type' => 'text', 'text' =>
                "That body_markdown is only {$words} words. The minimum is " . self::MIN_WORDS . ' words; aim for about 1400. '
                . 'Expand it: add concrete, practical examples, a short worked example with simple numbers, and a step-by-step checklist where it fits. '
                . 'Keep every rule from my first message (today\'s date, no invented statistics or studies, no invented features, same topic and title). '
                . 'Return the complete JSON object again, in the same format, with the full expanded body.',
            ]]];

            [$data] = $this->callClaude($messages);
        }

        return $this->validate($data, $titleSeed);
    }

    private function buildPrompt(string $titleSeed, Collection $allCategories, ?BlogCategory $preselected): string
    {
        $appName = config('app.name', 'TheCashFox');
        $appUrl  = rtrim(config('app.url', 'https://thecashfox.com'), '/');
        $today   = now()->format('l, j F Y');
        $year    = now()->year;
        $minWords = self::MIN_WORDS;

        // JSON-encode user-controlled strings to neutralise prompt injection
        $titleJson = json_encode($titleSeed, JSON_UNESCAPED_UNICODE);

        // Real published posts, so Claude links to URLs that exist instead of
        // inventing slugs. Invented blog links are 404s and cost more in SEO
        // than the internal link would ever earn.
        $existing = BlogPost::published()
            ->latestFirst()
            ->limit(12)
            ->get(['title', 'slug']);

        $linkBlock = $existing->isEmpty()
            ? "   (No earlier posts exist yet — skip post-to-post linking this time.)"
            : $existing->map(fn ($p) => '   * ' . json_encode($p->title, JSON_UNESCAPED_UNICODE)
                . ' → ' . $appUrl . '/blog/' . $p->slug)->implode("\n");

        if ($preselected) {
            $categoryBlock = '- Category (fixed, do not change): ' .
                json_encode($preselected->name, JSON_UNESCAPED_UNICODE);
            $categoryJsonField = '';
            $categoryPickRule  = '';
        } else {
            $list = $allCategories->map(function ($c) {
                return '  * ' . json_encode($c->slug, JSON_UNESCAPED_UNICODE) . ' — '
                    . json_encode($c->name, JSON_UNESCAPED_UNICODE)
                    . ($c->description ? ' (' . json_encode($c->description, JSON_UNESCAPED_UNICODE) . ')' : '');
            })->implode("\n");

            $categoryBlock = "- Category: pick the single best-fitting category by slug from this list:\n{$list}";
            $categoryJsonField = "\n  \"category_slug\":  \"one slug from the list above\",";
            $categoryPickRule  = "15. category_slug MUST be exactly one of the slugs listed above — no inventions, no renaming.\n";
        }

        $brief     = self::productBrief();
        $coreFacts = self::coreFacts();

        return <<<PROMPT
You are a senior content writer for {$appName}, a cash-flow tracking web app at {$appUrl}.
Write ONE practical, SEO-optimised blog post that small-business owners and freelancers will find genuinely useful — not generic AI fluff.

Today's date is {$today}. The current year is {$year}.

=== PRODUCT FACTS (reference these when relevant, never invent features) ===
{$brief}

Always true (these override anything above if they conflict):
{$coreFacts}
=== END PRODUCT FACTS ===

Post brief:
- Seed title (you may refine for SEO, keep the same topic): {$titleJson}
{$categoryBlock}

Rules:
1. Final display title ≤ 60 chars. Preserve the seed title's subject and primary keyword.
2. Meta description ≤ 155 chars. Include the primary keyword naturally.
3. Excerpt ≤ 220 chars. Must hook the reader — a concrete promise, not a generic summary.
4. Body: 1200–1800 words of plain markdown (hard minimum {$minWords} words — shorter posts are rejected). Structure:
   - Short intro (2–3 sentences, NO greeting, NO "in this post we'll cover").
   - 4–6 H2 sections (## headings), each with real substance (150+ words). Each H2 should contain a related keyword phrase.
   - Include at least one worked example with simple numbers and one practical checklist or step list.
   - Use bullet lists where helpful.
   - End with ONE final call-to-action sentence pointing to {$appUrl}/register.
5. Dates and facts — CRITICAL:
   - Write as of today ({$today}). Avoid naming specific years unless essential; if you do, the current year is {$year}. Never describe a past year as "this year" and never present old dates as current.
   - Do NOT cite or invent statistics, surveys, studies, research, reports, expert quotes or percentages from any outside source, and don't attribute numbers to organisations. Every number must either come from PRODUCT FACTS or be clearly framed as an illustrative example ("Say you spend 15 minutes a day…").
   - No first-person anecdotes ("When I ran my shop…"), no invented customers, testimonials or case studies.
   - Don't state tax, legal or regulatory rules for any specific country.
6. Product mentions — CRITICAL:
   - Reference product features ONLY when they're genuinely relevant to the topic. Max 2–3 references per post. NEVER force them.
   - When referenced, use concrete details from PRODUCT FACTS (e.g. "200 scans a month on Pro", not "some scans").
   - NEVER invent features, numbers, integrations or capabilities not in PRODUCT FACTS. Never say the mobile apps are available.
   - For purely educational topics (e.g. "What is cash flow?"), you may write the post without any product mentions at all — a single CTA at the end is enough.
7. Internal links, markdown syntax, where they genuinely help the reader:
   - 1–2 links to the product, e.g. [sign up free]({$appUrl}/register)
   - 1–2 links to EARLIER POSTS from this exact list, using the exact URL shown. Link only where the topic really connects, and use descriptive anchor text (never "click here" or "this post"):
{$linkBlock}
   - NEVER invent a blog URL. If nothing on the list fits, link none.
8. Global audience: no country-specific currencies (show amounts as plain numbers or USD), no region-specific framing.
9. Do NOT mention you are an AI. Do NOT use: "in today's fast-paced world", "in conclusion", "unlock", "leverage", "delve", "elevate", "synergy", "harness", "embark", "journey", "game-changer".
10. Do NOT include an H1 (#) — the title is rendered separately.
11. Do NOT wrap body in code fences.
12. No emojis. No table of contents.
13. Slug: lowercase, hyphenated, 3–6 words, must contain the primary keyword from the title.
14. SEO title may differ slightly from display title if it improves keyword density — both ≤ 60 chars.
15. image_query: a concrete scene a stock photographer would shoot, e.g. "small business owner desk", "coffee shop counter receipts", "warehouse inventory clipboard". NOT abstract nouns ("growth", "success"), NOT brand names, NOT text or UI. Plain words only.
{$categoryPickRule}
Return ONLY this JSON object (no markdown fences, no prose outside JSON):

{
  "title":           "display title",
  "slug":            "primary-keyword-slug",{$categoryJsonField}
  "excerpt":         "≤ 220 chars hook",
  "body_markdown":   "full markdown body",
  "seo_title":       "≤ 60 chars",
  "seo_description": "≤ 155 chars meta description",
  "image_query":     "2–4 plain words naming a PHOTOGRAPHABLE scene for the header image",
  "image_alt":       "≤ 120 chars describing that scene for screen readers — describe the PICTURE, never repeat the title"
}
PROMPT;
    }

    /**
     * One Messages API call. Logs usage for every call (including the
     * expansion retry). Returns [decoded JSON array, raw text].
     */
    private function callClaude(array $messages): array
    {
        if ($this->apiKey === '') {
            throw new \RuntimeException('ANTHROPIC_API_KEY is not configured.');
        }

        $response = Http::withHeaders([
            'x-api-key'         => $this->apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ])->timeout(180)->post('https://api.anthropic.com/v1/messages', [
            'model'      => $this->model,
            'max_tokens' => 8000,
            'messages'   => $messages,
        ]);

        if (! $response->successful()) {
            Log::warning('BlogAutopilot: Claude API error', ['status' => $response->status()]);
            throw new \RuntimeException('Claude API error: HTTP ' . $response->status());
        }

        $json = $response->json();

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

        $stop = $json['stop_reason'] ?? null;
        if ($stop === 'max_tokens') {
            throw new \RuntimeException('Claude output was cut off (max_tokens) — post not saved.');
        }
        if ($stop === 'refusal') {
            throw new \RuntimeException('Claude declined to write this post — try rewording the title.');
        }

        $text = '';
        foreach ((array) ($json['content'] ?? []) as $block) {
            if (($block['type'] ?? null) === 'text') {
                $text .= $block['text'] ?? '';
            }
        }

        // Parse JSON (strip defensive code fences)
        $clean = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', trim($text));
        $decoded = json_decode((string) $clean, true);
        if (! is_array($decoded)) {
            throw new \RuntimeException('Claude returned unparseable JSON.');
        }

        return [$decoded, $text];
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

        // Word count guard on the final body (same counter as reading_time)
        $wc = BlogPost::wordCount($body);
        if ($wc < self::MIN_WORDS) {
            throw new \RuntimeException("Body too short ({$wc} < " . self::MIN_WORDS . ' words).');
        }
        if ($wc > self::MAX_WORDS) {
            throw new \RuntimeException("Body too long ({$wc} > " . self::MAX_WORDS . ' words).');
        }

        // Optional image_query — a photo search phrase for the header image.
        // Optional because an older prompt or a stubborn model may omit it;
        // the renderer just falls back to its typographic design.
        $imageQuery = null;
        if (isset($data['image_query']) && is_string($data['image_query'])) {
            $iq = trim(preg_replace('/\s+/', ' ', $data['image_query']) ?? '');
            if ($iq !== '') {
                $imageQuery = mb_substr($iq, 0, 120);
            }
        }

        // Optional image_alt — describes the photo, not the post. Falls back
        // to the title so the attribute is never empty.
        $imageAlt = null;
        if (isset($data['image_alt']) && is_string($data['image_alt'])) {
            $ia = trim(preg_replace('/\s+/', ' ', $data['image_alt']) ?? '');
            if ($ia !== '') {
                $imageAlt = mb_substr($ia, 0, 120);
            }
        }

        // Optional category_slug — only present when admin didn't pre-select.
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
            'image_query'     => $imageQuery,
            'image_alt'       => $imageAlt,
        ];
    }

    private function uniqueSlug(string $slug): string
    {
        $slug = mb_substr($slug, 0, 180);
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

    // ─── Guards + alerts ───────────────────────────────────────────────

    private function assertNotRecentlyPublished(): void
    {
        $lastAuto = BlogPost::whereNotNull('auto_topic_key')
            ->latest('created_at')
            ->first();

        if ($lastAuto && $lastAuto->created_at->gt(now()->subHours(self::MIN_HOURS_BETWEEN_POSTS))) {
            throw new BlogAutopilotSkipped(
                'Last auto-post was ' . $lastAuto->created_at->diffForHumans() .
                ' — within the ' . self::MIN_HOURS_BETWEEN_POSTS . 'h cooldown.'
            );
        }
    }

    /**
     * When the queue is running dry, log it and email the admins — at most
     * once per day so a stuck queue doesn't spam. Never throws.
     */
    public function notifyIfQueueLow(): void
    {
        try {
            $remaining = BlogAutopilotQueueItem::count();
            if ($remaining > self::LOW_QUEUE_THRESHOLD) {
                return;
            }

            Log::warning('BlogAutopilot: queue running low', ['remaining' => $remaining]);

            $today = now()->toDateString();
            if (Setting::get('blog_autopilot.low_queue_notified_on') === $today) {
                return;
            }
            Setting::set('blog_autopilot.low_queue_notified_on', $today);

            $emails = User::where('is_admin', true)->pluck('email')->filter()->all();
            if (empty($emails)) {
                return;
            }

            $appName = config('app.name', 'TheCashFox');
            $url     = rtrim(config('app.url', ''), '/') . '/admin/blog/autopilot';
            $status  = $remaining === 0
                ? 'The blog autopilot queue is EMPTY — no post will be published until you add titles.'
                : "Only {$remaining} title" . ($remaining === 1 ? '' : 's') . ' left in the blog autopilot queue '
                  . '(one is published per day).';

            Mail::raw(
                "{$status}\n\nAdd more titles here: {$url}\n\n— {$appName} blog autopilot",
                function ($m) use ($emails, $appName, $remaining) {
                    $m->to($emails)->subject(
                        "[{$appName}] Blog queue " . ($remaining === 0 ? 'is empty' : "low: {$remaining} left")
                    );
                }
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
