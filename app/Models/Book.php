<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'business_id',
        'name',
        'description',
        'opening_balance',
        'period_starts_at',
        'period_ends_at',
        'ai_insights_cache',
        'ai_insights_generated_at',
    ];

    protected function casts(): array
    {
        return [
            'opening_balance'          => 'decimal:2',
            'period_starts_at'         => 'date',
            'period_ends_at'           => 'date',
            'ai_insights_generated_at' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(Entry::class);
    }

    public function recurringEntries(): HasMany
    {
        return $this->hasMany(RecurringEntry::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(BookCategory::class)->orderBy('name');
    }

    public function paymentModes(): HasMany
    {
        return $this->hasMany(BookPaymentMode::class)->orderBy('name');
    }

    public function reportSchedule(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ReportSchedule::class);
    }

    public function totalIn(): string
    {
        return $this->entries()->where('type', 'in')->sum('amount');
    }

    public function totalOut(): string
    {
        return $this->entries()->where('type', 'out')->sum('amount');
    }

    public function balance(): string
    {
        return bcsub(
            bcadd((string) $this->opening_balance, (string) $this->totalIn(), 2),
            (string) $this->totalOut(),
            2
        );
    }

    /**
     * Pure-statistical 30-day cash flow forecast. No AI, no API calls.
     *
     * Model: trailing 90-day daily-mean for non-recurring baseline
     *      + scheduled active recurring entries firing in the next 30 days.
     * Confidence: ± 1 × std-dev of daily flow, scaled for a 30-day window.
     *
     * Returns an array the Reports tab can render directly. If the book has
     * less than 21 days of entry history the forecast is skipped (key:
     * has_enough_history = false) because means computed from <3 weeks of
     * data are noise, not a signal.
     */
    public function forecast30Days(): array
    {
        $firstEntry = $this->entries()->orderBy('date', 'asc')->value('date');
        $days       = $firstEntry ? max(1, (int) \Carbon\Carbon::parse($firstEntry)->diffInDays(now())) : 0;

        $currentBalance = (float) $this->balance();

        if (! $firstEntry || $days < 21) {
            return [
                'has_enough_history' => false,
                'days_of_history'    => $days,
                'current_balance'    => $currentBalance,
                'projected_in_30d'   => 0.0,
                'projected_out_30d'  => 0.0,
                'projected_net_30d'  => 0.0,
                'projected_balance'  => $currentBalance,
                'confidence_range'   => 0.0,
                'recurring_in_30d'   => 0.0,
                'recurring_out_30d'  => 0.0,
                'first_entry_date'   => $firstEntry ? \Carbon\Carbon::parse($firstEntry) : null,
            ];
        }

        // ── Trailing 90-day baseline from real entries ───────────────────
        $since = now()->subDays(90)->startOfDay();

        // Sum amounts per (date, type). Using CAST() + ordinary columns keeps
        // this portable across Postgres (prod) and SQLite (local/CI tests).
        $rows = $this->entries()
            ->where('date', '>=', $since->toDateString())
            ->selectRaw('date as d, type, SUM(CAST(amount AS DECIMAL(18,2))) as total')
            ->groupBy('date', 'type')
            ->get();

        $dailyIn  = array_fill(0, 90, 0.0);
        $dailyOut = array_fill(0, 90, 0.0);
        $today    = now()->startOfDay();
        foreach ($rows as $r) {
            $daysAgo = (int) \Carbon\Carbon::parse($r->d)->startOfDay()
                ->diffInDays($today, absolute: true);
            $idx = 89 - $daysAgo;
            if ($idx < 0 || $idx > 89) continue;
            if ($r->type === 'in')  $dailyIn[$idx]  += (float) $r->total;
            if ($r->type === 'out') $dailyOut[$idx] += (float) $r->total;
        }

        $meanInDaily  = array_sum($dailyIn)  / 90;
        $meanOutDaily = array_sum($dailyOut) / 90;

        // Std-dev of daily net (in - out) — used for confidence band.
        $netDaily = [];
        for ($i = 0; $i < 90; $i++) $netDaily[] = $dailyIn[$i] - $dailyOut[$i];
        $meanNet  = array_sum($netDaily) / 90;
        $variance = array_sum(array_map(fn ($x) => ($x - $meanNet) ** 2, $netDaily)) / 90;
        $stdDev   = sqrt($variance);

        $baselineIn30  = $meanInDaily  * 30;
        $baselineOut30 = $meanOutDaily * 30;

        // ── Scheduled recurring entries firing in the next 30 days ───────
        $recurringIn30  = 0.0;
        $recurringOut30 = 0.0;
        $horizon = now()->addDays(30)->endOfDay();

        foreach ($this->recurringEntries()->where('status', 'active')->get() as $rr) {
            $firings = $this->countRecurringFirings($rr, $horizon);
            if ($firings <= 0) continue;
            if ($rr->type === 'in')  $recurringIn30  += $firings * (float) $rr->amount;
            if ($rr->type === 'out') $recurringOut30 += $firings * (float) $rr->amount;
        }

        $projectedIn  = $baselineIn30  + $recurringIn30;
        $projectedOut = $baselineOut30 + $recurringOut30;
        $projectedNet = $projectedIn - $projectedOut;

        // Confidence: 1 σ on the 30-day net, scaled by √30 from daily.
        // Floor at 5% of absolute projected net so books with perfectly flat
        // historical data don't render "± 0" and imply false certainty.
        $confidence = $stdDev * sqrt(30);
        $floor      = abs($projectedNet) * 0.05;
        if ($confidence < $floor) {
            $confidence = $floor;
        }

        return [
            'has_enough_history' => true,
            'days_of_history'    => $days,
            'current_balance'    => $currentBalance,
            'projected_in_30d'   => round($projectedIn,  2),
            'projected_out_30d'  => round($projectedOut, 2),
            'projected_net_30d'  => round($projectedNet, 2),
            'projected_balance'  => round($currentBalance + $projectedNet, 2),
            'confidence_range'   => round($confidence,   2),
            'recurring_in_30d'   => round($recurringIn30,  2),
            'recurring_out_30d'  => round($recurringOut30, 2),
            'first_entry_date'   => \Carbon\Carbon::parse($firstEntry),
        ];
    }

    /**
     * How many times will a recurring entry fire between now and $horizon,
     * respecting its `ends_at` (if set) and `next_run_at`?
     */
    private function countRecurringFirings(RecurringEntry $rr, \Carbon\Carbon $horizon): int
    {
        $step = match ($rr->frequency) {
            'daily'    => 1,
            'weekly'   => 7,
            'biweekly' => 14,
            default    => 7,
        };

        $cursor = $rr->next_run_at ? $rr->next_run_at->copy()->startOfDay() : now()->startOfDay();
        $endAt  = $rr->ends_at ? min($horizon, $rr->ends_at->copy()->endOfDay()) : $horizon;

        $count = 0;
        $safety = 200;
        while ($cursor->lte($endAt) && $safety-- > 0) {
            if ($cursor->gte(now()->startOfDay())) {
                $count++;
            }
            $cursor->addDays($step);
        }
        return $count;
    }
}
