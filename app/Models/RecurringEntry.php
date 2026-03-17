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
        'frequency',
        'starts_at',
        'next_run_at',
        'ends_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'amount'      => 'decimal:2',
            'starts_at'   => 'date',
            'next_run_at' => 'date',
            'ends_at'     => 'date',
            'is_active'   => 'boolean',
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

    public function advanceNextRun(): void
    {
        $this->next_run_at = match ($this->frequency) {
            'daily'   => $this->next_run_at->addDay(),
            'weekly'  => $this->next_run_at->addWeek(),
            'monthly' => $this->next_run_at->addMonth(),
            'yearly'  => $this->next_run_at->addYear(),
        };
    }
}
