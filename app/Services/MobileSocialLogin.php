<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Glue for the browser-based mobile OAuth flow (Google):
 *
 *   app → GET /auth/google/mobile?redirect_uri=thecashfox://...[&code_challenge=...]
 *       → Google → /auth/google/callback
 *       → redirect to {redirect_uri}?code=<one-time code>
 *   app → POST /api/v1/auth/social/exchange {code[, codeVerifier]} → Sanctum token
 *
 * The one-time code lives in cache for 120s, keyed by its SHA-256 hash (the
 * raw code is never stored) and can be redeemed exactly once. An optional
 * PKCE-style S256 challenge binds the code to the app instance that started
 * the flow, so another app that intercepts the custom-scheme redirect can't
 * redeem it.
 */
class MobileSocialLogin
{
    public const SESSION_KEY = 'social_mobile';
    public const CODE_TTL    = 120;

    private const CODE_PREFIX = 'mobile_social_code:';
    private const USED_PREFIX = 'mobile_social_code_used:';

    /**
     * Allowlist: thecashfox:// always; exp:// and exps:// (Expo Go) only when
     * services.mobile.allow_expo_go is on. No fragments, whitespace, control
     * characters or backslashes.
     */
    public static function isAllowedRedirectUri(mixed $uri): bool
    {
        if (! is_string($uri) || $uri === '' || strlen($uri) > 2048) {
            return false;
        }

        if (preg_match('/[\x00-\x20\x7f#\\\\]/', $uri)) {
            return false;
        }

        if (str_starts_with($uri, 'thecashfox://')) {
            return true;
        }

        if (config('services.mobile.allow_expo_go')
            && (str_starts_with($uri, 'exp://') || str_starts_with($uri, 'exps://'))) {
            return true;
        }

        return false;
    }

    public static function isValidChallenge(mixed $challenge): bool
    {
        return is_string($challenge) && preg_match('/^[A-Za-z0-9_-]{43,128}$/', $challenge) === 1;
    }

    /**
     * Append query params to a (validated) redirect URI.
     */
    public static function appendQuery(string $uri, array $params): string
    {
        $query = http_build_query($params, '', '&', PHP_QUERY_RFC3986);

        if (! str_contains($uri, '?')) {
            return $uri . '?' . $query;
        }

        return (str_ends_with($uri, '?') || str_ends_with($uri, '&'))
            ? $uri . $query
            : $uri . '&' . $query;
    }

    public function issueCode(string $userId, ?string $codeChallenge = null): string
    {
        $code = Str::random(80);

        Cache::put(self::CODE_PREFIX . hash('sha256', $code), [
            'user_id'        => $userId,
            'code_challenge' => $codeChallenge,
        ], self::CODE_TTL);

        return $code;
    }

    /**
     * Redeem a code. Returns the user id, or null if the code is unknown,
     * expired, already used, or the PKCE verifier doesn't match.
     */
    public function consumeCode(string $code, ?string $codeVerifier = null): ?string
    {
        $hash = hash('sha256', $code);

        // Atomic "first redeemer wins" guard (SET NX on Redis) so two
        // concurrent requests can't both pass a get-then-forget.
        if (! Cache::add(self::USED_PREFIX . $hash, true, self::CODE_TTL * 2)) {
            return null;
        }

        $payload = Cache::pull(self::CODE_PREFIX . $hash);

        if (! is_array($payload) || empty($payload['user_id'])) {
            return null;
        }

        if (! empty($payload['code_challenge'])) {
            if (! is_string($codeVerifier) || $codeVerifier === '') {
                return null;
            }
            $computed = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');
            if (! hash_equals($payload['code_challenge'], $computed)) {
                return null;
            }
        }

        return (string) $payload['user_id'];
    }
}
