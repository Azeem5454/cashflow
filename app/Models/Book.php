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
}
