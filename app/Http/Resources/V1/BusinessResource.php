<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BusinessResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'description'    => $this->description,
            'currency'       => $this->currency,
            'currencySymbol' => $this->currencySymbol(),
            'role'           => $this->whenPivotLoaded('business_user', fn () => $this->pivot->role),
            'isPro'          => $this->isPro(),
            'booksCount'     => $this->whenCounted('books'),
            'membersCount'   => $this->whenCounted('members'),
            'createdAt'      => $this->created_at->toIso8601String(),
        ];
    }
}
