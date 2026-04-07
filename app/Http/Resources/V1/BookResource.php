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
            // Totals are populated dynamically by BusinessController@books / BookController@show.
            // Cast to string for the mobile client which expects strings.
            'totalIn'        => isset($this->total_in)  ? (string) $this->total_in  : null,
            'totalOut'       => isset($this->total_out) ? (string) $this->total_out : null,
            'balance'        => isset($this->balance)   ? (string) $this->balance   : null,
            'entriesCount'   => $this->whenCounted('entries'),
            'createdAt'      => $this->created_at->toIso8601String(),
        ];
    }
}
