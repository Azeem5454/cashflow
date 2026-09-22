<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PlanService;
use App\Services\RevenueCat;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * POST /api/webhooks/revenuecat
 *
 * App Store / Google Play subscription events relayed by RevenueCat.
 * Auth: RevenueCat sends the Authorization header value configured in its
 * dashboard; we accept "Bearer {secret}" (or the bare secret). No secret
 * configured → every request is rejected.
 *
 * Sandbox events are honoured unless services.revenuecat.allow_sandbox is false.
 *
 * Idempotent by event id (revenuecat_events table). Always answers 200 for
 * events we understand but choose to ignore, so RevenueCat doesn't retry them.
 */
class RevenueCatWebhookController extends Controller
{
    private const EXTEND_EVENTS = [
        'INITIAL_PURCHASE',
        'RENEWAL',
        'UNCANCELLATION',
        'PRODUCT_CHANGE',
        'NON_RENEWING_PURCHASE',
    ];

    public function __construct(
        private readonly PlanService $plans,
        private readonly RevenueCat $revenueCat,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        if (! $this->authorised($request)) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $event = $request->input('event');
        if (! is_array($event) || empty($event['id']) || empty($event['type'])) {
            return response()->json(['message' => 'Malformed event.'], 422);
        }

        $eventId = Str::limit((string) $event['id'], 191, '');
        $type = strtoupper((string) $event['type']);

        try {
            $status = DB::transaction(function () use ($eventId, $type, $event) {
                if (DB::table('revenuecat_events')->where('id', $eventId)->exists()) {
                    return 'duplicate';
                }

                DB::table('revenuecat_events')->insert([
                    'id'          => $eventId,
                    'type'        => Str::limit($type, 50, ''),
                    'app_user_id' => isset($event['app_user_id']) ? Str::limit((string) $event['app_user_id'], 191, '') : null,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);

                return $this->handle($type, $event);
            });
        } catch (UniqueConstraintViolationException) {
            // Same event delivered concurrently — the other request processed it.
            $status = 'duplicate';
        }

        Log::info('RevenueCat webhook', ['event_id' => $eventId, 'type' => $type, 'result' => $status]);

        return response()->json(['status' => $status]);
    }

    private function authorised(Request $request): bool
    {
        $secret = (string) config('services.revenuecat.webhook_secret');
        if ($secret === '') {
            Log::warning('RevenueCat webhook rejected: REVENUECAT_WEBHOOK_SECRET is not set');

            return false;
        }

        $header = (string) $request->header('Authorization', '');

        return hash_equals('Bearer ' . $secret, $header) || hash_equals($secret, $header);
    }

    private function handle(string $type, array $event): string
    {
        if (! $this->revenueCat->allowSandbox() && strtoupper((string) ($event['environment'] ?? '')) === 'SANDBOX') {
            return 'ignored';
        }

        if ($type === 'TRANSFER') {
            return $this->handleTransfer($event);
        }

        if (! in_array($type, [...self::EXTEND_EVENTS, 'CANCELLATION', 'EXPIRATION', 'BILLING_ISSUE'], true)) {
            // SUBSCRIBER_ALIAS, TEST, and anything new RevenueCat adds later.
            return 'ignored';
        }

        if (! $this->isOurEntitlement($event)) {
            return 'ignored';
        }

        $user = $this->resolveUser($event);
        if (! $user) {
            return 'ignored';
        }

        $expiresAt = $this->msToCarbon($event['expiration_at_ms'] ?? null);
        $stored = $user->store_pro_expires_at;

        if (in_array($type, self::EXTEND_EVENTS, true)) {
            $platform = RevenueCat::platformFromStore($event['store'] ?? null);
            if (! $platform || ! $expiresAt) {
                // Not an App Store / Google Play purchase (promotional, Stripe…), or no expiry.
                return 'ignored';
            }

            // Events can arrive out of order — extend events never shorten access.
            $user->store_pro_expires_at = ($stored && $stored->greaterThan($expiresAt)) ? $stored : $expiresAt;
            $user->store_product_id = Str::limit((string) ($event['new_product_id'] ?? $event['product_id'] ?? ''), 191, '') ?: null;
            $user->store_platform = $platform;
            $user->save();
        } elseif ($type === 'BILLING_ISSUE') {
            // Card failed; the store keeps retrying during its grace period and
            // the user keeps access until then.
            $grace = $this->msToCarbon($event['grace_period_expiration_at_ms'] ?? null);
            if (! $grace || ($stored && $stored->greaterThanOrEqualTo($grace))) {
                return 'ignored';
            }
            if (! in_array($user->store_platform, PlanService::STORE_SOURCES, true)) {
                $platform = RevenueCat::platformFromStore($event['store'] ?? null);
                if (! $platform) {
                    return 'ignored';
                }
                $user->store_platform = $platform;
            }
            $user->store_pro_expires_at = $grace;
            $user->save();
        } elseif ($type === 'CANCELLATION') {
            // Auto-renew turned off: they stay Pro until the period ends. A refund
            // (cancel_reason CUSTOMER_SUPPORT) ends access at the refund time.
            if (($event['cancel_reason'] ?? null) === 'CUSTOMER_SUPPORT' && $expiresAt
                && ($stored === null || $stored->greaterThan($expiresAt))) {
                $user->store_pro_expires_at = $expiresAt;
                $user->save();
            }
        } elseif ($type === 'EXPIRATION') {
            // Ignore an EXPIRATION for a period older than what we already have
            // (a later renewal / grace extension arrived first).
            $isStale = $expiresAt && $stored && $stored->greaterThan($expiresAt->copy()->addMinute());

            if (! $isStale) {
                $endedAt = $this->msToCarbon($event['event_timestamp_ms'] ?? null) ?? now();
                if ($stored === null || $stored->greaterThan($endedAt)) {
                    $user->store_pro_expires_at = $endedAt;
                    $user->save();
                }
            }
        }

        $this->plans->recompute($user, 'revenuecat:' . $type);

        return 'processed';
    }

    /**
     * TRANSFER: the store receipt moved from one app user id to another (e.g. a
     * different account restored purchases on the same Apple ID).
     */
    private function handleTransfer(array $event): string
    {
        $from = $this->usersFromIds($event['transferred_from'] ?? []);
        $to = $this->usersFromIds($event['transferred_to'] ?? []);

        if ($from->isEmpty() && $to->isEmpty()) {
            return 'ignored';
        }

        $source = $from->first(fn (User $u) => $u->store_pro_expires_at !== null);
        $expiresAt = $this->msToCarbon($event['expiration_at_ms'] ?? null) ?? $source?->store_pro_expires_at;
        $productId = $event['product_id'] ?? $source?->store_product_id;
        $platform = RevenueCat::platformFromStore($event['store'] ?? null) ?? $source?->store_platform;

        foreach ($from as $user) {
            $user->store_pro_expires_at = null;
            $user->store_product_id = null;
            $user->store_platform = null;
            $user->save();
            $this->plans->recompute($user, 'revenuecat:TRANSFER_FROM');
        }

        foreach ($to as $user) {
            if ($expiresAt && $platform) {
                if ($user->store_pro_expires_at === null || $user->store_pro_expires_at->lessThan($expiresAt)) {
                    $user->store_pro_expires_at = $expiresAt;
                    $user->store_product_id = $productId;
                    $user->store_platform = $platform;
                    $user->save();
                }
            } elseif ($this->revenueCat->isConfigured()) {
                // Nothing to copy locally — ask RevenueCat directly.
                try {
                    $this->revenueCat->applyFetchedEntitlement($user, $this->revenueCat->fetchEntitlement($user->id));
                } catch (\Throwable $e) {
                    Log::warning('RevenueCat transfer lookup failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
                }
            }

            $this->plans->recompute($user, 'revenuecat:TRANSFER_TO');
        }

        return 'processed';
    }

    private function isOurEntitlement(array $event): bool
    {
        $ids = $event['entitlement_ids'] ?? null;

        // Older payloads may omit entitlement ids — accept those (single-product app).
        return ! is_array($ids) || in_array($this->revenueCat->entitlementId(), $ids, true);
    }

    private function resolveUser(array $event): ?User
    {
        $candidates = array_merge(
            [$event['app_user_id'] ?? null, $event['original_app_user_id'] ?? null],
            is_array($event['aliases'] ?? null) ? $event['aliases'] : [],
        );

        return $this->usersFromIds($candidates)->first();
    }

    /** Only real account ids (our UUIDs) — anonymous RevenueCat ids ($RCAnonymousID:...) are skipped. */
    private function usersFromIds(mixed $ids): \Illuminate\Support\Collection
    {
        $uuids = collect(is_array($ids) ? $ids : [])
            ->filter(fn ($id) => is_string($id) && ! str_starts_with($id, '$RCAnonymousID') && Str::isUuid($id))
            ->unique()
            ->values();

        if ($uuids->isEmpty()) {
            return collect();
        }

        $users = User::whereIn('id', $uuids)->get()->keyBy('id');

        // Preserve the payload order.
        return $uuids->map(fn ($id) => $users->get($id))->filter()->values();
    }

    private function msToCarbon(mixed $ms): ?Carbon
    {
        if (! is_numeric($ms) || (int) $ms <= 0) {
            return null;
        }

        return Carbon::createFromTimestampMs((int) $ms);
    }
}
