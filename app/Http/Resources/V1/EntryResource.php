<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $hasAttachment = ! is_null($this->attachment_path);

        return [
            'id'               => $this->id,
            'bookId'           => $this->book_id,
            'type'             => $this->type,
            'amount'           => $this->amount,
            'description'      => $this->description,
            'date'             => $this->date->toDateString(),
            'category'         => $this->category,
            'paymentMode'      => $this->payment_mode,
            'reference'        => $this->reference,
            'hasAttachment'    => $hasAttachment,
            'attachmentUrl'    => $hasAttachment ? url("/api/v1/entries/{$this->id}/attachment") : null,
            'runningBalance'   => $this->whenHas('running_balance'),
            // Deliberately only id + name — never expose another member's email/plan.
            'createdBy'        => $this->relationLoaded('creator') && $this->creator
                ? ['id' => $this->creator->id, 'name' => $this->creator->name]
                : null,
            'isRecurring'      => ! is_null($this->recurring_entry_id),
            'recurringEntryId' => $this->recurring_entry_id,
            'commentsCount'    => (int) ($this->comments_count ?? 0),
            'isFlagged'        => (bool) ($this->is_flagged ?? false),
            'flagReason'       => $this->flag_reason,
            'createdAt'        => $this->created_at?->toIso8601String(),
            'updatedAt'        => $this->updated_at?->toIso8601String(),
        ];
    }
}
