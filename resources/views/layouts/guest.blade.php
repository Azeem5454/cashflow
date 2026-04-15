<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        $appName = config('app.name', 'TheCashFox');
        $appUrl  = rtrim(config('app.url', 'https://cashflow.app'), '/');

        // Per-route title + description (conversion-focused, distinct per page,
        // under SEO-friendly length limits). Falls back to generic for any
        // route not explicitly listed.
        $pageMeta = match (true) {
            request()->routeIs('login')            => ['title' => 'Sign In | ' . $appName,                'desc' => 'Sign in to your ' . $appName . ' account to track business cash flow, scan receipts, and see live balances.'],
            request()->routeIs('register')         => ['title' => 'Create Your Free Account | ' . $appName, 'desc' => 'Start tracking your business cash flow in under 2 minutes. Free forever plan — no credit card required.'],
            request()->routeIs('password.request') => ['title' => 'Reset Password | ' . $appName,           'desc' => 'Reset your ' . $appName . ' account password.'],
            request()->routeIs('password.reset')   => ['title' => 'Choose a New Password | ' . $appName,    'desc' => 'Choose a new password for your ' . $appName . ' account.'],
            request()->routeIs('invitations.accept') => ['title' => 'Accept Invitation | ' . $appName,       'desc' => 'Accept an invitation to collaborate on ' . $appName . '.'],
            default                                => ['title' => $appName,                                 'desc' => config('app.tagline') ?: 'Track every transaction, scan receipts with AI, and get cash flow insights.'],
        };
        $pageTitle = $pageMeta['title'];
        $pageDesc  = $pageMeta['desc'];

        // Sensitive / tokenised routes shouldn't be indexed.
        $shouldNoindex = request()->routeIs('password.reset') || request()->routeIs('invitations.accept');

        $ogImage = $appUrl . '/brand/cashflow_logo.png';
        try {
            if (\App\Models\UploadedAsset::has('og-image')) {
                $ogImage = $appUrl . route('brand-asset', 'og-image', false) . '?v=' . \App\Models\UploadedAsset::cacheBuster('og-image');
            } elseif (\App\Models\UploadedAsset::has('logo-dark')) {
                $ogImage = $appUrl . route('brand-asset', 'logo-dark', false) . '?v=' . \App\Models\UploadedAsset::cacheBuster('logo-dark');
            }
        } catch (\Throwable $e) {
            // keep default
        }
    @endphp
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDesc }}">
    <meta name="theme-color" content="#0a0f1e">
    <meta name="author" content="{{ $appName }}">
    <meta name="format-detection" content="telephone=no">
    @if($shouldNoindex)
        <meta name="robots" content="noindex,nofollow">
    @else
        <meta name="robots" content="index,follow">
        <link rel="canonical" href="{{ url()->current() }}">
    @endif

    {{-- Open Graph (Facebook, LinkedIn, WhatsApp, iMessage, Slack, Discord) --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $appName }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDesc }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="{{ $appName }}">
    <meta property="og:locale" content="en_US">

    {{-- Twitter / X --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ $pageDesc }}">
    <meta name="twitter:image" content="{{ $ogImage }}">
    <meta name="twitter:image:alt" content="{{ $appName }}">

    @php $faviconSrc = \App\Models\UploadedAsset::has('favicon') ? route('brand-asset', 'favicon') . '?v=' . \App\Models\UploadedAsset::cacheBuster('favicon') : asset('favicon.png'); @endphp
    <link rel="icon" type="image/png" href="{{ $faviconSrc }}">
    <link rel="apple-touch-icon" href="{{ $faviconSrc }}">

    <!-- Brand Fonts — loaded from admin appearance settings -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="{{ \App\Helpers\Setting::get('google_fonts_url', 'https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400;12..96,700;12..96,800&family=Plus+Jakarta+Sans:wght@400;600;700&family=Outfit:wght@300;400;500&family=Geist+Mono:wght@400;500&display=swap') }}" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @if(file_exists(public_path('brand/theme.css')))
        <link rel="stylesheet" href="{{ asset('brand/theme.css') }}?v={{ filemtime(public_path('brand/theme.css')) }}">
    @endif

    {{-- Alpine.js — guest pages have no Livewire, so Alpine must be loaded standalone for password toggles etc. --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>

    {{-- Cloudflare Turnstile — only loaded when configured. --}}
    @if(config('services.turnstile.site_key'))
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endif

    {{-- Google Analytics 4 — only loaded when configured. --}}
    @if(config('services.analytics.ga4_id'))
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ config('services.analytics.ga4_id') }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', @json(config('services.analytics.ga4_id')));
        </script>
    @endif

    {{-- Auth pages are always dark to match the landing page. Dashboard theme toggle does not apply here. --}}
    <script>document.documentElement.classList.add('dark');</script>

    <style>
        /* ── Guest font declarations — use CSS variables from theme.css, fall back to defaults ── */
        .guest-display { font-family: var(--font-display, 'Bricolage Grotesque'), sans-serif !important; }
        .guest-body    { font-family: var(--font-body, 'Outfit'), sans-serif !important; }
        .guest-mono    { font-family: var(--font-mono, 'Geist Mono'), monospace !important; }

        .guest-dot-grid {
            background-image: radial-gradient(circle, rgba(26,86,219,0.13) 1px, transparent 1px);
            background-size: 26px 26px;
        }

        /* Eyebrow label — CSS-based dark mode */
        .guest-eyebrow { color: rgba(26,86,219,0.65); }
        html.dark .guest-eyebrow { color: rgba(147,197,253,0.75); }

        /* Stat card */
        .guest-stat-card {
            background: rgba(255,255,255,0.75);
            border: 1px solid rgba(0,0,0,0.08);
            border-radius: 14px;
            padding: 18px 12px;
            text-align: center;
        }
        html.dark .guest-stat-card {
            background: rgba(255,255,255,0.04);
            border-color: rgba(255,255,255,0.07);
        }

        /* Trust bullet icon bg */
        .guest-trust-icon {
            background: rgba(26,86,219,0.08);
            border-radius: 8px;
        }
        html.dark .guest-trust-icon {
            background: rgba(26,86,219,0.18);
        }

        /* Mock card — CSS-based dark mode (avoids arbitrary Tailwind value compilation issues) */
        .mock-card-shell {
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid rgba(0,0,0,0.08);
            box-shadow: 0 4px 24px rgba(0,0,0,0.06);
        }
        html.dark .mock-card-shell {
            border-color: rgba(255,255,255,0.07);
            box-shadow: 0 4px 24px rgba(26,86,219,0.08);
        }
        .mock-card-header {
            background: #f8fafc;
            border-bottom: 1px solid rgba(0,0,0,0.06);
            padding: 14px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        html.dark .mock-card-header {
            background: #0d1424;
            border-bottom-color: rgba(255,255,255,0.06);
        }
        .mock-card-icon {
            width:28px; height:28px; border-radius:8px;
            background: rgba(26,86,219,0.1);
            display:flex; align-items:center; justify-content:center;
        }
        html.dark .mock-card-icon { background: rgba(26,86,219,0.2); }

        .mock-balance-strip {
            background: #ffffff;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            border-bottom: 1px solid rgba(0,0,0,0.06);
        }
        html.dark .mock-balance-strip {
            background: #111827;
            border-bottom-color: rgba(255,255,255,0.06);
        }
        .mock-balance-col {
            padding: 12px 16px;
            border-right: 1px solid rgba(0,0,0,0.06);
        }
        .mock-balance-col:last-child { border-right: none; }
        html.dark .mock-balance-col { border-right-color: rgba(255,255,255,0.05); }

        .mock-entry-row {
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 20px;
            border-bottom: 1px solid rgba(0,0,0,0.04);
        }
        .mock-entry-row:last-child { border-bottom: none; }
        html.dark .mock-entry-row {
            background: #111827;
            border-bottom-color: rgba(255,255,255,0.03);
        }

        /* Testimonial card */
        .guest-testimonial {
            background: rgba(255,255,255,0.72);
            border: 1px solid rgba(0,0,0,0.07);
            border-radius: 12px;
            padding: 16px 20px;
            backdrop-filter: blur(8px);
        }
        html.dark .guest-testimonial {
            background: rgba(255,255,255,0.04);
            border-color: rgba(255,255,255,0.07);
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .anim-fade-up    { animation: fadeInUp 0.6s ease both; }
        .anim-fade-up-d1 { animation: fadeInUp 0.6s 0.08s ease both; }
        .anim-fade-up-d2 { animation: fadeInUp 0.6s 0.16s ease both; }
        .anim-fade-up-d3 { animation: fadeInUp 0.6s 0.24s ease both; }

        /* ── Auth inputs — light default, dark override ── */
        .auth-input {
            font-family: var(--font-body, 'Outfit'), sans-serif;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            color: #0f172a;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .auth-input::placeholder { color: #94a3b8; }
        .auth-input:focus {
            outline: none;
            border-color: #1a56db;
            box-shadow: 0 0 0 3px rgba(26,86,219,0.12);
        }
        html.dark .auth-input {
            background: #0d1424;
            border-color: rgba(255,255,255,0.08);
            color: #f8fafc;
        }
        html.dark .auth-input::placeholder { color: #64748b; }
        html.dark .auth-input:focus {
            border-color: #1a56db;
            box-shadow: 0 0 0 3px rgba(26,86,219,0.18);
        }
    </style>
</head>
<body class="font-body antialiased overflow-x-hidden min-h-screen bg-slate-50 dark:bg-navy">

<div class="min-h-screen flex">

    {{-- ===== LEFT BRAND PANEL ===== --}}
    <div class="hidden lg:flex lg:w-[52%] xl:w-[55%] relative flex-col overflow-hidden">

        {{-- Background --}}
        <div class="absolute inset-0 bg-gradient-to-br from-blue-50 via-indigo-50/60 to-sky-50 dark:from-navy dark:via-navy dark:to-navy"></div>
        <div class="guest-dot-grid absolute inset-0 opacity-[0.45] dark:opacity-[0.06]"></div>
        <div class="dark:hidden absolute top-0 right-0 w-[500px] h-[500px] rounded-full pointer-events-none"
             style="background:radial-gradient(circle,rgba(26,86,219,.06) 0%,transparent 70%)"></div>
        <div class="hidden dark:block absolute -top-40 -left-40 w-[600px] h-[600px] rounded-full pointer-events-none"
             style="background:radial-gradient(circle,rgba(26,86,219,.18) 0%,transparent 70%)"></div>
        <div class="hidden dark:block absolute bottom-0 right-0 w-[400px] h-[400px] rounded-full pointer-events-none"
             style="background:radial-gradient(circle,rgba(26,86,219,.1) 0%,transparent 70%)"></div>

        <div class="relative z-10 flex flex-col h-full p-10 xl:p-12">

            {{-- Logo (DB-backed; see App\Models\UploadedAsset) --}}
            @php
                $gHasDark  = \App\Models\UploadedAsset::has('logo-dark');
                $gHasLight = \App\Models\UploadedAsset::has('logo-light');
                $gDarkUrl  = $gHasDark  ? route('brand-asset', 'logo-dark')  . '?v=' . \App\Models\UploadedAsset::cacheBuster('logo-dark')  : null;
                $gLightUrl = $gHasLight ? route('brand-asset', 'logo-light') . '?v=' . \App\Models\UploadedAsset::cacheBuster('logo-light') : null;
            @endphp
            <a href="/" class="flex-shrink-0 flex items-center gap-3 mb-10">
                @if($gHasDark && $gHasLight)
                    <img src="{{ $gDarkUrl }}"  alt="{{ config('app.name', 'TheCashFox') }}" class="h-9 w-auto hidden dark:block">
                    <img src="{{ $gLightUrl }}" alt="{{ config('app.name', 'TheCashFox') }}" class="h-9 w-auto dark:hidden">
                @elseif($gHasDark)
                    <img src="{{ $gDarkUrl }}"  alt="{{ config('app.name', 'TheCashFox') }}" class="h-9 w-auto">
                @elseif($gHasLight)
                    <img src="{{ $gLightUrl }}" alt="{{ config('app.name', 'TheCashFox') }}" class="h-9 w-auto">
                @else
                    <img src="/brand/cashflow_logo.png" alt="{{ config('app.name', 'TheCashFox') }}"
                         class="h-9 w-9 rounded-xl">
                    <span class="guest-display font-extrabold text-2xl tracking-tight text-slate-900 dark:text-white">
                        {{ config('app.name', 'TheCashFox') }}
                    </span>
                @endif
            </a>

            {{-- ══════════════════════════════════
                 LOGIN — "Welcome back" panel
            ══════════════════════════════════ --}}
            @if(request()->routeIs('login'))

            <div class="flex-1 flex flex-col justify-center gap-8">

                <div>
                    <p class="guest-eyebrow guest-body text-xs font-semibold uppercase tracking-widest mb-4">
                        Good to have you back
                    </p>
                    <h2 class="guest-display font-extrabold text-4xl xl:text-[2.5rem] leading-[1.1] text-slate-900 dark:text-white mb-3">
                        Your books are<br>waiting for you.
                    </h2>
                    <p class="guest-body text-sm text-slate-500 dark:text-slate-400 leading-relaxed max-w-xs">
                        Pick up right where you left off. Every entry, every balance — exactly as you left it.
                    </p>
                </div>

                {{-- Stats grid --}}
                <div class="grid grid-cols-3 gap-3">
                    @foreach([
                        ['500+',  'Businesses',   'tracking cash'],
                        ['₨1T+',  'Transactions', 'recorded'],
                        ['4.9★',  'Average',      'user rating'],
                    ] as $stat)
                    <div class="guest-stat-card">
                        <p class="guest-mono text-lg font-semibold text-primary dark:text-blue-light mb-1">{{ $stat[0] }}</p>
                        <p class="guest-body text-[10px] leading-tight text-slate-500 dark:text-slate-500">{{ $stat[1] }}<br>{{ $stat[2] }}</p>
                    </div>
                    @endforeach
                </div>

                {{-- Trust bullets --}}
                <div class="space-y-3">
                    @foreach([
                        ['M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',    'End-to-end encrypted — your data stays yours'],
                        ['M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z', 'No card needed — free plan, forever'],
                        ['M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z', 'Your books sync across all devices instantly'],
                    ] as $item)
                    <div class="flex items-center gap-3">
                        <div class="guest-trust-icon flex-shrink-0 w-7 h-7 flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-primary dark:text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item[0] }}" />
                            </svg>
                        </div>
                        <span class="guest-body text-xs text-slate-600 dark:text-slate-400">{{ $item[1] }}</span>
                    </div>
                    @endforeach
                </div>

            </div>

            {{-- ══════════════════════════════════
                 SIGNUP / OTHER — Product preview
            ══════════════════════════════════ --}}
            @else

            <div class="flex-1 flex flex-col justify-center gap-7">

                <div>
                    <p class="guest-eyebrow guest-body text-xs font-semibold uppercase tracking-widest mb-4">
                        Free to start, easy to use
                    </p>
                    <h2 class="guest-display font-extrabold text-4xl xl:text-[2.5rem] leading-[1.1] text-slate-900 dark:text-white mb-3">
                        See exactly where<br>your money goes.
                    </h2>
                    <p class="guest-body text-sm text-slate-500 dark:text-slate-400 leading-relaxed max-w-sm">
                        A real cash book for your business — track income, expenses, and live balance with no spreadsheet chaos.
                    </p>
                </div>

                {{-- Mini dashboard preview card --}}
                <div class="mock-card-shell">

                    {{-- Header --}}
                    <div class="mock-card-header">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div class="mock-card-icon">
                                <svg style="width:14px;height:14px;color:#1a56db;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="guest-body text-xs font-semibold text-slate-700 dark:text-slate-200" style="line-height:1.2">March 2025</p>
                                <p class="guest-body text-slate-400 dark:text-slate-500" style="font-size:10px;">Main Business · Cash Book</p>
                            </div>
                        </div>
                        <span class="guest-body" style="font-size:10px;font-weight:600;padding:2px 8px;border-radius:999px;background:rgba(16,185,129,0.1);color:#059669;">
                            <span class="dark:hidden">● Live</span>
                            <span class="hidden dark:inline" style="color:#34d399;">● Live</span>
                        </span>
                    </div>

                    {{-- Balance strip --}}
                    <div class="mock-balance-strip">
                        <div class="mock-balance-col">
                            <p class="guest-body" style="font-size:9px;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;margin-bottom:4px;">Cash In</p>
                            <p class="guest-mono" style="font-size:12px;font-weight:600;color:#16a34a;">₨ 2,45,800</p>
                        </div>
                        <div class="mock-balance-col">
                            <p class="guest-body" style="font-size:9px;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;margin-bottom:4px;">Cash Out</p>
                            <p class="guest-mono" style="font-size:12px;font-weight:600;color:#dc2626;">₨ 98,500</p>
                        </div>
                        <div class="mock-balance-col">
                            <p class="guest-body" style="font-size:9px;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;margin-bottom:4px;">Balance</p>
                            <p class="guest-mono" style="font-size:12px;font-weight:600;color:#1a56db;">₨ 1,47,300</p>
                        </div>
                    </div>

                    {{-- Entry rows --}}
                    @foreach([
                        ['Client payment — Eveso Ltd',  '+₨ 45,000', '#16a34a', '#34d399'],
                        ['Office rent — March 2025',    '−₨ 15,000', '#dc2626', '#f87171'],
                        ['Supplier invoice #812',       '−₨ 5,000',  '#dc2626', '#f87171'],
                    ] as $row)
                    <div class="mock-entry-row">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:6px;height:6px;border-radius:50%;background:{{ $row[2] }};flex-shrink:0;" class="dark:hidden"></div>
                            <div style="width:6px;height:6px;border-radius:50%;background:{{ $row[3] }};flex-shrink:0;" class="hidden dark:block"></div>
                            <p class="guest-body text-slate-600 dark:text-slate-400" style="font-size:12px;">{{ $row[0] }}</p>
                        </div>
                        <p class="guest-mono dark:hidden" style="font-size:12px;font-weight:500;color:{{ $row[2] }};">{{ $row[1] }}</p>
                        <p class="guest-mono hidden dark:block" style="font-size:12px;font-weight:500;color:{{ $row[3] }};">{{ $row[1] }}</p>
                    </div>
                    @endforeach

                </div>{{-- /mock card --}}

            </div>

            @endif

            {{-- Shared testimonial --}}
            <div class="guest-testimonial mt-6">
                <div style="display:flex;align-items:flex-start;gap:12px;">
                    <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#1a56db,#3b82f6);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <span class="guest-body" style="font-size:11px;font-weight:700;color:#fff;">MW</span>
                    </div>
                    <div>
                        <p class="guest-body text-slate-600 dark:text-slate-300" style="font-size:13px;line-height:1.5;margin-bottom:6px;">
                            "Cleared 3 months of backlog in one afternoon. Finally a cash tracker that doesn't need an accountant to operate."
                        </p>
                        <span class="guest-body text-slate-400 dark:text-blue-light" style="font-size:11px;">Marcus Webb · Toronto, Canada</span>
                    </div>
                </div>
            </div>

        </div>{{-- /content wrapper --}}
    </div>{{-- /left panel --}}

    {{-- ===== RIGHT FORM PANEL ===== --}}
    <div class="w-full lg:w-[48%] xl:w-[45%] flex flex-col items-center justify-center
                px-6 py-12 lg:px-12 xl:px-16 relative
                bg-white dark:bg-dark">

        {{-- Mobile logo --}}
        <div class="lg:hidden mb-8 self-start">
            <a href="/" class="flex items-center gap-2.5">
                @if($gHasDark && $gHasLight)
                    <img src="{{ $gDarkUrl }}"  alt="{{ config('app.name', 'TheCashFox') }}" class="h-8 w-auto hidden dark:block">
                    <img src="{{ $gLightUrl }}" alt="{{ config('app.name', 'TheCashFox') }}" class="h-8 w-auto dark:hidden">
                @elseif($gHasDark)
                    <img src="{{ $gDarkUrl }}"  alt="{{ config('app.name', 'TheCashFox') }}" class="h-8 w-auto">
                @elseif($gHasLight)
                    <img src="{{ $gLightUrl }}" alt="{{ config('app.name', 'TheCashFox') }}" class="h-8 w-auto">
                @else
                    <img src="/brand/cashflow_logo.png" alt="{{ config('app.name', 'TheCashFox') }}"
                         class="h-8 w-8 rounded-lg">
                    <span class="guest-display font-extrabold text-xl tracking-tight text-slate-900 dark:text-white">
                        {{ config('app.name', 'TheCashFox') }}
                    </span>
                @endif
            </a>
        </div>

        <div class="w-full max-w-md">
            {{ $slot }}
        </div>
    </div>

</div>

</body>
</html>
