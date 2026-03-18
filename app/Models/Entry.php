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
}
