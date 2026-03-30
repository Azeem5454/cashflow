<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportSchedule extends Model
{
    use HasUuids;

    protected $fillable = [
        'book_id',
        'frequency',
        'recipients',
        'is_active',
        'last_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'recipients'   => 'array',
            'is_active'    => 'boolean',
            'last_sent_at' => 'datetime',
        ];
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    /**
     * Build the report data array used by the BookEmailReport mailable.
     */
    public function buildReportData(): array
    {
        $book    = $this->book;
        $entries = $book->entries()
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $inEntries  = $entries->where('type', 'in');
        $outEntries = $entries->where('type', 'out');

        $totalIn    = $inEntries->reduce(fn ($c, $e) => bcadd($c, (string) $e->amount, 2), '0.00');
        $totalOut   = $outEntries->reduce(fn ($c, $e) => bcadd($c, (string) $e->amount, 2), '0.00');
        $netBalance = bcsub($totalIn, $totalOut, 2);

        $minDate = $entries->min('date');
        $maxDate = $entries->max('date');
        $daySpan = ($minDate && $maxDate) ? max(1, $minDate->diffInDays($maxDate) + 1) : 1;
        $dailyAverage = $daySpan > 0 ? bcdiv($netBalance, (string) $daySpan, 2) : '0.00';

        $periodSummary = [
            'totalIn'      => $totalIn,
            'totalOut'     => $totalOut,
            'netBalance'   => $netBalance,
            'inCount'      => $inEntries->count(),
            'outCount'     => $outEntries->count(),
            'dailyAverage' => $dailyAverage,
            'daySpan'      => $daySpan,
        ];

        $periodLabel = '';
        if ($book->period_starts_at && $book->period_ends_at) {
            $periodLabel = $book->period_starts_at->format('M d, Y') . ' — ' . $book->period_ends_at->format('M d, Y');
        }

        $topCategories = $outEntries
            ->filter(fn ($e) => $e->category)
            ->groupBy('category')
            ->map(fn ($group, $cat) => [
                'name'  => $cat,
                'total' => $group->reduce(fn ($c, $e) => bcadd($c, (string) $e->amount, 2), '0.00'),
            ])
            ->sortByDesc(fn ($c) => (float) $c['total'])
            ->take(5)
            ->map(function ($cat) use ($totalOut) {
                $pct = (float) $totalOut > 0
                    ? round(((float) $cat['total'] / (float) $totalOut) * 100, 1)
                    : 0;
                return array_merge($cat, ['percentage' => $pct]);
            })
            ->values()
            ->toArray();

        $recentEntries = $entries->take(10)->map(fn ($e) => [
            'date'        => $e->date->format('M d'),
            'description' => $e->description,
            'amount'      => $e->amount,
            'type'        => $e->type,
        ])->toArray();

        return compact('periodSummary', 'periodLabel', 'topCategories', 'recentEntries');
    }

    /**
     * Determine if this schedule is due to send right now.
     */
    public function isDue(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();

        // Never sent → due immediately
        if (! $this->last_sent_at) {
            return true;
        }

        return match ($this->frequency) {
            'weekly'  => $this->last_sent_at->diffInDays($now) >= 7,
            'monthly' => $this->last_sent_at->diffInDays($now) >= 28
                         && $this->last_sent_at->month !== $now->month,
            default   => false,
        };
    }
}
