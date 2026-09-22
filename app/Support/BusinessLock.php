<?php

namespace App\Support;

use App\Models\Business;
use App\Models\User;

/**
 * Free-plan business lock — the same rule the web applies in routes/web.php
 * (businesses.show gate) and the dashboard's locked-card overlay:
 *
 *   a Free user who OWNS more than one business can only use the oldest one;
 *   every other business they own is locked until they upgrade.
 *
 * Businesses shared with the user (editor/viewer) are never locked by the
 * user's own plan.
 */
class BusinessLock
{
    public static function isLocked(User $user, Business|string $business, ?string $role): bool
    {
        if ($user->isPro() || $role !== 'owner') {
            return false;
        }

        $businessId   = $business instanceof Business ? $business->id : $business;
        $firstOwnedId = $user->ownedBusinesses()->oldest()->value('id');

        return $firstOwnedId !== null && $businessId !== $firstOwnedId;
    }
}
