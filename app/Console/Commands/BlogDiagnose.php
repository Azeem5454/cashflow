<?php

namespace App\Console\Commands;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use Illuminate\Console\Command;

/**
 * Emergency diagnostic + auto-repair for the blog. Prints the live state
 * of every post + category, then offers to force-publish any drafts that
 * look stuck (status=draft but title+body present).
 *
 *   php artisan blog:diagnose              → read-only report
 *   php artisan blog:diagnose --fix        → flip any stuck drafts to published
 *   php artisan blog:diagnose --fix --all  → force EVERY post to status=published
 */
class BlogDiagnose extends Command
{
    protected $signature = 'blog:diagnose
                            {--fix : Flip drafts with content to status=published}
                            {--all : With --fix, force ALL posts to status=published}';

    protected $description = 'Report blog DB state and optionally force-publish stuck drafts';

    public function handle(): int
    {
        $total     = BlogPost::count();
        $published = BlogPost::where('status', 'published')->count();
        $drafts    = BlogPost::where('status', 'draft')->count();
        $visible   = BlogPost::published()->count();

        $this->newLine();
        $this->line('<fg=cyan>── Blog state ───────────────────────────────</>');
        $this->line("Total posts:             {$total}");
        $this->line("status='published':      {$published}");
        $this->line("status='draft':          {$drafts}");
        $this->line("Visible on /blog:        <fg=green>{$visible}</>");
        $this->newLine();

        if ($total === 0) {
            $this->warn('No posts in the database. Write one at /admin/blog/create.');
            return self::SUCCESS;
        }

        $this->line('<fg=cyan>── Posts ────────────────────────────────────</>');
        $this->table(
            ['Title', 'Slug', 'Status', 'Published at', 'Category', 'Visible?'],
            BlogPost::with('category')->orderByDesc('created_at')->get()->map(function (BlogPost $p) {
                $visible = $p->status === 'published'
                    && (is_null($p->published_at) || $p->published_at->lte(now()));
                return [
                    \Illuminate\Support\Str::limit($p->title, 42),
                    $p->slug,
                    $p->status,
                    $p->published_at?->format('Y-m-d H:i') ?? '—',
                    $p->category?->name ?? '—',
                    $visible ? '✓' : '✗',
                ];
            }),
        );

        $this->newLine();
        $this->line('<fg=cyan>── Categories ───────────────────────────────</>');
        $this->table(
            ['Name', 'Slug', 'post_count'],
            BlogCategory::orderBy('name')->get()->map(fn ($c) => [
                $c->name, $c->slug, $c->post_count,
            ]),
        );

        if ($this->option('fix')) {
            $this->newLine();
            $this->warn('Applying fixes...');

            if ($this->option('all')) {
                $updated = BlogPost::where('status', '!=', 'published')
                    ->update(['status' => 'published']);
                $this->info("Forced {$updated} post(s) to status=published.");
            } else {
                $updated = 0;
                BlogPost::where('status', 'draft')
                    ->whereNotNull('title')
                    ->whereNotNull('body_markdown')
                    ->get()
                    ->each(function (BlogPost $p) use (&$updated) {
                        $p->status = 'published';
                        $p->save(); // saving() hook stamps published_at
                        $updated++;
                    });
                $this->info("Promoted {$updated} stuck draft(s) to published.");
            }

            BlogCategory::all()->each->refreshPostCount();
            $this->info('Refreshed category post counts.');
        } else {
            $this->newLine();
            $this->line('<fg=yellow>Run with --fix to auto-publish stuck drafts, or --fix --all to force everything.</>');
        }

        return self::SUCCESS;
    }
}
