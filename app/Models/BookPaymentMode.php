<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookPaymentMode extends Model
{
    use HasUuids;

    protected $fillable = ['book_id', 'name'];

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
