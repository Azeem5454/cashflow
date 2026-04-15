<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Verify Your Email — {{ config('app.name', 'CashFlow') }}</title>

    <link rel="icon" type="image/png" href="/favicon.png">

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
            background: radial-gradient(circle, rgba(26,86,219,0.20) 0%, transparent 70%);
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50%       { transform: translateY(-8px); }
        }
        .anim-fade-up    { animation: fadeInUp 0.6s ease both; }
        .anim-fade-up-d1 { animation: fadeInUp 0.6s 0.1s ease both; }
        .anim-fade-up-d2 { animation: fadeInUp 0.6s 0.2s ease both; }
        .anim-fade-up-d3 { animation: fadeInUp 0.6s 0.3s ease both; }
        .anim-float      { animation: float 3s ease-in-out infinite; }
    </style>
</head>
<body class="bg-navy font-body antialiased min-h-screen dot-grid flex flex-col items-center justify-center px-6 py-12">

    {{-- Glow orb --}}
    <div class="glow-orb fixed top-0 left-1/2 -translate-x-1/2 w-[700px] h-[500px] pointer-events-none opacity-50"></div>

    {{-- Logo --}}
    <a href="/" class="anim-fade-up relative z-10 mb-12">
        @if(file_exists(public_path('brand/logo-dark.png')))
            <img src="{{ asset('brand/logo-dark.png') }}?v={{ filemtime(public_path('brand/logo-dark.png')) }}"
                 alt="{{ config('app.name', 'CashFlow') }}" class="h-9 w-auto object-contain">
        @else
            <img src="/brand/cashflow_logo_horizontal.png" alt="{{ config('app.name', 'CashFlow') }}" class="h-9">
        @endif
    </a>

    {{-- Card --}}
    <div class="anim-fade-up-d1 relative z-10 w-full max-w-md bg-dark border border-white/8 rounded-2xl p-10 text-center shadow-2xl">

        {{-- Envelope icon --}}
        <div class="anim-float mx-auto mb-7 w-20 h-20 rounded-2xl bg-primary/10 border border-primary/20 flex items-center justify-center">
            <svg class="w-9 h-9 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
        </div>

        <h1 class="font-display font-extrabold text-2xl text-white mb-3">Check your inbox</h1>

        <p class="font-body text-sm text-slate-400 leading-relaxed mb-2">
            We've sent a verification link to your email address. Click the link to activate your account and start tracking your cash flow.
        </p>
        <p class="font-body text-xs text-slate-600 mb-8">
            Can't find it? Check your spam or promotions folder.
        </p>

        {{-- Success notice --}}
        @if (session('status') == 'verification-link-sent')
            <div class="mb-6 flex items-center gap-2.5 bg-green-500/10 border border-green-500/20 rounded-lg px-4 py-3 text-left">
                <svg class="w-4 h-4 text-green-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="font-body text-sm text-green-300">A new verification link has been sent to your email.</span>
            </div>
        @endif

        {{-- Resend form --}}
        <form method="POST" action="{{ route('verification.send') }}" class="anim-fade-up-d2">
            @csrf
            <button
                type="submit"
                class="w-full font-body font-medium text-sm text-white bg-primary hover:bg-accent rounded-lg px-4 py-3 transition-all duration-200 shadow-lg shadow-primary/25 hover:shadow-accent/30 hover:-translate-y-px"
            >
                Resend verification email
            </button>
        </form>

        {{-- Divider --}}
        <div class="relative flex items-center gap-4 py-6">
            <div class="flex-1 h-px bg-white/8"></div>
            <span class="font-body text-xs text-slate-600">or</span>
            <div class="flex-1 h-px bg-white/8"></div>
        </div>

        {{-- Logout --}}
        <form method="POST" action="{{ route('logout') }}" class="anim-fade-up-d3">
            @csrf
            <button
                type="submit"
                class="w-full font-body font-medium text-sm text-slate-400 hover:text-white border border-white/10 hover:border-white/20 rounded-lg px-4 py-3 transition-all duration-200 hover:bg-white/5"
            >
                Sign out
            </button>
        </form>

    </div>

    {{-- Footer note --}}
    <p class="anim-fade-up-d3 relative z-10 mt-8 font-body text-xs text-slate-600 text-center">
        @php $support = config('app.support_email') ?: 'hello@' . (parse_url(config('app.url', 'https://cashflow.app'), PHP_URL_HOST) ?: 'cashflow.app'); @endphp
        Having trouble? <a href="mailto:{{ $support }}" class="text-blue-light hover:text-accent transition-colors underline underline-offset-2">Contact support</a>
    </p>

</body>
</html>
