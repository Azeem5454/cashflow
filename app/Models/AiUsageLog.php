<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiUsageLog extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'type',
        'tokens_in',
        'tokens_out',
        'cost_usd',
    ];

    protected $casts = [
        'tokens_in'  => 'integer',
        'tokens_out' => 'integer',
        'cost_usd'   => 'decimal:6',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function monthlyOcrCount(string $userId): int
    {
        return static::where('user_id', $userId)
            ->where('type', 'ocr')
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();
    }
}
