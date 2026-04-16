<?php

namespace App\Console\Commands;

use App\Services\BlogAutopilot;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Generate + publish one blog post from the top of the autopilot queue.
 * Scheduled daily at 09:00 UTC in routes/console.php.
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
            $this->line('  URL:      ' . rtrim(config('app.url', ''), '/') . '/blog/' . $post->slug);
            return self::SUCCESS;
        } catch (\Throwable $e) {
            // Expected skips (disabled, empty queue, cooldown) should exit cleanly
            // so cron log doesn't fill with red noise.
            $message = $e->getMessage();
            $expected = str_contains($message, 'disabled')
                || str_contains($message, 'empty')
                || str_contains($message, 'cooldown');

            if ($expected) {
                $this->warn('Skipped: ' . $message);
                return self::SUCCESS;
            }

            $this->error('Failed: ' . $message);
            Log::error('BlogGenerate command failed', [
                'err' => $message,
                'force' => $force,
            ]);
            return self::FAILURE;
        }
    }
}
