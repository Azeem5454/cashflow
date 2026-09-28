<?php

namespace Tests\Feature;

use App\Support\CookieConsent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * GA4 was loading on every page with nothing asking first. In the EEA and UK
 * the rule is consent BEFORE the cookie, so a banner that appears after
 * gtag.js has already run is not compliance, it is decoration.
 */
class CookieConsentTest extends TestCase
{
    // Rendering the landing page reads the uploaded_assets table for the logo.
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.analytics.ga4_id' => 'G-TESTID']);
    }

    /** Named fromCountry, not from: TestCase::from() already exists. */
    private function fromCountry(string $country): array
    {
        return [CookieConsent::COUNTRY_HEADER => $country];
    }

    private function requestFrom(string $country): Request
    {
        return Request::create('/', 'GET', [], [], [], ['HTTP_CF_IPCOUNTRY' => $country]);
    }

    public function test_eu_uk_and_swiss_visitors_need_asking(): void
    {
        foreach (['DE', 'FR', 'IE', 'GB', 'CH', 'NO', 'is'] as $country) {
            $this->assertTrue(
                CookieConsent::isRequired($this->requestFrom($country)),
                "{$country} should require consent"
            );
        }
    }

    public function test_an_unknown_country_fails_towards_asking(): void
    {
        // No Cloudflare header at all, plus its two "we don't know" values.
        // Failing the other way sets cookies on someone who never agreed.
        $this->assertTrue(CookieConsent::isRequired(Request::create('/')));

        foreach (['XX', 'T1', ''] as $country) {
            $this->assertTrue(CookieConsent::isRequired($this->requestFrom($country)));
        }
    }

    public function test_the_rest_of_the_world_is_not_asked(): void
    {
        foreach (['US', 'PK', 'IN', 'AU', 'CA', 'NG'] as $country) {
            $this->assertFalse(
                CookieConsent::isRequired($this->requestFrom($country)),
                "{$country} should not be shown a banner"
            );
        }
    }

    public function test_a_european_visitor_gets_the_banner_and_no_tag_script(): void
    {
        $html = $this->withHeaders($this->fromCountry('DE'))->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('id="cookie-consent"', $html);

        // The loader script is on the page and holds the tag URL as a string,
        // which is fine — what must not exist is a <script src> that fetches
        // it, because the browser would run it before anyone answered.
        $this->assertDoesNotMatchRegularExpression(
            '#<script[^>]+src=["\']https://www\.googletagmanager\.com#',
            $html,
            'gtag.js must not be fetched before consent'
        );

        // And the loader must be waiting on the accept event rather than
        // starting itself.
        $this->assertStringContainsString('cashfox:cookie-consent-accepted', $html);
    }

    public function test_a_us_visitor_gets_no_banner(): void
    {
        $html = $this->withHeaders($this->fromCountry('US'))->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('id="cookie-consent"', $html);
        $this->assertStringContainsString('G-TESTID', $html);
    }

    public function test_nothing_renders_when_analytics_is_not_configured(): void
    {
        config(['services.analytics.ga4_id' => null]);

        $html = $this->withHeaders($this->fromCountry('DE'))->get('/')->assertOk()->getContent();

        // No analytics means no analytics cookie, so there is nothing to ask
        // about and the banner would be pure friction.
        $this->assertStringNotContainsString('id="cookie-consent"', $html);
        $this->assertStringNotContainsString('gtag', $html);
    }

    public function test_the_banner_offers_reject_as_plainly_as_accept(): void
    {
        $html = $this->withHeaders($this->fromCountry('GB'))->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('data-consent="rejected"', $html);
        $this->assertStringContainsString('data-consent="accepted"', $html);
        $this->assertStringContainsString(route('privacy'), $html);
    }
}
