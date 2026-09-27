<?php

namespace App\Console\Commands;

use App\Models\BlogPost;
use App\Services\BlogAutopilot;
use Illuminate\Console\Command;

/**
 * Removes em and en dashes from existing posts.
 *
 * New posts are handled at generation time, but the ones already published
 * were written before the rule existed. The dash is one of the clearest
 * signals that a machine wrote the sentence, so it is worth a pass over the
 * back catalogue.
 *
 *   php artisan blog:dedash --dry-run
 *   php artisan blog:dedash
 */
class BlogDeDash extends Command
{
    protected $signature = 'blog:dedash {--dry-run : Show what would change without saving}';

    protected $description = 'Replace em and en dashes in published posts with commas';

    private const FIELDS = ['title', 'excerpt', 'body_markdown', 'seo_title', 'seo_description', 'featured_image_alt'];

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $touched = 0;
        $total = 0;

        foreach (BlogPost::query()->cursor() as $post) {
            $changes = [];

            foreach (self::FIELDS as $field) {
                $value = $post->{$field};
                if (! is_string($value) || ! preg_match('/[—–]/u', $value)) {
                    continue;
                }

                $count = preg_match_all('/[—–]/u', $value);
                $post->{$field} = BlogAutopilot::deDash($value);
                $changes[$field] = $count;
                $total += $count;
            }

            if ($changes === []) {
                continue;
            }

            $touched++;
            $this->line(sprintf(
                '  %-62s %s',
                $post->slug,
                collect($changes)->map(fn ($n, $f) => "{$f}:{$n}")->implode(' ')
            ));

            if (! $dry) {
                // A save regenerates body_html and reading_time from the
                // markdown, and moves updated_at — correct here, this is a
                // real edit that sitemap lastmod should reflect.
                $post->save();
            }
        }

        $this->newLine();

        if ($touched === 0) {
            $this->info('No dashes found.');
            return self::SUCCESS;
        }

        $this->info(sprintf(
            '%s %d dash(es) across %d post(s).',
            $dry ? 'Would replace' : 'Replaced',
            $total,
            $touched
        ));

        if ($dry) {
            $this->comment('Dry run — nothing saved. Re-run without --dry-run to apply.');
        }

        return self::SUCCESS;
    }
}
