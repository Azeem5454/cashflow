<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'CashFlow') }}</title>

    <link rel="icon" type="image/png" href="/favicon.png">

    <!-- Brand Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400;12..96,700;12..96,800&family=Plus+Jakarta+Sans:wght@400;600;700&family=Outfit:wght@300;400;500&family=Geist+Mono:wght@400;500&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .dot-grid {
            background-image: radial-gradient(circle, rgba(26,86,219,0.10) 1px, transparent 1px);
            background-size: 28px 28px;
        }
        .glow-orb {
            background: radial-gradient(circle, rgba(26,86,219,0.22) 0%, transparent 70%);
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .anim-fade-up    { animation: fadeInUp 0.6s ease both; }
        .anim-fade-up-d1 { animation: fadeInUp 0.6s 0.08s ease both; }
        .anim-fade-up-d2 { animation: fadeInUp 0.6s 0.16s ease both; }
        .anim-fade-up-d3 { animation: fadeInUp 0.6s 0.24s ease both; }
        .anim-fade-up-d4 { animation: fadeInUp 0.6s 0.32s ease both; }

        /* Custom input styling */
        .auth-input {
            background: #0d1424;
            border: 1px solid rgba(255,255,255,0.08);
            color: #f8fafc;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .auth-input::placeholder { color: #64748b; }
        .auth-input:focus {
            outline: none;
            border-color: #1a56db;
            box-shadow: 0 0 0 3px rgba(26,86,219,0.18);
        }
        .auth-input:focus + .auth-input-icon { color: #3b82f6; }
    </style>
</head>
<body class="bg-navy font-body antialiased overflow-x-hidden min-h-screen">

<div class="min-h-screen flex">

    {{-- ===== LEFT BRAND PANEL ===== --}}
    <div class="hidden lg:flex lg:w-[52%] xl:w-[55%] relative flex-col p-12 dot-grid overflow-hidden">

        {{-- Glow orbs --}}
        <div class="glow-orb absolute -top-32 -left-32 w-[600px] h-[600px] pointer-events-none opacity-60"></div>
        <div class="glow-orb absolute bottom-0 right-0 w-[400px] h-[400px] pointer-events-none opacity-30"></div>

        {{-- Logo --}}
        <a href="/" class="relative z-10 flex-shrink-0 flex items-center gap-3">
            <img src="/brand/cashflow_logo.png" alt="CashFlow" class="h-9 w-9 rounded-xl">
            <span class="font-display font-extrabold text-2xl text-white tracking-tight">CashFlow</span>
        </a>

        {{-- Middle content --}}
        <div class="relative z-10 max-w-lg mt-16">
            <div class="inline-flex items-center gap-2 bg-primary/10 border border-primary/20 rounded-full px-4 py-1.5 mb-8">
                <span class="w-1.5 h-1.5 rounded-full bg-green-400 animate-pulse"></span>
                <span class="font-body text-xs text-blue-light font-medium tracking-widest uppercase">Real-Time Cash Tracking</span>
            </div>

            <h2 class="font-display font-extrabold text-4xl xl:text-5xl leading-[1.08] text-white mb-5">
                Your business finances,<br>
                <span class="text-accent">crystal clear.</span>
            </h2>

            <p class="font-body text-base text-slate-400 leading-relaxed mb-10">
                Track income, expenses, and live balance across all your books — built for small business owners who need clarity, not complexity.
            </p>

            {{-- Feature bullets --}}
            <ul class="space-y-4">
                @foreach([
                    ['icon' => 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 19h16a2 2 0 002-2V7a2 2 0 00-2-2H4a2 2 0 00-2 2v10a2 2 0 002 2z', 'text' => 'Multi-business ledger with live balance'],
                    ['icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z', 'text' => 'Collaborate with your team, scoped by role'],
                    ['icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'text' => 'PDF & CSV export for every book (Pro)'],
                ] as $item)
                <li class="flex items-start gap-3">
                    <span class="flex-shrink-0 w-8 h-8 rounded-lg bg-primary/15 border border-primary/20 flex items-center justify-center mt-0.5">
                        <svg class="w-4 h-4 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" />
                        </svg>
                    </span>
                    <span class="font-body text-sm text-slate-300 leading-relaxed pt-1">{{ $item['text'] }}</span>
                </li>
                @endforeach
            </ul>
        </div>

        {{-- Bottom testimonial --}}
        <div class="relative z-10 mt-auto bg-white/[0.04] border border-white/8 rounded-xl p-5 backdrop-blur-sm">
            <div class="flex items-start gap-3">
                <div class="w-9 h-9 rounded-full bg-gradient-to-br from-primary to-accent flex-shrink-0 flex items-center justify-center">
                    <span class="font-heading font-bold text-sm text-white">MW</span>
                </div>
                <div>
                    <p class="font-body text-sm text-slate-300 leading-relaxed mb-1.5">
                        "Finally a cash tracker that doesn't need an accountant to operate. Cleared my backlog of 3 months of entries in one afternoon."
                    </p>
                    <span class="font-body text-xs text-blue-light">Marcus Webb · Toronto, Canada</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== RIGHT FORM PANEL ===== --}}
    <div class="w-full lg:w-[48%] xl:w-[45%] flex flex-col items-center justify-center px-6 py-12 lg:px-12 xl:px-16 relative bg-dark">

        {{-- Mobile logo --}}
        <div class="lg:hidden mb-8 self-start">
            <a href="/" class="flex items-center gap-2.5">
                <img src="/brand/cashflow_logo.png" alt="CashFlow" class="h-8 w-8 rounded-lg">
                <span class="font-display font-extrabold text-xl text-white tracking-tight">CashFlow</span>
            </a>
        </div>

        <div class="w-full max-w-md">
            {{ $slot }}
        </div>
    </div>

</div>

</body>
</html>
