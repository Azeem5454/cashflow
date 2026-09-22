<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Services\AppleIdentityTokenVerifier;
use App\Services\MobileSocialLogin;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;

class SocialAuthTest extends ApiTestCase
{
    private string $privateKey;
    private array $jwk;
    private const KID = 'test-kid-1';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.google.client_id'     => 'google-client',
            'services.google.client_secret' => 'google-secret',
            'services.google.redirect'      => 'https://thecashfox.test/auth/google/callback',
            'services.apple.bundle_id'      => 'com.thecashfox.app',
            'services.mobile.allow_expo_go' => false,
        ]);
    }

    // ── Exchange ─────────────────────────────────────────────────────

    public function test_exchange_code_is_single_use_and_returns_login_shape(): void
    {
        $user = $this->makeUser();
        $code = app(MobileSocialLogin::class)->issueCode($user->id);

        $this->postJson('/api/v1/auth/social/exchange', ['code' => $code])
            ->assertOk()
            ->assertJsonStructure(['user' => ['id', 'name', 'email', 'plan', 'isPro', 'emailVerified', 'createdAt'], 'token'])
            ->assertJsonPath('user.id', $user->id);

        $this->assertSame(1, $user->tokens()->where('name', 'mobile')->count());

        $this->postJson('/api/v1/auth/social/exchange', ['code' => $code])->assertStatus(422);
    }

    public function test_exchange_code_expires_after_120_seconds(): void
    {
        $user = $this->makeUser();
        $code = app(MobileSocialLogin::class)->issueCode($user->id);

        $this->travel(121)->seconds();

        $this->postJson('/api/v1/auth/social/exchange', ['code' => $code])->assertStatus(422);
    }

    public function test_exchange_rejects_unknown_code(): void
    {
        $this->postJson('/api/v1/auth/social/exchange', ['code' => str_repeat('a', 80)])->assertStatus(422);
        $this->postJson('/api/v1/auth/social/exchange', [])->assertStatus(422);
    }

    public function test_exchange_enforces_pkce_challenge_when_present(): void
    {
        $user      = $this->makeUser();
        $verifier  = str_repeat('v', 50);
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

        $code = app(MobileSocialLogin::class)->issueCode($user->id, $challenge);
        $this->postJson('/api/v1/auth/social/exchange', ['code' => $code, 'codeVerifier' => str_repeat('x', 50)])
            ->assertStatus(422);

        $code = app(MobileSocialLogin::class)->issueCode($user->id, $challenge);
        $this->postJson('/api/v1/auth/social/exchange', ['code' => $code, 'codeVerifier' => $verifier])
            ->assertOk();
    }

    public function test_exchange_refuses_admins(): void
    {
        $admin = $this->makeUser();
        $admin->is_admin = true;
        $admin->save();

        $code = app(MobileSocialLogin::class)->issueCode($admin->id);

        $this->postJson('/api/v1/auth/social/exchange', ['code' => $code])->assertStatus(403);
        $this->assertSame(0, $admin->tokens()->count());
    }

    // ── redirect_uri allowlist ───────────────────────────────────────

    public function test_redirect_uri_allowlist(): void
    {
        $this->assertTrue(MobileSocialLogin::isAllowedRedirectUri('thecashfox://auth'));
        $this->assertTrue(MobileSocialLogin::isAllowedRedirectUri('thecashfox:///oauth/callback?x=1'));
        $this->assertFalse(MobileSocialLogin::isAllowedRedirectUri('https://evil.com'));
        $this->assertFalse(MobileSocialLogin::isAllowedRedirectUri('thecashfox.evil://auth'));
        $this->assertFalse(MobileSocialLogin::isAllowedRedirectUri('thecashfox://auth#frag'));
        $this->assertFalse(MobileSocialLogin::isAllowedRedirectUri('exp://192.168.1.2:8081/--/auth'));
        $this->assertFalse(MobileSocialLogin::isAllowedRedirectUri(null));

        config(['services.mobile.allow_expo_go' => true]);
        $this->assertTrue(MobileSocialLogin::isAllowedRedirectUri('exp://192.168.1.2:8081/--/auth'));
        $this->assertTrue(MobileSocialLogin::isAllowedRedirectUri('exps://u.expo.dev/--/auth'));
        $this->assertFalse(MobileSocialLogin::isAllowedRedirectUri('https://evil.com'));
    }

    public function test_mobile_redirect_endpoint_validates_redirect_uri(): void
    {
        $this->mockGoogleRedirect();

        $this->get('/auth/google/mobile?redirect_uri=' . urlencode('https://evil.com/cb'))->assertStatus(400);
        $this->get('/auth/google/mobile?redirect_uri=' . urlencode('exp://127.0.0.1:8081/--/auth'))->assertStatus(400);
        $this->get('/auth/google/mobile')->assertStatus(400);

        $this->get('/auth/google/mobile?redirect_uri=' . urlencode('thecashfox://auth'))
            ->assertRedirect('https://accounts.google.com/o/oauth2/auth?fake=1')
            ->assertSessionHas(MobileSocialLogin::SESSION_KEY . '.redirect_uri', 'thecashfox://auth');

        config(['services.mobile.allow_expo_go' => true]);
        $this->get('/auth/google/mobile?redirect_uri=' . urlencode('exp://127.0.0.1:8081/--/auth'))
            ->assertRedirect('https://accounts.google.com/o/oauth2/auth?fake=1');
    }

    public function test_mobile_redirect_works_for_already_logged_in_browser(): void
    {
        $this->mockGoogleRedirect();

        $this->actingAs($this->makeUser())
            ->get('/auth/google/mobile?redirect_uri=' . urlencode('thecashfox://auth'))
            ->assertRedirect('https://accounts.google.com/o/oauth2/auth?fake=1');
    }

    // ── Google callback ──────────────────────────────────────────────

    public function test_mobile_callback_creates_user_and_redirects_with_code(): void
    {
        $this->mockGoogleUser('g-123', 'new.person@example.com', 'New Person');

        $response = $this->withSession([MobileSocialLogin::SESSION_KEY => [
            'mobile' => 1, 'redirect_uri' => 'thecashfox://auth?from=app', 'code_challenge' => null,
        ]])->get('/auth/google/callback?code=x&state=y');

        $location = $response->assertRedirect()->headers->get('Location');
        $this->assertStringStartsWith('thecashfox://auth?from=app&code=', $location);
        $this->assertGuest();
        $response->assertSessionMissing(MobileSocialLogin::SESSION_KEY);

        $user = User::where('email', 'new.person@example.com')->firstOrFail();
        $this->assertSame('free', $user->plan);
        $this->assertSame('google', $user->provider);
        $this->assertSame('g-123', $user->provider_id);
        $this->assertNotNull($user->email_verified_at);

        parse_str(parse_url($location, PHP_URL_QUERY), $query);
        $this->assertGreaterThanOrEqual(64, strlen($query['code']));

        $this->postJson('/api/v1/auth/social/exchange', ['code' => $query['code']])
            ->assertOk()
            ->assertJsonPath('user.email', 'new.person@example.com');
    }

    public function test_mobile_callback_keeps_existing_user_password_and_plan(): void
    {
        $existing = $this->makeUser(pro: true, attrs: ['email' => 'owner@example.com']);
        $hash = $existing->password;
        $this->mockGoogleUser('g-999', 'owner@example.com', 'Someone Else');

        $location = $this->withSession([MobileSocialLogin::SESSION_KEY => [
            'mobile' => 1, 'redirect_uri' => 'thecashfox://auth', 'code_challenge' => null,
        ]])->get('/auth/google/callback?code=x&state=y')->headers->get('Location');

        $this->assertStringStartsWith('thecashfox://auth?code=', $location);
        $existing->refresh();
        $this->assertSame('pro', $existing->plan);
        $this->assertSame($hash, $existing->password);
        $this->assertSame(1, User::count());
    }

    public function test_mobile_callback_cancel_and_failure_redirect_to_app_without_details(): void
    {
        $session = [MobileSocialLogin::SESSION_KEY => [
            'mobile' => 1, 'redirect_uri' => 'thecashfox://auth', 'code_challenge' => null,
        ]];

        $this->withSession($session)->get('/auth/google/callback?error=access_denied')
            ->assertRedirect('thecashfox://auth?error=cancelled');

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andThrow(new \RuntimeException('secret internal detail'));
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $this->withSession($session)->get('/auth/google/callback?code=x&state=y')
            ->assertRedirect('thecashfox://auth?error=failed');
    }

    public function test_web_callback_still_logs_in_browser(): void
    {
        $this->mockGoogleUser('g-555', 'web.user@example.com', 'Web User');

        $this->get('/auth/google/callback?code=x&state=y')->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
        $this->assertSame('web.user@example.com', auth()->user()->email);
    }

    public function test_web_callback_redirects_authenticated_users_like_guest_middleware(): void
    {
        $this->actingAs($this->makeUser())
            ->get('/auth/google/callback?code=x&state=y')
            ->assertRedirect(route('dashboard'));
    }

    // ── Apple ────────────────────────────────────────────────────────

    public function test_apple_valid_token_creates_user_and_returns_token(): void
    {
        $this->fakeAppleKeys();

        $this->postJson('/api/v1/auth/apple', [
            'identityToken' => $this->appleToken(['email' => 'abc@privaterelay.appleid.com']),
            'fullName'      => ['givenName' => 'Ada', 'familyName' => 'Lovelace'],
            'email'         => 'ignored@example.com',
        ])
            ->assertOk()
            ->assertJsonStructure(['user' => ['id', 'name', 'email', 'plan', 'isPro', 'emailVerified', 'createdAt'], 'token'])
            ->assertJsonPath('user.email', 'abc@privaterelay.appleid.com')
            ->assertJsonPath('user.name', 'Ada Lovelace')
            ->assertJsonPath('user.plan', 'free')
            ->assertJsonPath('user.emailVerified', true);

        $user = User::firstOrFail();
        $this->assertSame('apple', $user->provider);
        $this->assertSame('apple-sub-1', $user->provider_id);
        $this->assertFalse(User::where('email', 'ignored@example.com')->exists());

        // Second sign-in: Apple omits the email; matched by sub.
        $this->postJson('/api/v1/auth/apple', [
            'identityToken' => $this->appleToken(['email' => null]),
        ])->assertOk()->assertJsonPath('user.id', $user->id);
        $this->assertSame(1, User::count());
    }

    public function test_apple_matches_existing_user_by_email(): void
    {
        $this->fakeAppleKeys();
        $existing = $this->makeUser(pro: true, attrs: ['email' => 'owner@example.com']);

        $this->postJson('/api/v1/auth/apple', [
            'identityToken' => $this->appleToken(['email' => 'owner@example.com']),
        ])->assertOk()->assertJsonPath('user.id', $existing->id)->assertJsonPath('user.plan', 'pro');

        $this->assertSame(1, User::count());
        $this->assertSame('apple', $existing->fresh()->provider);
    }

    public function test_apple_without_verified_email_and_no_sub_match_is_rejected(): void
    {
        $this->fakeAppleKeys();

        $this->postJson('/api/v1/auth/apple', ['identityToken' => $this->appleToken(['email' => null])])
            ->assertStatus(422);
        $this->postJson('/api/v1/auth/apple', [
            'identityToken' => $this->appleToken(['email' => 'x@example.com', 'email_verified' => 'false']),
        ])->assertStatus(422);
        $this->assertSame(0, User::count());
    }

    public function test_apple_rejects_bad_tokens(): void
    {
        $this->fakeAppleKeys();

        $cases = [
            'wrong aud'     => $this->appleToken(['aud' => 'com.evil.app']),
            'wrong iss'     => $this->appleToken(['iss' => 'https://evil.example.com']),
            'expired'       => $this->appleToken(['exp' => time() - 3600, 'iat' => time() - 7200]),
            'expo go aud'   => $this->appleToken(['aud' => 'host.exp.Exponent']),
            'bad signature' => $this->appleToken([], $this->otherPrivateKey()),
            'garbage'       => 'not.a.jwt',
        ];

        foreach ($cases as $label => $token) {
            $this->postJson('/api/v1/auth/apple', ['identityToken' => $token])
                ->assertStatus(401);
        }

        $this->postJson('/api/v1/auth/apple', [])->assertStatus(422);
        $this->assertSame(0, User::count());
    }

    public function test_apple_expo_go_audience_only_with_flag(): void
    {
        $this->fakeAppleKeys();
        config(['services.mobile.allow_expo_go' => true]);

        $this->postJson('/api/v1/auth/apple', [
            'identityToken' => $this->appleToken(['aud' => 'host.exp.Exponent', 'email' => 'dev@example.com']),
        ])->assertOk();
    }

    public function test_apple_refuses_admins(): void
    {
        $this->fakeAppleKeys();
        $admin = $this->makeUser(attrs: ['email' => 'admin@example.com']);
        $admin->is_admin = true;
        $admin->save();

        $this->postJson('/api/v1/auth/apple', [
            'identityToken' => $this->appleToken(['email' => 'admin@example.com']),
        ])->assertStatus(403);
        $this->assertSame(0, $admin->tokens()->count());
    }

    public function test_apple_keys_unavailable_returns_503(): void
    {
        Http::fake([AppleIdentityTokenVerifier::JWKS_URL => Http::response('down', 500)]);
        $this->makeKeypair();

        $this->postJson('/api/v1/auth/apple', ['identityToken' => $this->appleToken()])
            ->assertStatus(503);
    }

    // ── Helpers ──────────────────────────────────────────────────────

    // ── Starter workspace on social sign-up ─────────────────────────

    public function test_mobile_google_new_user_gets_starter_workspace_with_app_currency_on_exchange(): void
    {
        $this->mockGoogleUser('g-777', 'fresh@example.com', 'Fresh User');

        $location = $this->withSession([MobileSocialLogin::SESSION_KEY => [
            'mobile' => 1, 'redirect_uri' => 'thecashfox://auth', 'code_challenge' => null,
        ]])->get('/auth/google/callback?code=x&state=y')->headers->get('Location');
        parse_str(parse_url($location, PHP_URL_QUERY), $query);

        $user = User::where('email', 'fresh@example.com')->firstOrFail();
        $this->assertSame(0, $user->businesses()->count(), 'created on exchange, not callback');

        $response = $this->postJson('/api/v1/auth/social/exchange', ['code' => $query['code'], 'currency' => 'PKR'])
            ->assertOk()
            ->assertJsonStructure(['user', 'token', 'onboarding' => ['businessId', 'bookId']]);

        $business = $user->businesses()->firstOrFail();
        $this->assertSame('PKR', $business->currency);
        $this->assertSame($business->id, $response->json('onboarding.businessId'));
        $this->assertSame(1, $business->books()->count());
    }

    public function test_mobile_google_existing_user_gets_no_workspace(): void
    {
        $existing = $this->makeUser(attrs: ['email' => 'old@example.com']);
        $this->mockGoogleUser('g-778', 'old@example.com', 'Old User');

        $location = $this->withSession([MobileSocialLogin::SESSION_KEY => [
            'mobile' => 1, 'redirect_uri' => 'thecashfox://auth', 'code_challenge' => null,
        ]])->get('/auth/google/callback?code=x&state=y')->headers->get('Location');
        parse_str(parse_url($location, PHP_URL_QUERY), $query);

        $this->postJson('/api/v1/auth/social/exchange', ['code' => $query['code'], 'currency' => 'PKR'])
            ->assertOk()
            ->assertJsonMissingPath('onboarding');

        $this->assertSame(0, $existing->businesses()->count());
    }

    public function test_web_google_new_user_gets_usd_starter_workspace(): void
    {
        $this->mockGoogleUser('g-779', 'webnew@example.com', 'Web New');

        $this->get('/auth/google/callback?code=x&state=y')->assertRedirect(route('dashboard'));

        $business = User::where('email', 'webnew@example.com')->firstOrFail()->businesses()->firstOrFail();
        $this->assertSame('USD', $business->currency);
        $this->assertSame('My Business', $business->name);
    }

    public function test_apple_new_user_gets_workspace_once_with_currency(): void
    {
        $this->fakeAppleKeys();

        $this->postJson('/api/v1/auth/apple', [
            'identityToken' => $this->appleToken(['email' => 'apple.new@example.com']),
            'currency'      => 'EUR',
        ])->assertOk()->assertJsonStructure(['onboarding' => ['businessId', 'bookId']]);

        // Second sign-in: not a new account → no onboarding, no duplicate.
        $this->postJson('/api/v1/auth/apple', [
            'identityToken' => $this->appleToken(['email' => null]),
            'currency'      => 'EUR',
        ])->assertOk()->assertJsonMissingPath('onboarding');

        $user = User::where('email', 'apple.new@example.com')->firstOrFail();
        $this->assertSame(1, $user->businesses()->count());
        $this->assertSame('EUR', $user->businesses()->first()->currency);
    }

    public function test_apple_invalid_currency_falls_back_to_usd(): void
    {
        $this->fakeAppleKeys();

        $this->postJson('/api/v1/auth/apple', [
            'identityToken' => $this->appleToken(['email' => 'apple.usd@example.com']),
            'currency'      => 'eu',
        ])->assertOk();

        $this->assertSame('USD', User::where('email', 'apple.usd@example.com')->firstOrFail()->businesses()->first()->currency);
    }

    private function mockGoogleRedirect(): void
    {
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('redirect')
            ->andReturn(redirect()->away('https://accounts.google.com/o/oauth2/auth?fake=1'));
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    private function mockGoogleUser(string $id, string $email, string $name): void
    {
        $socialUser = (new SocialiteUser())->map(['id' => $id, 'email' => $email, 'name' => $name]);
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andReturn($socialUser);
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    private function makeKeypair(): void
    {
        $res = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($res, $pem);
        $this->privateKey = $pem;
        $details = openssl_pkey_get_details($res);

        $b64 = fn (string $bin) => rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
        $this->jwk = [
            'kty' => 'RSA',
            'kid' => self::KID,
            'use' => 'sig',
            'alg' => 'RS256',
            'n'   => $b64($details['rsa']['n']),
            'e'   => $b64($details['rsa']['e']),
        ];
    }

    private function fakeAppleKeys(): void
    {
        $this->makeKeypair();
        Http::fake([AppleIdentityTokenVerifier::JWKS_URL => Http::response(['keys' => [$this->jwk]])]);
    }

    private function otherPrivateKey(): string
    {
        $res = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($res, $pem);

        return $pem;
    }

    private function appleToken(array $overrides = [], ?string $key = null): string
    {
        $claims = array_merge([
            'iss'            => 'https://appleid.apple.com',
            'aud'            => 'com.thecashfox.app',
            'exp'            => time() + 600,
            'iat'            => time(),
            'sub'            => 'apple-sub-1',
            'email'          => 'someone@example.com',
            'email_verified' => 'true',
        ], $overrides);

        $claims = array_filter($claims, fn ($v) => $v !== null);

        return JWT::encode($claims, $key ?? $this->privateKey, 'RS256', self::KID);
    }
}
