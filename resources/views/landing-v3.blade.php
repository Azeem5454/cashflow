<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'CashFlow') }} — Your business balance. Live. Always.</title>
    <meta name="description" content="Track every transaction, scan receipts with AI, and get cash flow insights. The smartest cash book for small businesses worldwide.">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,700;12..96,800;12..96,900&family=Outfit:wght@300;400;500;600&family=Geist+Mono:wght@400;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; scroll-padding-top: 90px; }
        :root {
            --dark:    #0a0f1e;
            --dark2:   #111827;
            --light:   #eef2fa;
            --primary: #1a56db;
            --accent:  #3b82f6;
            --black:   #060c18;
        }
        body { font-family:'Outfit',sans-serif; background:var(--dark); overflow-x:hidden; color:#fff; }
        .fd { font-family:'Bricolage Grotesque',sans-serif; }
        .fm { font-family:'Geist Mono',monospace; }

        /* ─ Scroll reveal ─ */
        .sr { opacity:0; transform:translateY(24px); transition:opacity 0.6s ease, transform 0.6s ease; }
        .sr.in { opacity:1; transform:translateY(0); }
        .sr-left  { opacity:0; transform:translateX(-28px) scale(0.97); transition:opacity 0.7s cubic-bezier(.22,.68,0,1.05), transform 0.7s cubic-bezier(.22,.68,0,1.05); }
        .sr-left.in  { opacity:1; transform:translateX(0) scale(1); }
        .sr-right { opacity:0; transform:translateX(28px) scale(0.97);  transition:opacity 0.7s cubic-bezier(.22,.68,0,1.05), transform 0.7s cubic-bezier(.22,.68,0,1.05); }
        .sr-right.in { opacity:1; transform:translateX(0) scale(1); }
        .d1{transition-delay:.08s} .d2{transition-delay:.16s} .d3{transition-delay:.24s}
        .d4{transition-delay:.32s} .d5{transition-delay:.40s} .d6{transition-delay:.48s}

        /* ─ Hero fade-up ─ */
        @keyframes up { from{opacity:0;transform:translateY(18px)} to{opacity:1;transform:translateY(0)} }
        .hu  { animation:up 0.65s ease both; }
        .hu1 { animation-delay:.06s } .hu2 { animation-delay:.18s } .hu3 { animation-delay:.30s }
        .hu4 { animation-delay:.44s } .hu5 { animation-delay:.58s }

        /* ─ Hero mockup float ─ */
        @keyframes float { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-10px)} }
        .mock-float { animation:float 5.5s ease-in-out 1.4s infinite; }

        /* ─ Hero entry rows stagger in ─ */
        @keyframes rowIn { from{opacity:0;transform:translateX(12px)} to{opacity:1;transform:translateX(0)} }
        .ri  { animation:rowIn 0.4s ease both; }
        .ri1 { animation-delay:.60s } .ri2 { animation-delay:.75s } .ri3 { animation-delay:.90s }
        .ri4 { animation-delay:1.05s } .ri5 { animation-delay:1.20s } .ri6 { animation-delay:1.35s }

        /* ─ Cursor blink ─ */
        @keyframes blink { 0%,49%{opacity:1} 50%,100%{opacity:0} }
        .cursor { display:inline-block; width:2.5px; height:0.7em; background:var(--accent); margin-left:2px; vertical-align:text-bottom; animation:blink 1.1s step-end 1s infinite; }

        /* ─ AI field fill (triggers when parent .sr-left gets .in) ─ */
        @keyframes fieldIn { from{opacity:0;transform:translateY(7px)} to{opacity:1;transform:translateY(0)} }
        .fi { opacity:0; }
        .sr-left.in .fi                { animation:fieldIn .35s ease both; }
        .sr-left.in .fi1 { animation-delay:.10s; }
        .sr-left.in .fi2 { animation-delay:.45s; }
        .sr-left.in .fi3 { animation-delay:.80s; }
        .sr-left.in .fi4 { animation-delay:1.15s; }

        /* ─ Balance live flicker ─ */
        @keyframes balPulse { 0%,80%,100%{opacity:1} 85%{opacity:.45} }
        .bal-pulse { animation:balPulse 5s ease-in-out 2.5s infinite; }

        /* ─ Updated label slide ─ */
        @keyframes updateSlide { 0%,70%,100%{opacity:1;transform:translateX(0)} 75%{opacity:0;transform:translateX(-8px)} 80%{opacity:0;transform:translateX(8px)} }
        .update-anim { animation:updateSlide 7s ease-in-out 3s infinite; }

        /* ─ Marquee ─ */
        @keyframes mL { from{transform:translateX(0)} to{transform:translateX(-50%)} }
        @keyframes mR { from{transform:translateX(-50%)} to{transform:translateX(0)} }
        .ml { animation:mL 32s linear infinite; }
        .mr { animation:mR 36s linear infinite; }
        .mp:hover .ml, .mp:hover .mr { animation-play-state:paused; }

        /* ─ Bar chart animate ─ */
        .bar { transition:height 0.8s cubic-bezier(.22,.68,0,1.2); transition-delay:var(--bd,0s); }
        .bars-hidden .bar { height:2px !important; }

        /* ─ Pain card hover ─ */
        .pain-card { transition:border-color 0.2s, transform 0.2s, box-shadow 0.2s; }
        .pain-card:hover { border-color:rgba(59,130,246,0.4) !important; transform:translateY(-3px); box-shadow:0 12px 40px rgba(0,0,0,0.4); }

        /* ─ Comparison table ─ */
        .cf-col { background:rgba(59,130,246,0.07); border-color:rgba(59,130,246,0.35) !important; }

        /* ─ Buttons ─ */
        .btn-primary   { background:var(--primary); color:#fff; font-weight:600; transition:opacity .18s,transform .18s,box-shadow .18s; }
        .btn-primary:hover { opacity:.88; transform:scale(1.02); box-shadow:0 8px 24px rgba(26,86,219,.35); }
        .btn-ghost     { border:1px solid rgba(255,255,255,0.18); color:#f8fafc; transition:background .18s; }
        .btn-ghost:hover { background:rgba(255,255,255,0.07); }
        .btn-ghost-light { border:1px solid rgba(15,23,42,0.2); color:#0f172a; transition:background .18s; }
        .btn-ghost-light:hover { background:rgba(15,23,42,0.06); }

        /* ─ Entry row ─ */
        .erow:hover { background:rgba(255,255,255,0.03); }

        /* ─ Noise texture ─ */
        .noise::after { content:''; position:absolute; inset:0; background-image:url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E"); pointer-events:none; opacity:0.3; }

        /* ─ Glow pulse ─ */
        @keyframes glowPulse { 0%,100%{opacity:.55} 50%{opacity:1} }
        .glow-pulse { animation:glowPulse 7s ease-in-out infinite; }

        /* ─ Nav island (always) ─ */
        #nav-wrapper { padding:12px 5% 0; }
        #main-nav { border-radius:20px; border:1px solid rgba(255,255,255,0.13); background:rgba(7,11,22,0.92); backdrop-filter:blur(24px); -webkit-backdrop-filter:blur(24px); box-shadow:0 4px 6px rgba(0,0,0,0.1),0 16px 48px rgba(0,0,0,0.4),inset 0 1px 0 rgba(255,255,255,0.07); }
        @media(max-width:640px){ #nav-wrapper { padding:8px 3% 0; } #main-nav { border-radius:16px; } }

        /* ─ Hero email form ─ */
        .hero-form { position:relative; }
        .hero-form input { background:rgba(255,255,255,0.07); border:1px solid rgba(255,255,255,0.15); color:#fff; border-radius:9999px; font-family:'Outfit',sans-serif; transition:border-color 0.2s, background 0.2s; }
        .hero-form input::placeholder { color:rgba(248,250,252,0.35); }
        .hero-form input:focus { outline:none; border-color:rgba(59,130,246,0.6); background:rgba(255,255,255,0.1); }

        /* ─ Step connector ─ */
        .step-connector { position:absolute; top:28px; left:calc(50% + 32px); right:calc(-50% + 32px); height:1px; background:linear-gradient(to right,rgba(26,86,219,0.4),rgba(26,86,219,0.1)); }

        /* ─ Stat counter ─ */
        @keyframes countPop { 0%{transform:scale(0.7);opacity:0} 60%{transform:scale(1.08)} 100%{transform:scale(1);opacity:1} }
        .stat-pop { opacity:0; }
        .stat-pop.in { animation:countPop 0.55s cubic-bezier(.22,.68,0,1.2) both; }

        /* ─ Comparison modern ─ */
        .cmp-card { transition:transform 0.2s, box-shadow 0.2s; }
        .cmp-card:hover { transform:translateY(-4px); box-shadow:0 20px 60px rgba(0,0,0,0.4); }
        .cmp-cf { box-shadow:0 0 0 1px rgba(59,130,246,0.5), 0 20px 60px rgba(26,86,219,0.18); }

        /* ─ FAQ light theme ─ */
        .faq-light .faq-item { background:#fff; border-color:rgba(15,23,42,0.1) !important; }
        .faq-light .faq-q { color:#0f172a; }
        .faq-light .faq-a { color:rgba(15,23,42,0.6); }
        .faq-light .faq-icon { color:rgba(15,23,42,0.3); }

        /* ─ Feature row hover ─ */
        .feat-row { transition:background 0.15s; }
        .feat-row:hover { background:rgba(59,130,246,0.04); }

        /* ─ Pulse dot ─ */
        @keyframes pingOnce { 0%{transform:scale(1);opacity:1} 100%{transform:scale(2.5);opacity:0} }
        .ping-once { animation:pingOnce 1.5s ease-out 2s forwards; }
    </style>
</head>
<body>

{{-- ══ NAV ═══════════════════════════════════════════════════════════ --}}
<div id="nav-wrapper" class="sticky top-0 z-50">
    <nav id="main-nav" class="px-6 md:px-8 py-3.5 flex items-center justify-between">
        <a href="{{ route('home') }}" class="flex items-center gap-2.5">
            @if(file_exists(public_path('brand/logo-dark.png')))
                <img src="{{ asset('brand/logo-dark.png') }}?v={{ filemtime(public_path('brand/logo-dark.png')) }}"
                     alt="{{ config('app.name', 'CashFlow') }}" class="h-7 w-auto object-contain">
            @else
                <div class="w-7 h-7 rounded-lg flex items-center justify-center" style="background:var(--primary)">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none">
                        <path d="M3 17l4-8 4 4 4-6 4 4" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <span class="fd font-bold text-base text-white">{{ config('app.name', 'CashFlow') }}</span>
            @endif
        </a>
        <div class="hidden md:flex items-center gap-8">
            @foreach([['#pain','Problem'],['#how','How it works'],['#features','Features'],['#pricing','Pricing']] as [$h,$l])
            <a href="{{ $h }}" class="text-sm"
               style="color:rgba(248,250,252,0.5);transition:color .15s"
               onmouseover="this.style.color='rgba(248,250,252,.9)'"
               onmouseout="this.style.color='rgba(248,250,252,.5)'">{{ $l }}</a>
            @endforeach
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('login') }}"    class="text-sm px-4 py-2 rounded-full btn-ghost">Sign in</a>
            <a href="{{ route('register') }}" class="text-sm px-5 py-2 rounded-full btn-primary">Get started free</a>
        </div>
    </nav>
</div>


{{-- ══ HERO ════════════════════════════════════════════════════════════ --}}
<section style="background:var(--dark)" class="noise relative overflow-hidden">
    <div class="absolute inset-0 pointer-events-none overflow-hidden">
        <div class="absolute -top-40 left-1/4 w-[600px] h-[400px] rounded-full blur-3xl" style="background:rgba(26,86,219,0.13)"></div>
        <div class="absolute top-1/3 right-0 w-80 h-80 rounded-full blur-3xl" style="background:rgba(59,130,246,0.07)"></div>
    </div>
    <div class="relative max-w-7xl mx-auto px-6 md:px-12 pt-16 md:pt-24 grid grid-cols-1 md:grid-cols-2 gap-10 md:gap-16 items-center">

        {{-- Left: copy --}}
        <div class="pb-12 md:pb-24">
            {{-- Trust signal --}}
            <div class="hu hu1 flex items-center gap-3 mb-6 flex-wrap">
                <div class="flex items-center gap-1">
                    @for($i=0;$i<5;$i++)
                    <svg class="w-4 h-4" viewBox="0 0 20 20" fill="#f59e0b"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    @endfor
                </div>
                <span class="text-sm" style="color:rgba(248,250,252,0.5)">Trusted by <strong style="color:#f8fafc">1,200+ businesses</strong> in 60+ countries</span>
            </div>

            <h1 class="fd font-black leading-[0.92] tracking-tight mb-6 hu hu2"
                style="color:#f8fafc;font-size:clamp(2.8rem,5vw,4.75rem)">
                Your business<br>balance. <span style="color:var(--accent)">Live.<br>Always.<span class="cursor"></span></span>
            </h1>

            <p class="text-lg leading-relaxed mb-9 hu hu3 max-w-md" style="color:rgba(248,250,252,0.5)">
                Record a transaction in 10 seconds. Balance updates instantly. Share live read access with your accountant — no Excel, no phone calls, no month-end panic.
            </p>

            <form action="{{ route('register') }}" method="GET" class="hero-form flex flex-col sm:flex-row gap-2 mb-5 hu hu4" style="max-width:440px">
                <input type="email" name="email" placeholder="Enter your work email"
                       class="flex-1 px-5 py-3.5 text-sm"
                       style="min-width:0">
                <button type="submit" class="font-semibold px-6 py-3.5 rounded-full text-sm btn-primary whitespace-nowrap flex-shrink-0">
                    Get started free →
                </button>
            </form>
            <p class="text-xs hu hu5 flex items-center gap-4 flex-wrap" style="color:rgba(248,250,252,0.28)">
                <span>✓ Free forever plan</span>
                <span>✓ No credit card</span>
                <span>✓ Set up in 2 min</span>
            </p>
        </div>

        {{-- Right: live ledger mockup --}}
        <div class="relative hu hu3 md:-mt-16">
            <div class="mock-float rounded-t-2xl overflow-hidden shadow-2xl" style="background:#0d1526;border:1px solid rgba(255,255,255,0.1);border-bottom:none">
                <div class="flex items-center justify-between px-4 py-3" style="border-bottom:1px solid rgba(255,255,255,0.07)">
                    <div class="flex gap-1.5">
                        <div class="w-3 h-3 rounded-full" style="background:#ff5f57"></div>
                        <div class="w-3 h-3 rounded-full" style="background:#ffbd2e"></div>
                        <div class="w-3 h-3 rounded-full" style="background:#28c840"></div>
                    </div>
                    <span class="fm text-xs" style="color:rgba(248,250,252,0.3)">March 2026 · Acme Agency</span>
                    <span class="text-xs font-semibold px-2 py-0.5 rounded" style="background:rgba(74,222,128,0.15);color:#4ade80">● Live</span>
                </div>
                <div class="grid grid-cols-3" style="border-bottom:1px solid rgba(255,255,255,0.07)">
                    @foreach([['Cash In','#4ade80','$12,430'],['Cash Out','#f87171','$3,840'],['Net Balance','#f8fafc','$8,590']] as [$l,$c,$v])
                    <div class="px-4 py-3" style="{{ !$loop->last ? 'border-right:1px solid rgba(255,255,255,0.07)' : '' }}">
                        <p class="text-[10px] uppercase tracking-widest mb-1" style="color:rgba(248,250,252,0.3)">{{ $l }}</p>
                        <p class="fm font-bold text-sm {{ $l==='Net Balance'?'bal-pulse':'' }}" style="color:{{ $c }}">{{ $v }}</p>
                    </div>
                    @endforeach
                </div>
                @foreach([
                    ['in',  'Client Payment — Acme Corp.',  '2,400', 'Consulting', 'Mar 22'],
                    ['out', 'Office Rent — March',          '1,200', 'Rent',       'Mar 21'],
                    ['in',  'Freelance Invoice #44',        '3,800', 'Services',   'Mar 20'],
                    ['out', 'Team Salaries',                '2,400', 'Payroll',    'Mar 19'],
                    ['in',  'Product Sales — Batch 3',     '4,800', 'Sales',      'Mar 18'],
                    ['out', 'Software & Tools',             '240',   'SaaS',       'Mar 17'],
                ] as $idx => [$t,$d,$a,$c,$dt])
                <div class="ri ri{{ $idx+1 }} erow flex items-center justify-between px-4 py-3 transition-colors" style="border-bottom:1px solid rgba(255,255,255,0.04)">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-6 h-6 rounded-lg flex items-center justify-center flex-shrink-0"
                             style="background:{{ $t==='in'?'rgba(74,222,128,0.12)':'rgba(248,113,113,0.12)' }}">
                            <span class="text-xs font-bold" style="color:{{ $t==='in'?'#4ade80':'#f87171' }}">{{ $t==='in'?'+':'−' }}</span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs truncate" style="color:rgba(248,250,252,0.75)">{{ $d }}</p>
                            <p class="text-[10px] mt-0.5" style="color:rgba(248,250,252,0.28)">{{ $c }} · {{ $dt }}</p>
                        </div>
                    </div>
                    <span class="fm text-xs font-bold flex-shrink-0 ml-3" style="color:{{ $t==='in'?'#4ade80':'#f87171' }}">
                        {{ $t==='in'?'+':'−' }}${{ $a }}
                    </span>
                </div>
                @endforeach
                <div class="flex items-center justify-between px-4 py-2.5" style="background:rgba(255,255,255,0.02)">
                    <span class="text-[10px]" style="color:rgba(248,250,252,0.25)">6 of 43 entries</span>
                    <span class="update-anim text-[10px]" style="color:var(--accent)">↑ Updated just now</span>
                </div>
            </div>
        </div>

    </div>
</section>


{{-- ══ MARQUEE — currencies + business types ═══════════════════════════ --}}
<section style="background:#080e1c;border-top:1px solid rgba(255,255,255,0.05);border-bottom:1px solid rgba(255,255,255,0.05)" class="py-3 overflow-hidden">
    {{-- Row 1: currencies --}}
    <div class="overflow-hidden mp mb-2.5">
        <div class="ml flex gap-8 whitespace-nowrap" style="min-width:200%">
            @php
            $currencies=[
                ['$','USD','US Dollar'],['€','EUR','Euro'],['£','GBP','British Pound'],
                ['¥','JPY','Japanese Yen'],['AED','AED','UAE Dirham'],['₹','INR','Indian Rupee'],
                ['₦','NGN','Nigerian Naira'],['R$','BRL','Brazilian Real'],['CA$','CAD','Canadian Dollar'],
                ['CHF','CHF','Swiss Franc'],['A$','AUD','Australian Dollar'],['kr','SEK','Swedish Krona'],
                ['₺','TRY','Turkish Lira'],['zł','PLN','Polish Zloty'],['₱','PHP','Philippine Peso'],
                ['Rp','IDR','Indonesian Rupiah'],['RM','MYR','Malaysian Ringgit'],['฿','THB','Thai Baht'],
            ];
            @endphp
            @foreach(array_merge($currencies,$currencies) as $c)
            <span class="flex items-center gap-2 text-xs" style="color:rgba(255,255,255,0.28)">
                <span class="fm font-bold text-sm" style="color:rgba(59,130,246,0.7)">{{ $c[0] }}</span>
                <span style="color:rgba(255,255,255,0.18)">{{ $c[2] }}</span>
            </span>
            @endforeach
        </div>
    </div>
    {{-- Row 2: business types (reverse direction) --}}
    <div class="overflow-hidden mp">
        <div class="mr flex gap-8 whitespace-nowrap" style="min-width:200%">
            @php $tags=['Retail Shops','Freelancers','Restaurants','Agencies','Wholesalers','Consultants','E-commerce','Real Estate','Startups','Import/Export','Finance Teams','Partnerships','Law Firms','Salons & Spas','Food Trucks','Photography Studios']; @endphp
            @foreach(array_merge($tags,$tags) as $tag)
            <span class="text-xs font-medium flex items-center gap-2" style="color:rgba(255,255,255,0.25)">
                <span class="w-1 h-1 rounded-full inline-block flex-shrink-0" style="background:rgba(59,130,246,0.5)"></span>{{ $tag }}
            </span>
            @endforeach
        </div>
    </div>
</section>


{{-- ══ PAIN — "Sound familiar?" ══════════════════════════════════════ --}}
<section id="pain" style="background-color:var(--black);background-image:radial-gradient(circle,rgba(59,130,246,0.038) 1.5px,transparent 1.5px);background-size:28px 28px" class="relative overflow-hidden px-6 py-24 md:py-32">
    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[700px] h-56 rounded-full blur-3xl pointer-events-none glow-pulse" style="background:rgba(26,86,219,0.1)"></div>
    <div class="relative max-w-5xl mx-auto">
        <div class="text-center mb-14">
            <p class="sr text-xs font-semibold uppercase tracking-widest mb-4" style="color:rgba(255,255,255,0.35)">Before {{ config('app.name', 'CashFlow') }}</p>
            <h2 class="sr d1 fd font-black leading-tight" style="color:#fff;font-size:clamp(2.2rem,4.5vw,3.5rem)">
                Sound familiar?
            </h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @foreach([
                [
                    '"Was February profitable?"',
                    'It\'s Sunday night. You open your phone, check WhatsApp messages, pull up the notebook on your desk. An hour later, three tabs open, you still aren\'t sure. Your accountant wants the numbers by Monday.',
                    'M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z'
                ],
                [
                    '"I need the Excel file."',
                    'Your accountant called again. You find the file on your old laptop. Half of March is missing. You spend your afternoon reconstructing transactions from memory, bank screenshots, and WhatsApp messages.',
                    'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z'
                ],
                [
                    '"There\'s $340 missing somewhere."',
                    'Month-end. Something doesn\'t add up. You check your bag, your car, your phone camera roll. You find a crumpled receipt and a screenshot that might be a bill. You write down your best guess and call it done.',
                    'M9 14.25l6-6m4.5-3.493V21.75l-3.75-1.5-3.75 1.5-3.75-1.5-3.75 1.5V4.757c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0c1.1.128 1.907 1.077 1.907 2.185z'
                ],
            ] as [$q,$body,$icon])
            <div class="sr pain-card rounded-2xl p-6" style="background:#0d1526;border:1px solid rgba(255,255,255,0.07)">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-5" style="background:rgba(255,255,255,0.06)">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" style="color:rgba(255,255,255,0.4)">
                        <path d="{{ $icon }}" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <p class="fd font-bold text-base mb-3" style="color:#fff">{{ $q }}</p>
                <p class="text-sm leading-relaxed" style="color:rgba(255,255,255,0.42)">{{ $body }}</p>
            </div>
            @endforeach
        </div>
        <div class="text-center mt-12 sr d3">
            <p class="text-base" style="color:rgba(255,255,255,0.4)">{{ config('app.name', 'CashFlow') }} solves all three. Setup takes <strong style="color:#f8fafc">2 minutes.</strong></p>
        </div>
    </div>
</section>


{{-- ══ HOW IT WORKS — 3 steps ═════════════════════════════════════════ --}}
<section id="how" style="background:linear-gradient(140deg,var(--dark2) 0%,#0c1a2e 50%,var(--dark2) 100%)" class="relative overflow-hidden px-6 py-24 md:py-32">
    <div class="absolute bottom-0 right-0 w-[500px] h-[300px] rounded-full blur-3xl pointer-events-none" style="background:rgba(26,86,219,0.07)"></div>
    <div class="relative max-w-4xl mx-auto">
        <div class="text-center mb-20">
            <p class="sr text-xs font-semibold uppercase tracking-widest mb-4" style="color:rgba(255,255,255,0.25)">Simple by design</p>
            <h2 class="sr d1 fd font-black leading-tight" style="color:#f8fafc;font-size:clamp(2.2rem,4.5vw,3.5rem)">
                Up and running<br>in 2 minutes.
            </h2>
        </div>

        {{-- Steps with centred connector --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-10 md:gap-6 relative">

            {{-- connector line desktop --}}
            <div class="hidden md:block absolute" style="top:28px;left:calc(16.66% + 28px);right:calc(16.66% + 28px);height:1px;background:linear-gradient(to right,rgba(59,130,246,0.4),rgba(59,130,246,0.15),rgba(59,130,246,0.4))"></div>

            @foreach([
                ['1','Create a book','Name it anything — "March 2026", "Q2 Project", "Client Work". One book per period, project, or whatever makes sense for you.','M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25'],
                ['2','Add your transactions','Cash in. Cash out. Takes 10 seconds. Or take a photo of a receipt — the app reads it and fills the form for you.','M12 4.5v15m7.5-7.5h-15'],
                ['3','See your numbers live','Balance updates the moment you save. Share live read access with your accountant or team — they see exactly what you see.','M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z'],
            ] as [$num,$title,$body,$icon])
            <div class="sr d{{ $loop->index + 1 }} flex flex-col items-center text-center">
                {{-- numbered circle --}}
                <div class="w-14 h-14 rounded-full flex items-center justify-center mb-6 relative z-10 flex-shrink-0"
                     style="background:#0d1526;border:2px solid rgba(59,130,246,0.35);box-shadow:0 0 0 6px rgba(59,130,246,0.06)">
                    <span class="fd font-black text-xl" style="color:var(--accent)">{{ $num }}</span>
                </div>
                {{-- icon --}}
                <div class="w-11 h-11 rounded-2xl flex items-center justify-center mb-5" style="background:var(--primary)">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" style="color:#fff">
                        <path d="{{ $icon }}" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h3 class="fd font-bold text-xl mb-3" style="color:#f8fafc">{{ $title }}</h3>
                <p class="text-sm leading-relaxed" style="color:rgba(248,250,252,0.45)">{{ $body }}</p>
            </div>
            @endforeach
        </div>

        <div class="text-center mt-16 sr d4">
            <a href="{{ route('register') }}" class="inline-flex items-center gap-2 font-semibold px-7 py-3.5 rounded-full btn-primary text-base">
                Try it now — it's free
            </a>
            <p class="mt-4 text-xs" style="color:rgba(248,250,252,0.25)">2 min setup · no tutorial needed</p>
        </div>
    </div>
</section>


{{-- ══ FEATURE 1 — books & balance ════════════════════════════════════ --}}
<section id="features" style="background:var(--dark2)" class="relative overflow-hidden px-6 py-24 md:py-32">
    <div class="absolute -top-20 -left-20 w-96 h-96 rounded-full blur-3xl pointer-events-none glow-pulse" style="background:rgba(26,86,219,0.08)"></div>
    <div class="relative max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-16 items-center">
        <div class="sr-left">
            <p class="text-xs font-semibold uppercase tracking-widest mb-4" style="color:var(--accent)">Core feature</p>
            <h2 class="fd font-black leading-tight mb-5" style="color:#f8fafc;font-size:clamp(2rem,3.5vw,3rem)">
                Every business.<br>Every book.<br>One dashboard.
            </h2>
            <p class="text-lg leading-relaxed mb-8" style="color:rgba(248,250,252,0.5);max-width:420px">
                Create a separate book for each month, quarter, or project. Your balance — opening + cash in − cash out — is always calculated for you. No formulas.
            </p>
            <ul class="space-y-3">
                @foreach(['Live balance updates on every entry','Separate books per month, quarter, or project','Multiple businesses on one dashboard','Search, filter, and export entries instantly'] as $f)
                <li class="flex items-center gap-3 text-sm" style="color:rgba(248,250,252,0.65)">
                    <span class="w-5 h-5 rounded-full flex items-center justify-center flex-shrink-0" style="background:rgba(59,130,246,0.15)">
                        <svg class="w-3 h-3" viewBox="0 0 12 12" fill="none"><path d="M2 6l3 3 5-5" stroke="var(--accent)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>{{ $f }}
                </li>
                @endforeach
            </ul>
        </div>
        <div class="sr-right rounded-2xl overflow-hidden shadow-xl" style="background:#0d1526;border:1px solid rgba(255,255,255,0.09)">
            <div class="px-5 py-4" style="border-bottom:1px solid rgba(255,255,255,0.07)">
                <p class="font-semibold text-sm" style="color:#f8fafc">Eveso Business</p>
                <p class="text-xs mt-0.5" style="color:rgba(248,250,252,0.35)">3 books this year</p>
            </div>
            @foreach([['March 2026','8,590','43 entries','This month',true],['February 2026','5,240','38 entries','Last month',false],['Q1 Project 2026','2,180','12 entries','Special project',false]] as [$n,$net,$cnt,$sub,$active])
            <div class="flex items-center justify-between px-5 py-4" style="border-bottom:1px solid rgba(255,255,255,0.05)">
                <div class="flex items-center gap-3">
                    <span class="w-2 h-2 rounded-full flex-shrink-0" style="background:{{ $active?'#4ade80':'rgba(255,255,255,0.15)' }}"></span>
                    <div>
                        <p class="font-semibold text-sm" style="color:#f8fafc">{{ $n }}</p>
                        <p class="text-xs mt-0.5" style="color:rgba(248,250,252,0.3)">{{ $cnt }} · {{ $sub }}</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="fm font-bold text-sm" style="color:{{ $active?'#4ade80':'rgba(248,250,252,0.4)' }}">${{ $net }}</p>
                    <p class="text-[10px] mt-0.5" style="color:rgba(248,250,252,0.25)">net balance</p>
                </div>
            </div>
            @endforeach
            <div class="px-5 py-3.5 flex items-center justify-between" style="background:rgba(255,255,255,0.02)">
                <span class="text-xs" style="color:rgba(248,250,252,0.3)">↑ $16,010 total across all books</span>
            </div>
        </div>
    </div>
</section>


{{-- ══ FEATURE 2 — AI OCR ════════════════════════════════════════════ --}}
<section style="background:var(--black)" class="relative overflow-hidden px-6 py-24 md:py-32">
    <div class="absolute -top-16 right-0 w-[500px] h-80 rounded-full blur-3xl pointer-events-none glow-pulse" style="background:rgba(59,130,246,0.07)"></div>
    <div class="absolute bottom-0 left-0 w-80 h-64 rounded-full blur-3xl pointer-events-none" style="background:rgba(26,86,219,0.06)"></div>
    <div class="relative max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-16 items-center">
        {{-- Mockup left --}}
        <div class="sr-left order-2 md:order-1 rounded-2xl overflow-hidden shadow-2xl" style="background:#080e1c;border:1px solid rgba(59,130,246,0.2)">
            <div class="p-5" style="border-bottom:1px solid rgba(255,255,255,0.06)">
                <div class="flex items-center gap-2.5 mb-4">
                    <div class="w-7 h-7 rounded-lg flex items-center justify-center" style="background:rgba(59,130,246,0.18)">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none">
                            <path d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" stroke="var(--accent)" stroke-width="1.5" stroke-linecap="round"/>
                            <path d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z" stroke="var(--accent)" stroke-width="1.5" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold" style="color:#f8fafc">Receipt read</p>
                        <p class="text-xs" style="color:rgba(248,250,252,0.35)">Form filled automatically</p>
                    </div>
                    <span class="ml-auto text-[10px] font-bold px-2 py-0.5 rounded-full" style="background:rgba(59,130,246,0.18);color:var(--accent)">Done in 2s</span>
                </div>
                <div class="rounded-xl overflow-hidden" style="border:1px solid rgba(255,255,255,0.07)">
                    @foreach([['Amount','$45.00','#4ade80','✓ Filled','fi1'],['Date','March 22, 2026','#4ade80','✓ Filled','fi2'],['What was it for','Office supplies — Staples','#4ade80','✓ Filled','fi3'],['Category','Supplies','var(--accent)','Suggested','fi4']] as [$f,$v,$c,$badge,$fi])
                    <div class="fi {{ $fi }} flex items-center justify-between px-4 py-3" style="border-bottom:1px solid rgba(255,255,255,0.04)">
                        <div>
                            <p class="text-[10px] mb-0.5" style="color:rgba(248,250,252,0.3)">{{ $f }}</p>
                            <p class="text-sm font-medium" style="color:#f8fafc">{{ $v }}</p>
                        </div>
                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded" style="background:rgba(59,130,246,0.12);color:var(--accent)">{{ $badge }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
            <div class="px-5 py-4 flex items-center justify-between">
                <p class="text-xs" style="color:rgba(248,250,252,0.3)">Receipt photo attached to entry</p>
                <a class="text-xs font-semibold px-3 py-1.5 rounded-lg btn-primary cursor-default">Save Entry</a>
            </div>
        </div>
        <div class="sr-right order-1 md:order-2">
            <p class="text-xs font-semibold uppercase tracking-widest mb-4" style="color:var(--accent)">Receipt photo → entry in 2 seconds</p>
            <h2 class="fd font-black leading-tight mb-5" style="color:#f8fafc;font-size:clamp(2rem,3.5vw,3rem)">
                Take a photo.<br><span style="color:var(--accent)">We fill the form.</span>
            </h2>
            <p class="text-lg leading-relaxed mb-6" style="color:rgba(248,250,252,0.5);max-width:420px;line-height:1.65">
                Point your phone at any receipt. The app reads the amount, what it was for, and the date — then fills in the entry for you. You tap Save. That's it. Works on paper receipts, invoices, and digital screenshots.
            </p>
            <ul class="space-y-2.5 mb-6">
                @foreach(['Works with any receipt in any language or currency','Photo is saved and attached to the entry automatically','Suggests the right category — you can always change it'] as $f)
                <li class="flex items-center gap-3 text-sm" style="color:rgba(248,250,252,0.55)">
                    <span class="w-5 h-5 rounded-full flex items-center justify-center flex-shrink-0" style="background:rgba(59,130,246,0.15)">
                        <svg class="w-3 h-3" viewBox="0 0 12 12" fill="none"><path d="M2 6l3 3 5-5" stroke="var(--accent)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>{{ $f }}
                </li>
                @endforeach
            </ul>
        </div>
    </div>
</section>


{{-- ══ FEATURE 3 — team access ════════════════════════════════════════ --}}
<section style="background:var(--dark)" class="relative overflow-hidden px-6 py-24 md:py-32">
    <div class="absolute top-1/2 -translate-y-1/2 -right-20 w-96 h-96 rounded-full blur-3xl pointer-events-none glow-pulse" style="background:rgba(26,86,219,0.08)"></div>
    <div class="relative max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-16 items-center">
        <div class="sr-left">
            <p class="text-xs font-semibold uppercase tracking-widest mb-4" style="color:rgba(255,255,255,0.3)">Teams &amp; accountants</p>
            <h2 class="fd font-black leading-tight mb-5" style="color:#f8fafc;font-size:clamp(2rem,3.5vw,3rem)">
                Your accountant<br>stops calling you<br>for numbers.
            </h2>
            <p class="text-lg leading-relaxed mb-8" style="color:rgba(248,250,252,0.5);max-width:420px">
                Invite them as a viewer. They log in and see everything — live. No Excel file to email. No WhatsApp photo of a handwritten ledger. No month-end panic.
            </p>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                @foreach([['Owner','Full access · settings · billing'],['Editor','Add &amp; edit entries'],['Viewer','Read-only · perfect for accountants']] as [$r,$d])
                <div class="rounded-xl p-4" style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.07)">
                    <p class="fd font-bold text-sm mb-1.5" style="color:#f8fafc">{{ $r }}</p>
                    <p class="text-xs leading-relaxed" style="color:rgba(248,250,252,0.4)">{!! $d !!}</p>
                </div>
                @endforeach
            </div>
        </div>
        <div class="sr-right rounded-2xl overflow-hidden shadow-xl" style="background:#0d1526;border:1px solid rgba(255,255,255,0.09)">
            <div class="px-5 py-4" style="border-bottom:1px solid rgba(255,255,255,0.08)">
                <p class="font-semibold text-sm" style="color:#f8fafc">Team — Eveso Business</p>
            </div>
            @foreach([['A','Alex (You)','Owner','Active now'],['S','Sarah Kim','Editor','Added 3 entries today'],['J','Jordan Lee','Viewer','Last seen 2h ago'],['R','Rivera & Co.','Viewer','Accountant · read-only']] as [$i,$n,$r,$note])
            <div class="flex items-center gap-3 px-5 py-3.5" style="border-bottom:1px solid rgba(255,255,255,0.05)">
                <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 font-bold text-xs" style="background:rgba(59,130,246,0.15);color:var(--accent)">{{ $i }}</div>
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-sm truncate" style="color:#f8fafc">{{ $n }}</p>
                    <p class="text-xs truncate" style="color:rgba(248,250,252,0.3)">{{ $note }}</p>
                </div>
                <span class="text-[10px] font-semibold px-2 py-0.5 rounded flex-shrink-0"
                      style="background:{{ $r==='Owner'?'rgba(59,130,246,0.15)':'rgba(255,255,255,0.07)' }};color:{{ $r==='Owner'?'var(--accent)':'rgba(248,250,252,0.45)' }}">{{ $r }}</span>
            </div>
            @endforeach
        </div>
    </div>
</section>


{{-- ══ OUTCOMES STRIP ══════════════════════════════════════════════════ --}}
<section style="background:linear-gradient(to bottom,var(--black),#081525 50%,var(--black))" class="relative overflow-hidden px-6 py-20 md:py-24">
    <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
        <div class="w-[700px] h-48 rounded-full blur-3xl glow-pulse" style="background:rgba(26,86,219,0.09)"></div>
    </div>
    <div class="relative max-w-5xl mx-auto">
        <p class="sr text-center text-xs font-semibold uppercase tracking-widest mb-12" style="color:rgba(255,255,255,0.2)">What users tell us after 30 days</p>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach([
                ['"I saved 3 hours a month I used to spend on reconciliation."','2–3 hrs/month saved','on average'],
                ['"Month-end took 40 minutes before. Now I check in 2 minutes."','38 min → 2 min','month-end review'],
                ['"My accountant stopped calling. That alone made it worth it."','Zero calls','from accountant'],
            ] as [$quote,$stat,$sub])
            <div class="sr rounded-2xl p-6" style="background:#0d1526;border:1px solid rgba(255,255,255,0.07)">
                <p class="text-sm leading-relaxed mb-5" style="color:rgba(248,250,252,0.5)">{{ $quote }}</p>
                <div style="border-top:1px solid rgba(255,255,255,0.07)" class="pt-4">
                    <p class="fd font-black text-2xl mb-0.5" style="color:var(--accent)">{{ $stat }}</p>
                    <p class="text-xs" style="color:rgba(248,250,252,0.3)">{{ $sub }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>


{{-- ══ TESTIMONIALS ════════════════════════════════════════════════════ --}}
<section style="background:var(--dark2)" class="relative overflow-hidden px-6 py-20 md:py-28">
    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[800px] h-60 rounded-full blur-3xl pointer-events-none glow-pulse" style="background:rgba(26,86,219,0.09)"></div>
    <div class="relative max-w-5xl mx-auto">
        <div class="text-center mb-14">
            <p class="sr text-xs font-semibold uppercase tracking-widest mb-4" style="color:rgba(255,255,255,0.25)">Real users. Real businesses.</p>
            <h2 class="sr d1 fd font-black leading-tight" style="color:#f8fafc;font-size:clamp(2rem,4vw,3rem)">
                What they said after switching.
            </h2>
        </div>

        {{-- 3 large static featured quotes --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            {{-- Quote 1 --}}
            <div class="sr rounded-2xl p-7 flex flex-col" style="background:#0d1526;border:1px solid rgba(59,130,246,0.25);box-shadow:0 0 40px rgba(26,86,219,0.08)">
                <svg class="w-8 h-8 mb-5 flex-shrink-0" viewBox="0 0 32 32" fill="none">
                    <path d="M9 13h6l-4 8H7l2-8zm10 0h6l-4 8h-4l2-8z" fill="rgba(59,130,246,0.3)"/>
                </svg>
                <p class="text-base leading-relaxed mb-6 flex-1" style="color:rgba(248,250,252,0.75)">"My accountant has viewer access now. He stopped calling me for numbers every month. <strong style="color:#f8fafc">That alone is worth the subscription.</strong>"</p>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm flex-shrink-0" style="background:rgba(26,86,219,0.3);color:var(--accent)">B</div>
                    <div>
                        <p class="font-semibold text-sm" style="color:#f8fafc">Ben K.</p>
                        <p class="text-xs" style="color:rgba(248,250,252,0.35)">Agency owner, London</p>
                    </div>
                </div>
            </div>
            {{-- Quote 2 --}}
            <div class="sr d1 rounded-2xl p-7 flex flex-col" style="background:#0d1526;border:1px solid rgba(255,255,255,0.09)">
                <svg class="w-8 h-8 mb-5 flex-shrink-0" viewBox="0 0 32 32" fill="none">
                    <path d="M9 13h6l-4 8H7l2-8zm10 0h6l-4 8h-4l2-8z" fill="rgba(59,130,246,0.2)"/>
                </svg>
                <p class="text-base leading-relaxed mb-6 flex-1" style="color:rgba(248,250,252,0.75)">"I create a new book every month. At the end I know <strong style="color:#f8fafc">exactly</strong> if I made money. Simple, honest, done. I've been looking for something like this for years."</p>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm flex-shrink-0" style="background:rgba(26,86,219,0.3);color:var(--accent)">A</div>
                    <div>
                        <p class="font-semibold text-sm" style="color:#f8fafc">Aisha M.</p>
                        <p class="text-xs" style="color:rgba(248,250,252,0.35)">Restaurant owner, Dubai</p>
                    </div>
                </div>
            </div>
            {{-- Quote 3 --}}
            <div class="sr d2 rounded-2xl p-7 flex flex-col" style="background:#0d1526;border:1px solid rgba(255,255,255,0.09)">
                <svg class="w-8 h-8 mb-5 flex-shrink-0" viewBox="0 0 32 32" fill="none">
                    <path d="M9 13h6l-4 8H7l2-8zm10 0h6l-4 8h-4l2-8z" fill="rgba(59,130,246,0.2)"/>
                </svg>
                <p class="text-base leading-relaxed mb-6 flex-1" style="color:rgba(248,250,252,0.75)">"I used to calculate the balance in my head every morning. Now it's <strong style="color:#f8fafc">on the screen the moment I open the app.</strong> I genuinely can't go back to anything else."</p>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm flex-shrink-0" style="background:rgba(26,86,219,0.3);color:var(--accent)">M</div>
                    <div>
                        <p class="font-semibold text-sm" style="color:#f8fafc">Marcus T.</p>
                        <p class="text-xs" style="color:rgba(248,250,252,0.35)">Retail shop, Chicago</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>


{{-- ══ PRICING ══════════════════════════════════════════════════════════ --}}
<section id="pricing" style="background:var(--black)" class="relative overflow-hidden px-6 py-24 md:py-32">
    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[900px] h-72 rounded-full blur-3xl pointer-events-none glow-pulse" style="background:rgba(26,86,219,0.12)"></div>
    <div class="absolute bottom-0 left-1/2 -translate-x-1/2 w-[600px] h-40 rounded-full blur-3xl pointer-events-none" style="background:rgba(59,130,246,0.06)"></div>
    <div class="relative max-w-3xl mx-auto">
        <div class="text-center mb-10">
            <h2 class="sr fd font-black leading-tight mb-3" style="color:#fff;font-size:clamp(2.2rem,4vw,3.25rem)">Honest pricing.</h2>
            <p class="sr d1 text-lg mb-8" style="color:rgba(255,255,255,0.35)">No seat fees. No trial tricks. No surprise bills.</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            {{-- Free --}}
            <div class="sr rounded-2xl p-8 text-left" style="background:#0d1526;border:1px solid rgba(255,255,255,0.07)">
                <p class="text-sm font-semibold mb-2" style="color:rgba(255,255,255,0.4)">Free</p>
                <p class="fm font-bold mb-1" style="color:#fff;font-size:2.5rem">$0</p>
                <p class="text-xs mb-7" style="color:rgba(255,255,255,0.3)">Forever free · no card needed</p>
                <ul class="space-y-2.5 mb-8 text-sm" style="color:rgba(255,255,255,0.5)">
                    @foreach(['1 business','Unlimited books & entries','2 team members','Receipt photo attachments','Activity audit log'] as $f)
                    <li class="flex items-center gap-2.5">
                        <svg class="w-4 h-4 flex-shrink-0" viewBox="0 0 16 16" fill="none"><path d="M3 8l3 3 7-7" stroke="rgba(255,255,255,0.3)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>{{ $f }}
                    </li>
                    @endforeach
                </ul>
                <a href="{{ route('register') }}" class="block text-center text-sm font-semibold py-3 rounded-xl transition-all" style="border:1px solid rgba(255,255,255,0.14);color:#fff">Get started free</a>
            </div>
            {{-- Pro --}}
            <div class="sr d1 rounded-2xl p-8 text-left relative overflow-hidden" style="background:#0a1428;border:1px solid rgba(59,130,246,0.5);box-shadow:0 0 60px rgba(26,86,219,0.15)">
                <div class="absolute top-5 right-5 text-[10px] font-bold px-2 py-0.5 rounded-full" style="background:var(--primary);color:#fff">MOST POPULAR</div>
                <p class="text-sm font-semibold mb-2" style="color:var(--accent)">Pro</p>
                <div class="mb-0">
                    <span class="fm font-bold" style="color:#f8fafc;font-size:2.5rem">$5</span>
                    <span class="text-sm" style="color:rgba(248,250,252,0.4)">/month</span>
                </div>
                <p class="text-xs mb-7 mt-1" style="color:rgba(248,250,252,0.25)">Billed monthly · Cancel any time</p>
                {{-- Hero benefit --}}
                <div class="rounded-xl p-3.5 mb-6" style="background:rgba(59,130,246,0.08);border:1px solid rgba(59,130,246,0.2)">
                    <p class="text-xs font-semibold mb-1" style="color:var(--accent)">★ The reason most people upgrade</p>
                    <p class="text-sm" style="color:rgba(248,250,252,0.7)">Photo receipts fill entries automatically. Takes 2 seconds. Saves hours.</p>
                </div>
                <ul class="space-y-2.5 mb-8 text-sm" style="color:rgba(248,250,252,0.65)">
                    @foreach(['Everything in Free','Unlimited businesses & team members','Receipt photo → auto-fill entry','Monthly cash flow reports & charts','AI reads your numbers, tells you what happened','Recurring entries (rent, retainers, subscriptions)','PDF & CSV export'] as $f)
                    <li class="flex items-center gap-2.5">
                        <svg class="w-4 h-4 flex-shrink-0" viewBox="0 0 16 16" fill="none"><path d="M3 8l3 3 7-7" stroke="var(--accent)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>{{ $f }}
                    </li>
                    @endforeach
                </ul>
                <a href="{{ route('register', ['plan' => 'pro']) }}" class="block text-center text-sm font-semibold py-3.5 rounded-xl btn-primary">Get Pro →</a>
                <p class="text-center text-xs mt-3" style="color:rgba(248,250,252,0.25)">Sign up, then complete payment · Cancel any time</p>
            </div>
        </div>
    </div>
</section>


{{-- ══ FAQ ══════════════════════════════════════════════════════════════ --}}
<section style="background:var(--dark)" class="relative overflow-hidden px-6 py-24 md:py-32">
    <div class="absolute -bottom-20 -left-20 w-80 h-80 rounded-full blur-3xl pointer-events-none" style="background:rgba(26,86,219,0.07)"></div>
    <div class="relative max-w-3xl mx-auto">
        <div class="text-center mb-14">
            <p class="sr text-xs font-semibold uppercase tracking-widest mb-4" style="color:rgba(255,255,255,0.25)">Got questions?</p>
            <h2 class="sr d1 fd font-black leading-tight" style="color:#f8fafc;font-size:clamp(2.2rem,4vw,3rem)">
                Common questions.
            </h2>
        </div>
        <div class="space-y-3">
            @foreach([
                ['Do I need to know accounting to use this?','No. ' . config('app.name', 'CashFlow') . ' is built for business owners, not accountants. You just record what happened — "received $500 from client" or "paid $120 for supplies". The balance is always worked out for you. No formulas, no jargon. Works in any currency.'],
                ['Is my data safe?','Yes. Your data is stored securely, backed up automatically, and never shared with anyone. Only you and the people you explicitly invite can see your books.'],
                ['Can I use this for multiple businesses?','Yes. Pro plan gives you unlimited businesses on one dashboard. Free plan supports one business. Switch between them with one click — no logging out, no separate accounts.'],
                ['What if I want to cancel?','Cancel any time from your billing settings — no questions asked, no tricks, no guilt emails. Your data stays accessible on the free plan so you never lose your history.'],
                ['Does it work on my phone?','Yes. The app works in any mobile browser — adding an entry takes about 10 seconds on a phone. A dedicated mobile app is on the roadmap.'],
                ['How does the photo receipt feature work?','Take a photo of any receipt inside the app, or upload one from your phone. The app reads the amount, what it was for, and the date — then fills the entry form for you automatically. You just check it and tap Save. No typing needed.'],
            ] as $idx=>[$q,$a])
            <div class="sr d{{ min($idx+1,5) }} rounded-2xl overflow-hidden" style="background:#0d1526;border:1px solid rgba(255,255,255,0.07)" x-data="{open:false}">
                <button @click="open=!open" class="w-full flex items-center justify-between px-6 py-5 text-left"
                        style="background:transparent;transition:background 0.15s"
                        :style="open ? 'background:rgba(59,130,246,0.06)' : ''">
                    <span class="fd font-bold text-lg pr-6" style="color:#f8fafc;line-height:1.3">{{ $q }}</span>
                    <div class="w-7 h-7 rounded-full flex items-center justify-center flex-shrink-0 transition-all duration-200"
                         style="background:rgba(255,255,255,0.06)"
                         :style="open ? 'background:rgba(59,130,246,0.2);transform:rotate(45deg)' : ''">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" style="color:rgba(248,250,252,0.5)">
                            <path d="M12 4.5v15m7.5-7.5h-15" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
                        </svg>
                    </div>
                </button>
                <div x-show="open"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-2"
                     class="px-6 pb-6">
                    <p class="text-sm leading-relaxed" style="color:rgba(248,250,252,0.5)">{{ $a }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>


{{-- ══ FINAL CTA ════════════════════════════════════════════════════════ --}}
<section style="background:var(--dark)" class="px-6 py-28 md:py-40 text-center noise relative overflow-hidden">
    <div class="absolute inset-0 pointer-events-none overflow-hidden">
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[900px] h-72 rounded-full blur-3xl" style="background:rgba(26,86,219,0.2)"></div>
        <div class="absolute bottom-0 left-1/2 -translate-x-1/2 w-[600px] h-48 rounded-full blur-3xl" style="background:rgba(59,130,246,0.08)"></div>
    </div>
    <div class="relative max-w-3xl mx-auto">
        <p class="sr text-xs font-semibold uppercase tracking-widest mb-6" style="color:rgba(59,130,246,0.7)">Start today</p>
        <h2 class="sr d1 fd font-black leading-[0.92] mb-6" style="color:#f8fafc;font-size:clamp(3rem,7vw,6rem)">
            Know your<br>numbers.<br><span style="color:var(--accent)">Tonight.</span>
        </h2>
        <p class="sr d2 text-lg mb-10" style="color:rgba(248,250,252,0.45)">
            Setup takes 2 minutes. Your first balance update takes 10 seconds.
        </p>
        <div class="sr d3 flex flex-col sm:flex-row items-center justify-center gap-3">
            <a href="{{ route('register') }}" class="font-bold text-base px-8 py-4 rounded-full btn-primary">
                Create your free account →
            </a>
            <a href="{{ route('login') }}" class="text-base px-8 py-4 rounded-full btn-ghost">
                Already have an account
            </a>
        </div>
        <p class="sr d4 mt-5 text-xs" style="color:rgba(248,250,252,0.25)">No credit card required · Free plan available · Pro at $5/month</p>
    </div>
</section>


{{-- ══ FOOTER ═══════════════════════════════════════════════════════════ --}}
<footer style="background:var(--black);border-top:1px solid rgba(255,255,255,0.06)" class="px-6 pt-16 pb-10">
    <div class="max-w-6xl mx-auto">
        <div class="mb-10 text-center overflow-hidden">
            <p class="fd font-black select-none" style="color:rgba(255,255,255,0.04);font-size:clamp(5rem,14vw,11rem);line-height:1;letter-spacing:-0.04em">{{ config('app.name', 'CashFlow') }}</p>
        </div>
        <div class="flex flex-col sm:flex-row items-center justify-between gap-6 pt-6" style="border-top:1px solid rgba(255,255,255,0.07)">
            <div class="flex items-center gap-2">
                @if(file_exists(public_path('brand/logo-dark.png')))
                    <img src="{{ asset('brand/logo-dark.png') }}?v={{ filemtime(public_path('brand/logo-dark.png')) }}"
                         alt="{{ config('app.name', 'CashFlow') }}" class="h-6 w-auto object-contain opacity-60">
                @else
                    <div class="w-6 h-6 rounded-md flex items-center justify-center" style="background:var(--primary)">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none"><path d="M3 17l4-8 4 4 4-6 4 4" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </div>
                    <span class="fd font-bold text-sm" style="color:rgba(255,255,255,0.45)">{{ config('app.name', 'CashFlow') }}</span>
                @endif
            </div>
            <div class="flex items-center gap-6">
                @foreach([['#how','How it works'],['#features','Features'],['#pricing','Pricing'],['login','Sign in'],['register','Register']] as [$r,$l])
                <a href="{{ str_starts_with($r,'#') ? $r : route($r) }}" class="text-xs transition-colors"
                   style="color:rgba(255,255,255,0.28)" onmouseover="this.style.color='rgba(255,255,255,0.65)'" onmouseout="this.style.color='rgba(255,255,255,0.28)'">{{ $l }}</a>
                @endforeach
            </div>
            <p class="text-xs" style="color:rgba(255,255,255,0.18)">© {{ date('Y') }} {{ config('app.name', 'CashFlow') }}</p>
        </div>
    </div>
</footer>


{{-- ══ SCRIPTS ══════════════════════════════════════════════════════════ --}}
<script>
// ── Scroll reveal ────────────────────────────────────────────────────
(function(){
    var els = document.querySelectorAll('.sr,.sr-left,.sr-right');
    var io  = new IntersectionObserver(function(entries){
        entries.forEach(function(e){
            if(e.isIntersecting){ e.target.classList.add('in'); io.unobserve(e.target); }
        });
    },{threshold:0.1});
    els.forEach(function(el){ io.observe(el); });
})();

// ── Stat pop animation on scroll ────────────────────────────────────
(function(){
    var stats = document.querySelectorAll('.stat-pop');
    var io = new IntersectionObserver(function(entries){
        entries.forEach(function(e, idx){
            if(e.isIntersecting){
                setTimeout(function(){ e.target.classList.add('in'); }, idx * 100);
                io.unobserve(e.target);
            }
        });
    },{threshold:0.2});
    stats.forEach(function(el,i){
        el.style.animationDelay = (i * 0.1) + 's';
        io.observe(el);
    });
})();

// ── Chart bars animate on scroll ─────────────────────────────────────
(function(){
    var card = document.getElementById('chartCard');
    if(!card) return;
    var io = new IntersectionObserver(function(entries){
        if(entries[0].isIntersecting){
            card.classList.remove('bars-hidden');
            io.unobserve(card);
        }
    },{threshold:0.2});
    io.observe(card);
})();

// ── Hero mockup subtle parallax on mousemove ─────────────────────────
(function(){
    var hero = document.querySelector('.mock-float');
    if(!hero) return;
    document.addEventListener('mousemove', function(e){
        var x = (e.clientX / window.innerWidth - 0.5) * 6;
        var y = (e.clientY / window.innerHeight - 0.5) * 4;
        hero.style.transform = 'translateY(var(--float-y,0px)) rotateX('+(-y*0.3)+'deg) rotateY('+(x*0.3)+'deg)';
    });
})();
</script>
</body>
</html>
