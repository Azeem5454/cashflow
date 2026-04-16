<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Entry extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        // book_id intentionally excluded — always set via $book->entries()->create()
        'type',
        'amount',
        'description',
        'date',
        'reference',
        'category',
        'payment_mode',
        'recurring_entry_id', // set explicitly by code, never from user input
        'attachment_path',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function recurringEntry(): BelongsTo
    {
        return $this->belongsTo(RecurringEntry::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(EntryComment::class)->orderBy('created_at');
    }

    /**
     * Run synchronous anomaly detection after every save. Pro-only — free
     * users never get the flag badge, so no point spending the DB query.
     * Evaluate() swallows its own exceptions and uses saveQuietly() so it
     * can't loop back into this hook or break the save.
     */
    protected static function booted(): void
    {
        static::saved(function (Entry $entry) {
            // Skip if the only thing that changed was the flag itself —
            // otherwise saveQuietly inside evaluate() would still bounce us.
            if ($entry->wasChanged(['is_flagged', 'flag_reason', 'flagged_at'])
                && ! $entry->wasChanged(['amount', 'category', 'type'])) {
                return;
            }

            // Load the book's business via the book relation. Cheap if Entry
            // was created via $book->entries()->create() (most paths).
            $isPro = $entry->book?->business?->isPro() ?? false;
            if (! $isPro) {
                return;
            }

            app(\App\Services\AnomalyDetector::class)->evaluate($entry);
        });
    }
}
