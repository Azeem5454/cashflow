<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'businessId'     => $this->business_id,
            'name'           => $this->name,
            'description'    => $this->description,
            'openingBalance' => $this->opening_balance,
            'periodStartsAt' => $this->period_starts_at?->toDateString(),
            'periodEndsAt'   => $this->period_ends_at?->toDateString(),
            'totalIn'        => $this->whenAppended('total_in', fn () => $this->total_in),
            'totalOut'       => $this->whenAppended('total_out', fn () => $this->total_out),
            'balance'        => $this->whenAppended('balance', fn () => $this->balance),
            'entriesCount'   => $this->whenCounted('entries'),
            'createdAt'      => $this->created_at->toIso8601String(),
        ];
    }
}
