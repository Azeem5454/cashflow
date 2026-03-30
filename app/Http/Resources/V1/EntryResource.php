<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'bookId'         => $this->book_id,
            'type'           => $this->type,
            'amount'         => $this->amount,
            'description'    => $this->description,
            'date'           => $this->date->toDateString(),
            'category'       => $this->category,
            'paymentMode'    => $this->payment_mode,
            'reference'      => $this->reference,
            'hasAttachment'  => ! is_null($this->attachment_path),
            'runningBalance' => $this->whenHas('running_balance'),
            'createdBy'      => new UserResource($this->whenLoaded('creator')),
            'isRecurring'    => ! is_null($this->recurring_entry_id),
            'commentsCount'  => $this->whenCounted('comments'),
            'createdAt'      => $this->created_at->toIso8601String(),
        ];
    }
}
