<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Thin wrapper around the RevenueCat REST API (v1) plus the helpers that write
 * store entitlement data onto a user. Plan decisions stay in PlanService.
 */
class RevenueCat
{
    private const API_BASE = 'https://api.revenuecat.com/v1';

    public function isConfigured(): bool
    {
        return filled(config('services.revenuecat.secret_key'));
    }

    /**
     * Whether sandbox (TestFlight / license-tester) purchases grant Pro.
     * Defaults to true: Apple's reviewers buy with sandbox accounts against the
     * production backend. Sandbox monthly subscriptions renew every ~5 minutes
     * and lapse within hours, so any sandbox grant is short-lived.
     */
    public function allowSandbox(): bool
    {
        return (bool) config('services.revenuecat.allow_sandbox', true);
    }

    public function entitlementId(): string
    {
        return (string) config('services.revenuecat.entitlement', 'pro');
    }

    /**
     * Current `pro` entitlement for an app user id.
     *
     * @return array{active: bool, expires_at: ?Carbon, product_id: ?string, platform: ?string}
     *
     * @throws RuntimeException when RevenueCat is unreachable or returns an error
     */
    public function fetchEntitlement(string $appUserId): array
    {
        $response = Http::withToken((string) config('services.revenuecat.secret_key'))
            ->acceptJson()
            ->timeout(10)
            ->get(self::API_BASE . '/subscribers/' . rawurlencode($appUserId));

        if (! $response->successful()) {
            throw new RuntimeException('RevenueCat subscriber lookup failed with HTTP ' . $response->status());
        }

        $subscriber = $response->json('subscriber') ?? [];
        $entitlement = $subscriber['entitlements'][$this->entitlementId()] ?? null;

        $inactive = ['active' => false, 'expires_at' => null, 'product_id' => null, 'platform' => null];

        if (! is_array($entitlement)) {
            return $inactive;
        }

        $productId = $entitlement['product_identifier'] ?? null;
        $subscription = $this->subscriptionFor($subscriber['subscriptions'] ?? [], $productId);
        $platform = self::platformFromStore($subscription['store'] ?? null);

        // Only real App Store / Google Play subscriptions count. Promotional or
        // Stripe grants (null platform) and lifetime/non-expiring grants (null
        // expires_date) are ignored — never turned into a long store grant.
        if ($platform === null || empty($entitlement['expires_date'])) {
            return $inactive;
        }

        if (! $this->allowSandbox() && ! empty($subscription['is_sandbox'])) {
            return $inactive;
        }

        $expires = Carbon::parse($entitlement['expires_date']);

        // Store billing grace period (card failed, store still retrying).
        foreach ([$entitlement['grace_period_expires_date'] ?? null, $subscription['grace_period_expires_date'] ?? null] as $grace) {
            if (! empty($grace) && Carbon::parse($grace)->greaterThan($expires)) {
                $expires = Carbon::parse($grace);
            }
        }

        return [
            'active'     => $expires->isFuture(),
            'expires_at' => $expires,
            'product_id' => $productId,
            'platform'   => $platform,
        ];
    }

    /**
     * The subscription row for an entitlement's product. Google Play ids can
     * appear as "product" or "product:base_plan", so match either form.
     */
    private function subscriptionFor(mixed $subscriptions, ?string $productId): array
    {
        if (! is_array($subscriptions) || ! $productId) {
            return [];
        }

        $base = explode(':', $productId)[0];

        foreach ([$productId, $base] as $key) {
            if (isset($subscriptions[$key]) && is_array($subscriptions[$key])) {
                return $subscriptions[$key];
            }
        }

        foreach ($subscriptions as $key => $row) {
            if (is_array($row) && explode(':', (string) $key)[0] === $base) {
                return $row;
            }
        }

        return [];
    }

    /**
     * Write the result of fetchEntitlement() onto the user (does not recompute the plan).
     */
    public function applyFetchedEntitlement(User $user, array $entitlement): void
    {
        if ($entitlement['active']) {
            $user->store_pro_expires_at = $entitlement['expires_at'];
            $user->store_product_id = $entitlement['product_id'];
            $user->store_platform = $entitlement['platform'];
        } elseif ($user->store_pro_expires_at !== null && $user->store_pro_expires_at->isFuture()) {
            // RevenueCat is the source of truth for store purchases: no entitlement → ended now.
            $user->store_pro_expires_at = Carbon::now();
        }

        $user->save();
    }

    /**
     * Map a RevenueCat `store` value (webhook: APP_STORE / PLAY_STORE; REST: app_store / play_store)
     * to our plan_source vocabulary. Other stores (Stripe, Amazon, promotional) → null.
     */
    public static function platformFromStore(?string $store): ?string
    {
        return match (strtoupper((string) $store)) {
            'APP_STORE', 'MAC_APP_STORE' => PlanService::SOURCE_APP_STORE,
            'PLAY_STORE'                 => PlanService::SOURCE_PLAY_STORE,
            default                      => null,
        };
    }
}
