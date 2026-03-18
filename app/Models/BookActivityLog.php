<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookActivityLog extends Model
{
    use HasUuids;

    protected $table = 'book_activity_log';

    protected $fillable = [
        'book_id',
        'user_id',
        'action',
        'entry_id',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Human-readable description of the action for display in the activity feed.
     */
    public function describe(): string
    {
        $meta  = $this->meta ?? [];
        $count = $meta['count'] ?? 0;
        $noun  = $count === 1 ? 'entry' : 'entries';

        return match ($this->action) {
            'entry_created'            => 'added a ' . ($meta['type'] === 'in' ? 'Cash In' : 'Cash Out') . ' entry',
            'entry_updated'            => 'updated an entry',
            'entry_deleted'            => 'deleted a ' . ($meta['type'] === 'in' ? 'Cash In' : 'Cash Out') . ' entry',
            'bulk_delete'              => "deleted {$count} {$noun}",
            'bulk_move'                => "moved {$count} {$noun} to " . ($meta['target_book'] ?? 'another book'),
            'bulk_copy'                => "copied {$count} {$noun} to " . ($meta['target_book'] ?? 'another book'),
            'bulk_copy_opposite'       => "copied {$count} flipped {$noun} to " . ($meta['target_book'] ?? 'another book'),
            'bulk_change_category'     => 'set category to "' . ($meta['category'] ?? 'None') . '" on ' . "{$count} {$noun}",
            'bulk_change_payment_mode' => 'set payment mode to "' . ($meta['payment_mode'] ?? 'None') . '" on ' . "{$count} {$noun}",
            'comment_added'            => 'commented on "' . ($meta['entry_description'] ?? 'an entry') . '"',
            'comment_deleted'          => 'deleted a comment on "' . ($meta['entry_description'] ?? 'an entry') . '"',
            'attachment_added'         => 'attached a file to "' . ($meta['entry_description'] ?? 'an entry') . '"',
            'attachment_removed'       => 'removed the attachment from "' . ($meta['entry_description'] ?? 'an entry') . '"',
            'recurring_created'        => 'set up a recurring ' . ($meta['frequency'] ?? '') . ' rule for "' . ($meta['description'] ?? 'an entry') . '"',
            'recurring_deleted'        => 'deleted the recurring rule for "' . ($meta['description'] ?? 'an entry') . '"',
            'recurring_paused'         => 'paused the recurring rule for "' . ($meta['description'] ?? 'an entry') . '"',
            'recurring_resumed'        => 'resumed the recurring rule for "' . ($meta['description'] ?? 'an entry') . '"',
            default                    => str_replace('_', ' ', $this->action),
        };
    }

    /**
     * Semantic dot colour for the action.
     * Returns: 'created' (green) | 'updated' (blue) | 'deleted' (red)
     */
    public function iconType(): string
    {
        return match ($this->action) {
            'entry_created',
            'bulk_copy',
            'bulk_copy_opposite',
            'comment_added',
            'attachment_added',
            'recurring_created'        => 'created',   // green — something was added

            'entry_updated',
            'bulk_move',
            'bulk_change_category',
            'bulk_change_payment_mode',
            'recurring_paused',
            'recurring_resumed'        => 'updated',   // blue  — something was changed

            'entry_deleted',
            'bulk_delete',
            'comment_deleted',
            'attachment_removed',
            'recurring_deleted'        => 'deleted',   // red   — something was removed

            default                    => 'updated',
        };
    }
}
