<?php

namespace App\Livewire\Admin\Blog;

use App\Helpers\Setting;
use App\Models\BlogAutopilotQueueItem;
use App\Models\BlogCategory;
use App\Services\BlogAutopilot as AutopilotService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Blog Autopilot')]
class Autopilot extends Component
{
    public bool   $enabled         = false;
    public string $bulkTitles      = '';
    public string $newTitle        = '';
    public ?string $newCategoryId  = null;
    public bool   $generating      = false;
    public string $productBrief    = '';
    public bool   $briefEditOpen   = false;

    public function mount(): void
    {
        $this->enabled      = AutopilotService::isEnabled();
        $this->productBrief = AutopilotService::productBrief();
    }

    public function render()
    {
        $items = BlogAutopilotQueueItem::with('category')
            ->orderBy('position')
            ->orderBy('created_at')
            ->get();

        $lastRunIso = Setting::get('blog_autopilot.last_run_at');
        $lastRun = $lastRunIso ? Carbon::parse($lastRunIso) : null;

        // Count published posts that never got an image — lets the UI show
        // a single-click retry link when the autopilot's GD step failed.
        $missingImagesCount = \App\Models\BlogPost::query()
            ->whereNull('featured_image_key')
            ->where('status', 'published')
            ->count();

        // Live diagnostic — re-evaluated on every render so you always see
        // the CURRENT container state, not whatever was true when the
        // last_image_error was persisted yesterday.
        $gdLoaded = extension_loaded('gd');
        $ftLoaded = function_exists('imagettftext');
        $fontDir  = storage_path('fonts');
        $fontsOk  = is_file($fontDir . '/BricolageGrotesque-Bold.ttf')
                 && is_file($fontDir . '/Outfit-Regular.ttf')
                 && is_file($fontDir . '/Outfit-SemiBold.ttf');

        return view('livewire.admin.blog.autopilot', [
            'items'              => $items,
            'categories'         => BlogCategory::orderBy('name')->get(),
            'lastRun'            => $lastRun,
            'nextRunHint'        => $this->describeNextRun(),
            'lastImageError'     => Setting::get('blog_autopilot.last_image_error'),
            'missingImagesCount' => $missingImagesCount,
            'diag' => [
                'gd'    => $gdLoaded,
                'ft'    => $ftLoaded,
                'fonts' => $fontsOk,
                'allOk' => $gdLoaded && $ftLoaded && $fontsOk,
            ],
        ]);
    }

    // ─── Toggle ────────────────────────────────────────────────────────

    public function toggle(): void
    {
        $this->enabled = ! $this->enabled;
        Setting::set('blog_autopilot.enabled', $this->enabled ? '1' : '0');

        $this->dispatch('autopilot-toast',
            message: $this->enabled ? 'Autopilot is ON — next run tomorrow at 09:00 UTC.' : 'Autopilot is paused.'
        );
    }

    // ─── Queue mutations ──────────────────────────────────────────────

    public function addSingle(): void
    {
        $this->validate([
            'newTitle'       => ['required', 'string', 'min:8', 'max:255'],
            'newCategoryId'  => ['nullable', 'uuid', 'exists:blog_categories,id'],
        ], [], [
            'newTitle'      => 'title',
            'newCategoryId' => 'category',
        ]);

        $title = trim($this->newTitle);
        if (BlogAutopilotQueueItem::where('title', $title)->exists()) {
            $this->addError('newTitle', 'That title is already in the queue.');
            return;
        }

        $nextPos = ((int) BlogAutopilotQueueItem::max('position')) + 10;

        BlogAutopilotQueueItem::create([
            'title'       => $title,
            'category_id' => $this->newCategoryId ?: null,
            'position'    => $nextPos,
        ]);

        $this->newTitle = '';
        $this->newCategoryId = null;
        $this->dispatch('autopilot-toast', message: 'Added to queue.');
    }

    public function addBulk(): void
    {
        $raw = trim($this->bulkTitles);
        if ($raw === '') {
            $this->addError('bulkTitles', 'Paste at least one title.');
            return;
        }

        $lines = preg_split('/\r?\n/', $raw) ?: [];
        $added = 0;
        $skipped = 0;
        $pos = ((int) BlogAutopilotQueueItem::max('position')) + 10;

        foreach ($lines as $line) {
            $title = trim($line);
            if ($title === '' || mb_strlen($title) < 8 || mb_strlen($title) > 255) {
                $skipped++;
                continue;
            }
            if (BlogAutopilotQueueItem::where('title', $title)->exists()) {
                $skipped++;
                continue;
            }
            BlogAutopilotQueueItem::create([
                'title'    => $title,
                'position' => $pos,
            ]);
            $pos += 10;
            $added++;
        }

        $this->bulkTitles = '';
        $this->dispatch('autopilot-toast',
            message: "Added {$added} title" . ($added === 1 ? '' : 's') .
                     ($skipped > 0 ? " · {$skipped} skipped (duplicate or invalid)" : '')
        );
    }

    public function deleteItem(string $id): void
    {
        BlogAutopilotQueueItem::whereKey($id)->delete();
        $this->dispatch('autopilot-toast', message: 'Removed from queue.');
    }

    public function updateCategory(string $id, ?string $categoryId): void
    {
        $categoryId = $categoryId ?: null;

        if ($categoryId && ! BlogCategory::whereKey($categoryId)->exists()) {
            return;
        }

        BlogAutopilotQueueItem::whereKey($id)->update(['category_id' => $categoryId]);
    }

    /**
     * Called from the SortableJS `onEnd` hook with the new ID order. Writes
     * positions in multiples of 10 so later drag-and-drops don't need a full
     * re-pack (there's room between each slot).
     */
    public function reorder(array $ids): void
    {
        $pos = 10;
        DB::transaction(function () use ($ids, &$pos) {
            foreach ($ids as $id) {
                if (! is_string($id) || ! preg_match('/^[0-9a-f\-]{36}$/i', $id)) {
                    continue;
                }
                BlogAutopilotQueueItem::whereKey($id)->update(['position' => $pos]);
                $pos += 10;
            }
        });
    }

    // ─── Product brief ────────────────────────────────────────────────

    public function saveBrief(): void
    {
        $this->validate([
            'productBrief' => ['required', 'string', 'min:50', 'max:8000'],
        ], [], ['productBrief' => 'product brief']);

        // Only write to the Setting if it differs from the baked-in default —
        // keeps the row out of the settings table when the admin hasn't
        // customised anything.
        $trimmed = trim($this->productBrief);
        if ($trimmed === trim(AutopilotService::DEFAULT_PRODUCT_BRIEF)) {
            Setting::forget('blog_autopilot.product_brief');
        } else {
            Setting::set('blog_autopilot.product_brief', $trimmed);
        }

        $this->briefEditOpen = false;
        $this->dispatch('autopilot-toast', message: 'Product brief saved.');
    }

    public function resetBriefToDefault(): void
    {
        $this->productBrief = AutopilotService::DEFAULT_PRODUCT_BRIEF;
        Setting::forget('blog_autopilot.product_brief');
        $this->briefEditOpen = false;
        $this->dispatch('autopilot-toast', message: 'Product brief reset to default.');
    }

    // ─── Run now ──────────────────────────────────────────────────────

    public function runNow(): void
    {
        $this->generating = true;

        try {
            $post = app(AutopilotService::class)->run(force: true);
            $this->dispatch('autopilot-toast', message: 'Published: ' . $post->title);
        } catch (\Throwable $e) {
            $this->dispatch('autopilot-toast', message: 'Failed: ' . $e->getMessage(), error: true);
        } finally {
            $this->generating = false;
        }
    }

    /**
     * Re-render featured images for every published post missing one.
     * Same logic as `php artisan blog:regenerate-image --all-missing` but
     * callable from the admin UI.
     */
    public function retryMissingImages(): void
    {
        $renderer = app(\App\Services\BlogImageRenderer::class);

        $posts = \App\Models\BlogPost::query()
            ->with('category')
            ->whereNull('featured_image_key')
            ->where('status', 'published')
            ->get();

        if ($posts->isEmpty()) {
            $this->dispatch('autopilot-toast', message: 'No posts missing images.');
            return;
        }

        $ok = 0;
        $fail = 0;
        $lastErr = null;

        foreach ($posts as $post) {
            try {
                $key = $renderer->renderForPost($post->id, $post->title, $post->category);
                $post->update(['featured_image_key' => $key]);
                $ok++;
            } catch (\Throwable $e) {
                report($e);
                $lastErr = $e->getMessage();
                $fail++;
            }
        }

        if ($fail === 0) {
            Setting::forget('blog_autopilot.last_image_error');
            $this->dispatch('autopilot-toast', message: "Rendered {$ok} image" . ($ok === 1 ? '' : 's') . '.');
        } else {
            Setting::set(
                'blog_autopilot.last_image_error',
                $lastErr . ' @ ' . now()->toIso8601String()
            );
            $this->dispatch('autopilot-toast',
                message: "Rendered {$ok}, {$fail} failed — " . $lastErr,
                error: true
            );
        }
    }

    public function clearImageError(): void
    {
        Setting::forget('blog_autopilot.last_image_error');
        $this->dispatch('autopilot-toast', message: 'Error dismissed.');
    }

    // ─── Helpers ──────────────────────────────────────────────────────

    private function describeNextRun(): string
    {
        if (! $this->enabled) {
            return 'Paused — turn the toggle on to resume daily runs.';
        }

        // Schedule runs dailyAt('09:00') UTC
        $next = Carbon::now('UTC')->setTime(9, 0);
        if ($next->isPast()) {
            $next->addDay();
        }

        return 'Next run: ' . $next->format('D, M j, H:i') . ' UTC (' . $next->diffForHumans() . ')';
    }
}
