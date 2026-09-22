<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\UserResource;
use App\Services\PlanService;
use App\Services\RevenueCat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BillingController extends Controller
{
    /**
     * POST /api/v1/billing/sync
     *
     * Called by the app right after a purchase / restore. Reads the user's
     * `pro` entitlement straight from RevenueCat so the plan updates without
     * waiting for the webhook, then recomputes the plan.
     */
    public function sync(Request $request, RevenueCat $revenueCat, PlanService $plans): JsonResponse|UserResource
    {
        if (! $revenueCat->isConfigured()) {
            return response()->json(['message' => "Subscriptions aren't available right now."], 503);
        }

        $user = $request->user();

        try {
            $entitlement = $revenueCat->fetchEntitlement($user->id);
        } catch (\Throwable $e) {
            Log::warning('RevenueCat sync failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);

            return response()->json(['message' => "We couldn't confirm your subscription right now. Please try again."], 502);
        }

        $revenueCat->applyFetchedEntitlement($user, $entitlement);
        $plans->recompute($user, 'billing_sync');

        return new UserResource($user->fresh());
    }
}
