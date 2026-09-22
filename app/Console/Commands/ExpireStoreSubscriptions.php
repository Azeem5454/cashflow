<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\PlanService;
use App\Services\RevenueCat;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Safety net for App Store / Google Play subscriptions: if RevenueCat's
 * EXPIRATION webhook never arrives, users whose store entitlement ended more
 * than BUFFER_HOURS ago are recomputed here.
 *
 * The buffer covers late renewals (stores can bill a few hours after the period
 * ends). When RevenueCat is configured each user is re-checked against it first,
 * so a renewal whose webhook was lost extends access instead of downgrading.
 * A failed lookup skips the user (tried again next hour) — never downgrades blind.
 */
class ExpireStoreSubscriptions extends Command
{
    public const BUFFER_HOURS = 6;

    protected $signature = 'billing:expire-store';

    protected $description = 'Recompute the plan of users whose App Store / Google Play entitlement has ended';

    public function handle(PlanService $plans, RevenueCat $revenueCat): int
    {
        $checked = 0;
        $skipped = 0;

        User::where('plan', 'pro')
            ->whereIn('plan_source', PlanService::STORE_SOURCES)
            ->whereNotNull('store_pro_expires_at')
            ->where('store_pro_expires_at', '<=', now()->subHours(self::BUFFER_HOURS))
            ->chunkById(100, function ($users) use ($plans, $revenueCat, &$checked, &$skipped) {
                foreach ($users as $user) {
                    if ($revenueCat->isConfigured()) {
                        try {
                            $revenueCat->applyFetchedEntitlement($user, $revenueCat->fetchEntitlement($user->id));
                        } catch (\Throwable $e) {
                            Log::warning('billing:expire-store lookup failed; will retry', [
                                'user_id' => $user->id,
                                'error'   => $e->getMessage(),
                            ]);
                            $skipped++;
                            continue;
                        }
                    }

                    $plans->recompute($user, 'store_expired');
                    $checked++;
                }
            });

        $this->info("Recomputed {$checked} user(s), skipped {$skipped}.");

        return self::SUCCESS;
    }
}
