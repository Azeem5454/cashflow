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
        $label = $this->entryLabel($meta);

        return match ($this->action) {
            'entry_created'            => 'added a ' . (($meta['type'] ?? null) === 'in' ? 'Cash In' : 'Cash Out') . ' entry',
            'entry_updated'            => 'updated an entry',
            'entry_deleted'            => 'deleted a ' . (($meta['type'] ?? null) === 'in' ? 'Cash In' : 'Cash Out') . ' entry',
            'bulk_delete'              => "deleted {$count} {$noun}",
            'bulk_move'                => "moved {$count} {$noun} to " . ($meta['target_book'] ?? 'another book'),
            'bulk_copy'                => "copied {$count} {$noun} to " . ($meta['target_book'] ?? 'another book'),
            'bulk_copy_opposite'       => "copied {$count} flipped {$noun} to " . ($meta['target_book'] ?? 'another book'),
            'bulk_change_category'     => 'set category to "' . ($meta['category'] ?? 'None') . '" on ' . "{$count} {$noun}",
            'bulk_change_payment_mode' => 'set payment mode to "' . ($meta['payment_mode'] ?? 'None') . '" on ' . "{$count} {$noun}",
            'bulk_flip_type'           => "flipped Cash In/Cash Out on {$count} {$noun}",
            'comment_added'            => 'commented on "' . $label . '"',
            'comment_deleted'          => 'deleted a comment on "' . $label . '"',
            'attachment_added'         => 'attached a file to "' . $label . '"',
            'attachment_removed'       => 'removed the attachment from "' . $label . '"',
            'recurring_created'        => 'set up a recurring ' . ($meta['frequency'] ?? '') . ' rule for "' . $label . '"',
            'recurring_deleted'        => 'deleted the recurring rule for "' . $label . '"',
            'recurring_paused'         => 'paused the recurring rule for "' . $label . '"',
            'recurring_resumed'        => 'resumed the recurring rule for "' . $label . '"',
            'recurring_updated'        => 'edited the recurring rule for "' . $label . '"',
            default                    => str_replace('_', ' ', $this->action),
        };
    }

    /**
     * Label for the entry / recurring rule an action refers to. Descriptions
     * are optional, so fall back to the category or "Cash in" / "Cash out"
     * (Entry::labelFor), and to "an entry" when the log holds nothing usable.
     */
    private function entryLabel(array $meta): string
    {
        $description = $meta['entry_description'] ?? $meta['description'] ?? null;
        $category    = $meta['entry_category'] ?? $meta['category'] ?? null;
        $type        = $meta['entry_type'] ?? $meta['type'] ?? null;

        $description = is_string($description) ? $description : null;
        $category    = is_string($category) ? $category : null;
        $type        = is_string($type) ? $type : null;

        if (trim((string) $description) === '' && trim((string) $category) === '' && ! in_array($type, ['in', 'out'], true)) {
            return 'an entry';
        }

        return Entry::labelFor($description, $category, $type);
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
            'bulk_flip_type',
            'recurring_paused',
            'recurring_resumed',
            'recurring_updated'        => 'updated',   // blue  — something was changed

            'entry_deleted',
            'bulk_delete',
            'comment_deleted',
            'attachment_removed',
            'recurring_deleted'        => 'deleted',   // red   — something was removed

            default                    => 'updated',
        };
    }
}
