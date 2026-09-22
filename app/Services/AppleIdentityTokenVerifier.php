<?php

namespace App\Services;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Verifies a Sign in with Apple identity token (JWT, RS256) against Apple's
 * published JWKS.
 *
 * Checks: signature (kid → Apple key), alg = RS256, iss, aud (bundle id; the
 * Expo Go client id only when services.mobile.allow_expo_go is on), exp, sub.
 */
class AppleIdentityTokenVerifier
{
    public const JWKS_URL  = 'https://appleid.apple.com/auth/keys';
    public const ISSUER    = 'https://appleid.apple.com';
    public const CACHE_KEY = 'apple_jwks';
    private const CACHE_TTL = 86400;
    private const EXPO_GO_AUDIENCE = 'host.exp.Exponent';

    /**
     * @return object decoded claims
     * @throws AppleTokenInvalid when the token is not acceptable
     * @throws RuntimeException  when Apple's keys can't be fetched
     */
    public function verify(string $jwt): object
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            throw new AppleTokenInvalid('malformed');
        }

        $header = json_decode(JWT::urlsafeB64Decode($parts[0]), true);
        if (! is_array($header) || ($header['alg'] ?? null) !== 'RS256' || ! is_string($header['kid'] ?? null)) {
            throw new AppleTokenInvalid('bad header');
        }
        $kid = $header['kid'];

        $jwks = $this->keys();
        if (! $this->hasKid($jwks, $kid) && Cache::add('apple_jwks_refresh_lock', true, 60)) {
            // Apple may have rotated keys — refetch at most once a minute.
            $jwks = $this->keys(refresh: true);
        }
        if (! $this->hasKid($jwks, $kid)) {
            throw new AppleTokenInvalid('unknown kid');
        }

        $previousLeeway = JWT::$leeway;
        JWT::$leeway = 60; // tolerate small clock skew between us and Apple
        try {
            $claims = JWT::decode($jwt, JWK::parseKeySet($jwks, 'RS256'));
        } catch (\Throwable $e) {
            throw new AppleTokenInvalid('decode: ' . class_basename($e));
        } finally {
            JWT::$leeway = $previousLeeway;
        }

        if (($claims->iss ?? null) !== self::ISSUER) {
            throw new AppleTokenInvalid('iss');
        }

        $aud = $claims->aud ?? null;
        if (! is_string($aud) || ! in_array($aud, $this->allowedAudiences(), true)) {
            throw new AppleTokenInvalid('aud');
        }

        if (! isset($claims->exp) || ! is_numeric($claims->exp)) {
            throw new AppleTokenInvalid('exp');
        }

        if (! is_string($claims->sub ?? null) || $claims->sub === '' || strlen($claims->sub) > 191) {
            throw new AppleTokenInvalid('sub');
        }

        return $claims;
    }

    public function allowedAudiences(): array
    {
        $audiences = [(string) config('services.apple.bundle_id', 'com.thecashfox.app')];

        if (config('services.mobile.allow_expo_go')) {
            $audiences[] = self::EXPO_GO_AUDIENCE;
        }

        return $audiences;
    }

    private function keys(bool $refresh = false): array
    {
        if (! $refresh) {
            $cached = Cache::get(self::CACHE_KEY);
            if (is_array($cached) && ! empty($cached['keys'])) {
                return $cached;
            }
        }

        $response = Http::timeout(5)->acceptJson()->get(self::JWKS_URL);
        $jwks = $response->successful() ? $response->json() : null;

        if (! is_array($jwks) || empty($jwks['keys']) || ! is_array($jwks['keys'])) {
            throw new RuntimeException('Unable to fetch Apple JWKS (HTTP ' . $response->status() . ')');
        }

        Cache::put(self::CACHE_KEY, $jwks, self::CACHE_TTL);

        return $jwks;
    }

    private function hasKid(array $jwks, string $kid): bool
    {
        foreach ($jwks['keys'] as $key) {
            if (is_array($key) && ($key['kid'] ?? null) === $kid) {
                return true;
            }
        }

        return false;
    }
}
