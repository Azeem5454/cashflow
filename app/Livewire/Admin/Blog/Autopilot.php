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

    public function mount(): void
    {
        $this->enabled = AutopilotService::isEnabled();
    }

    public function render()
    {
        $items = BlogAutopilotQueueItem::with('category')
            ->orderBy('position')
            ->orderBy('created_at')
            ->get();

        $lastRunIso = Setting::get('blog_autopilot.last_run_at');
        $lastRun = $lastRunIso ? Carbon::parse($lastRunIso) : null;

        return view('livewire.admin.blog.autopilot', [
            'items'         => $items,
            'categories'    => BlogCategory::orderBy('name')->get(),
            'lastRun'       => $lastRun,
            'nextRunHint'   => $this->describeNextRun(),
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
