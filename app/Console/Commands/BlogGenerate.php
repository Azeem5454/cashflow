<?php

namespace App\Console\Commands;

use App\Exceptions\BlogAutopilotSkipped;
use App\Services\BlogAutopilot;
use Illuminate\Console\Command;

/**
 * Generate + publish one blog post from the top of the autopilot queue.
 * Scheduled daily at 09:00 (app timezone = UTC) in routes/console.php.
 *
 * Usage:
 *   php artisan blog:generate           # Respects the admin enable toggle
 *   php artisan blog:generate --force   # Ignores toggle + cooldown (admin UI uses this)
 */
class BlogGenerate extends Command
{
    protected $signature = 'blog:generate {--force : Skip the enabled toggle and cooldown check}';

    protected $description = 'Generate and publish one blog post from the autopilot queue';

    public function handle(BlogAutopilot $autopilot): int
    {
        $force = (bool) $this->option('force');

        try {
            $post = $autopilot->run(force: $force);
            $this->info('Published: ' . $post->title);
            $this->line('  Slug:     ' . $post->slug);
            $this->line('  Category: ' . ($post->category?->name ?? '—'));
            $this->line('  Words:    ' . \App\Models\BlogPost::wordCount($post->body_markdown) . ' (' . $post->reading_time . ' min read)');
            $this->line('  Image:    ' . ($post->featured_image_key ? 'yes' : 'MISSING — see /admin/blog/autopilot'));
            $this->line('  URL:      ' . $post->url());
            return self::SUCCESS;
        } catch (BlogAutopilotSkipped $e) {
            // Expected skips (disabled, empty queue, cooldown, run in progress)
            // exit cleanly so the cron log doesn't fill with red noise.
            $this->warn('Skipped: ' . $e->getMessage());
            return self::SUCCESS;
        } catch (\Throwable $e) {
            // Already reported to Sentry + recorded for the admin banner by
            // BlogAutopilot::run().
            $this->error('Failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
