<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntryComment extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'entry_id',
        'user_id',
        'body',
        'mentioned_user_ids',
    ];

    protected function casts(): array
    {
        return [
            'mentioned_user_ids' => 'array',
        ];
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(Entry::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Parse @[Name]{uuid} tokens from body and return array of user UUIDs */
    public static function extractMentionedIds(string $body): array
    {
        preg_match_all('/@\[[^\]]+\]\{([a-f0-9\-]{36})\}/i', $body, $matches);
        return array_values(array_unique($matches[1] ?? []));
    }

    /** Render body: replace @[Name]{uuid} tokens with styled @Name spans */
    public function renderedBody(): string
    {
        return preg_replace_callback(
            '/@\[([^\]]+)\]\{[a-f0-9\-]{36}\}/i',
            fn ($m) => '<span class="text-primary font-semibold">@' . e($m[1]) . '</span>',
            e($this->body)
        );
    }
}
