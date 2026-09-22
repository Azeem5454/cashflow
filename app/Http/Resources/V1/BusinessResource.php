<?php

namespace App\Http\Resources\V1;

use App\Support\BusinessLock;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BusinessResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $role = $this->relationLoaded('pivot') && $this->pivot ? $this->pivot->role : null;

        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'description'    => $this->description,
            'currency'       => $this->currency,
            'currencySymbol' => $this->currencySymbol(),
            'role'           => $this->whenPivotLoaded('business_user', fn () => $this->pivot->role),
            'isPro'          => $this->isPro(),
            // Same rule as the web free-plan gate (routes/web.php businesses.show).
            'isLocked'       => $request->user()
                ? BusinessLock::isLocked($request->user(), $this->resource, $role)
                : false,
            'booksCount'     => $this->whenCounted('books'),
            // Net across all books (opening balances included), in this business's currency.
            'balance'        => $this->net_balance
                ?? \App\Models\Business::netBalances([$this->id])[$this->id],
            'membersCount'   => (int) ($this->members_count ?? $this->members()->count()),
            // Team-size limit for the owner's plan (owner included); null = unlimited.
            'memberLimit'    => $this->memberLimit(),
            'pendingInvitesCount' => (int) ($this->pending_invitations_count
                ?? $this->pendingInvitations()->count()),
            'createdAt'      => $this->created_at->toIso8601String(),
        ];
    }
}
