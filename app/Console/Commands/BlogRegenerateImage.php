<?php

namespace App\Console\Commands;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Services\BlogImageRenderer;
use Illuminate\Console\Command;

/**
 * Re-render the featured image for one or many blog posts. Useful when the
 * autopilot's image step failed (e.g. missing fonts on a fresh deploy) or
 * when a post was created manually without an image.
 *
 * Usage:
 *   php artisan blog:regenerate-image --all-missing
 *   php artisan blog:regenerate-image --slug=my-post-slug
 *   php artisan blog:regenerate-image --latest
 */
class BlogRegenerateImage extends Command
{
    protected $signature = 'blog:regenerate-image
        {--slug= : Regenerate for this slug}
        {--latest : Regenerate for the most recent post}
        {--all-missing : Regenerate for every published post missing an image}';

    protected $description = 'Render the featured image for blog post(s)';

    public function handle(BlogImageRenderer $renderer): int
    {
        $slug = $this->option('slug');
        $latest = (bool) $this->option('latest');
        $allMissing = (bool) $this->option('all-missing');

        if (! $slug && ! $latest && ! $allMissing) {
            $this->error('Pass --slug=..., --latest, or --all-missing.');
            return self::FAILURE;
        }

        $query = BlogPost::query()->with('category');
        if ($slug) {
            $query->where('slug', $slug);
        } elseif ($latest) {
            $query->latest('created_at')->limit(1);
        } else { // all-missing
            $query->whereNull('featured_image_key')->where('status', 'published');
        }

        $posts = $query->get();
        if ($posts->isEmpty()) {
            $this->warn('No matching posts.');
            return self::SUCCESS;
        }

        $ok = 0;
        $fail = 0;

        foreach ($posts as $post) {
            try {
                $key = $renderer->renderForPost($post->id, $post->title, $post->category);
                $post->update(['featured_image_key' => $key]);
                $this->info('✓ ' . $post->slug);
                $ok++;
            } catch (\Throwable $e) {
                $this->error('✗ ' . $post->slug . ' — ' . $e->getMessage());
                report($e);
                $fail++;
            }
        }

        $this->line('');
        $this->line("Done. {$ok} rendered, {$fail} failed.");
        return $fail > 0 ? self::FAILURE : self::SUCCESS;
    }
}
