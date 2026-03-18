<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecurringEntry extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'book_id',
        'type',
        'amount',
        'description',
        'category',
        'payment_mode',
        'reference',
        'frequency',   // 'daily' | 'weekly' | 'biweekly'
        'starts_at',
        'next_run_at',
        'ends_at',
        'status',      // 'active' | 'paused' | 'completed'
    ];

    protected function casts(): array
    {
        return [
            'amount'      => 'decimal:2',
            'starts_at'   => 'date',
            'next_run_at' => 'date',
            'ends_at'     => 'date',
        ];
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(Entry::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isPaused(): bool
    {
        return $this->status === 'paused';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function advanceNextRun(): void
    {
        $this->next_run_at = match ($this->frequency) {
            'daily'    => $this->next_run_at->addDay(),
            'weekly'   => $this->next_run_at->addWeek(),
            'biweekly' => $this->next_run_at->addWeeks(2),
        };
    }
}
