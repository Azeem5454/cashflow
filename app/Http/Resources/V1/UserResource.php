<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'email'         => $this->email,
            'plan'          => $this->plan,
            'isPro'         => $this->isPro(),
            'emailVerified' => ! is_null($this->email_verified_at),
            // 'google' | 'apple' | null — when set, the email is managed by the provider.
            'authProvider'  => $this->authProvider(),
            // false for social-created accounts that never set a password.
            'hasPassword'   => (bool) ($this->has_password ?? true),
            'createdAt'     => $this->created_at->toIso8601String(),
        ];
    }
}
