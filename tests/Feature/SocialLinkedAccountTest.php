<?php

namespace Tests\Feature;

use App\Http\Resources\V1\UserResource;
use App\Livewire\Settings\Profile;
use App\Models\User;
use App\Notifications\CustomResetPassword;
use App\Services\SocialAccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Tests\TestCase;

class SocialLinkedAccountTest extends TestCase
{
    use RefreshDatabase;

    private function socialUser(): User
    {
        return app(SocialAccountService::class)
            ->resolveGoogleUser('g-' . uniqid(), 'social' . uniqid() . '@example.com', 'Social User')
            ->refresh();
    }

    private function linkedPasswordUser(): User
    {
        $user = User::factory()->create();
        $user->provider    = 'google';
        $user->provider_id = 'g-linked-' . uniqid();
        $user->save();

        return $user->refresh();
    }

    // ── Backfill heuristic ───────────────────────────────────────────

    public function test_backfill_flags_only_social_created_accounts(): void
    {
        $created = now()->subDays(10);

        $insert = function (string $email, ?string $provider, $verifiedAt) use ($created) {
            $id = (string) \Illuminate\Support\Str::uuid();
            DB::table('users')->insert([
                'id'                => $id,
                'name'              => 'U',
                'email'             => $email,
                'password'          => Hash::make('secret-pass'),
                'plan'              => 'free',
                'provider'          => $provider,
                'provider_id'       => $provider ? 'pid-' . $email : null,
                'email_verified_at' => $verifiedAt,
                'created_at'        => $created,
                'updated_at'        => $created,
            ]);

            return $id;
        };

        $socialCreated   = $insert('a@example.com', 'google', $created->copy()->addSeconds(1));
        $socialSameTick  = $insert('b@example.com', 'apple', $created->copy());
        $linkedLater     = $insert('c@example.com', 'google', $created->copy()->addMinutes(12));
        $passwordOnly    = $insert('d@example.com', null, $created->copy());
        $unverifiedLinked = $insert('e@example.com', 'google', null);

        $migration = require database_path('migrations/2026_09_22_000001_add_has_password_to_users_table.php');
        $migration->backfill();

        $flags = DB::table('users')->pluck('has_password', 'id')->map(fn ($v) => (bool) $v);

        $this->assertFalse($flags[$socialCreated]);
        $this->assertFalse($flags[$socialSameTick]);
        $this->assertTrue($flags[$linkedLater]);
        $this->assertTrue($flags[$passwordOnly]);
        $this->assertTrue($flags[$unverifiedLinked]);
    }

    // ── Creation / linking / reset ───────────────────────────────────

    public function test_social_created_user_has_no_password(): void
    {
        $user = $this->socialUser();

        $this->assertFalse($user->has_password);
        $this->assertSame('google', $user->authProvider());
    }

    public function test_linking_existing_password_user_keeps_has_password(): void
    {
        $existing = User::factory()->create(['email' => 'keep@example.com']);

        $user = app(SocialAccountService::class)->resolveGoogleUser('g-keep', 'keep@example.com', 'Keep');

        $this->assertSame($existing->id, $user->id);
        $this->assertTrue($user->refresh()->has_password);
        $this->assertSame('google', $user->provider);
    }

    public function test_regular_registration_has_password(): void
    {
        $user = User::factory()->create();
        $this->assertTrue($user->refresh()->has_password);
    }

    public function test_password_reset_link_flow_sets_has_password(): void
    {
        $user  = $this->socialUser();
        $token = Password::createToken($user);

        $this->post('/reset-password', [
            'token'                 => $token,
            'email'                 => $user->email,
            'password'              => 'BrandNewPass123!',
            'password_confirmation' => 'BrandNewPass123!',
        ])->assertSessionHasNoErrors()->assertRedirect(route('login'));

        $user->refresh();
        $this->assertTrue($user->has_password);
        $this->assertTrue(Hash::check('BrandNewPass123!', $user->password));
    }

    public function test_api_forgot_password_sends_link_to_social_user(): void
    {
        Notification::fake();
        $user = $this->socialUser();

        $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email])->assertOk();

        Notification::assertSentTo($user, CustomResetPassword::class);
    }

    // ── UserResource ─────────────────────────────────────────────────

    public function test_user_resource_exposes_provider_and_has_password(): void
    {
        $social = $this->socialUser();
        $plain  = User::factory()->create();

        $s = (new UserResource($social))->toArray(Request::create('/'));
        $p = (new UserResource($plain->refresh()))->toArray(Request::create('/'));

        $this->assertSame('google', $s['authProvider']);
        $this->assertFalse($s['hasPassword']);
        $this->assertNull($p['authProvider']);
        $this->assertTrue($p['hasPassword']);

        Sanctum::actingAs($social);
        $this->getJson('/api/v1/user')
            ->assertOk()
            ->assertJsonPath('data.authProvider', 'google')
            ->assertJsonPath('data.hasPassword', false);
    }

    // ── Email changes ────────────────────────────────────────────────

    public function test_api_blocks_email_change_for_provider_user_but_allows_name(): void
    {
        $user = $this->linkedPasswordUser();
        Sanctum::actingAs($user);

        $this->putJson('/api/v1/profile', ['email' => 'new@example.com'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Your email is managed by your Google account.');

        $this->putJson('/api/v1/profile', ['name' => 'Renamed', 'email' => strtoupper($user->email)])
            ->assertOk();

        $user->refresh();
        $this->assertSame('Renamed', $user->name);
        $this->assertNotSame('new@example.com', $user->email);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_api_still_allows_email_change_for_password_user(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->putJson('/api/v1/profile', ['email' => 'moved@example.com'])->assertOk();
        $this->assertSame('moved@example.com', $user->refresh()->email);
    }

    public function test_web_blocks_email_change_for_provider_user(): void
    {
        $user     = $this->linkedPasswordUser();
        $original = $user->email;

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->set('name', 'Web Rename')
            ->set('email', 'other@example.com')
            ->call('saveProfile')
            ->assertHasErrors('email');

        $this->assertSame($original, $user->refresh()->email);

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->set('name', 'Web Rename')
            ->call('saveProfile')
            ->assertHasNoErrors();

        $this->assertSame('Web Rename', $user->refresh()->name);
    }

    // ── Password change ──────────────────────────────────────────────

    public function test_api_password_change_rejected_for_no_password_user(): void
    {
        $user = $this->socialUser();
        Sanctum::actingAs($user);

        $this->putJson('/api/v1/profile/password', [
            'password'              => 'BrandNewPass123!',
            'password_confirmation' => 'BrandNewPass123!',
        ])->assertStatus(422)
          ->assertJsonPath('message', "Use 'Set a password' — we'll email you a secure link.");

        $this->assertFalse($user->refresh()->has_password);
    }

    public function test_web_set_password_link_sends_reset_email(): void
    {
        Notification::fake();
        $user = $this->socialUser();

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->call('sendSetPasswordLink')
            ->assertHasNoErrors()
            ->assertSet('setPasswordStatus', fn ($v) => str_contains($v, $user->email));

        Notification::assertSentTo($user, CustomResetPassword::class);
    }

    public function test_web_change_password_blocked_for_no_password_user(): void
    {
        $user = $this->socialUser();

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->set('currentPassword', 'whatever')
            ->set('newPassword', 'BrandNewPass123!')
            ->set('newPasswordConfirmation', 'BrandNewPass123!')
            ->call('savePassword')
            ->assertHasErrors('currentPassword');
    }

    // ── Account deletion ─────────────────────────────────────────────

    public function test_no_password_user_deletes_account_by_confirming_email(): void
    {
        $user = $this->socialUser();
        Sanctum::actingAs($user);

        $this->deleteJson('/api/v1/profile', ['confirmEmail' => 'wrong@example.com'])->assertStatus(422);
        $this->deleteJson('/api/v1/profile', ['password' => 'anything'])->assertStatus(422);
        $this->assertNotNull(User::find($user->id));

        $this->deleteJson('/api/v1/profile', ['confirmEmail' => strtoupper($user->email)])->assertOk();
        $this->assertNull(User::find($user->id));
    }

    public function test_password_user_must_supply_password_to_delete(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->deleteJson('/api/v1/profile', ['confirmEmail' => $user->email])->assertStatus(422);
        $this->deleteJson('/api/v1/profile', ['password' => 'wrong-password'])->assertStatus(422);
        $this->assertNotNull(User::find($user->id));

        $this->deleteJson('/api/v1/profile', ['password' => 'password'])->assertOk();
        $this->assertNull(User::find($user->id));
    }

    public function test_linked_password_user_still_changes_password_via_api(): void
    {
        $user = $this->linkedPasswordUser();
        Sanctum::actingAs($user);

        $this->putJson('/api/v1/profile/password', [
            'currentPassword'       => 'password',
            'password'              => 'BrandNewPass123!',
            'password_confirmation' => 'BrandNewPass123!',
        ])->assertOk();

        $this->assertTrue(Hash::check('BrandNewPass123!', $user->refresh()->password));
    }

    public function test_profile_page_renders_social_state(): void
    {
        $user = $this->socialUser();

        $this->actingAs($user)->get('/profile')
            ->assertOk()
            ->assertSee('Signed in with Google')
            ->assertSee('Managed by your Google account.')
            ->assertSee('Email me a link');

        $plain = User::factory()->create();
        $this->actingAs($plain)->get('/profile')
            ->assertOk()
            ->assertDontSee('Signed in with Google')
            ->assertSee('Update Password');
    }
}
