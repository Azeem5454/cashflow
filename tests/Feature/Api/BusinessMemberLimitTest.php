<?php

namespace Tests\Feature\Api;

use App\Livewire\Business\Settings;
use App\Models\Business;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

class BusinessMemberLimitTest extends ApiTestCase
{
    public function test_free_business_exposes_member_limit_and_counts(): void
    {
        $owner    = $this->makeUser();
        $business = $this->makeBusiness($owner);
        $business->invitations()->create(['email' => 'pending@example.com', 'role' => 'viewer', 'expires_at' => now()->addHours(72)]);
        $business->invitations()->create(['email' => 'old@example.com', 'role' => 'viewer', 'expires_at' => now()->subHour()]);
        $this->actingAsUser($owner);

        $this->getJson("/api/v1/businesses/{$business->id}")->assertOk()
            ->assertJsonPath('data.memberLimit', Business::FREE_MEMBER_LIMIT)
            ->assertJsonPath('data.membersCount', 1)
            ->assertJsonPath('data.pendingInvitesCount', 1);

        $row = collect($this->getJson('/api/v1/businesses')->assertOk()->json('data'))->firstWhere('id', $business->id);
        $this->assertSame(2, $row['memberLimit']);
        $this->assertSame(1, $row['membersCount']);
        $this->assertSame(1, $row['pendingInvitesCount']);
    }

    public function test_pro_business_has_unlimited_members(): void
    {
        $owner    = $this->makeUser(pro: true);
        $business = $this->makeBusiness($owner);
        $this->actingAsUser($owner);

        $this->getJson("/api/v1/businesses/{$business->id}")->assertOk()
            ->assertJsonPath('data.memberLimit', null)
            ->assertJsonPath('data.pendingInvitesCount', 0);
    }

    public function test_member_limit_matches_invite_gate_on_api_and_web(): void
    {
        Mail::fake();
        $owner    = $this->makeUser();
        $business = $this->makeBusiness($owner);
        $this->addMember($business, $this->makeUser(), 'viewer');
        $this->actingAsUser($owner);

        // Resource reports the team as full…
        $this->getJson("/api/v1/businesses/{$business->id}")->assertOk()
            ->assertJsonPath('data.membersCount', 2)
            ->assertJsonPath('data.memberLimit', 2);

        // …and both invite paths block the next invite.
        $this->postJson("/api/v1/businesses/{$business->id}/invitations", ['email' => 'third@example.com', 'role' => 'viewer'])
            ->assertForbidden();

        Livewire::actingAs($owner)->test(Settings::class, ['business' => $business])
            ->assertSee('members used · Free plan')
            ->set('inviteEmail', 'third@example.com')->call('sendInvite')
            ->assertSet('upgradeModalFeature', 'team');

        $this->assertSame(0, $business->invitations()->count());
    }

    public function test_web_settings_shows_unlimited_for_pro(): void
    {
        $owner    = $this->makeUser(pro: true);
        $business = $this->makeBusiness($owner);

        Livewire::actingAs($owner)->test(Settings::class, ['business' => $business])
            ->assertSee('Unlimited members');
    }
}
