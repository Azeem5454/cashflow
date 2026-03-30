<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') — {{ config('app.name', 'CashFlow') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@400;700;800&family=Outfit:wght@300;400;500&family=Geist+Mono:wght@400&display=swap" rel="stylesheet">
    <script>if((localStorage.getItem('cashflow_theme')||'light')==='dark'){document.documentElement.classList.add('dark');}</script>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --navy: #0a0f1e;
            --dark: #111827;
            --primary: #1a56db;
            --accent: #3b82f6;
            --blue-light: #93c5fd;
            --slate-800: #1e293b;
        }

        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background-color: #f8fafc;
            color: #1e293b;
            font-family: 'Outfit', sans-serif;
            overflow: hidden;
            position: relative;
        }

        html.dark body {
            background-color: var(--navy);
            color: #e2e8f0;
        }

        /* Animated background orbs */
        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.12;
            animation: float 20s ease-in-out infinite;
            pointer-events: none;
        }
        html.dark .orb { opacity: 0.08; }

        .orb-1 {
            width: 500px; height: 500px;
            background: var(--primary);
            top: -150px; left: -100px;
            animation-delay: 0s;
        }
        .orb-2 {
            width: 400px; height: 400px;
            background: var(--accent);
            bottom: -100px; right: -80px;
            animation-delay: -7s;
        }
        .orb-3 {
            width: 300px; height: 300px;
            background: #8b5cf6;
            top: 40%; left: 60%;
            animation-delay: -14s;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(30px, -30px) scale(1.05); }
            66% { transform: translate(-20px, 20px) scale(0.95); }
        }

        /* Content */
        .error-container {
            position: relative;
            z-index: 1;
            text-align: center;
            padding: 2rem;
            max-width: 480px;
        }

        /* Error code — large animated number */
        .error-code {
            font-family: 'Bricolage Grotesque', sans-serif;
            font-weight: 800;
            font-size: clamp(80px, 20vw, 140px);
            line-height: 1;
            background: linear-gradient(135deg, var(--primary), var(--accent), #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: shimmer 6s ease-in-out infinite;
            background-size: 200% 200%;
            margin-bottom: 0.25rem;
        }

        @keyframes shimmer {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        .error-title {
            font-family: 'Bricolage Grotesque', sans-serif;
            font-weight: 700;
            font-size: clamp(20px, 4vw, 28px);
            margin-bottom: 0.75rem;
            color: #111827;
        }
        html.dark .error-title { color: #ffffff; }

        .error-description {
            font-size: 15px;
            line-height: 1.6;
            color: #64748b;
            margin-bottom: 2rem;
        }
        html.dark .error-description { color: #94a3b8; }

        /* Animated icon */
        .error-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 1.5rem;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: pulse-icon 3s ease-in-out infinite;
        }

        @keyframes pulse-icon {
            0%, 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(26, 86, 219, 0.2); }
            50% { transform: scale(1.05); box-shadow: 0 0 0 12px rgba(26, 86, 219, 0); }
        }

        .error-icon svg {
            width: 32px;
            height: 32px;
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
            padding: 12px 24px;
            font-size: 14px;
            font-weight: 600;
            font-family: 'Outfit', sans-serif;
            border-radius: 12px;
            text-decoration: none;
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background-color: var(--primary);
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(26, 86, 219, 0.3);
        }
        .btn-primary:hover {
            background-color: var(--accent);
            box-shadow: 0 4px 20px rgba(59, 130, 246, 0.4);
            transform: translateY(-1px);
        }

        .btn-secondary {
            background-color: transparent;
            color: #64748b;
            border: 1px solid #e2e8f0;
        }
        html.dark .btn-secondary {
            color: #94a3b8;
            border-color: var(--slate-800);
        }
        .btn-secondary:hover {
            color: var(--primary);
            border-color: var(--primary);
            background-color: rgba(26, 86, 219, 0.05);
        }

        /* Footer */
        .error-footer {
            position: absolute;
            bottom: 2rem;
            font-size: 12px;
            color: #94a3b8;
            z-index: 1;
        }
        html.dark .error-footer { color: #475569; }

        /* Entrance animation */
        .error-container {
            animation: fadeUp 0.6s ease-out both;
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>

    <div class="error-container">
        @yield('content')
    </div>

    <div class="error-footer">
        &copy; {{ date('Y') }} {{ config('app.name', 'CashFlow') }}
    </div>
</body>
</html>
