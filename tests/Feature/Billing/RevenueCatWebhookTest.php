<?php

namespace Tests\Feature\Billing;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class RevenueCatWebhookTest extends BillingTestCase
{
    // ── Auth ──────────────────────────────────────────────────────────────

    public function test_rejects_missing_or_wrong_authorization(): void
    {
        $user = $this->makeUser();
        $payload = $this->rcEvent('INITIAL_PURCHASE', ['app_user_id' => $user->id]);

        $this->postRc($payload, null)->assertStatus(401);
        $this->postRc($payload, 'Bearer wrong')->assertStatus(401);

        $this->assertFalse($user->fresh()->isPro());
        $this->assertSame(0, DB::table('revenuecat_events')->count());
    }

    public function test_rejects_everything_when_secret_is_not_configured(): void
    {
        config(['services.revenuecat.webhook_secret' => null]);
        $user = $this->makeUser();

        $this->postRc($this->rcEvent('INITIAL_PURCHASE', ['app_user_id' => $user->id]), 'Bearer ')
            ->assertStatus(401);
        $this->assertFalse($user->fresh()->isPro());
    }

    public function test_accepts_bare_secret_header_too(): void
    {
        $user = $this->makeUser();

        $this->postRc($this->rcEvent('INITIAL_PURCHASE', ['app_user_id' => $user->id]), self::SECRET)
            ->assertOk();
        $this->assertTrue($user->fresh()->isPro());
    }

    public function test_malformed_payload_is_rejected(): void
    {
        $this->postRc(['event' => ['type' => 'RENEWAL']])->assertStatus(422);
    }

    // ── Event types ───────────────────────────────────────────────────────

    public function test_purchase_type_events_grant_pro_with_expiry_product_and_platform(): void
    {
        foreach (['INITIAL_PURCHASE', 'RENEWAL', 'UNCANCELLATION', 'PRODUCT_CHANGE', 'NON_RENEWING_PURCHASE'] as $type) {
            $user = $this->makeUser();
            $expires = now()->addMonth()->startOfSecond();

            $this->postRc($this->rcEvent($type, [
                'app_user_id'      => $user->id,
                'store'            => 'PLAY_STORE',
                'expiration_at_ms' => $expires->getTimestampMs(),
            ]))->assertOk()->assertJson(['status' => 'processed']);

            $user->refresh();
            $this->assertTrue($user->isPro(), $type);
            $this->assertSame('play_store', $user->plan_source, $type);
            $this->assertSame('thecashfox_pro_monthly', $user->store_product_id);
            $this->assertTrue($user->store_pro_expires_at->equalTo($expires), $type);
        }
    }

    public function test_cancellation_keeps_pro_until_expiry(): void
    {
        $user = $this->makeUser();
        $this->postRc($this->rcEvent('INITIAL_PURCHASE', ['app_user_id' => $user->id]))->assertOk();
        $expires = $user->fresh()->store_pro_expires_at;

        $this->postRc($this->rcEvent('CANCELLATION', [
            'app_user_id'   => $user->id,
            'cancel_reason' => 'UNSUBSCRIBE',
        ]))->assertOk();

        $user->refresh();
        $this->assertTrue($user->isPro());
        $this->assertTrue($user->store_pro_expires_at->equalTo($expires));
    }

    public function test_refund_cancellation_ends_access(): void
    {
        $user = $this->makeUser();
        $this->postRc($this->rcEvent('INITIAL_PURCHASE', ['app_user_id' => $user->id]))->assertOk();

        $this->postRc($this->rcEvent('CANCELLATION', [
            'app_user_id'      => $user->id,
            'cancel_reason'    => 'CUSTOMER_SUPPORT',
            'expiration_at_ms' => now()->subMinute()->getTimestampMs(),
        ]))->assertOk();

        $this->assertFalse($user->fresh()->isPro());
    }

    public function test_expiration_downgrades_and_pauses_pro_features(): void
    {
        $user = $this->makeUser();
        $expires = now()->addMonth();
        $this->postRc($this->rcEvent('INITIAL_PURCHASE', [
            'app_user_id'      => $user->id,
            'expiration_at_ms' => $expires->getTimestampMs(),
        ]))->assertOk();
        $book = $this->withActiveProFeatures($user->fresh());

        $this->postRc($this->rcEvent('EXPIRATION', [
            'app_user_id'      => $user->id,
            'expiration_at_ms' => $expires->getTimestampMs(),
        ]))->assertOk();

        $user->refresh();
        $this->assertFalse($user->isPro());
        $this->assertFalse($user->store_pro_expires_at->isFuture());
        $this->assertProFeaturesPaused($book);
    }

    public function test_stale_expiration_after_renewal_is_ignored(): void
    {
        $user = $this->makeUser();
        $this->postRc($this->rcEvent('RENEWAL', [
            'app_user_id'      => $user->id,
            'expiration_at_ms' => now()->addMonths(2)->getTimestampMs(),
        ]))->assertOk();

        // An EXPIRATION for the previous period arrives late.
        $this->postRc($this->rcEvent('EXPIRATION', [
            'app_user_id'      => $user->id,
            'expiration_at_ms' => now()->addMonth()->getTimestampMs(),
        ]))->assertOk();

        $this->assertTrue($user->fresh()->isPro());
    }

    public function test_expiration_with_active_stripe_keeps_pro(): void
    {
        $user = $this->stripeUser('active');
        $this->giveStoreEntitlement($user, now()->addDay());

        $this->postRc($this->rcEvent('EXPIRATION', [
            'app_user_id'      => $user->id,
            'expiration_at_ms' => now()->addDay()->getTimestampMs(),
        ]))->assertOk();

        $user->refresh();
        $this->assertTrue($user->isPro());
        $this->assertSame('stripe', $user->plan_source);
    }

    public function test_billing_issue_without_grace_period_changes_nothing(): void
    {
        $user = $this->makeUser();
        $this->postRc($this->rcEvent('INITIAL_PURCHASE', ['app_user_id' => $user->id]))->assertOk();
        $expires = $user->fresh()->store_pro_expires_at;

        $this->postRc($this->rcEvent('BILLING_ISSUE', ['app_user_id' => $user->id]))
            ->assertOk()->assertJson(['status' => 'ignored']);

        $this->assertTrue($user->fresh()->isPro());
        $this->assertTrue($user->fresh()->store_pro_expires_at->equalTo($expires));
    }

    public function test_billing_issue_extends_access_through_store_grace_period(): void
    {
        $user = $this->makeUser();
        $periodEnd = now()->addHour()->startOfSecond();
        $graceEnd = now()->addDays(16)->startOfSecond();
        $this->postRc($this->rcEvent('RENEWAL', [
            'app_user_id'      => $user->id,
            'expiration_at_ms' => $periodEnd->getTimestampMs(),
        ]))->assertOk();

        $this->postRc($this->rcEvent('BILLING_ISSUE', [
            'app_user_id'                   => $user->id,
            'expiration_at_ms'              => $periodEnd->getTimestampMs(),
            'grace_period_expiration_at_ms' => $graceEnd->getTimestampMs(),
        ]))->assertOk()->assertJson(['status' => 'processed']);

        $user->refresh();
        $this->assertTrue($user->isPro());
        $this->assertTrue($user->store_pro_expires_at->equalTo($graceEnd));

        // A stale EXPIRATION for the original period must not cut the grace short.
        $this->postRc($this->rcEvent('EXPIRATION', [
            'app_user_id'      => $user->id,
            'expiration_at_ms' => $periodEnd->getTimestampMs(),
        ]))->assertOk();
        $this->assertTrue($user->fresh()->store_pro_expires_at->equalTo($graceEnd));
    }

    public function test_out_of_order_extend_events_never_shorten_access(): void
    {
        $user = $this->makeUser();
        $later = now()->addMonths(2)->startOfSecond();
        $earlier = now()->addMonth()->startOfSecond();

        $this->postRc($this->rcEvent('RENEWAL', ['app_user_id' => $user->id, 'expiration_at_ms' => $later->getTimestampMs()]))->assertOk();
        foreach (['INITIAL_PURCHASE', 'RENEWAL', 'UNCANCELLATION', 'PRODUCT_CHANGE'] as $type) {
            $this->postRc($this->rcEvent($type, ['app_user_id' => $user->id, 'expiration_at_ms' => $earlier->getTimestampMs()]))->assertOk();
            $this->assertTrue($user->fresh()->store_pro_expires_at->equalTo($later), $type);
        }
    }

    public function test_sandbox_events_honoured_by_default_and_ignored_when_disabled(): void
    {
        $user = $this->makeUser();
        $this->postRc($this->rcEvent('INITIAL_PURCHASE', ['app_user_id' => $user->id, 'environment' => 'SANDBOX']))
            ->assertOk()->assertJson(['status' => 'processed']);
        $this->assertTrue($user->fresh()->isPro());

        config(['services.revenuecat.allow_sandbox' => false]);
        $other = $this->makeUser();
        $this->postRc($this->rcEvent('INITIAL_PURCHASE', ['app_user_id' => $other->id, 'environment' => 'SANDBOX']))
            ->assertOk()->assertJson(['status' => 'ignored']);
        $this->postRc($this->rcEvent('INITIAL_PURCHASE', ['app_user_id' => $other->id, 'environment' => 'PRODUCTION']))
            ->assertOk()->assertJson(['status' => 'processed']);
        $this->assertTrue($other->fresh()->isPro());
    }

    public function test_non_store_grants_are_ignored(): void
    {
        $user = $this->makeUser();
        foreach (['PROMOTIONAL', 'STRIPE', null] as $store) {
            $this->postRc($this->rcEvent('INITIAL_PURCHASE', ['app_user_id' => $user->id, 'store' => $store]))
                ->assertOk()->assertJson(['status' => 'ignored']);
        }
        $this->assertFalse($user->fresh()->isPro());
        $this->assertNull($user->fresh()->store_platform);
    }

    public function test_alias_unknown_and_other_entitlement_events_are_ignored(): void
    {
        $user = $this->makeUser();

        $this->postRc($this->rcEvent('SUBSCRIBER_ALIAS', ['app_user_id' => $user->id]))
            ->assertOk()->assertJson(['status' => 'ignored']);
        $this->postRc($this->rcEvent('SOMETHING_NEW', ['app_user_id' => $user->id]))
            ->assertOk()->assertJson(['status' => 'ignored']);
        $this->postRc($this->rcEvent('INITIAL_PURCHASE', ['app_user_id' => $user->id, 'entitlement_ids' => ['other']]))
            ->assertOk()->assertJson(['status' => 'ignored']);

        $this->assertFalse($user->fresh()->isPro());
    }

    public function test_anonymous_and_unknown_app_user_ids_are_ignored(): void
    {
        $this->postRc($this->rcEvent('INITIAL_PURCHASE', ['app_user_id' => '$RCAnonymousID:abc123']))
            ->assertOk()->assertJson(['status' => 'ignored']);
        $this->postRc($this->rcEvent('INITIAL_PURCHASE', ['app_user_id' => '0b7a3c52-3d0e-4f25-9b44-1e8f8c7a9d10']))
            ->assertOk()->assertJson(['status' => 'ignored']);
    }

    public function test_anonymous_app_user_id_resolves_via_alias(): void
    {
        $user = $this->makeUser();

        $this->postRc($this->rcEvent('INITIAL_PURCHASE', [
            'app_user_id' => '$RCAnonymousID:abc123',
            'aliases'     => ['$RCAnonymousID:abc123', $user->id],
        ]))->assertOk();

        $this->assertTrue($user->fresh()->isPro());
    }

    // ── Idempotency ───────────────────────────────────────────────────────

    public function test_duplicate_event_ids_are_processed_once(): void
    {
        $user = $this->makeUser();
        $payload = $this->rcEvent('INITIAL_PURCHASE', ['app_user_id' => $user->id]);

        $this->postRc($payload)->assertOk()->assertJson(['status' => 'processed']);

        // Downgrade out-of-band, then replay the same event: must not re-grant.
        $user->refresh();
        $user->store_pro_expires_at = now()->subDay();
        $user->save();
        app(\App\Services\PlanService::class)->recompute($user);

        $this->postRc($payload)->assertOk()->assertJson(['status' => 'duplicate']);
        $this->assertFalse($user->fresh()->isPro());
        $this->assertSame(1, DB::table('revenuecat_events')->count());
    }

    // ── Transfer ──────────────────────────────────────────────────────────

    public function test_transfer_moves_entitlement_between_users(): void
    {
        $from = $this->makeUser();
        $to = $this->makeUser();
        $this->postRc($this->rcEvent('INITIAL_PURCHASE', ['app_user_id' => $from->id, 'store' => 'PLAY_STORE']))->assertOk();
        $this->assertTrue($from->fresh()->isPro());

        $this->postRc($this->rcEvent('TRANSFER', [
            'transferred_from' => [$from->id],
            'transferred_to'   => [$to->id],
            'store'            => null,
            'expiration_at_ms' => null,
        ]))->assertOk()->assertJson(['status' => 'processed']);

        $from->refresh();
        $to->refresh();
        $this->assertFalse($from->isPro());
        $this->assertNull($from->store_pro_expires_at);
        $this->assertTrue($to->isPro());
        $this->assertSame('play_store', $to->plan_source);
    }

    public function test_transfer_to_user_without_local_source_asks_revenuecat(): void
    {
        $to = $this->makeUser();
        Http::fake([
            'api.revenuecat.com/*' => Http::response($this->subscriberPayload(now()->addMonth()->toIso8601String())),
        ]);

        $this->postRc($this->rcEvent('TRANSFER', [
            'transferred_from' => ['$RCAnonymousID:zzz'],
            'transferred_to'   => [$to->id],
            'expiration_at_ms' => null,
        ]))->assertOk();

        $this->assertTrue($to->fresh()->isPro());
        Http::assertSent(fn ($r) => str_contains($r->url(), '/v1/subscribers/' . $to->id)
            && $r->hasHeader('Authorization', 'Bearer sk_test_rc'));
    }

    private function subscriberPayload(?string $expires): array
    {
        return ['subscriber' => [
            'entitlements' => $expires === null ? [] : ['pro' => [
                'expires_date'       => $expires,
                'product_identifier' => 'thecashfox_pro_monthly',
            ]],
            'subscriptions' => ['thecashfox_pro_monthly' => ['store' => 'app_store']],
        ]];
    }
}
