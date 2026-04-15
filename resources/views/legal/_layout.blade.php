{{--
    Reusable legal-page layout — standalone (no Livewire), matches landing-v3 aesthetic.
    Usage: @extends('legal._layout') / @section('title', '...') / @section('updated', 'Apr 2026') / @section('content', '...')
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    @php
        $appName   = config('app.name', 'TheCashFox');
        $appUrl    = rtrim(config('app.url', 'https://cashflow.app'), '/');
        $pageTitle = trim(View::yieldContent('title')) ?: 'Legal';
        $pageDesc  = trim(View::yieldContent('description')) ?: ('Legal information for ' . $appName);
        $fullTitle = $pageTitle . ' — ' . $appName;

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
        $faviconSrc = \App\Models\UploadedAsset::has('favicon')
            ? route('brand-asset', 'favicon') . '?v=' . \App\Models\UploadedAsset::cacheBuster('favicon')
            : asset('favicon.png');
    @endphp
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $fullTitle }}</title>
    <meta name="description" content="{{ $pageDesc }}">
    <meta name="robots" content="index, follow">
    <meta name="theme-color" content="#0a0f1e">
    <meta name="author" content="{{ $appName }}">
    <meta name="format-detection" content="telephone=no">
    <link rel="canonical" href="{{ url()->current() }}">
    <link rel="icon" type="image/png" href="{{ $faviconSrc }}">
    <link rel="apple-touch-icon" href="{{ $faviconSrc }}">

    {{-- Open Graph --}}
    <meta property="og:type" content="article">
    <meta property="og:site_name" content="{{ $appName }}">
    <meta property="og:title" content="{{ $fullTitle }}">
    <meta property="og:description" content="{{ $pageDesc }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="{{ $appName }}">
    <meta property="og:locale" content="en_US">

    {{-- Twitter --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $fullTitle }}">
    <meta name="twitter:description" content="{{ $pageDesc }}">
    <meta name="twitter:image" content="{{ $ogImage }}">

    {{-- BreadcrumbList schema (Home › Current) --}}
    <script type="application/ld+json">
    @verbatim{
        "@context": "https://schema.org",
        "@type": "BreadcrumbList",
        "itemListElement": [
            {"@type": "ListItem", "position": 1, "name": @endverbatim@json($appName)@verbatim, "item": @endverbatim@json($appUrl . '/')@verbatim},
            {"@type": "ListItem", "position": 2, "name": @endverbatim@json($pageTitle)@verbatim, "item": @endverbatim@json(url()->current())@verbatim}
        ]
    }@endverbatim
    </script>

    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@700;800&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        html{scroll-behavior:smooth}
        body{font-family:'Outfit',sans-serif;background:#0a0f1e;color:#e2e8f0;line-height:1.65;-webkit-font-smoothing:antialiased}
        .fd{font-family:'Bricolage Grotesque',sans-serif}
        a{color:#93c5fd;text-decoration:none;transition:color .15s}
        a:hover{color:#f8fafc;text-decoration:underline}
        .wrap{max-width:780px;margin:0 auto;padding:64px 24px 96px}
        .back{display:inline-flex;align-items:center;gap:8px;font-size:13px;color:rgba(255,255,255,.55);margin-bottom:32px}
        .back:hover{color:#f8fafc}
        h1{font-family:'Bricolage Grotesque',sans-serif;font-weight:800;font-size:clamp(2rem,4.5vw,2.75rem);line-height:1.15;color:#f8fafc;letter-spacing:-.01em;margin-bottom:12px}
        .meta{font-size:13px;color:rgba(255,255,255,.45);margin-bottom:48px;padding-bottom:24px;border-bottom:1px solid rgba(255,255,255,.08)}
        h2{font-family:'Bricolage Grotesque',sans-serif;font-weight:700;font-size:1.35rem;color:#f8fafc;margin-top:48px;margin-bottom:14px;letter-spacing:-.005em}
        h3{font-size:1rem;font-weight:600;color:#e2e8f0;margin-top:28px;margin-bottom:10px}
        p{font-size:15px;color:rgba(226,232,240,.78);margin-bottom:16px}
        ul{margin:0 0 18px 22px}
        li{font-size:15px;color:rgba(226,232,240,.78);margin-bottom:8px}
        strong{color:#f8fafc;font-weight:600}
        .callout{margin:28px 0;padding:18px 20px;border:1px solid rgba(26,86,219,.25);background:rgba(26,86,219,.08);border-radius:12px;font-size:14px;color:rgba(219,234,254,.9)}
        .footer{max-width:780px;margin:0 auto;padding:32px 24px;border-top:1px solid rgba(255,255,255,.08);font-size:13px;color:rgba(255,255,255,.45);display:flex;flex-wrap:wrap;gap:24px;justify-content:space-between}
        .footer a{color:rgba(255,255,255,.55)}
    </style>
</head>
<body>
    <div class="wrap">
        <a href="{{ url('/') }}" class="back">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            Back to {{ config('app.name', 'TheCashFox') }}
        </a>
        <h1>@yield('title')</h1>
        <p class="meta">Last updated: @yield('updated', date('F Y'))</p>

        @yield('content')
    </div>

    <div class="footer">
        <span>&copy; {{ date('Y') }} {{ config('app.name', 'TheCashFox') }}</span>
        <span>
            <a href="{{ route('terms') }}">Terms</a> &nbsp;·&nbsp;
            <a href="{{ route('privacy') }}">Privacy</a> &nbsp;·&nbsp;
            <a href="{{ url('/') }}">Home</a>
        </span>
    </div>
</body>
</html>
