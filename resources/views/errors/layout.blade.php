<!DOCTYPE html>
<html lang="en">
<head>
    @php
        $faviconSrc = \App\Models\UploadedAsset::has('favicon')
            ? route('brand-asset', 'favicon') . '?v=' . \App\Models\UploadedAsset::cacheBuster('favicon')
            : asset('favicon.png');
    @endphp
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0a0f1e">
    <meta name="robots" content="noindex,nofollow">
    <title>@yield('title') — {{ config('app.name', 'TheCashFox') }}</title>
    <link rel="icon" type="image/png" href="{{ $faviconSrc }}">
    <link rel="apple-touch-icon" href="{{ $faviconSrc }}">
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@400;700;800&family=Outfit:wght@300;400;500;600&family=Geist+Mono:wght@400&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --navy: #0a0f1e;
            --black: #060c18;
            --dark: #111827;
            --primary: #1a56db;
            --accent: #3b82f6;
            --blue-light: #93c5fd;
            --slate-800: #1e293b;
            --slate-600: #475569;
            --slate-400: #94a3b8;
            --slate-300: #cbd5e1;
        }

        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: var(--navy);
            color: #e2e8f0;
            font-family: 'Outfit', sans-serif;
            overflow: hidden;
            position: relative;
            -webkit-font-smoothing: antialiased;
        }

        /* Subtle dot grid for depth */
        body::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: radial-gradient(circle, rgba(26, 86, 219, 0.09) 1px, transparent 1px);
            background-size: 32px 32px;
            pointer-events: none;
            z-index: 0;
            mask-image: radial-gradient(ellipse at center, rgba(0,0,0,1) 0%, rgba(0,0,0,0) 70%);
            -webkit-mask-image: radial-gradient(ellipse at center, rgba(0,0,0,1) 0%, rgba(0,0,0,0) 70%);
        }

        /* Soft glow orbs */
        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(90px);
            opacity: 0.18;
            animation: float 22s ease-in-out infinite;
            pointer-events: none;
            z-index: 0;
        }
        .orb-1 {
            width: 560px; height: 560px;
            background: var(--primary);
            top: -180px; left: -140px;
            animation-delay: 0s;
        }
        .orb-2 {
            width: 420px; height: 420px;
            background: var(--accent);
            bottom: -120px; right: -100px;
            animation-delay: -8s;
        }
        .orb-3 {
            width: 320px; height: 320px;
            background: #8b5cf6;
            top: 38%; left: 58%;
            animation-delay: -15s;
            opacity: 0.12;
        }
        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(34px, -28px) scale(1.04); }
            66% { transform: translate(-24px, 22px) scale(0.97); }
        }

        /* Brand strip at top */
        .brand-strip {
            position: absolute;
            top: 32px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 2;
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            opacity: 0.55;
            transition: opacity 0.2s ease;
        }
        .brand-strip:hover { opacity: 1; }
        .brand-strip img { height: 24px; width: auto; }
        .brand-strip .brand-text {
            font-family: 'Bricolage Grotesque', sans-serif;
            font-weight: 700;
            font-size: 14px;
            color: #f8fafc;
            letter-spacing: -0.01em;
        }
        .brand-strip .brand-mark {
            width: 24px; height: 24px;
            border-radius: 6px;
            background: var(--primary);
            display: flex; align-items: center; justify-content: center;
        }

        /* Content */
        .error-container {
            position: relative;
            z-index: 2;
            text-align: center;
            padding: 2rem;
            max-width: 520px;
            animation: fadeUp 0.7s cubic-bezier(.22,.68,0,1.1) both;
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(16px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Error code — giant gradient number */
        .error-code {
            font-family: 'Bricolage Grotesque', sans-serif;
            font-weight: 900;
            font-size: clamp(96px, 22vw, 180px);
            line-height: 0.92;
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 45%, #8b5cf6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: shimmer 7s ease-in-out infinite;
            background-size: 200% 200%;
            margin-bottom: 8px;
            letter-spacing: -0.03em;
        }
        @keyframes shimmer {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        .error-title {
            font-family: 'Bricolage Grotesque', sans-serif;
            font-weight: 800;
            font-size: clamp(22px, 4.2vw, 30px);
            margin-bottom: 12px;
            color: #ffffff;
            letter-spacing: -0.01em;
        }

        .error-description {
            font-size: 15px;
            line-height: 1.65;
            color: var(--slate-400);
            margin-bottom: 32px;
            max-width: 440px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Animated icon */
        .error-icon {
            width: 72px;
            height: 72px;
            margin: 0 auto 24px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(26, 86, 219, 0.12);
            border: 1px solid rgba(59, 130, 246, 0.18);
            animation: pulse-icon 3.2s ease-in-out infinite;
        }
        @keyframes pulse-icon {
            0%, 100% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(26, 86, 219, 0.22);
                border-color: rgba(59, 130, 246, 0.18);
            }
            50% {
                transform: scale(1.04);
                box-shadow: 0 0 0 14px rgba(26, 86, 219, 0);
                border-color: rgba(59, 130, 246, 0.32);
            }
        }
        .error-icon svg {
            width: 34px; height: 34px;
            color: var(--accent);
        }

        /* Helpful links block — shown where layout supplies it */
        .quick-links {
            margin: 24px auto 0;
            padding-top: 24px;
            border-top: 1px solid rgba(255,255,255,0.06);
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: center;
        }
        .quick-links .ql {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            font-size: 13px;
            font-weight: 500;
            color: var(--slate-300);
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 999px;
            text-decoration: none;
            transition: all 0.18s ease;
        }
        .quick-links .ql:hover {
            color: #ffffff;
            background: rgba(26, 86, 219, 0.12);
            border-color: rgba(59, 130, 246, 0.3);
            transform: translateY(-1px);
        }

        /* Buttons */
        .btn-row {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 22px;
            font-size: 14px;
            font-weight: 600;
            font-family: 'Outfit', sans-serif;
            border-radius: 999px;
            text-decoration: none;
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
        }
        .btn-primary {
            background: var(--primary);
            color: #ffffff;
            box-shadow: 0 6px 22px rgba(26, 86, 219, 0.35);
        }
        .btn-primary:hover {
            background: var(--accent);
            box-shadow: 0 8px 28px rgba(59, 130, 246, 0.45);
            transform: translateY(-1px);
        }
        .btn-secondary {
            background: transparent;
            color: var(--slate-400);
            border: 1px solid rgba(255,255,255,0.09);
        }
        .btn-secondary:hover {
            color: #ffffff;
            border-color: rgba(59, 130, 246, 0.45);
            background: rgba(26, 86, 219, 0.08);
        }

        /* Footer */
        .error-footer {
            position: absolute;
            bottom: 1.5rem;
            font-size: 12px;
            color: var(--slate-600);
            z-index: 1;
            display: flex;
            gap: 16px;
            align-items: center;
        }
        .error-footer a {
            color: var(--slate-600);
            text-decoration: none;
            transition: color 0.15s ease;
        }
        .error-footer a:hover { color: var(--slate-400); }
        .error-footer .sep { opacity: 0.4; }
    </style>
</head>
<body>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>

    <a href="{{ url('/') }}" class="brand-strip">
        @if(\App\Models\UploadedAsset::has('logo-dark'))
            <img src="{{ route('brand-asset', 'logo-dark') }}?v={{ \App\Models\UploadedAsset::cacheBuster('logo-dark') }}" alt="{{ config('app.name', 'TheCashFox') }}">
        @else
            <div class="brand-mark">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none"><path d="M3 17l4-8 4 4 4-6 4 4" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </div>
            <span class="brand-text">{{ config('app.name', 'TheCashFox') }}</span>
        @endif
    </a>

    <div class="error-container">
        @yield('content')
    </div>

    <div class="error-footer">
        <span>&copy; {{ date('Y') }} {{ config('app.name', 'TheCashFox') }}</span>
        <span class="sep">·</span>
        <a href="{{ route('terms') }}">Terms</a>
        <span class="sep">·</span>
        <a href="{{ route('privacy') }}">Privacy</a>
    </div>
</body>
</html>
