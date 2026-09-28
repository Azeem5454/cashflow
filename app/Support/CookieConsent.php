<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Decides whether a visitor has to be asked before analytics cookies are set.
 *
 * The EU/UK rule is consent BEFORE the cookie, not a notice afterwards. The
 * US mostly is not opt-in, so asking everyone would add friction on a signup
 * flow for no legal gain.
 *
 * Cloudflare sits in front of this app and stamps every request with the
 * visitor's country, so the question can be answered without geolocating
 * anyone ourselves and without a third-party service.
 *
 * If that header is missing — direct origin hit, Cloudflare geolocation off,
 * local development — we ask. Failing towards consent costs a banner; failing
 * the other way means setting cookies on someone who never agreed.
 */
final class CookieConsent
{
    /** Cloudflare's country header. "XX" means unknown, "T1" means Tor. */
    public const COUNTRY_HEADER = 'CF-IPCountry';

    /**
     * EEA + UK + Switzerland: everywhere GDPR-style prior consent applies.
     */
    private const CONSENT_REQUIRED = [
        // EU
        'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE',
        'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL', 'PL', 'PT',
        'RO', 'SK', 'SI', 'ES', 'SE',
        // Non-EU EEA
        'IS', 'LI', 'NO',
        // UK (UK GDPR + PECR) and Switzerland (revFADP)
        'GB', 'CH',
    ];

    public static function isRequired(?Request $request = null): bool
    {
        $request ??= request();

        $country = strtoupper(trim((string) $request->header(self::COUNTRY_HEADER)));

        // No header, or Cloudflare could not place them: ask.
        if ($country === '' || $country === 'XX' || $country === 'T1') {
            return true;
        }

        return in_array($country, self::CONSENT_REQUIRED, true);
    }
}
