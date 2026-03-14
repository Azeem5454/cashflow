<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CashFlow — Real-Time Cash Flow Tracking for Your Business</title>
    <meta name="description" content="Track income, expenses, and live balance across all your businesses. Built for small business owners worldwide. Free to start.">

    <link rel="icon" type="image/png" href="/favicon.png">

    <!-- Brand Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400;12..96,700;12..96,800&family=Plus+Jakarta+Sans:wght@400;600;700&family=Outfit:wght@300;400;500&family=Geist+Mono:wght@400;500&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .dot-grid {
            background-image: radial-gradient(circle, rgba(26,86,219,0.12) 1px, transparent 1px);
            background-size: 28px 28px;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to   { opacity: 1; }
        }
        .anim-fade-up       { animation: fadeInUp 0.7s ease both; }
        .anim-fade-up-d1    { animation: fadeInUp 0.7s 0.1s ease both; }
        .anim-fade-up-d2    { animation: fadeInUp 0.7s 0.2s ease both; }
        .anim-fade-up-d3    { animation: fadeInUp 0.7s 0.3s ease both; }
        .anim-fade-up-d4    { animation: fadeInUp 0.7s 0.4s ease both; }
        .glow-orb {
            background: radial-gradient(circle, rgba(26,86,219,0.18) 0%, transparent 70%);
        }
        .balance-positive { color: #22c55e; }
        .balance-negative { color: #ef4444; }
    </style>
</head>
<body class="bg-navy text-white font-body antialiased overflow-x-hidden">

{{-- ===================== NAVBAR ===================== --}}
<nav class="fixed top-0 left-0 right-0 z-50 border-b border-white/5 backdrop-blur-xl bg-navy/80"
     x-data="{ mobileMenu: false }">
    <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
        <a href="/" class="flex-shrink-0 flex items-center gap-3">
            <img src="/brand/cashflow_logo.png" alt="CashFlow" class="h-9 w-9 rounded-xl">
            <span class="font-display font-extrabold text-xl text-white tracking-tight">CashFlow</span>
        </a>

        <div class="hidden md:flex items-center gap-8">
            <a href="#features" class="font-body text-sm text-blue-light hover:text-white transition-colors duration-200">Features</a>
            <a href="#pricing"  class="font-body text-sm text-blue-light hover:text-white transition-colors duration-200">Pricing</a>
            <a href="#reviews"  class="font-body text-sm text-blue-light hover:text-white transition-colors duration-200">Reviews</a>
        </div>

        <div class="flex items-center gap-2 sm:gap-3">
            <a href="{{ route('login') }}"
               class="font-body text-sm text-blue-light hover:text-white transition-colors duration-200 px-4 py-2 hidden sm:block">
                Login
            </a>
            <a href="{{ route('register') }}"
               class="font-body text-sm font-medium bg-primary hover:bg-accent text-white px-4 py-2 sm:px-5 sm:py-2.5 rounded-lg transition-all duration-200 shadow-lg shadow-primary/25 hover:shadow-accent/30">
                Get Started
            </a>
            {{-- Mobile menu toggle --}}
            <button @click="mobileMenu = !mobileMenu"
                    class="md:hidden p-2 rounded-lg text-slate-400 hover:text-white hover:bg-white/10 transition-colors">
                <svg x-show="!mobileMenu" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                </svg>
                <svg x-show="mobileMenu" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display:none;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>

    {{-- Mobile menu dropdown --}}
    <div x-show="mobileMenu"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         class="md:hidden border-t border-white/5 bg-navy/95 backdrop-blur-xl px-6 py-4 space-y-1"
         style="display:none;">
        <a href="#features" @click="mobileMenu = false"
           class="block font-body text-sm text-blue-light hover:text-white py-2.5 transition-colors">Features</a>
        <a href="#pricing" @click="mobileMenu = false"
           class="block font-body text-sm text-blue-light hover:text-white py-2.5 transition-colors">Pricing</a>
        <a href="#reviews" @click="mobileMenu = false"
           class="block font-body text-sm text-blue-light hover:text-white py-2.5 transition-colors">Reviews</a>
        <div class="pt-2 border-t border-white/5">
            <a href="{{ route('login') }}"
               class="block font-body text-sm text-blue-light hover:text-white py-2.5 transition-colors">Login</a>
        </div>
    </div>
</nav>

{{-- ===================== HERO ===================== --}}
<section class="relative min-h-screen flex items-center dot-grid pt-20">
    {{-- Glow orb --}}
    <div class="glow-orb absolute top-0 left-1/2 -translate-x-1/2 w-[900px] h-[600px] pointer-events-none"></div>

    <div class="relative max-w-7xl mx-auto px-6 py-16 sm:py-24 grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">

        {{-- Left: Copy --}}
        <div>
            <div class="anim-fade-up inline-flex items-center gap-2 bg-primary/10 border border-primary/25 rounded-full px-4 py-1.5 mb-8">
                <span class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></span>
                <span class="font-body text-xs text-blue-light font-medium tracking-widest uppercase">Built for Small Businesses Worldwide</span>
            </div>

            <h1 class="anim-fade-up-d1 font-display font-extrabold text-4xl sm:text-5xl lg:text-[3.75rem] leading-[1.05] text-white mb-6">
                Know Exactly<br>
                Where Your<br>
                <span class="text-accent">Cash Flows.</span>
            </h1>

            <p class="anim-fade-up-d2 font-body text-base sm:text-lg text-slate-400 leading-relaxed mb-8 sm:mb-10 max-w-lg">
                Real-time ledger for every business you run.
                Track income, expenses, and balance — across multiple books
                and teams — without needing an accountant.
            </p>

            <div class="anim-fade-up-d3 flex flex-col sm:flex-row flex-wrap gap-3 sm:gap-4">
                <a href="{{ route('register') }}"
                   class="font-body font-medium bg-primary hover:bg-accent text-white px-6 py-3 sm:px-8 sm:py-3.5 rounded-lg transition-all duration-200 shadow-xl shadow-primary/30 hover:shadow-accent/30 hover:-translate-y-px text-center">
                    Start for Free — No Card Needed
                </a>
                <a href="#features"
                   class="font-body font-medium border border-white/10 hover:border-primary/40 text-blue-light hover:text-white px-6 py-3 sm:px-8 sm:py-3.5 rounded-lg transition-all duration-200 text-center">
                    See How It Works →
                </a>
            </div>

            {{-- Stats --}}
            <div class="anim-fade-up-d4 grid grid-cols-2 sm:flex sm:flex-wrap gap-6 sm:gap-8 mt-10 sm:mt-14 pt-8 border-t border-white/5">
                <div>
                    <div class="font-mono text-2xl font-bold text-white">$0</div>
                    <div class="font-body text-xs text-slate-500 mt-1">to get started</div>
                </div>
                <div class="hidden sm:block w-px bg-white/5 self-stretch"></div>
                <div>
                    <div class="font-mono text-2xl font-bold text-white">∞</div>
                    <div class="font-body text-xs text-slate-500 mt-1">books & entries</div>
                </div>
                <div class="hidden sm:block w-px bg-white/5 self-stretch"></div>
                <div>
                    <div class="font-mono text-2xl font-bold text-white">3</div>
                    <div class="font-body text-xs text-slate-500 mt-1">team roles</div>
                </div>
                <div class="hidden sm:block w-px bg-white/5 self-stretch"></div>
                <div>
                    <div class="font-mono text-2xl font-bold text-white">150+</div>
                    <div class="font-body text-xs text-slate-500 mt-1">countries</div>
                </div>
            </div>
        </div>

        {{-- Right: Dashboard preview --}}
        <div class="relative hidden lg:block anim-fade-up-d2">
            {{-- Main card --}}
            <div class="bg-dark rounded-2xl border border-white/8 p-6 shadow-2xl ring-1 ring-white/5">
                {{-- Card header --}}
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <div class="font-heading font-bold text-white text-sm">Eveso IT Company</div>
                        <div class="font-body text-xs text-slate-500 mt-0.5">March 2026 — Book</div>
                    </div>
                    <span class="bg-green-500/10 text-green-400 text-xs font-body font-medium px-3 py-1 rounded-full border border-green-500/20">
                        Positive
                    </span>
                </div>

                {{-- Balance --}}
                <div class="mb-6 pb-6 border-b border-white/5">
                    <div class="font-body text-xs text-slate-500 uppercase tracking-widest mb-2">Net Balance</div>
                    <div class="font-mono text-4xl font-bold text-white balance-positive">$12,450.00</div>
                </div>

                {{-- In/Out --}}
                <div class="grid grid-cols-2 gap-3 mb-6">
                    <div class="bg-navy rounded-xl p-4 border border-white/5">
                        <div class="flex items-center gap-1.5 mb-2">
                            <div class="w-1.5 h-1.5 rounded-full bg-green-400"></div>
                            <div class="font-body text-xs text-slate-500">Cash In</div>
                        </div>
                        <div class="font-mono text-xl text-green-400 font-medium">+$18,200</div>
                    </div>
                    <div class="bg-navy rounded-xl p-4 border border-white/5">
                        <div class="flex items-center gap-1.5 mb-2">
                            <div class="w-1.5 h-1.5 rounded-full bg-red-400"></div>
                            <div class="font-body text-xs text-slate-500">Cash Out</div>
                        </div>
                        <div class="font-mono text-xl text-red-400 font-medium">-$5,750</div>
                    </div>
                </div>

                {{-- Recent entries --}}
                <div class="space-y-1">
                    <div class="font-body text-xs text-slate-500 uppercase tracking-widest mb-3">Recent Entries</div>
                    @foreach([
                        ['Client Invoice #104', '$3,200', 'in',  'Mar 12'],
                        ['Office Rent — March', '$1,800', 'out', 'Mar 10'],
                        ['Consulting Project',  '$4,800', 'in',  'Mar 8'],
                    ] as $entry)
                    <div class="flex items-center justify-between py-2.5 pl-3 rounded-lg
                                border-l-2 {{ $entry[2] === 'in' ? 'border-green-500/60 hover:bg-green-500/5' : 'border-red-500/60 hover:bg-red-500/5' }}
                                transition-colors duration-150">
                        <div>
                            <div class="font-body text-sm text-white">{{ $entry[0] }}</div>
                            <div class="font-body text-xs text-slate-500">{{ $entry[3] }}</div>
                        </div>
                        <div class="font-mono text-sm font-medium {{ $entry[2] === 'in' ? 'text-green-400' : 'text-red-400' }}">
                            {{ $entry[2] === 'in' ? '+' : '-' }}{{ $entry[1] }}
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Floating: Team badge --}}
            <div class="absolute -bottom-5 -left-8 bg-primary rounded-2xl px-5 py-3.5 shadow-2xl shadow-primary/40 ring-1 ring-primary/50">
                <div class="font-body text-xs text-blue-200 mb-2 font-medium">Team Access</div>
                <div class="flex items-center gap-1.5">
                    @foreach(['AZ','FM','BK'] as $i => $initials)
                    <div class="w-7 h-7 rounded-full bg-navy/60 border-2 border-primary flex items-center justify-center text-[10px] text-white font-bold {{ $i > 0 ? '-ml-2' : '' }}">
                        {{ $initials }}
                    </div>
                    @endforeach
                    <span class="font-body text-xs text-blue-100 ml-2">3 members</span>
                </div>
            </div>

            {{-- Floating: Export badge --}}
            <div class="absolute -top-5 -right-6 bg-dark border border-white/10 rounded-2xl px-5 py-3.5 shadow-2xl">
                <div class="font-body text-xs text-slate-500 mb-1.5">Export Ready</div>
                <div class="flex items-center gap-2.5">
                    <svg class="w-5 h-5 text-red-400" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20M10.92,12.31C10.68,11.54 10.15,9.08 11.55,9.04C12.95,9 12.03,11.54 11.72,12.31C12.58,14.54 14.14,17.16 15.21,17.54C15.82,16.95 16.5,16.67 17.02,16.67C18.5,16.67 18.5,17.17 18.5,17.38C18.5,17.67 17.5,18.5 15.21,17.54C13.13,16.77 11.26,17.16 10.92,17.16C10.57,17.16 9.45,15.85 10.92,12.31Z" />
                    </svg>
                    <div>
                        <div class="font-body text-sm text-white font-medium">PDF + CSV</div>
                        <div class="font-body text-xs text-slate-500">Pro feature</div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>

{{-- ===================== SOCIAL PROOF BAR ===================== --}}
<div class="border-y border-white/5 bg-dark/40 py-6">
    <div class="max-w-7xl mx-auto px-6">
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4 sm:gap-12 text-center">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                <span class="font-body text-sm text-slate-400">Bank-grade data isolation</span>
            </div>
            <div class="hidden sm:block w-px h-4 bg-white/10"></div>
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                <span class="font-body text-sm text-slate-400">Real-time balance updates</span>
            </div>
            <div class="hidden sm:block w-px h-4 bg-white/10"></div>
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-blue-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="font-body text-sm text-slate-400">No hidden fees — ever</span>
            </div>
            <div class="hidden sm:block w-px h-4 bg-white/10"></div>
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-blue-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                </svg>
                <span class="font-body text-sm text-slate-400">Works in any currency</span>
            </div>
        </div>
    </div>
</div>

{{-- ===================== FEATURES ===================== --}}
<section id="features" class="py-20 md:py-32 relative">
    <div class="max-w-7xl mx-auto px-6">

        {{-- Section header --}}
        <div class="text-center mb-20">
            <div class="font-body text-sm text-accent font-medium uppercase tracking-widest mb-4">Why CashFlow</div>
            <h2 class="font-display font-extrabold text-4xl lg:text-5xl text-white mb-5 leading-tight">
                Built for how you<br>actually run a business
            </h2>
            <p class="font-body text-slate-400 text-lg max-w-xl mx-auto">
                No spreadsheets. No jargon. Just a clean ledger that keeps up with you.
            </p>
        </div>

        {{-- Feature grid --}}
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">

            {{-- Feature 1: Track Income & Expenses --}}
            <div class="group bg-dark rounded-2xl p-8 border border-white/8 hover:border-primary/40 transition-all duration-300 hover:shadow-xl hover:shadow-primary/5">
                <div class="w-12 h-12 bg-primary/10 rounded-xl flex items-center justify-center mb-6 group-hover:bg-primary/20 transition-colors duration-300">
                    <svg class="w-6 h-6 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 19h16a2 2 0 002-2V7a2 2 0 00-2-2H4a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="font-heading font-bold text-white text-xl mb-3">Track Every Transaction</h3>
                <p class="font-body text-slate-400 text-sm leading-relaxed mb-6">
                    Log cash-in and cash-out entries with description, date, and reference. Your running balance updates the moment you save.
                </p>
                <div class="bg-navy rounded-xl p-4 border border-white/5">
                    <div class="flex justify-between items-center">
                        <span class="font-body text-xs text-slate-500">Live Balance</span>
                        <span class="font-mono text-sm text-green-400 font-medium">+$12,450.00</span>
                    </div>
                </div>
            </div>

            {{-- Feature 2: Multiple Businesses --}}
            <div class="group bg-dark rounded-2xl p-8 border border-white/8 hover:border-primary/40 transition-all duration-300 hover:shadow-xl hover:shadow-primary/5">
                <div class="w-12 h-12 bg-primary/10 rounded-xl flex items-center justify-center mb-6 group-hover:bg-primary/20 transition-colors duration-300">
                    <svg class="w-6 h-6 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <h3 class="font-heading font-bold text-white text-xl mb-3">Multiple Businesses</h3>
                <p class="font-body text-slate-400 text-sm leading-relaxed mb-6">
                    Run a shop and a side hustle? Manage them all under one account. Each business is completely isolated — no data mixing.
                </p>
                <div class="flex gap-2 flex-wrap">
                    <span class="bg-navy border border-white/8 rounded-lg px-3 py-1.5 font-body text-xs text-blue-light">Design Studio</span>
                    <span class="bg-navy border border-white/8 rounded-lg px-3 py-1.5 font-body text-xs text-blue-light">Retail Store</span>
                    <span class="bg-primary/10 border border-primary/25 rounded-lg px-3 py-1.5 font-body text-xs text-accent">+ Add Business</span>
                </div>
            </div>

            {{-- Feature 3: Team Collaboration --}}
            <div class="group bg-dark rounded-2xl p-8 border border-white/8 hover:border-primary/40 transition-all duration-300 hover:shadow-xl hover:shadow-primary/5">
                <div class="w-12 h-12 bg-primary/10 rounded-xl flex items-center justify-center mb-6 group-hover:bg-primary/20 transition-colors duration-300">
                    <svg class="w-6 h-6 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <h3 class="font-heading font-bold text-white text-xl mb-3">Team Collaboration</h3>
                <p class="font-body text-slate-400 text-sm leading-relaxed mb-6">
                    Invite your accountant or staff as editors or viewers. You control what they see — and what they can change.
                </p>
                <div class="flex gap-2 flex-wrap">
                    <span class="bg-primary/10 border border-primary/30 text-accent rounded-full px-3 py-1 font-body text-xs font-medium">Owner</span>
                    <span class="bg-navy border border-white/8 text-slate-400 rounded-full px-3 py-1 font-body text-xs">Editor</span>
                    <span class="bg-navy border border-white/8 text-slate-400 rounded-full px-3 py-1 font-body text-xs">Viewer</span>
                </div>
            </div>

            {{-- Feature 4: Books --}}
            <div class="group bg-dark rounded-2xl p-8 border border-white/8 hover:border-primary/40 transition-all duration-300 hover:shadow-xl hover:shadow-primary/5">
                <div class="w-12 h-12 bg-primary/10 rounded-xl flex items-center justify-center mb-6 group-hover:bg-primary/20 transition-colors duration-300">
                    <svg class="w-6 h-6 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                </div>
                <h3 class="font-heading font-bold text-white text-xl mb-3">Organised by Books</h3>
                <p class="font-body text-slate-400 text-sm leading-relaxed mb-6">
                    Group entries into books by month, quarter, or project. Find any period's data instantly — no endless scrolling.
                </p>
                <div class="space-y-2.5">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2.5 font-body text-sm text-white">
                            <span class="w-2 h-2 rounded-full bg-accent"></span>
                            March 2026
                        </div>
                        <span class="font-mono text-xs text-green-400">+$12,450</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2.5 font-body text-sm text-slate-500">
                            <span class="w-2 h-2 rounded-full bg-slate-600"></span>
                            February 2026
                        </div>
                        <span class="font-mono text-xs text-slate-500">+$8,320</span>
                    </div>
                </div>
            </div>

            {{-- Feature 5: PDF/CSV Export --}}
            <div class="group bg-dark rounded-2xl p-8 border border-white/8 hover:border-primary/40 transition-all duration-300 hover:shadow-xl hover:shadow-primary/5">
                <div class="w-12 h-12 bg-primary/10 rounded-xl flex items-center justify-center mb-6 group-hover:bg-primary/20 transition-colors duration-300">
                    <svg class="w-6 h-6 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/>
                    </svg>
                </div>
                <h3 class="font-heading font-bold text-white text-xl mb-3">PDF & CSV Export</h3>
                <p class="font-body text-slate-400 text-sm leading-relaxed mb-6">
                    Download a clean, professional PDF report or a CSV spreadsheet. Share with your accountant in one click — no formatting required.
                </p>
                <div class="inline-flex items-center gap-2 bg-primary/10 border border-primary/25 rounded-lg px-3 py-2">
                    <div class="w-1.5 h-1.5 rounded-full bg-accent"></div>
                    <span class="font-body text-xs text-accent font-medium">Available on Pro Plan — $3/mo</span>
                </div>
            </div>

            {{-- Feature 6: Secure --}}
            <div class="group bg-dark rounded-2xl p-8 border border-white/8 hover:border-primary/40 transition-all duration-300 hover:shadow-xl hover:shadow-primary/5">
                <div class="w-12 h-12 bg-primary/10 rounded-xl flex items-center justify-center mb-6 group-hover:bg-primary/20 transition-colors duration-300">
                    <svg class="w-6 h-6 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <h3 class="font-heading font-bold text-white text-xl mb-3">Private & Secure</h3>
                <p class="font-body text-slate-400 text-sm leading-relaxed mb-6">
                    Your business data stays yours. Every business is fully isolated. We never mix data between accounts — ever.
                </p>
                <div class="flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full bg-green-400"></div>
                    <span class="font-body text-xs text-slate-500">Data isolated per business</span>
                </div>
            </div>

        </div>
    </div>
</section>

{{-- ===================== HOW IT WORKS ===================== --}}
<section class="py-20 md:py-32 bg-dark/40 border-y border-white/5">
    <div class="max-w-5xl mx-auto px-6">

        <div class="text-center mb-16">
            <div class="font-body text-sm text-accent font-medium uppercase tracking-widest mb-4">How It Works</div>
            <h2 class="font-display font-extrabold text-4xl text-white">Up and running in minutes</h2>
        </div>

        <div class="grid md:grid-cols-4 gap-8 relative">
            {{-- Connector line --}}
            <div class="hidden md:block absolute top-8 left-[12.5%] right-[12.5%] h-px bg-gradient-to-r from-transparent via-primary/40 to-transparent"></div>

            @foreach([
                ['01', 'Create Account', 'Sign up free. No credit card, no commitment.'],
                ['02', 'Add a Business', 'Create your business profile in seconds.'],
                ['03', 'Open a Book', 'Create a book for the month or project.'],
                ['04', 'Log Entries', 'Add cash in/out. Watch your balance update live.'],
            ] as $step)
            <div class="text-center relative">
                <div class="w-16 h-16 bg-dark border border-primary/30 rounded-2xl flex items-center justify-center mx-auto mb-5 relative z-10">
                    <span class="font-mono text-xl font-bold text-primary">{{ $step[0] }}</span>
                </div>
                <h3 class="font-heading font-bold text-white text-base mb-2">{{ $step[1] }}</h3>
                <p class="font-body text-sm text-slate-500 leading-relaxed">{{ $step[2] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ===================== PRICING ===================== --}}
<section id="pricing" class="py-20 md:py-32 relative dot-grid">
    <div class="glow-orb absolute bottom-0 left-1/2 -translate-x-1/2 w-[700px] h-[400px] pointer-events-none opacity-60"></div>
    <div class="relative max-w-7xl mx-auto px-6">

        <div class="text-center mb-16">
            <div class="font-body text-sm text-accent font-medium uppercase tracking-widest mb-4">Pricing</div>
            <h2 class="font-display font-extrabold text-4xl lg:text-5xl text-white mb-4">Simple. Honest. Fair.</h2>
            <p class="font-body text-slate-400 text-lg">Start free. Upgrade when your team grows.</p>
        </div>

        <div class="grid md:grid-cols-2 gap-8 max-w-3xl mx-auto">

            {{-- Free Plan --}}
            <div class="bg-dark rounded-2xl border border-white/8 p-8">
                <div class="font-heading font-bold text-white text-xl mb-2">Free</div>
                <div class="flex items-end gap-1 mb-1">
                    <span class="font-mono text-5xl font-bold text-white">$0</span>
                </div>
                <div class="font-body text-sm text-slate-500 mb-8">per month, forever</div>
                <ul class="space-y-4 mb-8">
                    @foreach([
                        '1 business',
                        'Unlimited books & entries',
                        'Up to 2 team members',
                        'Live balance view',
                        'Community support',
                    ] as $item)
                    <li class="flex items-center gap-3 font-body text-sm text-blue-light">
                        <svg class="w-4 h-4 text-green-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        {{ $item }}
                    </li>
                    @endforeach
                </ul>
                <a href="{{ route('register') }}"
                   class="block w-full text-center font-body font-medium border border-white/10 hover:border-accent/50 text-white px-6 py-3 rounded-lg transition-all duration-200 hover:bg-white/5">
                    Get Started Free
                </a>
            </div>

            {{-- Pro Plan --}}
            <div class="relative bg-navy rounded-2xl border border-primary/40 p-8 shadow-2xl shadow-primary/15 ring-1 ring-primary/20">
                <div class="absolute -top-3.5 left-1/2 -translate-x-1/2">
                    <span class="bg-primary text-white text-xs font-body font-medium px-5 py-1.5 rounded-full shadow-lg shadow-primary/40">
                        Most Popular
                    </span>
                </div>
                <div class="font-heading font-bold text-white text-xl mb-2">Pro</div>
                <div class="flex items-end gap-1 mb-1">
                    <span class="font-mono text-5xl font-bold text-white">$3</span>
                    <span class="font-body text-slate-400 mb-2">/month</span>
                </div>
                <div class="font-body text-sm text-slate-500 mb-8">billed monthly — cancel anytime</div>
                <ul class="space-y-4 mb-8">
                    @foreach([
                        'Unlimited businesses',
                        'Unlimited books & entries',
                        'Unlimited team members',
                        'PDF & CSV export',
                        'Priority support',
                        'Everything in Free',
                    ] as $item)
                    <li class="flex items-center gap-3 font-body text-sm text-blue-light">
                        <svg class="w-4 h-4 text-green-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        {{ $item }}
                    </li>
                    @endforeach
                </ul>
                <a href="{{ route('register') }}"
                   class="block w-full text-center font-body font-medium bg-primary hover:bg-accent text-white px-6 py-3 rounded-lg transition-all duration-200 shadow-lg shadow-primary/30 hover:shadow-accent/30">
                    Start Pro — $3/mo
                </a>
            </div>

        </div>

        <p class="text-center font-body text-xs text-slate-600 mt-8">
            Payments processed securely via Stripe. No card stored on our servers.
        </p>
    </div>
</section>

{{-- ===================== TESTIMONIALS ===================== --}}
<section id="reviews" class="py-20 md:py-32">
    <div class="max-w-7xl mx-auto px-6">

        <div class="text-center mb-16">
            <div class="font-body text-sm text-accent font-medium uppercase tracking-widest mb-4">Testimonials</div>
            <h2 class="font-display font-extrabold text-4xl lg:text-5xl text-white">
                Trusted by business owners<br>around the world
            </h2>
        </div>

        <div class="grid md:grid-cols-3 gap-6">
            @foreach([
                [
                    'James Okafor',
                    'E-commerce Store, Lagos',
                    'We track 4 businesses in CashFlow. The books feature alone saved me hours every month that I used to spend sorting through one messy spreadsheet.',
                    5,
                    'JO',
                ],
                [
                    'Sophie Müller',
                    'Freelance Consultant, Berlin',
                    "I never knew exactly how much was coming in and going out. CashFlow gave me clarity within the first week. I check the balance every morning now.",
                    5,
                    'SM',
                ],
                [
                    'Carlos Reyes',
                    'Wholesale Distributor, Mexico City',
                    'My accountant is an editor on my account. He adds entries, I see the live balance. The role system is exactly what a small business needs.',
                    5,
                    'CR',
                ],
            ] as $t)
            <div class="bg-dark rounded-2xl border border-white/8 p-7 flex flex-col">
                {{-- Stars --}}
                <div class="flex gap-1 mb-5">
                    @for($i = 0; $i < $t[3]; $i++)
                    <svg class="w-4 h-4 text-accent" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                    @endfor
                </div>

                {{-- Quote --}}
                <p class="font-body text-blue-light text-sm leading-relaxed flex-grow mb-6">
                    "{{ $t[2] }}"
                </p>

                {{-- Author --}}
                <div class="flex items-center gap-3 pt-5 border-t border-white/5">
                    <div class="w-9 h-9 rounded-full bg-primary/20 border border-primary/30 flex items-center justify-center">
                        <span class="font-mono text-xs font-bold text-accent">{{ $t[4] }}</span>
                    </div>
                    <div>
                        <div class="font-heading font-bold text-white text-sm">{{ $t[0] }}</div>
                        <div class="font-body text-xs text-slate-500">{{ $t[1] }}</div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ===================== CTA BANNER ===================== --}}
<section class="py-16 sm:py-24 relative overflow-hidden">
    <div class="absolute inset-0 dot-grid opacity-60"></div>
    <div class="glow-orb absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[900px] h-[400px] pointer-events-none"></div>

    <div class="relative max-w-3xl mx-auto px-6 text-center">
        <div class="bg-dark border border-primary/25 rounded-2xl p-6 sm:p-12 shadow-2xl shadow-primary/10 ring-1 ring-primary/10">
            <div class="inline-flex items-center gap-2 bg-primary/10 border border-primary/25 rounded-full px-4 py-1.5 mb-8">
                <span class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></span>
                <span class="font-body text-xs text-blue-light font-medium">Free to start — always</span>
            </div>
            <h2 class="font-display font-extrabold text-4xl lg:text-5xl text-white mb-5 leading-tight">
                Start tracking your cash<br>— today.
            </h2>
            <p class="font-body text-slate-400 text-lg mb-8">
                No credit card. No accountant required.<br>Up and running in under 2 minutes.
            </p>
            <a href="{{ route('register') }}"
               class="inline-block font-body font-medium bg-primary hover:bg-accent text-white px-10 py-4 rounded-xl transition-all duration-200 shadow-xl shadow-primary/30 hover:shadow-accent/30 hover:-translate-y-px text-base">
                Create Your Free Account →
            </a>
            <div class="mt-6 sm:mt-8 flex flex-wrap items-center justify-center gap-3 sm:gap-6">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span class="font-body text-xs text-slate-500">Free forever plan</span>
                </div>
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span class="font-body text-xs text-slate-500">No card required</span>
                </div>
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span class="font-body text-xs text-slate-500">Cancel anytime</span>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ===================== FOOTER ===================== --}}
<footer class="bg-dark border-t border-white/5 py-12">
    <div class="max-w-7xl mx-auto px-6">
        <div class="grid md:grid-cols-4 gap-10 mb-12">
            <div class="md:col-span-2">
                <div class="flex items-center gap-2.5 mb-4">
                    <img src="/brand/cashflow_logo.png" alt="CashFlow" class="h-8 w-8 rounded-lg">
                    <span class="font-display font-extrabold text-xl text-white tracking-tight">CashFlow</span>
                </div>
                <p class="font-body text-sm text-slate-500 leading-relaxed max-w-xs">
                    Real-time cash flow tracking for small businesses, freelancers, and their teams — worldwide.
                </p>
            </div>
            <div>
                <div class="font-heading font-bold text-white text-sm mb-4">Product</div>
                <div class="space-y-3">
                    <a href="#features" class="block font-body text-sm text-slate-500 hover:text-white transition-colors">Features</a>
                    <a href="#pricing"  class="block font-body text-sm text-slate-500 hover:text-white transition-colors">Pricing</a>
                    <a href="#reviews"  class="block font-body text-sm text-slate-500 hover:text-white transition-colors">Reviews</a>
                </div>
            </div>
            <div>
                <div class="font-heading font-bold text-white text-sm mb-4">Account</div>
                <div class="space-y-3">
                    <a href="{{ route('login') }}"    class="block font-body text-sm text-slate-500 hover:text-white transition-colors">Login</a>
                    <a href="{{ route('register') }}" class="block font-body text-sm text-slate-500 hover:text-white transition-colors">Sign Up Free</a>
                </div>
            </div>
        </div>
        <div class="pt-8 border-t border-white/5 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="font-body text-xs text-slate-600">
                © {{ date('Y') }} CashFlow. Built for business owners everywhere.
            </div>
            <div class="font-body text-xs text-slate-600">
                Payments secured by Stripe.
            </div>
        </div>
    </div>
</footer>

</body>
</html>
