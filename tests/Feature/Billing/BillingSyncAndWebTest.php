<?php

namespace Tests\Feature\Billing;

use App\Livewire\Settings\Billing;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

class BillingSyncAndWebTest extends BillingTestCase
{
    // ── POST /api/v1/billing/sync ─────────────────────────────────────────

    public function test_sync_requires_auth(): void
    {
        $this->postJson('/api/v1/billing/sync')->assertStatus(401);
    }

    public function test_sync_returns_503_when_not_configured(): void
    {
        config(['services.revenuecat.secret_key' => null]);
        $this->actingAsUser($this->makeUser());

        $this->postJson('/api/v1/billing/sync')->assertStatus(503);
    }

    public function test_sync_grants_pro_from_revenuecat_entitlement(): void
    {
        $user = $this->actingAsUser($this->makeUser());
        $expires = now()->addMonth()->startOfSecond();

        Http::fake([
            'api.revenuecat.com/v1/subscribers/*' => Http::response(['subscriber' => [
                'entitlements'  => ['pro' => [
                    'expires_date'       => $expires->toIso8601String(),
                    'product_identifier' => 'thecashfox_pro_monthly',
                ]],
                'subscriptions' => ['thecashfox_pro_monthly' => ['store' => 'play_store']],
            ]]),
        ]);

        $this->postJson('/api/v1/billing/sync')
            ->assertOk()
            ->assertJsonPath('data.isPro', true)
            ->assertJsonPath('data.planSource', 'play_store')
            ->assertJsonPath('data.proExpiresAt', $expires->toIso8601String());

        Http::assertSent(fn ($r) => $r->url() === 'https://api.revenuecat.com/v1/subscribers/' . $user->id
            && $r->hasHeader('Authorization', 'Bearer sk_test_rc'));
    }

    public function test_sync_without_entitlement_ends_stale_store_access(): void
    {
        $user = $this->makeUser(pro: true);
        $user->plan_source = 'app_store';
        $this->giveStoreEntitlement($user, now()->addDays(3));
        $this->actingAsUser($user);

        Http::fake(['api.revenuecat.com/*' => Http::response(['subscriber' => ['entitlements' => [], 'subscriptions' => []]])]);

        $this->postJson('/api/v1/billing/sync')->assertOk()->assertJsonPath('data.isPro', false);
    }

    public function test_sync_does_not_touch_stripe_pro_user_without_store_entitlement(): void
    {
        $user = $this->actingAsUser($this->stripeUser('active'));
        Http::fake(['api.revenuecat.com/*' => Http::response(['subscriber' => ['entitlements' => []]])]);

        $this->postJson('/api/v1/billing/sync')
            ->assertOk()
            ->assertJsonPath('data.isPro', true)
            ->assertJsonPath('data.planSource', 'stripe');
    }

    public function test_sync_reports_upstream_failure(): void
    {
        $this->actingAsUser($this->makeUser());
        Http::fake(['api.revenuecat.com/*' => Http::response([], 500)]);

        $this->postJson('/api/v1/billing/sync')->assertStatus(502);
    }

    public function test_user_resource_exposes_plan_source_and_store_expiry(): void
    {
        $user = $this->makeUser();
        $this->actingAsUser($user);

        $this->getJson('/api/v1/user')
            ->assertOk()
            ->assertJsonPath('data.planSource', null)
            ->assertJsonPath('data.proExpiresAt', null);
    }

    // ── Web billing page ──────────────────────────────────────────────────

    public function test_billing_page_for_store_billed_user_hides_stripe(): void
    {
        $user = $this->makeUser(pro: true);
        $user->plan_source = 'app_store';
        $this->giveStoreEntitlement($user, now()->addMonth());
        $this->actingAs($user);

        Livewire::test(Billing::class)
            ->assertSee('Your Pro plan is billed through the App Store.')
            ->assertSee('Manage it on your device.')
            ->assertDontSee('Open Billing Portal')
            ->assertDontSee('Manage Billing');
    }

    public function test_billing_page_names_google_play(): void
    {
        $user = $this->makeUser(pro: true);
        $user->plan_source = 'play_store';
        $this->giveStoreEntitlement($user, now()->addMonth(), 'play_store');
        $this->actingAs($user);

        Livewire::test(Billing::class)->assertSee('billed through Google Play');
    }

    public function test_billing_page_for_stripe_user_is_unchanged(): void
    {
        $this->actingAs($this->stripeUser('active'));

        Livewire::test(Billing::class)
            ->assertSee('Open Billing Portal')
            ->assertSee('Manage Billing')
            ->assertSee('Active subscription')
            ->assertDontSee('Manage it on your device.');
    }

    public function test_billing_page_for_free_user_offers_upgrade(): void
    {
        $this->actingAs($this->makeUser());

        Livewire::test(Billing::class)
            ->assertSee('Upgrade to Pro')
            ->assertDontSee('Manage it on your device.');
    }

    public function test_stripe_checkout_is_refused_with_active_store_entitlement(): void
    {
        // Even if the local plan somehow says Free, an active store entitlement blocks checkout.
        $user = $this->makeUser();
        $this->giveStoreEntitlement($user, now()->addMonth(), 'play_store');
        $this->actingAs($user);

        Livewire::test(Billing::class)
            ->call('subscribe')
            ->assertHasErrors('stripe')
            ->assertNoRedirect();

        $this->assertSame(0, $user->subscriptions()->count());
    }

    public function test_resume_is_refused_with_active_store_entitlement(): void
    {
        $user = $this->stripeUser('active', now()->addDays(3));
        $this->giveStoreEntitlement($user, now()->addMonth());
        $this->actingAs($user);

        Livewire::test(Billing::class)
            ->call('resume')
            ->assertHasErrors('stripe')
            ->assertNoRedirect();

        $this->assertNotNull($user->subscription('default')->fresh()->ends_at);
    }
}
