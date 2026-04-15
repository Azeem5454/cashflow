@php
    $appName = config('app.name', 'TheCashFox');
    $appUrl  = rtrim(config('app.url', 'https://thecashfox.com'), '/');

    $pageTitle       = $pageTitle       ?? 'Blog — ' . $appName;
    $pageDescription = $pageDescription ?? config('app.tagline', 'Insights, guides, and product updates.');
    $canonical       = $canonical       ?? url()->current();
    $ogType          = $ogType          ?? 'website';
    $ogImage         = $ogImage         ?? null;
    $articleMeta     = $articleMeta     ?? null;
    $postForSchema   = $postForSchema   ?? null;

    // OG image fallback: post featured image → uploaded og-image → uploaded logo-dark → default
    if (! $ogImage) {
        try {
            if (\App\Models\UploadedAsset::has('og-image')) {
                $ogImage = $appUrl . route('brand-asset', 'og-image', false) . '?v=' . \App\Models\UploadedAsset::cacheBuster('og-image');
            } elseif (\App\Models\UploadedAsset::has('logo-dark')) {
                $ogImage = $appUrl . route('brand-asset', 'logo-dark', false) . '?v=' . \App\Models\UploadedAsset::cacheBuster('logo-dark');
            } else {
                $ogImage = $appUrl . '/brand/cashflow_logo.png';
            }
        } catch (\Throwable $e) {
            $ogImage = $appUrl . '/brand/cashflow_logo.png';
        }
    }

    $fullTitle = str_contains($pageTitle, $appName) ? $pageTitle : ($pageTitle . ' — ' . $appName);
    $faviconSrc = \App\Models\UploadedAsset::has('favicon')
        ? route('brand-asset', 'favicon') . '?v=' . \App\Models\UploadedAsset::cacheBuster('favicon')
        : asset('favicon.png');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $fullTitle }}</title>
    <meta name="description" content="{{ $pageDescription }}">
    <meta name="theme-color" content="#0a0f1e">
    <meta name="author" content="{{ $appName }}">
    <meta name="format-detection" content="telephone=no">
    <meta name="robots" content="index,follow">
    <link rel="canonical" href="{{ $canonical }}">

    {{-- Open Graph --}}
    <meta property="og:type"             content="{{ $ogType }}">
    <meta property="og:site_name"        content="{{ $appName }}">
    <meta property="og:title"            content="{{ $pageTitle }}">
    <meta property="og:description"      content="{{ $pageDescription }}">
    <meta property="og:url"              content="{{ $canonical }}">
    <meta property="og:image"            content="{{ $ogImage }}">
    <meta property="og:image:width"      content="1200">
    <meta property="og:image:height"     content="630">
    <meta property="og:image:alt"        content="{{ $appName }}">
    <meta property="og:locale"           content="en_US">
    @if($articleMeta)
        @if($articleMeta['published_time'] ?? null)
            <meta property="article:published_time" content="{{ $articleMeta['published_time'] }}">
        @endif
        @if($articleMeta['modified_time'] ?? null)
            <meta property="article:modified_time" content="{{ $articleMeta['modified_time'] }}">
        @endif
        @if($articleMeta['author'] ?? null)
            <meta property="article:author" content="{{ $articleMeta['author'] }}">
        @endif
        @if($articleMeta['section'] ?? null)
            <meta property="article:section" content="{{ $articleMeta['section'] }}">
        @endif
    @endif

    {{-- Twitter --}}
    <meta name="twitter:card"        content="summary_large_image">
    <meta name="twitter:title"       content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ $pageDescription }}">
    <meta name="twitter:image"       content="{{ $ogImage }}">

    {{-- BlogPosting JSON-LD (only on single-post pages) --}}
    @if($postForSchema)
        <script type="application/ld+json">
        @verbatim{
            "@context": "https://schema.org",
            "@type": "BlogPosting",@endverbatim
            "mainEntityOfPage": @json($postForSchema->url()),
            "headline": @json($postForSchema->title),
            "description": @json($postForSchema->seoDescription()),
            "image": @json($postForSchema->featuredImageUrl() ?: $ogImage),
            "datePublished": @json(optional($postForSchema->published_at)->toIso8601String()),
            "dateModified": @json($postForSchema->updated_at?->toIso8601String()),
            @verbatim"author": {@endverbatim
                @verbatim"@type": "Person",@endverbatim
                "name": @json($postForSchema->author?->name ?: $appName)
            },
            @verbatim"publisher": {@endverbatim
                @verbatim"@type": "Organization",@endverbatim
                "name": @json($appName),
                @verbatim"logo": {@endverbatim
                    @verbatim"@type": "ImageObject",@endverbatim
                    "url": @json($ogImage)
                @verbatim}
            }
        }@endverbatim
        </script>
    @endif

    {{-- Feed discovery --}}
    <link rel="alternate" type="application/rss+xml" title="{{ $appName }} Blog" href="{{ route('blog.feed') }}">

    <link rel="icon" type="image/png" href="{{ $faviconSrc }}">
    <link rel="apple-touch-icon" href="{{ $faviconSrc }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@400;700;800;900&family=Outfit:wght@300;400;500;600;700&family=Geist+Mono:wght@400&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Google Analytics 4 --}}
    @if(config('services.analytics.ga4_id'))
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ config('services.analytics.ga4_id') }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', @json(config('services.analytics.ga4_id')));
        </script>
    @endif

    <script>document.documentElement.classList.add('dark');</script>

    <style>
        body { background:#0a0f1e; color:#e2e8f0; font-family:'Outfit',sans-serif; -webkit-font-smoothing:antialiased; }
        .fd { font-family:'Bricolage Grotesque', sans-serif; }
        .fm { font-family:'Geist Mono', monospace; }

        /* Article typography — optimized for reading */
        .prose-blog { font-size:17px; line-height:1.75; color:rgba(226,232,240,0.85); }
        .prose-blog > * + * { margin-top:1.1em; }
        .prose-blog h1, .prose-blog h2, .prose-blog h3, .prose-blog h4 {
            font-family:'Bricolage Grotesque', sans-serif;
            color:#f8fafc; font-weight:800; line-height:1.2; letter-spacing:-0.01em;
        }
        .prose-blog h2 { font-size:1.7rem; margin-top:2.2em; }
        .prose-blog h3 { font-size:1.3rem; margin-top:1.8em; }
        .prose-blog h4 { font-size:1.1rem; margin-top:1.6em; }
        .prose-blog p, .prose-blog li, .prose-blog blockquote { font-size:17px; }
        .prose-blog a { color:#93c5fd; text-decoration:underline; text-underline-offset:3px; text-decoration-thickness:1px; }
        .prose-blog a:hover { color:#f8fafc; text-decoration-thickness:2px; }
        .prose-blog strong { color:#f8fafc; font-weight:700; }
        .prose-blog code { background:rgba(59,130,246,0.12); border:1px solid rgba(59,130,246,0.2); padding:2px 6px; border-radius:4px; font-size:0.9em; font-family:'Geist Mono', monospace; }
        .prose-blog pre { background:#060c18; border:1px solid rgba(255,255,255,0.08); padding:20px 22px; border-radius:12px; overflow-x:auto; font-family:'Geist Mono', monospace; font-size:14px; line-height:1.6; }
        .prose-blog pre code { background:none; border:none; padding:0; }
        .prose-blog blockquote { border-left:3px solid #3b82f6; background:rgba(59,130,246,0.04); padding:14px 20px; border-radius:0 10px 10px 0; color:rgba(226,232,240,0.7); font-style:italic; }
        .prose-blog ul, .prose-blog ol { padding-left:1.6em; }
        .prose-blog ul li { list-style:disc; margin-bottom:0.4em; }
        .prose-blog ol li { list-style:decimal; margin-bottom:0.4em; }
        .prose-blog img { border-radius:12px; border:1px solid rgba(255,255,255,0.06); max-width:100%; height:auto; margin:1.5em 0; }
        .prose-blog hr { border:0; border-top:1px solid rgba(255,255,255,0.08); margin:2.5em 0; }
        .prose-blog table { width:100%; border-collapse:collapse; font-size:15px; }
        .prose-blog th, .prose-blog td { padding:10px 14px; border-bottom:1px solid rgba(255,255,255,0.08); text-align:left; }
        .prose-blog th { color:#f8fafc; font-weight:700; background:rgba(255,255,255,0.02); }
    </style>
</head>
<body>

{{-- ── Minimal nav bar ─────────────────────────────────────────────── --}}
<nav class="sticky top-0 z-50" style="background:rgba(7,11,22,0.92);backdrop-filter:blur(20px);border-bottom:1px solid rgba(255,255,255,0.06);">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-3.5 flex items-center justify-between gap-4">
        <a href="{{ url('/') }}" class="flex items-center gap-2.5">
            @if(\App\Models\UploadedAsset::has('logo-dark'))
                <img src="{{ route('brand-asset', 'logo-dark') }}?v={{ \App\Models\UploadedAsset::cacheBuster('logo-dark') }}"
                     alt="{{ $appName }}" class="h-7 w-auto object-contain">
            @else
                <div class="w-7 h-7 rounded-lg flex items-center justify-center" style="background:#1a56db">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none"><path d="M3 17l4-8 4 4 4-6 4 4" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </div>
                <span class="fd font-bold text-base text-white">{{ $appName }}</span>
            @endif
        </a>
        <div class="flex items-center gap-1 sm:gap-3">
            <a href="{{ route('blog.index') }}" class="text-xs sm:text-sm px-3 sm:px-4 py-2 rounded-full"
               style="color:{{ request()->routeIs('blog.*') ? '#f8fafc' : 'rgba(248,250,252,0.55)' }};background:{{ request()->routeIs('blog.*') ? 'rgba(59,130,246,0.12)' : 'transparent' }};transition:all .15s">Blog</a>
            <a href="{{ route('login') }}" class="text-xs sm:text-sm px-3 sm:px-4 py-2 rounded-full"
               style="color:rgba(248,250,252,0.55);transition:color .15s" onmouseover="this.style.color='#f8fafc'" onmouseout="this.style.color='rgba(248,250,252,0.55)'">Sign in</a>
            <a href="{{ route('register') }}" class="text-xs sm:text-sm px-4 sm:px-5 py-2 rounded-full font-semibold" style="background:#1a56db;color:#fff;transition:background .15s" onmouseover="this.style.background='#3b82f6'" onmouseout="this.style.background='#1a56db'">Get started</a>
        </div>
    </div>
</nav>

{{ $slot }}

{{-- ── Footer ──────────────────────────────────────────────────────── --}}
<footer class="px-4 sm:px-6 py-12 mt-20" style="background:#060c18;border-top:1px solid rgba(255,255,255,0.06);">
    <div class="max-w-6xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4">
        <p class="text-xs" style="color:rgba(255,255,255,0.42)">© {{ date('Y') }} {{ $appName }}</p>
        <div class="flex items-center gap-5">
            <a href="{{ route('blog.index') }}" class="text-xs" style="color:rgba(255,255,255,0.55)">Blog</a>
            <a href="{{ route('blog.feed') }}" class="text-xs" style="color:rgba(255,255,255,0.55)">RSS</a>
            <a href="{{ route('terms') }}"  class="text-xs" style="color:rgba(255,255,255,0.55)">Terms</a>
            <a href="{{ route('privacy') }}" class="text-xs" style="color:rgba(255,255,255,0.55)">Privacy</a>
        </div>
    </div>
</footer>

@livewireScripts
</body>
</html>
