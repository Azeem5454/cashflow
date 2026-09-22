<?php

namespace Tests\Feature;

use App\Livewire\Business\Settings;
use App\Mail\TeamInvitation;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\Feature\Api\ApiTestCase;

class TeamInviteTest extends ApiTestCase
{
    public function test_api_reinvite_refreshes_instead_of_duplicating_regardless_of_case(): void
    {
        Mail::fake();
        $owner    = $this->makeUser(pro: true);
        $business = $this->makeBusiness($owner);
        $this->actingAsUser($owner);

        $this->postJson("/api/v1/businesses/{$business->id}/invitations", ['email' => 'Sara@Example.com', 'role' => 'viewer'])
            ->assertCreated()->assertJsonPath('resent', false);
        $first = $business->invitations()->first();

        $this->postJson("/api/v1/businesses/{$business->id}/invitations", ['email' => 'sara@example.COM', 'role' => 'editor'])
            ->assertOk()->assertJsonPath('resent', true);

        $this->assertSame(1, $business->invitations()->count());
        $invite = $business->invitations()->first();
        $this->assertSame('sara@example.com', $invite->email);
        $this->assertSame('editor', $invite->role);
        $this->assertNotSame($first->token, $invite->token);
    }

    public function test_api_cannot_invite_an_existing_member_or_yourself_in_any_case(): void
    {
        Mail::fake();
        $owner    = $this->makeUser(pro: true, attrs: ['email' => 'azeem@e1sol.com']);
        $business = $this->makeBusiness($owner);
        $this->actingAsUser($owner);

        $this->postJson("/api/v1/businesses/{$business->id}/invitations", ['email' => 'azeem@E1sol.com', 'role' => 'viewer'])
            ->assertStatus(422);

        $this->assertSame(0, $business->invitations()->count());
        Mail::assertNothingQueued();
    }

    public function test_web_reinvite_refreshes_and_rejects_self_invite(): void
    {
        Mail::fake();
        $owner    = $this->makeUser(pro: true, attrs: ['email' => 'owner@example.com']);
        $business = $this->makeBusiness($owner);

        Livewire::actingAs($owner)->test(Settings::class, ['business' => $business])
            ->set('inviteEmail', 'New@Example.com')->set('inviteRole', 'viewer')->call('sendInvite')
            ->set('inviteEmail', 'new@example.com')->set('inviteRole', 'editor')->call('sendInvite')
            ->set('inviteEmail', 'OWNER@example.com')->call('sendInvite')
            ->assertHasErrors('inviteEmail');

        $this->assertSame(1, $business->invitations()->count());
        $this->assertSame('editor', $business->invitations()->first()->role);
        Mail::assertSent(TeamInvitation::class, 2);
    }
}
