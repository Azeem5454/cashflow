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
        'period_starts_at',
        'period_ends_at',
    ];

    protected function casts(): array
    {
        return [
            'period_starts_at' => 'date',
            'period_ends_at' => 'date',
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
        return bcsub((string) $this->totalIn(), (string) $this->totalOut(), 2);
    }
}
