<?php

namespace Tests\Feature;

use App\Helpers\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Brand colours and fonts used to be written to public/brand/theme.css, which
 * Railway wipes on every redeploy — the admin set a palette, saw it work, and
 * found it reverted days later with nothing in the logs.
 */
class ThemeCssTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_serves_the_saved_theme_as_css(): void
    {
        Setting::set('theme.css', ":root {\n  --color-primary: 26 86 219;\n}\n");

        $this->get('/brand-theme.css')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/css; charset=UTF-8')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertSee('--color-primary: 26 86 219', escape: false);
    }

    public function test_it_serves_empty_css_rather_than_failing_when_unset(): void
    {
        $this->get('/brand-theme.css')->assertOk()->assertSee('');
    }

    public function test_layouts_link_the_theme_only_once_it_exists(): void
    {
        $user = \App\Models\User::factory()->create();

        $this->actingAs($user)->get('/dashboard')
            ->assertOk()
            ->assertDontSee('brand-theme.css');

        Setting::set('theme.css', ':root { --color-primary: 1 2 3; }');
        Setting::set('theme.version', '12345');

        $this->actingAs($user)->get('/dashboard')
            ->assertOk()
            ->assertSee('brand-theme.css?v=12345', escape: false);
    }

    public function test_saving_appearance_persists_css_to_the_database_not_disk(): void
    {
        $admin = \App\Models\User::factory()->create();
        $admin->is_admin = true;
        $admin->save();

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\Appearance::class)
            ->set('colorPrimary', '#ff0000')
            ->call('saveColours');

        $css = Setting::get('theme.css');

        $this->assertNotNull($css);
        $this->assertStringContainsString('--color-primary: 255 0 0', $css);
        $this->assertNotNull(Setting::get('theme.version'));
        $this->assertFileDoesNotExist(public_path('brand/theme.css'));
    }
}
