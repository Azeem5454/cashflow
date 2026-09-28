<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebManifestTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_serves_an_installable_manifest(): void
    {
        $res = $this->get('/site.webmanifest')->assertOk();

        $this->assertStringStartsWith('application/manifest+json', $res->headers->get('Content-Type'));

        $m = $res->json();

        $this->assertSame(config('app.name'), $m['name']);
        $this->assertSame('standalone', $m['display']);
        $this->assertSame('#0a0f1e', $m['theme_color']);
        $this->assertNotEmpty($m['icons']);
        $this->assertStringEndsWith('.png', parse_url($m['icons'][0]['src'], PHP_URL_PATH));
    }

    public function test_the_name_follows_the_admin_setting(): void
    {
        // The app name is editable at /admin/appearance, which is why this is
        // generated rather than a static file in public/.
        config(['app.name' => 'Renamed App']);

        $this->get('/site.webmanifest')->assertOk()->assertJsonPath('name', 'Renamed App');
    }

    public function test_every_layout_links_to_it(): void
    {
        $this->get('/')->assertOk()->assertSee('rel="manifest"', false);
        $this->get('/login')->assertOk()->assertSee('rel="manifest"', false);
        $this->get('/blog')->assertOk()->assertSee('rel="manifest"', false);
    }
}
