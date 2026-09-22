<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;

class BiometricTokenTest extends ApiTestCase
{
    private const DEVICE = '3f1c2a9e-8b7d-4c6e-9a1b-2d3e4f5a6b7c';

    /** Real bearer-token request (resets the guard so each call re-authenticates). */
    private function as(string $token, string $method, string $uri, array $data = []): TestResponse
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($token)->json($method, $uri, $data);
    }

    private function sessionToken(User $user): string
    {
        return $user->createToken('mobile')->plainTextToken;
    }

    public function test_create_returns_token_named_per_device(): void
    {
        $user = $this->makeUser();
        $session = $this->sessionToken($user);

        $res = $this->as($session, 'POST', '/api/v1/auth/biometric-token', ['deviceId' => self::DEVICE])
            ->assertCreated()
            ->assertJsonStructure(['token']);

        $this->assertNotEmpty($res->json('token'));
        $this->assertSame(1, $user->tokens()->where('name', 'mobile-biometric:' . self::DEVICE)->count());
    }

    public function test_create_replaces_previous_token_for_same_device_only(): void
    {
        $user = $this->makeUser();
        $session = $this->sessionToken($user);

        $first = $this->as($session, 'POST', '/api/v1/auth/biometric-token', ['deviceId' => self::DEVICE])->json('token');
        $this->as($session, 'POST', '/api/v1/auth/biometric-token', ['deviceId' => 'other-device'])->assertCreated();
        $second = $this->as($session, 'POST', '/api/v1/auth/biometric-token', ['deviceId' => self::DEVICE])->json('token');

        $this->assertNotSame($first, $second);
        $this->assertSame(1, $user->tokens()->where('name', 'mobile-biometric:' . self::DEVICE)->count());
        $this->assertSame(1, $user->tokens()->where('name', 'mobile-biometric:other-device')->count());

        $this->as($first, 'GET', '/api/v1/user')->assertUnauthorized();
        $this->as($second, 'GET', '/api/v1/user')->assertOk()->assertJsonPath('data.id', $user->id);
    }

    public function test_create_validates_device_id(): void
    {
        $user = $this->makeUser();
        $session = $this->sessionToken($user);

        $this->as($session, 'POST', '/api/v1/auth/biometric-token', ['deviceId' => str_repeat('a', 101)])
            ->assertStatus(422);
        $this->as($session, 'POST', '/api/v1/auth/biometric-token', ['deviceId' => 'bad id/..'])
            ->assertStatus(422);
    }

    public function test_requires_authentication(): void
    {
        $this->postJson('/api/v1/auth/biometric-token', ['deviceId' => self::DEVICE])->assertUnauthorized();
        $this->deleteJson('/api/v1/auth/biometric-token', ['deviceId' => self::DEVICE])->assertUnauthorized();
        $this->postJson('/api/v1/auth/biometric-login')->assertUnauthorized();
    }

    public function test_delete_revokes_only_that_devices_token(): void
    {
        $user = $this->makeUser();
        $session = $this->sessionToken($user);
        $bio = $this->as($session, 'POST', '/api/v1/auth/biometric-token', ['deviceId' => self::DEVICE])->json('token');
        $other = $this->as($session, 'POST', '/api/v1/auth/biometric-token', ['deviceId' => 'other-device'])->json('token');

        $this->as($session, 'DELETE', '/api/v1/auth/biometric-token', ['deviceId' => self::DEVICE])->assertOk();

        $this->as($bio, 'GET', '/api/v1/user')->assertUnauthorized();
        $this->as($other, 'GET', '/api/v1/user')->assertOk();
        $this->as($session, 'GET', '/api/v1/user')->assertOk();
    }

    public function test_delete_cannot_touch_another_users_token(): void
    {
        $alice = $this->makeUser();
        $bob = $this->makeUser();
        $aliceBio = $this->as($this->sessionToken($alice), 'POST', '/api/v1/auth/biometric-token', ['deviceId' => self::DEVICE])->json('token');

        $this->as($this->sessionToken($bob), 'DELETE', '/api/v1/auth/biometric-token', ['deviceId' => self::DEVICE])->assertOk();

        $this->as($aliceBio, 'GET', '/api/v1/user')->assertOk()->assertJsonPath('data.id', $alice->id);
    }

    public function test_biometric_token_works_for_get_user(): void
    {
        $user = $this->makeUser();
        $bio = $this->as($this->sessionToken($user), 'POST', '/api/v1/auth/biometric-token', ['deviceId' => self::DEVICE])->json('token');

        $this->as($bio, 'GET', '/api/v1/user')->assertOk()->assertJsonPath('data.email', $user->email);
    }

    public function test_logout_with_normal_token_leaves_biometric_token_valid(): void
    {
        $user = $this->makeUser();
        $session = $this->sessionToken($user);
        $bio = $this->as($session, 'POST', '/api/v1/auth/biometric-token', ['deviceId' => self::DEVICE])->json('token');

        $this->as($session, 'POST', '/api/v1/auth/logout')->assertOk();

        $this->as($session, 'GET', '/api/v1/user')->assertUnauthorized();
        $this->as($bio, 'GET', '/api/v1/user')->assertOk();
    }

    public function test_biometric_login_issues_fresh_session_and_keeps_biometric_token(): void
    {
        $user = $this->makeUser();
        $session = $this->sessionToken($user);
        $bio = $this->as($session, 'POST', '/api/v1/auth/biometric-token', ['deviceId' => self::DEVICE])->json('token');
        $this->as($session, 'POST', '/api/v1/auth/logout')->assertOk();

        $res = $this->as($bio, 'POST', '/api/v1/auth/biometric-login')
            ->assertOk()
            ->assertJsonStructure(['user' => ['id', 'name', 'email', 'plan', 'isPro', 'emailVerified', 'createdAt'], 'token'])
            ->assertJsonPath('user.id', $user->id);

        $newSession = $res->json('token');
        $this->assertNotSame($bio, $newSession);

        // Signing out of the new session doesn't kill Face ID sign-in.
        $this->as($newSession, 'POST', '/api/v1/auth/logout')->assertOk();
        $this->as($newSession, 'GET', '/api/v1/user')->assertUnauthorized();
        $this->as($bio, 'POST', '/api/v1/auth/biometric-login')->assertOk();
    }

    public function test_biometric_login_rejects_normal_tokens(): void
    {
        $user = $this->makeUser();

        $this->as($this->sessionToken($user), 'POST', '/api/v1/auth/biometric-login')->assertForbidden();
    }

    public function test_password_change_revokes_biometric_tokens(): void
    {
        $user = $this->makeUser(false, ['password' => Hash::make('old-password-123')]);
        $session = $this->sessionToken($user);
        $bio = $this->as($session, 'POST', '/api/v1/auth/biometric-token', ['deviceId' => self::DEVICE])->json('token');

        $this->as($session, 'PUT', '/api/v1/profile/password', [
            'currentPassword'       => 'old-password-123',
            'password'              => 'New-password-456!',
            'password_confirmation' => 'New-password-456!',
        ])->assertOk();

        $this->as($bio, 'GET', '/api/v1/user')->assertUnauthorized();
        $this->as($session, 'GET', '/api/v1/user')->assertOk();
    }

    public function test_expired_biometric_token_is_rejected(): void
    {
        config(['sanctum.expiration' => 60]);
        $user = $this->makeUser();
        $bio = $this->as($this->sessionToken($user), 'POST', '/api/v1/auth/biometric-token', ['deviceId' => self::DEVICE])->json('token');

        $this->travel(61)->minutes();

        $this->as($bio, 'POST', '/api/v1/auth/biometric-login')->assertUnauthorized();
    }
}
