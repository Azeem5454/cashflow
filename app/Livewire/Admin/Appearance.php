<?php

namespace App\Livewire\Admin;

use App\Helpers\Setting;
use Livewire\Component;
use Livewire\WithFileUploads;

class Appearance extends Component
{
    use WithFileUploads;

    public string $activeTab = 'general';

    // ── General ──
    public string $appName = '';
    public string $tagline = '';
    public string $supportEmail = '';
    public string $appUrl = '';
    public ?string $generalSuccess = null;

    // ── Logos ──
    public $logoDark;
    public $logoLight;
    public $favicon;
    public ?string $logoSuccess = null;

    // ── Colours ──
    public string $colorNavy = '#0a0f1e';
    public string $colorDark = '#111827';
    public string $colorPrimary = '#1a56db';
    public string $colorAccent = '#3b82f6';
    public string $colorBlueLight = '#93c5fd';
    public string $colorBlueXlight = '#dbeafe';
    public ?string $colourSuccess = null;

    // ── Typography ──
    public string $fontDisplay = 'Bricolage Grotesque';
    public string $fontHeading = 'Plus Jakarta Sans';
    public string $fontBody = 'Outfit';
    public string $fontMono = 'Geist Mono';
    public ?string $typographySuccess = null;

    // ── Landing Page Copy ──
    public string $heroHeadline = '';
    public string $heroSubheadline = '';
    public string $ctaText = '';
    public string $footerTagline = '';
    public ?string $copySuccess = null;

    // ── Email Sender ──
    public string $mailFromName = '';
    public string $mailFromAddress = '';
    public ?string $emailSuccess = null;

    // ── Font options ──
    public array $fontOptions = [
        'Bricolage Grotesque',
        'Plus Jakarta Sans',
        'Outfit',
        'Geist Mono',
        'Inter',
        'Poppins',
        'Manrope',
        'Space Grotesk',
        'DM Sans',
        'Sora',
        'Albert Sans',
        'Figtree',
        'JetBrains Mono',
        'Fira Code',
        'Source Code Pro',
    ];

    // ── Default colour values ──
    protected array $defaultColours = [
        'colorNavy'       => '#0a0f1e',
        'colorDark'       => '#111827',
        'colorPrimary'    => '#1a56db',
        'colorAccent'     => '#3b82f6',
        'colorBlueLight'  => '#93c5fd',
        'colorBlueXlight' => '#dbeafe',
    ];

    public function mount(): void
    {
        // General
        $this->appName      = Setting::get('app.name', config('app.name', 'CashFlow'));
        $this->tagline      = Setting::get('app.tagline', 'Real-Time Cash Flow Tracking for Your Business');
        $this->supportEmail = Setting::get('app.support_email', '');
        $this->appUrl       = Setting::get('app.url', config('app.url', ''));

        // Colours
        $this->colorNavy       = Setting::get('color.navy', '#0a0f1e');
        $this->colorDark       = Setting::get('color.dark', '#111827');
        $this->colorPrimary    = Setting::get('color.primary', '#1a56db');
        $this->colorAccent     = Setting::get('color.accent', '#3b82f6');
        $this->colorBlueLight  = Setting::get('color.blue_light', '#93c5fd');
        $this->colorBlueXlight = Setting::get('color.blue_xlight', '#dbeafe');

        // Typography
        $this->fontDisplay = Setting::get('font.display', 'Bricolage Grotesque');
        $this->fontHeading = Setting::get('font.heading', 'Plus Jakarta Sans');
        $this->fontBody    = Setting::get('font.body', 'Outfit');
        $this->fontMono    = Setting::get('font.mono', 'Geist Mono');

        // Landing copy
        $this->heroHeadline    = Setting::get('landing.hero_headline', '');
        $this->heroSubheadline = Setting::get('landing.hero_subheadline', '');
        $this->ctaText         = Setting::get('landing.cta_text', '');
        $this->footerTagline   = Setting::get('landing.footer_tagline', '');

        // Email sender
        $this->mailFromName    = Setting::get('mail.from_name', config('mail.from.name', 'CashFlow'));
        $this->mailFromAddress = Setting::get('mail.from_address', config('mail.from.address', ''));
    }

    // ── General Tab ──
    public function saveGeneral(): void
    {
        $this->validate([
            'appName'      => ['required', 'string', 'max:100'],
            'tagline'      => ['nullable', 'string', 'max:200'],
            'supportEmail' => ['nullable', 'email', 'max:255'],
            'appUrl'       => ['nullable', 'url', 'max:255'],
        ]);

        Setting::set('app.name', trim($this->appName));
        Setting::set('app.tagline', trim($this->tagline));
        Setting::set('app.support_email', trim($this->supportEmail));
        Setting::set('app.url', trim($this->appUrl));

        $this->generalSuccess = 'General settings saved.';
    }

    // ── Logo Uploads ──
    public function uploadLogoDark(): void
    {
        $this->validate(['logoDark' => ['required', 'image', 'mimes:png', 'max:1024']]);
        $this->logoDark->storeAs('', 'logo-dark.png', 'brand');
        $this->logoDark = null;
        $this->logoSuccess = 'Dark logo uploaded.';
    }

    public function uploadLogoLight(): void
    {
        $this->validate(['logoLight' => ['required', 'image', 'mimes:png', 'max:1024']]);
        $this->logoLight->storeAs('', 'logo-light.png', 'brand');
        $this->logoLight = null;
        $this->logoSuccess = 'Light logo uploaded.';
    }

    public function uploadFavicon(): void
    {
        $this->validate(['favicon' => ['required', 'image', 'mimes:png', 'max:1024']]);
        $this->favicon->storeAs('', 'favicon.png', 'brand');

        // Also copy to public root for browser tab
        copy(public_path('brand/favicon.png'), public_path('favicon.png'));

        $this->favicon = null;
        $this->logoSuccess = 'Favicon uploaded.';
    }

    public function revertLogo(string $type): void
    {
        $map = [
            'dark'    => 'brand/logo-dark.png',
            'light'   => 'brand/logo-light.png',
            'favicon' => 'brand/favicon.png',
        ];

        $path = public_path($map[$type] ?? '');
        if (file_exists($path)) {
            unlink($path);
        }

        $this->logoSuccess = ucfirst($type) . ' logo reverted to default.';
    }

    // ── Colours ──
    public function saveColours(): void
    {
        $this->validate([
            'colorNavy'       => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'colorDark'       => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'colorPrimary'    => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'colorAccent'     => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'colorBlueLight'  => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'colorBlueXlight' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        Setting::set('color.navy', $this->colorNavy);
        Setting::set('color.dark', $this->colorDark);
        Setting::set('color.primary', $this->colorPrimary);
        Setting::set('color.accent', $this->colorAccent);
        Setting::set('color.blue_light', $this->colorBlueLight);
        Setting::set('color.blue_xlight', $this->colorBlueXlight);

        $this->generateThemeCss();
        $this->colourSuccess = 'Colour palette saved. Reload to see changes.';
    }

    public function resetColours(): void
    {
        foreach ($this->defaultColours as $prop => $hex) {
            $this->{$prop} = $hex;
        }

        $this->saveColours();
        $this->colourSuccess = 'Colours reset to defaults.';
    }

    // ── Typography ──
    public function saveTypography(): void
    {
        $this->validate([
            'fontDisplay' => ['required', 'string', 'max:100'],
            'fontHeading' => ['required', 'string', 'max:100'],
            'fontBody'    => ['required', 'string', 'max:100'],
            'fontMono'    => ['required', 'string', 'max:100'],
        ]);

        Setting::set('font.display', $this->fontDisplay);
        Setting::set('font.heading', $this->fontHeading);
        Setting::set('font.body', $this->fontBody);
        Setting::set('font.mono', $this->fontMono);

        $this->generateThemeCss();
        $this->generateGoogleFontsUrl();
        $this->typographySuccess = 'Typography saved. Reload to see changes.';
    }

    // ── Landing Page Copy ──
    public function saveCopy(): void
    {
        $this->validate([
            'heroHeadline'    => ['nullable', 'string', 'max:200'],
            'heroSubheadline' => ['nullable', 'string', 'max:500'],
            'ctaText'         => ['nullable', 'string', 'max:100'],
            'footerTagline'   => ['nullable', 'string', 'max:200'],
        ]);

        Setting::set('landing.hero_headline', trim($this->heroHeadline));
        Setting::set('landing.hero_subheadline', trim($this->heroSubheadline));
        Setting::set('landing.cta_text', trim($this->ctaText));
        Setting::set('landing.footer_tagline', trim($this->footerTagline));

        $this->copySuccess = 'Landing page copy saved.';
    }

    // ── Email Sender ──
    public function saveEmail(): void
    {
        $this->validate([
            'mailFromName'    => ['required', 'string', 'max:100'],
            'mailFromAddress' => ['required', 'email', 'max:255'],
        ]);

        Setting::set('mail.from_name', trim($this->mailFromName));
        Setting::set('mail.from_address', trim($this->mailFromAddress));

        $this->emailSuccess = 'Email sender settings saved.';
    }

    // ── Generate theme.css ──
    protected function generateThemeCss(): void
    {
        $css = ":root {\n";
        $css .= "  --color-navy: {$this->hexToRgbChannels($this->colorNavy)};\n";
        $css .= "  --color-dark: {$this->hexToRgbChannels($this->colorDark)};\n";
        $css .= "  --color-primary: {$this->hexToRgbChannels($this->colorPrimary)};\n";
        $css .= "  --color-accent: {$this->hexToRgbChannels($this->colorAccent)};\n";
        $css .= "  --color-blue-light: {$this->hexToRgbChannels($this->colorBlueLight)};\n";
        $css .= "  --color-blue-xlight: {$this->hexToRgbChannels($this->colorBlueXlight)};\n";
        $css .= "  --font-display: '{$this->fontDisplay}';\n";
        $css .= "  --font-heading: '{$this->fontHeading}';\n";
        $css .= "  --font-body: '{$this->fontBody}';\n";
        $css .= "  --font-mono: '{$this->fontMono}';\n";
        $css .= "}\n";

        $brandDir = public_path('brand');
        if (! is_dir($brandDir)) {
            mkdir($brandDir, 0755, true);
        }

        file_put_contents($brandDir . '/theme.css', $css);
    }

    protected function hexToRgbChannels(string $hex): string
    {
        $hex = ltrim($hex, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        return "{$r} {$g} {$b}";
    }

    protected function generateGoogleFontsUrl(): void
    {
        $fonts = collect([
            $this->fontDisplay,
            $this->fontHeading,
            $this->fontBody,
            $this->fontMono,
        ])->unique()->map(function ($font) {
            $encoded = urlencode($font);
            // Mono fonts don't need weight variants
            if (str_contains(strtolower($font), 'mono') || str_contains(strtolower($font), 'code')) {
                return "family={$encoded}:wght@400";
            }

            return "family={$encoded}:wght@300;400;500;600;700;800";
        })->implode('&');

        $url = "https://fonts.googleapis.com/css2?{$fonts}&display=swap";

        Setting::set('google_fonts_url', $url);
    }

    public function render()
    {
        return view('livewire.admin.appearance')
            ->layout('layouts.admin');
    }
}
