<?php

namespace Tests\Feature\Billing;

use App\Models\RecurringEntry;
use App\Models\ReportSchedule;
use App\Models\User;
use App\Services\PlanService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Plan-source protection, system pause/resume, sync filtering and the
 * store-expiry sweep.
 */
class PlanProtectionTest extends BillingTestCase
{
    private function rcSubscriber(?array $pro, array $subscription = ['store' => 'app_store']): array
    {
        return ['subscriber' => [
            'entitlements'  => $pro === null ? [] : ['pro' => array_merge(['product_identifier' => 'thecashfox_pro_monthly'], $pro)],
            'subscriptions' => ['thecashfox_pro_monthly' => $subscription],
        ]];
    }

    /** Pro via Stripe per the webhook payload, but Cashier hasn't written the subscription row yet. */
    private function stripeSourcedWithoutRow(): User
    {
        $user = $this->makeUser(pro: true);
        $user->stripe_id = 'cus_' . uniqid();
        $user->plan_source = 'stripe';
        $user->save();

        return $user;
    }

    // ── H1: Stripe-sourced Pro is never downgraded by recompute() ─────────

    public function test_stripe_sourced_user_without_row_survives_sync(): void
    {
        $user = $this->actingAsUser($this->stripeSourcedWithoutRow());
        $book = $this->withActiveProFeatures($user);
        Http::fake(['api.revenuecat.com/*' => Http::response($this->rcSubscriber(null))]);

        $this->postJson('/api/v1/billing/sync')
            ->assertOk()
            ->assertJsonPath('data.isPro', true)
            ->assertJsonPath('data.planSource', 'stripe');

        $this->assertProFeaturesActive($book);
    }

    public function test_stripe_sourced_user_without_row_survives_revenuecat_events(): void
    {
        $user = $this->stripeSourcedWithoutRow();
        $book = $this->withActiveProFeatures($user);

        $this->postRc($this->rcEvent('EXPIRATION', ['app_user_id' => $user->id]))->assertOk();
        $this->postRc($this->rcEvent('TRANSFER', [
            'transferred_from' => [$user->id],
            'transferred_to'   => ['$RCAnonymousID:x'],
        ]))->assertOk();

        $user->refresh();
        $this->assertTrue($user->isPro());
        $this->assertSame('stripe', $user->plan_source);
        $this->assertProFeaturesActive($book);
    }

    public function test_stripe_sourced_user_without_row_survives_expire_sweep(): void
    {
        $user = $this->stripeSourcedWithoutRow();
        $user->store_pro_expires_at = now()->subDays(2);
        $user->store_platform = 'app_store';
        $user->save();
        $book = $this->withActiveProFeatures($user);

        $this->artisan('billing:expire-store')->assertSuccessful();
        app(PlanService::class)->recompute($user->fresh());

        $this->assertTrue($user->fresh()->isPro());
        $this->assertProFeaturesActive($book);
    }

    public function test_stripe_terminal_status_still_downgrades_stripe_sourced_user(): void
    {
        $user = $this->stripeSourcedWithoutRow();
        $book = $this->withActiveProFeatures($user);

        $this->stripeWebhook($user, 'canceled', 'customer.subscription.deleted');

        $this->assertFalse($user->fresh()->isPro());
        $this->assertProFeaturesPaused($book);
    }

    public function test_past_due_stripe_user_stays_pro_on_recompute(): void
    {
        $user = $this->stripeUser('past_due');
        $user->plan = 'pro';
        $user->plan_source = null; // even without the sticky source, the row itself counts
        $user->save();
        $book = $this->withActiveProFeatures($user);

        app(PlanService::class)->recompute($user);
        $this->stripeWebhook($user, 'past_due');

        $user->refresh();
        $this->assertTrue($user->isPro());
        $this->assertSame('stripe', $user->plan_source);
        $this->assertProFeaturesActive($book);
    }

    // ── M3: legacy Pro protection ─────────────────────────────────────────

    public function test_legacy_pro_user_is_not_downgraded_by_store_lapse(): void
    {
        $user = $this->makeUser(pro: true); // plan_source null (pre-IAP Pro)
        $expires = now()->addMonth();

        $this->postRc($this->rcEvent('INITIAL_PURCHASE', ['app_user_id' => $user->id, 'expiration_at_ms' => $expires->getTimestampMs()]))->assertOk();
        $this->postRc($this->rcEvent('EXPIRATION', ['app_user_id' => $user->id, 'expiration_at_ms' => $expires->getTimestampMs()]))->assertOk();

        $this->assertTrue($user->fresh()->isPro());
    }

    public function test_backfill_marks_stripe_and_never_subscribed_pro_users_only(): void
    {
        $stripe = $this->stripeUser('active');
        $stripe->plan_source = null;
        $stripe->save();

        $adminGranted = $this->makeUser(pro: true);        // no stripe_id, no subscription rows

        $lapsedStripe = $this->stripeUser('canceled');     // had Stripe; still Pro locally
        $lapsedStripe->plan = 'pro';
        $lapsedStripe->plan_source = null;
        $lapsedStripe->save();

        $free = $this->makeUser();

        $migration = require database_path('migrations/2026_09_22_200001_add_store_billing_to_users_table.php');
        $migration->backfill();

        $this->assertSame('stripe', $stripe->fresh()->plan_source);
        $this->assertSame('admin', $adminGranted->fresh()->plan_source);
        $this->assertNull($lapsedStripe->fresh()->plan_source);   // stays legacy (sticky)
        $this->assertNull($free->fresh()->plan_source);
        $this->assertSame('pro', $lapsedStripe->fresh()->plan);    // plans untouched
        $this->assertSame('free', $free->fresh()->plan);
    }

    // ── M4: Stripe takes over from an admin grant ─────────────────────────

    public function test_stripe_active_replaces_admin_source_so_cancel_downgrades(): void
    {
        $user = $this->stripeUser('incomplete');
        app(PlanService::class)->forcePro($user);

        $this->stripeWebhook($user, 'active');
        $this->assertSame('stripe', $user->fresh()->plan_source);

        $this->stripeWebhook($user, 'canceled', 'customer.subscription.deleted');
        $this->assertFalse($user->fresh()->isPro());
    }

    public function test_stripe_activation_with_store_entitlement_logs_double_billing(): void
    {
        Log::spy();
        $user = $this->stripeUser('incomplete');
        $this->giveStoreEntitlement($user, now()->addMonth());

        $this->stripeWebhook($user, 'active');

        Log::shouldHaveReceived('warning')->withArgs(fn ($msg) => str_contains($msg, 'double billing'))->once();
        $this->assertSame('stripe', $user->fresh()->plan_source);
    }

    // ── H2: system pause → resume only what the system paused ─────────────

    public function test_return_to_pro_resumes_only_system_paused_rows(): void
    {
        $user = $this->makeUser();
        $this->postRc($this->rcEvent('INITIAL_PURCHASE', ['app_user_id' => $user->id]))->assertOk();
        $book = $this->withActiveProFeatures($user->fresh());
        $systemRule = RecurringEntry::where('book_id', $book->id)->first();

        // A rule the owner paused themselves while Pro.
        $manualRule = $book->recurringEntries()->create([
            'type' => 'out', 'amount' => '9.00', 'description' => 'Manual', 'frequency' => 'weekly',
            'starts_at' => '2026-01-01', 'next_run_at' => '2026-01-08', 'status' => 'active',
        ]);
        $manualRule->update(['status' => 'paused']);

        // Store lapses → automatic downgrade.
        $this->postRc($this->rcEvent('EXPIRATION', [
            'app_user_id'      => $user->id,
            'expiration_at_ms' => $user->fresh()->store_pro_expires_at->getTimestampMs(),
        ]))->assertOk();
        $this->assertFalse($user->fresh()->isPro());
        $this->assertNotNull($systemRule->fresh()->paused_by_system_at);
        $this->assertNull($manualRule->fresh()->paused_by_system_at);

        // Owner re-subscribes.
        $this->postRc($this->rcEvent('RENEWAL', ['app_user_id' => $user->id]))->assertOk();

        $this->assertTrue($user->fresh()->isPro());
        $systemRule->refresh();
        $this->assertSame('active', $systemRule->status);
        $this->assertNull($systemRule->paused_by_system_at);
        $this->assertTrue($systemRule->next_run_at->greaterThanOrEqualTo(now()->startOfDay()), 'no back-fill of missed runs');
        $this->assertSame('paused', $manualRule->fresh()->status);
        $this->assertTrue((bool) ReportSchedule::where('book_id', $book->id)->value('is_active'));
    }

    public function test_owner_toggling_a_system_paused_rule_clears_the_marker(): void
    {
        $user = $this->makeUser(pro: true);
        $user->plan_source = 'admin';
        $user->save();
        $book = $this->withActiveProFeatures($user);
        app(PlanService::class)->forceFree($user);

        $rule = RecurringEntry::where('book_id', $book->id)->first();
        $this->assertNotNull($rule->paused_by_system_at);

        $rule->update(['status' => 'active']);
        $rule->update(['status' => 'paused']); // now a manual pause
        $this->assertNull($rule->fresh()->paused_by_system_at);

        app(PlanService::class)->forcePro($user);
        $this->assertSame('paused', $rule->fresh()->status);
    }

    // ── H2: expire-store sweep ────────────────────────────────────────────

    private function lapsedStoreUser(int $hoursAgo): User
    {
        $user = $this->makeUser(pro: true);
        $user->plan_source = 'app_store';
        $this->giveStoreEntitlement($user, now()->subHours($hoursAgo));

        return $user;
    }

    public function test_sweep_waits_for_the_buffer(): void
    {
        $recent = $this->lapsedStoreUser(2);
        Http::fake(); // must not be called

        $this->artisan('billing:expire-store')->assertSuccessful();

        $this->assertTrue($recent->fresh()->isPro());
        Http::assertNothingSent();
    }

    public function test_sweep_downgrades_when_revenuecat_confirms_no_entitlement(): void
    {
        $user = $this->lapsedStoreUser(7);
        $book = $this->withActiveProFeatures($user);
        Http::fake(['api.revenuecat.com/*' => Http::response($this->rcSubscriber(null))]);

        $this->artisan('billing:expire-store')->assertSuccessful();

        $this->assertFalse($user->fresh()->isPro());
        $this->assertProFeaturesPaused($book);
    }

    public function test_sweep_extends_when_revenuecat_shows_a_late_renewal(): void
    {
        $user = $this->lapsedStoreUser(7);
        Http::fake(['api.revenuecat.com/*' => Http::response($this->rcSubscriber([
            'expires_date' => now()->addMonth()->toIso8601String(),
        ]))]);

        $this->artisan('billing:expire-store')->assertSuccessful();

        $this->assertTrue($user->fresh()->isPro());
        $this->assertTrue($user->fresh()->store_pro_expires_at->isFuture());
    }

    public function test_sweep_skips_user_when_revenuecat_lookup_fails(): void
    {
        $user = $this->lapsedStoreUser(7);
        Http::fake(['api.revenuecat.com/*' => Http::response([], 500)]);

        $this->artisan('billing:expire-store')->assertSuccessful();

        $this->assertTrue($user->fresh()->isPro());
    }

    public function test_sweep_without_revenuecat_configured_uses_local_expiry(): void
    {
        config(['services.revenuecat.secret_key' => null]);
        $user = $this->lapsedStoreUser(7);

        $this->artisan('billing:expire-store')->assertSuccessful();

        $this->assertFalse($user->fresh()->isPro());
    }

    // ── M2: sync filtering ────────────────────────────────────────────────

    public function test_sync_ignores_promotional_lifetime_and_disallowed_sandbox_grants(): void
    {
        $user = $this->actingAsUser($this->makeUser());

        $cases = [
            'promotional' => $this->rcSubscriber(['expires_date' => now()->addMonth()->toIso8601String()], ['store' => 'promotional']),
            'stripe'      => $this->rcSubscriber(['expires_date' => now()->addMonth()->toIso8601String()], ['store' => 'stripe']),
            'lifetime'    => $this->rcSubscriber(['expires_date' => null], ['store' => 'app_store']),
        ];

        foreach ($cases as $label => $payload) {
            Http::fake(['api.revenuecat.com/*' => Http::response($payload)]);
            $this->postJson('/api/v1/billing/sync')->assertOk()->assertJsonPath('data.isPro', false);
            $this->assertNull($user->fresh()->store_pro_expires_at, $label);
        }

        config(['services.revenuecat.allow_sandbox' => false]);
        Http::fake(['api.revenuecat.com/*' => Http::response($this->rcSubscriber(
            ['expires_date' => now()->addMonth()->toIso8601String()],
            ['store' => 'app_store', 'is_sandbox' => true],
        ))]);
        $this->postJson('/api/v1/billing/sync')->assertOk()->assertJsonPath('data.isPro', false);
    }

    public function test_sync_honours_sandbox_by_default_and_store_grace_period(): void
    {
        $this->actingAsUser($this->makeUser());
        Http::fake(['api.revenuecat.com/*' => Http::response($this->rcSubscriber(
            [
                'expires_date'              => now()->subHour()->toIso8601String(),
                'grace_period_expires_date' => now()->addDays(10)->toIso8601String(),
            ],
            ['store' => 'play_store', 'is_sandbox' => true],
        ))]);

        $this->postJson('/api/v1/billing/sync')
            ->assertOk()
            ->assertJsonPath('data.isPro', true)
            ->assertJsonPath('data.planSource', 'play_store');
    }

    public function test_store_entitlement_without_platform_does_not_grant_pro(): void
    {
        $user = $this->makeUser();
        $user->store_pro_expires_at = now()->addMonth();
        $user->store_platform = null;
        $user->save();

        app(PlanService::class)->recompute($user);

        $this->assertFalse($user->fresh()->isPro());
        $this->assertSame(0, DB::table('revenuecat_events')->count());
    }

    public function test_recompute_never_grants_pro_from_a_stale_local_stripe_row(): void
    {
        // e.g. an old test-mode subscription row, or a failed cancelNow during Force Free
        $user = $this->stripeUser('active');
        $user->plan = 'free';
        $user->plan_source = null;
        $user->save();

        app(PlanService::class)->recompute($user->fresh(), 'test');

        $this->assertSame('free', $user->fresh()->plan);
    }
}
