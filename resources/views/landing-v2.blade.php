<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'CashFlow') }} — AI-Powered Cash Flow for Small Business</title>
    <meta name="description" content="Track every transaction, scan receipts with AI, and get cash flow insights. The smartest cash book for small businesses worldwide.">
    <link rel="icon" type="image/png" href="/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400;12..96,700;12..96,800&family=Plus+Jakarta+Sans:wght@400;600;700&family=Outfit:wght@300;400;500&family=Geist+Mono:wght@400;500&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @if(file_exists(public_path('brand/theme.css')))
        <link rel="stylesheet" href="{{ asset('brand/theme.css') }}?v={{ filemtime(public_path('brand/theme.css')) }}">
    @endif
    {{-- Apply theme BEFORE paint to avoid flash --}}
    <script>
        /* Landing page always starts in light mode — toggle works for current visit */
    </script>
    {{-- Alpine CDN: this page has no Livewire components so Alpine isn't auto-loaded --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.9/dist/cdn.min.js"></script>
    <style>
        /* ── Tailwind RGB vars (fallback when theme.css absent) ── */
        :root {
            --color-navy:       10 15 30;
            --color-dark:       17 24 39;
            --color-primary:    26 86 219;
            --color-accent:     59 130 246;
            --color-blue-light: 147 197 253;
            --color-blue-xlight:219 234 254;
            --navy:#0a0f1e; --dark:#111827; --primary:#1a56db; --accent:#3b82f6;

            /* ── Theme tokens — LIGHT default ── */
            --bg-page:    #f2f4f8;
            --bg-alt:     #e8ecf2;
            --bg-card:    #ffffff;
            --text-h:     #0f172a;
            --text-body:  #475569;
            --text-muted: #94a3b8;
            --border:     rgba(0,0,0,.09);
            --nav-bg:     rgba(242,244,248,.95);
            --nav-border: rgba(0,0,0,.09);
            --shadow-card:0 2px 20px rgba(0,0,0,.07);
        }
        /* ── Dark mode overrides ── */
        html[data-theme="dark"] {
            --bg-page:    #0a0f1e;
            --bg-alt:     rgba(7,11,24,.6);
            --bg-card:    #111827;
            --text-h:     #f8fafc;
            --text-body:  #94a3b8;
            --text-muted: #64748b;
            --border:     rgba(255,255,255,.07);
            --nav-bg:     rgba(10,15,30,.88);
            --nav-border: rgba(255,255,255,.07);
            --shadow-card:none;
        }
        body { background-color:var(--bg-page); color:var(--text-h); font-family:'Outfit',sans-serif; }

        /* ── Noise texture overlay ── */
        body::before {
            content:'';position:fixed;inset:0;pointer-events:none;z-index:0;
            background-image:url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.03'/%3E%3C/svg%3E");
            opacity:.4;
        }

        /* ── Typography ── */
        .font-display { font-family:'Bricolage Grotesque',sans-serif; }
        .font-heading  { font-family:'Plus Jakarta Sans',sans-serif; }
        .font-mono-cf  { font-family:'Geist Mono',monospace; }

        /* ── Glows ── */
        .hero-glow {
            position:absolute;inset-x:0;top:-120px;height:700px;pointer-events:none;
            background:radial-gradient(ellipse 70% 50% at 50% 0%, rgba(26,86,219,.28) 0%, transparent 70%);
        }
        .blue-line {
            position:absolute;inset-x:0;top:0;height:1px;
            background:linear-gradient(90deg,transparent,rgba(59,130,246,.6),transparent);
        }

        /* ── Scroll reveal ── */
        .sr { opacity:0; transform:translateY(28px); transition:opacity .65s cubic-bezier(.16,1,.3,1), transform .65s cubic-bezier(.16,1,.3,1); }
        .sr.on { opacity:1; transform:translateY(0); }
        .sr-delay-1 { transition-delay:.1s; }
        .sr-delay-2 { transition-delay:.2s; }
        .sr-delay-3 { transition-delay:.3s; }
        .sr-delay-4 { transition-delay:.4s; }

        /* ── Cards ── */
        .card-hover { transition:border-color .2s ease, transform .22s ease; }
        .card-hover:hover { border-color:rgba(26,86,219,.5); transform:translateY(-2px); }

        /* ── Gradient borders ── */
        .grad-border {
            position:relative;border-radius:16px;
            background:linear-gradient(135deg,rgba(26,86,219,.15),rgba(59,130,246,.08));
        }
        .grad-border::before {
            content:'';position:absolute;inset:0;border-radius:16px;padding:1px;
            background:linear-gradient(135deg,rgba(26,86,219,.4),rgba(59,130,246,.15),transparent);
            -webkit-mask:linear-gradient(#fff 0 0) content-box,linear-gradient(#fff 0 0);
            -webkit-mask-composite:xor; mask-composite:exclude; pointer-events:none;
        }

        /* ── AI badge ── */
        .ai-badge {
            display:inline-flex;align-items:center;gap:6px;
            padding:6px 14px;border-radius:9999px;
            background:rgba(26,86,219,.12);border:1px solid rgba(26,86,219,.3);
            font-size:13px;font-family:'Outfit',sans-serif;color:#93c5fd;
        }
        .ai-dot { width:6px;height:6px;border-radius:50%;background:#1a56db;
            box-shadow:0 0 8px rgba(26,86,219,.8);animation:pulse 2s infinite; }
        @keyframes pulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.6;transform:scale(.8)} }

        /* ── Email form (override @tailwindcss/forms resets with !important) ── */
        .email-form {
            display:flex !important;gap:0;border-radius:12px;
            border:1px solid rgba(26,86,219,.4);background:rgba(255,255,255,.04);
            backdrop-filter:blur(12px);overflow:hidden;
        }
        .email-form input,
        .email-form input[type="email"] {
            -webkit-appearance:none !important;appearance:none !important;
            flex:1 !important;padding:16px 20px !important;
            background:transparent !important;border:none !important;
            outline:none !important;box-shadow:none !important;
            color:#f8fafc !important;font-family:'Outfit',sans-serif !important;
            font-size:15px !important;border-radius:0 !important;
            min-width:0;
        }
        .email-form input::placeholder { color:#64748b !important; }
        .email-form input:focus { outline:none !important; box-shadow:none !important; border:none !important; }
        .email-form button {
            padding:14px 24px !important;background:#1a56db;border:none;cursor:pointer;
            color:#fff !important;font-family:'Outfit',sans-serif;font-size:15px;font-weight:500;
            white-space:nowrap;transition:background .18s;flex-shrink:0;
        }
        .email-form button:hover { background:#2563eb; }

        /* ── Stat number ── */
        .stat-num { font-family:'Geist Mono',monospace;font-size:clamp(2.5rem,6vw,4rem);font-weight:400;color:#f8fafc;line-height:1; }

        /* ── Feature pill ── */
        .feat-pill {
            display:inline-flex;align-items:center;gap:8px;
            padding:8px 16px;border-radius:9999px;
            background:rgba(26,86,219,.1);border:1px solid rgba(26,86,219,.2);
            font-size:13px;color:#93c5fd;font-family:'Outfit',sans-serif;
        }

        /* ── Dashboard mockup ── */
        .mockup-bar { display:flex;align-items:center;gap:6px;padding:12px 16px;background:#0d1526;border-bottom:1px solid rgba(255,255,255,.06); }
        .mockup-dot { width:10px;height:10px;border-radius:50%; }

        /* ── Pricing card ── */
        .price-card {
            border-radius:20px;border:1px solid rgba(255,255,255,.08);
            background:rgba(17,24,39,.6);backdrop-filter:blur(20px);
            padding:40px;transition:border-color .2s;
        }
        .price-card.featured { border-color:rgba(26,86,219,.5);background:rgba(26,86,219,.1); }
        .price-card:hover { border-color:rgba(26,86,219,.4); }

        /* ── FAQ ── */
        .faq-item { border-bottom:1px solid rgba(255,255,255,.06); }
        .faq-btn { display:flex;justify-content:space-between;align-items:center;width:100%;padding:24px 0;
            background:none;border:none;cursor:pointer;text-align:left;color:var(--text-h,#f8fafc); }
        .faq-icon { flex-shrink:0;transition:transform .3s; }
        .faq-body { overflow:hidden; }

        /* ── Theme toggle button ── */
        .theme-btn {
            width:34px;height:34px;border-radius:8px;border:1px solid var(--border);
            background:var(--bg-card);cursor:pointer;
            display:flex;align-items:center;justify-content:center;
            color:var(--text-muted);transition:all .2s;flex-shrink:0;
        }
        .theme-btn:hover { color:#1a56db;border-color:rgba(26,86,219,.3);background:rgba(26,86,219,.06); }
        .icon-sun,.icon-moon { pointer-events:none;display:none; }
        html[data-theme="dark"] .icon-moon { display:block; }
        html:not([data-theme="dark"]) .icon-sun { display:block; }

        /* ── Scroll-shrink nav ── */
        .nav-scrolled {
            top:10px !important;left:20px !important;right:20px !important;
            height:52px !important;border-radius:14px !important;
        }
        html[data-theme="dark"] .nav-scrolled {
            background:rgba(8,12,26,.97) !important;
            border:1px solid rgba(255,255,255,.12) !important;
            box-shadow:0 8px 40px rgba(0,0,0,.5),0 0 0 1px rgba(26,86,219,.08) !important;
        }
        html:not([data-theme="dark"]) .nav-scrolled {
            background:rgba(255,255,255,.97) !important;
            border:1px solid rgba(0,0,0,.1) !important;
            box-shadow:0 4px 24px rgba(0,0,0,.1) !important;
        }

        /* ── Light mode overrides ── */
        html:not([data-theme="dark"]) body::before { opacity:.06; }
        html:not([data-theme="dark"]) .trust-bar { background:#e8ecf2 !important; }
        html:not([data-theme="dark"]) .trust-bar::before { background:linear-gradient(90deg,#e8ecf2,transparent) !important; }
        html:not([data-theme="dark"]) .trust-bar::after { background:linear-gradient(270deg,#e8ecf2,transparent) !important; }
        html:not([data-theme="dark"]) .c-pill { background:#fff !important;border-color:rgba(0,0,0,.1) !important; }
        html:not([data-theme="dark"]) .c-code { color:#1e40af !important; }
        html:not([data-theme="dark"]) .c-name { color:#64748b !important; }
        html:not([data-theme="dark"]) .biz-pill { background:#fff !important;border-color:rgba(0,0,0,.1) !important;color:#475569 !important; }
        html:not([data-theme="dark"]) .biz-dot { background:#cbd5e1 !important; }
        html:not([data-theme="dark"]) .trust-label { color:#94a3b8 !important; }
        html:not([data-theme="dark"]) .feat-pill { background:rgba(26,86,219,.08) !important;border-color:rgba(26,86,219,.15) !important;color:#1d4ed8 !important; }
        html:not([data-theme="dark"]) .grad-border { background:#fff !important;box-shadow:0 2px 24px rgba(0,0,0,.07); }
        html:not([data-theme="dark"]) .ai-badge { background:rgba(26,86,219,.08) !important;border-color:rgba(26,86,219,.2) !important;color:#1d4ed8 !important; }
        html:not([data-theme="dark"]) .ai-dot { background:#1a56db !important;box-shadow:0 0 6px rgba(26,86,219,.3) !important; }
        html:not([data-theme="dark"]) .card-hover { background:#fff !important;border-color:rgba(26,86,219,.15) !important;box-shadow:0 2px 20px rgba(0,0,0,.07); }
        html:not([data-theme="dark"]) .price-card { background:#fff !important;border-color:rgba(0,0,0,.1) !important;box-shadow:0 2px 20px rgba(0,0,0,.06); }
        html:not([data-theme="dark"]) .price-card.featured { background:rgba(26,86,219,.06) !important;border-color:rgba(26,86,219,.3) !important; }
        html:not([data-theme="dark"]) .faq-item { border-bottom-color:rgba(0,0,0,.09) !important; }
        html:not([data-theme="dark"]) .stat-num { color:var(--text-h); }
        html:not([data-theme="dark"]) .email-form { background:#fff !important;border-color:rgba(26,86,219,.3) !important;box-shadow:0 2px 16px rgba(0,0,0,.09); }
        html:not([data-theme="dark"]) .email-form input { color:#0f172a !important; }
        html:not([data-theme="dark"]) .email-form input::placeholder { color:#94a3b8 !important; }
        html:not([data-theme="dark"]) .mobile-menu { background:rgba(242,244,248,.99) !important;border-bottom-color:rgba(0,0,0,.08) !important; }
        html:not([data-theme="dark"]) .mobile-menu a { color:#334155 !important;border-bottom-color:rgba(0,0,0,.06) !important; }
        /* Tailwind text class overrides */
        html:not([data-theme="dark"]) .text-white  { color:var(--text-h) !important; }
        html:not([data-theme="dark"]) .text-slate-400 { color:var(--text-body) !important; }
        html:not([data-theme="dark"]) .text-slate-500 { color:var(--text-body) !important; }
        html:not([data-theme="dark"]) .text-slate-600 { color:var(--text-muted) !important; }
        html:not([data-theme="dark"]) .text-slate-300 { color:#334155 !important; }
        html:not([data-theme="dark"]) .text-blue-300 { color:#1d4ed8 !important; }
        html:not([data-theme="dark"]) .text-blue-400 { color:#2563eb !important; }
        html:not([data-theme="dark"]) .text-blue-200 { color:#1e40af !important; }
        /* Section-level backgrounds */
        html:not([data-theme="dark"]) #ai { background:linear-gradient(180deg,#eff6ff 0%,#e0e7ff 50%,#eff6ff 100%) !important;border-top-color:rgba(26,86,219,.2) !important;border-bottom-color:rgba(26,86,219,.2) !important; }
        html:not([data-theme="dark"]) #pricing { background:#e8ecf2 !important; }
        html:not([data-theme="dark"]) .cta-section { background:linear-gradient(180deg,#f2f4f8 0%,rgba(26,86,219,.07) 50%,#f2f4f8 100%) !important; }

        /* ══ LIGHT MOCKUP THEME — mockups adapt to page theme ══ */
        /* Outer shell backgrounds */
        html:not([data-theme="dark"]) .mock-card { background:#f1f5f9 !important; }
        html:not([data-theme="dark"]) .mock-card .mockup-bar,
        html:not([data-theme="dark"]) .hero-mock-frame .mockup-bar { background:#dde3ec !important; border-bottom-color:rgba(0,0,0,.08) !important; }

        /* Inner dark backgrounds → white (feature mockup rows + hero sections) */
        html:not([data-theme="dark"]) .mock-card [style*="background:#111827"],
        html:not([data-theme="dark"]) .hero-mock-frame [style*="background:#111827"] { background:#ffffff !important; }
        html:not([data-theme="dark"]) .hero-mock-frame [style*="background:#0d1526"] { background:#f1f5f9 !important; }
        html:not([data-theme="dark"]) .hero-mock-frame [style*="background:#1e293b"] { background:#e2e8f0 !important; }
        /* Keep blue-tinted surfaces, just lighten them */
        html:not([data-theme="dark"]) .hero-mock-frame [style*="background:rgba(26,86,219,.15)"] { background:rgba(26,86,219,.07) !important; }
        html:not([data-theme="dark"]) .hero-mock-frame [style*="background:rgba(26,86,219,.1)"] { background:rgba(26,86,219,.06) !important; }

        /* Borders: near-invisible white borders → visible slate borders */
        html:not([data-theme="dark"]) .mock-card [style*="rgba(255,255,255,.05)"],
        html:not([data-theme="dark"]) .hero-mock-frame [style*="rgba(255,255,255,.05)"] { border-color:rgba(0,0,0,.08) !important; }
        html:not([data-theme="dark"]) .mock-card [style*="rgba(255,255,255,.04)"],
        html:not([data-theme="dark"]) .hero-mock-frame [style*="rgba(255,255,255,.04)"] { border-color:rgba(0,0,0,.06) !important; }
        html:not([data-theme="dark"]) .mock-card [style*="rgba(255,255,255,.06)"],
        html:not([data-theme="dark"]) .hero-mock-frame [style*="rgba(255,255,255,.06)"] { border-color:rgba(0,0,0,.07) !important; }

        /* Tailwind text class overrides inside feature mockups */
        html:not([data-theme="dark"]) .mock-card .text-white { color:#0f172a !important; }
        html:not([data-theme="dark"]) .mock-card .text-slate-300 { color:#475569 !important; }
        html:not([data-theme="dark"]) .mock-card .text-slate-400 { color:#64748b !important; }
        html:not([data-theme="dark"]) .mock-card .text-slate-500 { color:#94a3b8 !important; }
        html:not([data-theme="dark"]) .mock-card .text-slate-600 { color:#94a3b8 !important; }
        html:not([data-theme="dark"]) .mock-card .text-green-400 { color:#16a34a !important; }
        html:not([data-theme="dark"]) .mock-card .text-red-400 { color:#dc2626 !important; }
        html:not([data-theme="dark"]) .mock-card .text-blue-300 { color:#2563eb !important; }
        html:not([data-theme="dark"]) .mock-card .text-primary { color:#1a56db !important; }
        html:not([data-theme="dark"]) .mock-card .text-emerald-600 { color:#059669 !important; }
        html:not([data-theme="dark"]) .mock-card .text-amber-400 { color:#d97706 !important; }
        html:not([data-theme="dark"]) .mock-card .text-violet-400 { color:#7c3aed !important; }

        /* Hero mockup inline text overrides (inline styles → overridden via !important) */
        html:not([data-theme="dark"]) .hero-mock-frame [style*="color:#f8fafc"] { color:#0f172a !important; }
        html:not([data-theme="dark"]) .hero-mock-frame [style*="color:#cbd5e1"] { color:#334155 !important; }
        html:not([data-theme="dark"]) .hero-mock-frame [style*="color:#93c5fd"] { color:#2563eb !important; }
        html:not([data-theme="dark"]) .hero-mock-frame [style*="color:#bfdbfe"] { color:#1e40af !important; }
        html:not([data-theme="dark"]) .hero-mock-frame [style*="color:#4ade80"] { color:#16a34a !important; }
        html:not([data-theme="dark"]) .hero-mock-frame [style*="color:#f87171"] { color:#dc2626 !important; }

        /* ── Hero h1 light mode ── */
        html:not([data-theme="dark"]) .hero-left h1 { color:#0f172a !important; }
        html:not([data-theme="dark"]) .hero-sub { color:#475569 !important; }

        /* ── Hero mockup frame in light ── */
        html:not([data-theme="dark"]) .hero-mock-frame { border-color:rgba(0,0,0,.1) !important; box-shadow:0 24px 64px rgba(0,0,0,.12) !important; }

        /* ── Free plan CTA button in light ── */
        html:not([data-theme="dark"]) .free-plan-btn { border-color:rgba(0,0,0,.15) !important; color:#1e293b !important; }
        html:not([data-theme="dark"]) .free-plan-btn:hover { background:rgba(0,0,0,.04) !important; }

        /* ── Most popular badge in light ── */
        html:not([data-theme="dark"]) .popular-badge { background:rgba(26,86,219,.12) !important; color:#1d4ed8 !important; }

        /* ── Footer light mode ── */
        html:not([data-theme="dark"]) .site-footer { background:#dde2ea !important; border-top-color:rgba(0,0,0,.1) !important; }
        html:not([data-theme="dark"]) .site-footer a { color:#475569 !important; }
        html:not([data-theme="dark"]) .site-footer a:hover { color:#0f172a !important; }
        html:not([data-theme="dark"]) .footer-brand-name { color:#0f172a !important; }

        /* ── Navbar (guaranteed responsive — no Tailwind breakpoint classes) ── */
        .nav-root {
            position:fixed;top:0;left:0;right:0;z-index:50;height:64px;
            display:flex;align-items:center;justify-content:space-between;
            padding:0 2.5rem;
            background:var(--nav-bg);backdrop-filter:blur(20px);
            border-bottom:1px solid var(--nav-border);
            transition:top .45s cubic-bezier(.16,1,.3,1),
                        left .45s cubic-bezier(.16,1,.3,1),
                        right .45s cubic-bezier(.16,1,.3,1),
                        height .45s cubic-bezier(.16,1,.3,1),
                        border-radius .45s cubic-bezier(.16,1,.3,1),
                        box-shadow .45s cubic-bezier(.16,1,.3,1),
                        background .3s ease;
        }
        .nav-scrolled {
            top:10px !important;left:20px !important;right:20px !important;
            height:52px !important;border-radius:14px !important;
        }
        html[data-theme="dark"] .nav-scrolled {
            background:rgba(8,12,26,.97) !important;
            border:1px solid rgba(255,255,255,.12) !important;
            box-shadow:0 8px 40px rgba(0,0,0,.5),0 0 0 1px rgba(26,86,219,.08) !important;
        }
        html:not([data-theme="dark"]) .nav-scrolled {
            background:rgba(255,255,255,.97) !important;
            border:1px solid rgba(0,0,0,.1) !important;
            box-shadow:0 4px 24px rgba(0,0,0,.1),0 1px 4px rgba(0,0,0,.06) !important;
        }
        .nav-logo {
            display:flex;align-items:center;gap:10px;text-decoration:none;flex-shrink:0;
            font-family:'Bricolage Grotesque',sans-serif;font-weight:700;font-size:18px;color:var(--text-h);
        }
        .nav-links {
            display:flex;align-items:center;gap:2rem;position:absolute;left:50%;transform:translateX(-50%);
        }
        .nav-links a {
            font-size:14px;color:var(--text-body);text-decoration:none;
            font-family:'Outfit',sans-serif;transition:color .15s;white-space:nowrap;
        }
        .nav-links a:hover { color:var(--text-h); }
        .nav-right { display:flex;align-items:center;gap:8px;flex-shrink:0; }
        .nav-signin {
            font-size:14px;color:var(--text-body);text-decoration:none;
            font-family:'Outfit',sans-serif;transition:color .15s;
        }
        .nav-signin:hover { color:var(--text-h); }
        .nav-cta {
            display:inline-block;padding:9px 20px;border-radius:8px;
            background:#1a56db;color:#fff;font-size:14px;font-weight:500;
            font-family:'Outfit',sans-serif;text-decoration:none;
            transition:background .15s;white-space:nowrap;
        }
        .nav-cta:hover { background:#2563eb; }
        .nav-hamburger {
            display:none;background:none;border:none;cursor:pointer;
            color:#94a3b8;padding:4px;width:36px;height:36px;
            align-items:center;justify-content:center;
        }
        .mobile-menu {
            position:fixed;top:64px;left:0;right:0;z-index:40;
            padding:1.25rem 1.5rem 1.5rem;
            background:rgba(7,11,24,.98);border-bottom:1px solid rgba(255,255,255,.06);
        }
        .mobile-menu a {
            display:block;padding:.75rem 0;font-size:15px;color:#cbd5e1;
            text-decoration:none;font-family:'Outfit',sans-serif;
            border-bottom:1px solid rgba(255,255,255,.04);
        }
        .mobile-menu a:last-child { border-bottom:none; }

        @media (max-width:768px) {
            .nav-root { padding:0 1.25rem; }
            .nav-links  { display:none !important; }
            .nav-signin { display:none !important; }
            .nav-hamburger { display:flex !important; }
        }
        @media (min-width:769px) {
            .mobile-menu { display:none !important; }
        }

        /* ── Hero 2-column ── */
        .hero-root {
            min-height:100vh;display:flex;align-items:center;
            padding:6rem 2.5rem 4rem;position:relative;overflow:hidden;
        }
        .hero-inner {
            max-width:1200px;width:100%;margin:0 auto;
            display:grid;grid-template-columns:1fr 1fr;gap:5rem;align-items:center;
        }
        .hero-left { display:flex;flex-direction:column; }
        .hero-right { position:relative; }

        @media (max-width:900px) {
            .hero-inner { grid-template-columns:1fr;gap:3rem; }
            .hero-left { align-items:center;text-align:center; }
            .hero-left .email-form { max-width:100% !important; }
            .hero-left .ai-badge { align-self:center; }
            .hero-root { padding:5rem 1.5rem 3rem; }
        }
    </style>
</head>

<body class="antialiased" x-data="{ mobileMenu: false }">

<!-- ══════════════════════════════════════
     NAVBAR
══════════════════════════════════════ -->
<header class="nav-root" x-data="{ open: false }">

    <a href="/" class="nav-logo">
        <svg width="28" height="28" viewBox="0 0 28 28" fill="none">
            <rect width="28" height="28" rx="7" fill="#1a56db"/>
            <path d="M8 14h12M8 10h7M8 18h10" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
        </svg>
        CashFlow
    </a>

    <nav class="nav-links">
        <a href="#features">Features</a>
        <a href="#ai">AI Tools</a>
        <a href="#pricing">Pricing</a>
        <a href="#faq">FAQ</a>
    </nav>

    <div class="nav-right">
        <a href="/login" class="nav-signin">Sign in</a>
        <a href="/register" class="nav-cta">Start free</a>
        <button class="theme-btn" onclick="toggleTheme()" aria-label="Toggle theme">
            <svg class="icon-sun" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42" stroke-linecap="round"/></svg>
            <svg class="icon-moon" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
        <button class="nav-hamburger" @click="open = !open" aria-label="Menu">
            <svg x-show="!open" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M3 12h18M3 18h18" stroke-linecap="round"/></svg>
            <svg x-show="open"  width="22" height="22" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6l12 12M6 18L18 6" stroke-linecap="round"/></svg>
        </button>
    </div>
</header>

<!-- Mobile drawer -->
<div class="mobile-menu" x-data x-show="$store.nav && $store.nav.open" style="display:none">
    <a href="#features">Features</a>
    <a href="#ai">AI Tools</a>
    <a href="#pricing">Pricing</a>
    <a href="#faq">FAQ</a>
    <a href="/login">Sign in</a>
    <a href="/register" style="color:#3b82f6;font-weight:500;">Start free →</a>
</div>

<script>
// Simpler mobile menu — no Alpine store needed, just direct DOM toggle
document.addEventListener('DOMContentLoaded', function() {
    var btn = document.querySelector('.nav-hamburger');
    var menu = document.querySelector('.mobile-menu');
    if (btn && menu) {
        btn.addEventListener('click', function() {
            var isOpen = menu.style.display === 'block';
            menu.style.display = isOpen ? 'none' : 'block';
        });
        // Close on link click
        menu.querySelectorAll('a').forEach(function(a) {
            a.addEventListener('click', function() { menu.style.display = 'none'; });
        });
    }
});
</script>


<!-- ══════════════════════════════════════
     HERO  (2-column)
══════════════════════════════════════ -->
<section class="hero-root">

    <!-- Background grid -->
    <div style="position:absolute;inset:0;pointer-events:none;background-image:linear-gradient(rgba(26,86,219,.04) 1px,transparent 1px),linear-gradient(90deg,rgba(26,86,219,.04) 1px,transparent 1px);background-size:64px 64px;"></div>
    <!-- Left ambient glow -->
    <div style="position:absolute;top:0;left:-100px;width:700px;height:700px;pointer-events:none;background:radial-gradient(circle at 30% 40%,rgba(26,86,219,.18) 0%,transparent 65%);"></div>

    <div class="hero-inner">

        <!-- ── LEFT: Copy ── -->
        <div class="hero-left sr">
            <!-- Badge -->
            <div class="ai-badge" style="margin-bottom:1.75rem;align-self:flex-start;">
                <span class="ai-dot"></span>
                Snap a receipt · AI fills it in · Done
            </div>

            <!-- Headline -->
            <h1 class="font-display" style="font-weight:800;font-size:clamp(2.8rem,4.5vw,5rem);line-height:1.05;letter-spacing:-.02em;color:#f8fafc;margin-bottom:1.25rem;">
                Your money.<br>
                <span style="background:linear-gradient(135deg,#60a5fa 0%,#1a56db 60%,#818cf8 100%);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">
                    Crystal clear.
                </span>
            </h1>

            <!-- Subheading -->
            <p class="hero-sub" style="font-family:'Outfit',sans-serif;font-size:17px;line-height:1.7;color:#94a3b8;margin-bottom:2.25rem;max-width:420px;">
                Write down every payment you receive or make. Take a photo of any receipt and the details fill themselves in. See exactly where you stand — at a glance.
            </p>

            <!-- Email form -->
            <form action="/register" method="GET" class="email-form" style="max-width:440px;margin-bottom:1rem;">
                <input type="email" name="email" placeholder="Enter your work email" required autocomplete="email">
                <button type="submit">Get started free →</button>
            </form>
            <p style="font-size:12px;color:#334155;font-family:'Outfit',sans-serif;">
                Free forever · No credit card · Ready in 2 minutes
            </p>

            <!-- Trust signals -->
            <div style="display:flex;align-items:center;gap:1.5rem;margin-top:2rem;flex-wrap:wrap;">
                <div style="display:flex;align-items:center;gap:6px;">
                    <svg width="14" height="14" fill="none" viewBox="0 0 14 14"><path d="M2 7l3 3 7-7" stroke="#22c55e" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <span style="font-size:12px;color:#64748b;font-family:'Outfit',sans-serif;">Snap any receipt</span>
                </div>
                <div style="display:flex;align-items:center;gap:6px;">
                    <svg width="14" height="14" fill="none" viewBox="0 0 14 14"><path d="M2 7l3 3 7-7" stroke="#22c55e" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <span style="font-size:12px;color:#64748b;font-family:'Outfit',sans-serif;">Share with your team</span>
                </div>
                <div style="display:flex;align-items:center;gap:6px;">
                    <svg width="14" height="14" fill="none" viewBox="0 0 14 14"><path d="M2 7l3 3 7-7" stroke="#22c55e" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <span style="font-size:12px;color:#64748b;font-family:'Outfit',sans-serif;">Smart money insights</span>
                </div>
            </div>
        </div>

        <!-- ── RIGHT: Dashboard mockup ── -->
        <div class="hero-right sr sr-delay-2">
            <!-- Glow behind card -->
            <div style="position:absolute;inset:-30px;pointer-events:none;background:radial-gradient(ellipse 80% 60% at 50% 50%,rgba(26,86,219,.2) 0%,transparent 70%);border-radius:24px;"></div>

            <div class="hero-mock-frame" style="position:relative;border-radius:16px;overflow:hidden;border:1px solid rgba(255,255,255,.1);box-shadow:0 32px 80px rgba(0,0,0,.6);">

                <!-- Browser chrome -->
                <div class="mockup-bar">
                    <div class="mockup-dot" style="background:#ef4444;"></div>
                    <div class="mockup-dot" style="background:#f59e0b;"></div>
                    <div class="mockup-dot" style="background:#22c55e;"></div>
                    <div style="flex:1;display:flex;justify-content:center;padding:0 8px;">
                        <div style="background:#1e293b;border-radius:6px;padding:4px 12px;font-size:11px;color:#64748b;max-width:240px;width:100%;text-align:center;">
                            app.cashflow.in/books/march-2026
                        </div>
                    </div>
                </div>

                <!-- Book header -->
                <div style="background:#0d1526;padding:16px 20px 0;border-bottom:1px solid rgba(255,255,255,.05);">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
                        <div>
                            <p style="font-size:13px;color:#64748b;font-family:'Outfit',sans-serif;">Meridian Design Studio</p>
                            <p style="font-size:16px;font-weight:600;color:#f8fafc;font-family:'Plus Jakarta Sans',sans-serif;">March 2026</p>
                        </div>
                        <div style="display:flex;gap:8px;">
                            <span style="font-size:11px;padding:4px 10px;border-radius:6px;background:rgba(26,86,219,.15);color:#93c5fd;font-family:'Outfit',sans-serif;">+ Add Entry</span>
                        </div>
                    </div>
                    <!-- Balance strip -->
                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;padding-bottom:16px;">
                        <div style="background:#111827;border:1px solid rgba(255,255,255,.06);border-radius:10px;padding:12px;">
                            <p style="font-size:10px;color:#64748b;margin-bottom:4px;font-family:'Outfit',sans-serif;">Cash In</p>
                            <p class="font-mono-cf" style="font-size:15px;color:#4ade80;">+$24,800</p>
                        </div>
                        <div style="background:#111827;border:1px solid rgba(255,255,255,.06);border-radius:10px;padding:12px;">
                            <p style="font-size:10px;color:#64748b;margin-bottom:4px;font-family:'Outfit',sans-serif;">Cash Out</p>
                            <p class="font-mono-cf" style="font-size:15px;color:#f87171;">-$14,200</p>
                        </div>
                        <div style="background:rgba(26,86,219,.15);border:1px solid rgba(26,86,219,.3);border-radius:10px;padding:12px;">
                            <p style="font-size:10px;color:#93c5fd;margin-bottom:4px;font-family:'Outfit',sans-serif;">Net Balance</p>
                            <p class="font-mono-cf" style="font-size:15px;color:#f8fafc;">$10,600</p>
                        </div>
                    </div>
                </div>

                <!-- Entries -->
                <div style="background:#0d1526;padding:12px 20px 16px;">
                    @foreach([
                        ['Client invoice — Project Alpha',  '$ +8,400', '#4ade80', 'in',  '15 Mar'],
                        ['Office rent — March 2026',        '$ -2,200', '#f87171', 'out', '14 Mar'],
                        ['Freelance invoice #247',          '£ +3,500', '#4ade80', 'in',  '12 Mar'],
                        ['Team salaries — 4 members',       '€ -9,600', '#f87171', 'out', '10 Mar'],
                        ['AI receipt scan ✦ auto-filled',  '$ +1,800', '#4ade80', 'in',  '8 Mar'],
                    ] as $row)
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 12px;border-radius:8px;margin-bottom:4px;background:#111827;border:1px solid rgba(255,255,255,.04);">
                        <div style="display:flex;align-items:center;gap:8px;min-width:0;">
                            <span style="width:6px;height:6px;border-radius:50%;background:{{ $row[2] }};flex-shrink:0;"></span>
                            <span style="font-size:12px;color:#cbd5e1;font-family:'Outfit',sans-serif;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $row[0] }}</span>
                        </div>
                        <div style="display:flex;align-items:center;gap:12px;flex-shrink:0;margin-left:8px;">
                            <span style="font-size:10px;color:#475569;font-family:'Outfit',sans-serif;">{{ $row[4] }}</span>
                            <span class="font-mono-cf" style="font-size:12px;color:{{ $row[2] }};">{{ $row[1] }}</span>
                        </div>
                    </div>
                    @endforeach

                    <!-- AI insight bar -->
                    <div style="margin-top:10px;padding:10px 12px;border-radius:8px;background:rgba(26,86,219,.1);border:1px solid rgba(26,86,219,.2);display:flex;align-items:flex-start;gap:8px;">
                        <span class="ai-dot" style="margin-top:3px;flex-shrink:0;"></span>
                        <p style="font-size:11px;color:#93c5fd;font-family:'Outfit',sans-serif;line-height:1.5;">
                            <span style="font-weight:600;color:#bfdbfe;">AI:</span>
                            Cash in ↑34% vs last month. Salaries = 57% of outflow. Balance: healthy.
                        </p>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>


<!-- ══════════════════════════════════════
     TRUST BAR
══════════════════════════════════════ -->
<style>
.trust-bar { position:relative;overflow:hidden;padding:3.5rem 0; }
.trust-bar::before {
    content:'';position:absolute;inset-y:0;left:0;width:120px;z-index:2;
    background:linear-gradient(90deg,#0a0f1e,transparent);
}
.trust-bar::after {
    content:'';position:absolute;inset-y:0;right:0;width:120px;z-index:2;
    background:linear-gradient(270deg,#0a0f1e,transparent);
}
.trust-label {
    text-align:center;font-size:11px;letter-spacing:.14em;text-transform:uppercase;
    color:#334155;font-family:'Outfit',sans-serif;font-weight:500;margin-bottom:2rem;
}

/* Currency ticker */
.currency-ticker {
    display:flex;align-items:center;gap:2rem;
    animation:ticker 28s linear infinite;
    width:max-content;
}
.currency-ticker:hover { animation-play-state:paused; }
@keyframes ticker { from{transform:translateX(0)} to{transform:translateX(-50%)} }

.c-pill {
    display:inline-flex;align-items:center;gap:10px;
    padding:10px 20px;border-radius:10px;
    border:1px solid rgba(255,255,255,.07);
    background:rgba(255,255,255,.03);
    white-space:nowrap;
    transition:border-color .2s,background .2s;
}
.c-pill:hover { border-color:rgba(26,86,219,.3);background:rgba(26,86,219,.06); }
.c-flag { font-size:20px;line-height:1; }
.c-code { font-family:'Geist Mono',monospace;font-size:13px;font-weight:500;color:#94a3b8; }
.c-name { font-size:12px;color:#475569;font-family:'Outfit',sans-serif; }

/* Business type pills */
.biz-pills { display:flex;align-items:center;justify-content:center;flex-wrap:wrap;gap:10px;margin-top:2.5rem; }
.biz-pill {
    display:inline-flex;align-items:center;gap:6px;
    padding:7px 16px;border-radius:9999px;
    background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.07);
    font-size:13px;color:#64748b;font-family:'Outfit',sans-serif;
    transition:all .2s;
}
.biz-pill:hover { background:rgba(26,86,219,.08);border-color:rgba(26,86,219,.25);color:#93c5fd; }
.biz-dot { width:5px;height:5px;border-radius:50%;background:#334155; }
</style>

<section class="trust-bar" style="border-top:1px solid rgba(255,255,255,.05);border-bottom:1px solid rgba(255,255,255,.05);">

    <p class="trust-label">Works with every currency · Built for businesses worldwide</p>

    <!-- Scrolling currency strip -->
    <div style="overflow:hidden;width:100%;">
        <div class="currency-ticker">
            @php
            $currencies = [
                ['🇺🇸','USD','US Dollar'],
                ['🇬🇧','GBP','Pound Sterling'],
                ['🇪🇺','EUR','Euro'],
                ['🇦🇪','AED','UAE Dirham'],
                ['🇵🇰','PKR','Pakistani Rupee'],
                ['🇮🇳','INR','Indian Rupee'],
                ['🇸🇦','SAR','Saudi Riyal'],
                ['🇨🇦','CAD','Canadian Dollar'],
                ['🇦🇺','AUD','Australian Dollar'],
                ['🇸🇬','SGD','Singapore Dollar'],
                ['🇧🇩','BDT','Bangladeshi Taka'],
                ['🇳🇬','NGN','Nigerian Naira'],
            ];
            @endphp
            {{-- Render twice for seamless loop --}}
            @foreach(array_merge($currencies, $currencies) as $c)
            <div class="c-pill">
                <span class="c-flag">{{ $c[0] }}</span>
                <span class="c-code">{{ $c[1] }}</span>
                <span class="c-name">{{ $c[2] }}</span>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Business type pills -->
    <div class="biz-pills">
        @foreach([
            ['🛍', 'Retail Stores'],
            ['💻', 'Freelancers'],
            ['🍽', 'Restaurants'],
            ['📦', 'Import / Export'],
            ['🏥', 'Clinics'],
            ['🏗', 'Contractors'],
            ['📱', 'Agencies'],
            ['🎓', 'Education'],
        ] as $b)
        <span class="biz-pill"><span>{{ $b[0] }}</span><span class="biz-dot"></span>{{ $b[1] }}</span>
        @endforeach
    </div>

</section>


<!-- ══════════════════════════════════════
     CORE FEATURES  (alternating layout)
══════════════════════════════════════ -->
<section id="features" class="px-6" style="padding-top:8rem;padding-bottom:8rem;">
<div class="max-w-6xl mx-auto">

    <div class="text-center sr" style="margin-bottom:5rem;">
        <p class="feat-pill mb-4">Simple by design</p>
        <h2 class="font-display font-extrabold text-white mb-4" style="font-size:clamp(2rem,4vw,3rem);">Everything your cash book needs</h2>
        <p class="text-slate-400 max-w-xl mx-auto">One place for every payment, every receipt, every person on your team — so you always know where your money stands.</p>
    </div>

    <!-- Feature 1 -->
    <div class="grid lg:grid-cols-2 gap-12 items-center sr" style="margin-bottom:6rem;">
        <div>
            <div class="feat-pill mb-6">Keep it organized</div>
            <h3 class="font-display font-extrabold text-white mb-4" style="font-size:clamp(1.75rem,3vw,2.5rem);">A separate book for every month, project, or client</h3>
            <p class="text-slate-400 mb-8 leading-relaxed">Instead of one messy spreadsheet, create a separate book for each month, each client, or each project. Each book has its own total and its own history — clean, simple, and impossible to mix up.</p>
            <ul class="space-y-3">
                @foreach(['As many books as you need','Running total updates after every entry','Filter by money in, money out, or category','Download as PDF or spreadsheet (Pro)'] as $feat)
                <li class="flex items-center gap-3 text-slate-300 text-sm">
                    <span class="w-5 h-5 rounded-full bg-primary/20 flex items-center justify-center flex-shrink-0">
                        <svg width="10" height="10" viewBox="0 0 10 10" fill="none"><path d="M2 5l2 2 4-4" stroke="#3b82f6" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    {{ $feat }}
                </li>
                @endforeach
            </ul>
        </div>
        <div class="grad-border p-6">
            <div class="mock-card rounded-xl overflow-hidden" style="background:#0d1526">
                <div class="mockup-bar">
                    <div class="mockup-dot bg-red-500"></div><div class="mockup-dot bg-yellow-500"></div><div class="mockup-dot bg-green-500"></div>
                    <span class="ml-2 text-xs text-slate-600">Books — Meridian Studio</span>
                </div>
                <div class="p-5 space-y-2">
                    @foreach([['March 2026','Active','$ 10,600'],['February 2026','Closed','£ 8,200'],['Q1 — Client Alpha','Active','€ 22,400'],['Holiday Campaign','Active','$ 4,900']] as $b)
                    <div class="flex items-center justify-between rounded-lg px-4 py-3 text-sm" style="background:#111827;border:1px solid rgba(255,255,255,.05)">
                        <div>
                            <p class="text-white text-sm">{{ $b[0] }}</p>
                            <p class="text-xs text-slate-600">{{ $b[1] }}</p>
                        </div>
                        <span class="font-mono-cf text-sm text-green-400">+{{ $b[2] }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Feature 2 -->
    <div class="grid lg:grid-cols-2 gap-12 items-center sr" style="margin-bottom:6rem;">
        <div class="order-2 lg:order-1 grad-border p-6">
            <div class="mock-card rounded-xl overflow-hidden" style="background:#0d1526">
                <div class="mockup-bar">
                    <div class="mockup-dot bg-red-500"></div><div class="mockup-dot bg-yellow-500"></div><div class="mockup-dot bg-green-500"></div>
                    <span class="ml-2 text-xs text-slate-600">Team — Settings</span>
                </div>
                <div class="p-5">
                    <p class="text-xs text-slate-600 mb-3">Members · 3 of unlimited</p>
                    <div class="space-y-2">
                        @foreach([['JS','James Scott','Owner','bg-primary'],['AL','Amelia Lee','Editor','bg-emerald-600'],['RK','Ravi Kumar','Viewer','bg-slate-600']] as $m)
                        <div class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm" style="background:#111827;border:1px solid rgba(255,255,255,.05)">
                            <div class="w-8 h-8 rounded-full {{ $m[3] }} flex items-center justify-center text-xs font-bold text-white flex-shrink-0">{{ $m[0] }}</div>
                            <div class="flex-1">
                                <p class="text-white text-sm">{{ $m[1] }}</p>
                                <p class="text-xs text-slate-500">{{ $m[2] }}</p>
                            </div>
                            <span class="text-xs px-2 py-1 rounded" style="background:rgba(255,255,255,.05);color:#64748b">{{ $m[2] }}</span>
                        </div>
                        @endforeach
                    </div>
                    <div class="mt-3 flex items-center gap-2 text-xs text-primary cursor-pointer hover:text-accent transition-colors">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 1v12M1 7h12"/></svg>
                        Invite team member
                    </div>
                </div>
            </div>
        </div>
        <div class="order-1 lg:order-2">
            <div class="feat-pill mb-6">Work with your team</div>
            <h3 class="font-display font-extrabold text-white mb-4" style="font-size:clamp(1.75rem,3vw,2.5rem);">Invite your accountant, partner, or staff</h3>
            <p class="text-slate-400 mb-8 leading-relaxed">Add your accountant so they can enter transactions, give your business partner read-only access, or bring your whole team in. You decide exactly what each person can see or do.</p>
            <ul class="space-y-3">
                @foreach(['Full access, editor, or view-only roles','Unlimited team members (Pro)','See a full history of who changed what','Every entry shows who added it'] as $feat)
                <li class="flex items-center gap-3 text-slate-300 text-sm">
                    <span class="w-5 h-5 rounded-full bg-primary/20 flex items-center justify-center flex-shrink-0">
                        <svg width="10" height="10" viewBox="0 0 10 10" fill="none"><path d="M2 5l2 2 4-4" stroke="#3b82f6" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    {{ $feat }}
                </li>
                @endforeach
            </ul>
        </div>
    </div>

    <!-- Feature 3 — Recurring -->
    <div class="grid lg:grid-cols-2 gap-12 items-center sr">
        <div>
            <div class="feat-pill mb-6">Stop repeating yourself</div>
            <h3 class="font-display font-extrabold text-white mb-4" style="font-size:clamp(1.75rem,3vw,2.5rem);">Regular expenses enter themselves</h3>
            <p class="text-slate-400 mb-8 leading-relaxed">Rent, salaries, loan repayments — expenses that happen every month are tedious to enter manually. Set them once, choose how often they repeat, and they show up automatically. Never miss logging a regular payment again.</p>
            <ul class="space-y-3">
                @foreach(['Repeat daily, weekly, monthly, or yearly','Set an end date or let it run indefinitely','Change one or all future entries at once','Pauses automatically if you downgrade your plan'] as $feat)
                <li class="flex items-center gap-3 text-slate-300 text-sm">
                    <span class="w-5 h-5 rounded-full bg-primary/20 flex items-center justify-center flex-shrink-0">
                        <svg width="10" height="10" viewBox="0 0 10 10" fill="none"><path d="M2 5l2 2 4-4" stroke="#3b82f6" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    {{ $feat }}
                </li>
                @endforeach
            </ul>
        </div>
        <div class="grad-border p-6">
            <div class="mock-card rounded-xl p-5 space-y-3" style="background:#0d1526">
                <p class="text-xs text-slate-600 mb-1">Recurring · 4 active rules</p>
                @foreach([['Office Rent','Monthly · 1st','$ 2,200','active'],['Team Salaries','Monthly · 10th','$ 9,600','active'],['Cloud Hosting','Monthly · 5th','$ 149','active'],['Adobe Suite','Monthly · 15th','€ 89','paused']] as $r)
                <div class="flex items-center justify-between rounded-lg px-4 py-3 text-sm" style="background:#111827;border:1px solid rgba(255,255,255,.05)">
                    <div>
                        <p class="text-white text-sm">{{ $r[0] }}</p>
                        <p class="text-xs text-slate-500">{{ $r[1] }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="font-mono-cf text-sm text-red-400">-{{ $r[2] }}</span>
                        <span class="text-xs px-2 py-0.5 rounded-full" style="{{ $r[3] === 'active' ? 'color:#4ade80;background:rgba(74,222,128,.1)' : 'color:#94a3b8;background:rgba(51,65,85,.4)' }}">{{ $r[3] }}</span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

</div>
</section>


<!-- ══════════════════════════════════════
     AI SECTION
══════════════════════════════════════ -->
<section id="ai" class="px-6 relative overflow-hidden" style="padding-top:8rem;padding-bottom:8rem;background:linear-gradient(180deg,rgba(5,9,26,.6) 0%,rgba(8,14,32,.9) 50%,rgba(5,9,26,.6) 100%);border-top:1px solid rgba(26,86,219,.2);border-bottom:1px solid rgba(26,86,219,.2);">

    <div class="blue-line"></div>

    <!-- Background orb -->
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 pointer-events-none"
        style="width:600px;height:600px;background:radial-gradient(circle,rgba(26,86,219,.12) 0%,transparent 70%);"></div>

    <div class="max-w-6xl mx-auto relative z-10">

        <div class="text-center sr" style="margin-bottom:5rem;">
            <div class="ai-badge mb-6 mx-auto inline-flex">
                <span class="ai-dot"></span>
                Less typing. More clarity.
            </div>
            <h2 class="font-display font-extrabold text-white mb-4" style="font-size:clamp(2rem,4vw,3rem);">AI handles the<br>boring parts</h2>
            <p class="text-slate-400 max-w-xl mx-auto">Take a photo of a receipt, describe a purchase, or just open your reports — and the app does the rest. No accounting knowledge needed.</p>
        </div>

        <div class="grid md:grid-cols-3 gap-6">

            <!-- OCR card -->
            <div class="rounded-2xl p-8 card-hover border sr sr-delay-1" style="background:rgba(17,24,39,.7);border-color:rgba(26,86,219,.25);border-top:2px solid #1a56db;">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-6" style="background:rgba(26,86,219,.15)">
                    <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="#3b82f6" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><circle cx="12" cy="13" r="3"/>
                    </svg>
                </div>
                <h3 class="font-heading font-bold text-white text-xl mb-3">Snap a receipt, done</h3>
                <p class="text-slate-400 text-sm leading-relaxed mb-6">Take a photo of any receipt — paper, digital, PDF. The app reads it and fills in the amount, date, shop name, and category for you. No typing at all.</p>
                <div class="mock-card rounded-xl p-4 text-xs space-y-2" style="background:#0d1526;border:1px solid rgba(26,86,219,.15)">
                    <div class="flex justify-between">
                        <span class="text-slate-500">Amount</span>
                        <span class="font-mono-cf text-green-400">$ 148.50 <span class="text-emerald-600 text-[10px]">✦ AI</span></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Category</span>
                        <span class="text-blue-300">Supplies <span class="text-emerald-600 text-[10px]">✦ AI</span></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Date</span>
                        <span class="text-slate-300">Mar 15, 2026 <span class="text-emerald-600 text-[10px]">✦ AI</span></span>
                    </div>
                </div>
                <p class="text-xs text-slate-600 mt-4">Up to 200 receipts/month · Pro</p>
            </div>

            <!-- Auto-categorization card -->
            <div class="rounded-2xl p-8 card-hover border sr sr-delay-2" style="background:rgba(17,24,39,.7);border-color:rgba(59,130,246,.2);border-top:2px solid #3b82f6;">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-6" style="background:rgba(59,130,246,.12)">
                    <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="#3b82f6" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a2 2 0 014-4z"/>
                    </svg>
                </div>
                <h3 class="font-heading font-bold text-white text-xl mb-3">It sorts your expenses</h3>
                <p class="text-slate-400 text-sm leading-relaxed mb-6">Just write what you spent money on — "office stationery" or "team lunch". The app suggests what category it belongs to. Tap once to confirm. No manual sorting.</p>
                <div class="mock-card rounded-xl p-4 text-sm" style="background:#0d1526;border:1px solid rgba(59,130,246,.12)">
                    <p class="text-slate-300 mb-3">Office stationery — A4 paper</p>
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs" style="background:rgba(139,92,246,.12);border:1px solid rgba(139,92,246,.25);color:#a78bfa">
                        <span class="ai-dot" style="background:#8b5cf6;box-shadow:0 0 8px rgba(139,92,246,.8)"></span>
                        AI suggests: Office Supplies
                        <span class="text-violet-400 cursor-pointer">Apply →</span>
                    </div>
                </div>
                <p class="text-xs text-slate-600 mt-4">Unlimited · Pro</p>
            </div>

            <!-- Cash Flow Insights card -->
            <div class="rounded-2xl p-8 card-hover border sr sr-delay-3" style="background:rgba(17,24,39,.7);border-color:rgba(99,102,241,.2);border-top:2px solid #6366f1;">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-6" style="background:rgba(99,102,241,.12)">
                    <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="#6366f1" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                </div>
                <h3 class="font-heading font-bold text-white text-xl mb-3">Understand your money</h3>
                <p class="text-slate-400 text-sm leading-relaxed mb-6">Open your reports and get a plain-language summary: what went up, what's eating most of your budget, and what to watch. Written in simple sentences, not numbers.</p>
                <div class="mock-card rounded-xl p-4 space-y-2.5" style="background:#0d1526;border:1px solid rgba(99,102,241,.15)">
                    <div class="flex items-start gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-green-400 mt-1.5 flex-shrink-0"></span>
                        <p class="text-xs text-slate-300">Cash in up <span class="text-green-400">+34%</span> vs last month. Healthy trend.</p>
                    </div>
                    <div class="flex items-start gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400 mt-1.5 flex-shrink-0"></span>
                        <p class="text-xs text-slate-300">Salaries are <span class="text-amber-400">57%</span> of total outflow. Watch overhead.</p>
                    </div>
                    <div class="flex items-start gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-400 mt-1.5 flex-shrink-0"></span>
                        <p class="text-xs text-slate-300">3 recurring expenses due this week: <span class="text-blue-300">$1,840</span> total.</p>
                    </div>
                </div>
                <p class="text-xs text-slate-600 mt-4">Refreshes daily · Pro</p>
            </div>

        </div>
    </div>
</section>


<!-- ══════════════════════════════════════
     STATS
══════════════════════════════════════ -->
<section class="px-6" style="padding-top:8rem;padding-bottom:8rem;">
<div class="max-w-5xl mx-auto">
    <div class="grid md:grid-cols-3 gap-8 text-center">
        <div class="sr">
            <div class="stat-num mb-2">$0</div>
            <p class="text-slate-400 text-sm">to get started — free forever</p>
        </div>
        <div class="sr sr-delay-1">
            <div class="stat-num mb-2">2 min</div>
            <p class="text-slate-400 text-sm">and your first book is ready</p>
        </div>
        <div class="sr sr-delay-2">
            <div class="stat-num mb-2">$5</div>
            <p class="text-slate-400 text-sm">per month for AI features — nothing hidden</p>
        </div>
    </div>
</div>
</section>


<!-- ══════════════════════════════════════
     PRICING
══════════════════════════════════════ -->
<section id="pricing" class="px-6" style="padding-top:8rem;padding-bottom:8rem;background:rgba(7,11,24,.5)">
<div class="max-w-4xl mx-auto">

    <div class="text-center sr" style="margin-bottom:4rem;">
        <h2 class="font-display font-extrabold text-white mb-4" style="font-size:clamp(2rem,4vw,3rem);">Simple, honest pricing</h2>
        <p class="text-slate-400">Start free. Upgrade when you need AI and advanced features.</p>
    </div>

    <div class="grid md:grid-cols-2 gap-6">

        <!-- Free -->
        <div class="price-card sr">
            <div class="mb-8">
                <h3 class="font-heading font-bold text-white text-xl mb-2">Free</h3>
                <div class="flex items-baseline gap-1 mb-3">
                    <span class="font-mono-cf text-4xl text-white">$0</span>
                    <span class="text-slate-500 text-sm">forever</span>
                </div>
                <p class="text-slate-400 text-sm">Everything you need to track cash flow for one business.</p>
            </div>
            <ul class="space-y-3 mb-8">
                @foreach(['1 business','Unlimited books & entries','Up to 2 people on your team','Attach receipt photos to any entry','Full history of who changed what','Community support'] as $f)
                <li class="flex items-center gap-3 text-sm text-slate-300">
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M2 7l3 3 7-7" stroke="#22c55e" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    {{ $f }}
                </li>
                @endforeach
            </ul>
            <a href="/register" class="free-plan-btn block w-full text-center py-3 rounded-xl border border-white/10 text-white text-sm font-medium hover:bg-white/5 transition-colors">
                Get started free
            </a>
        </div>

        <!-- Pro -->
        <div class="price-card featured sr sr-delay-1">
            <div class="mb-8">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="font-heading font-bold text-white text-xl">Pro</h3>
                    <span class="popular-badge text-xs px-2 py-1 rounded-full font-medium" style="background:rgba(26,86,219,.2);color:#93c5fd">Most popular</span>
                </div>
                <div class="flex items-baseline gap-1 mb-3">
                    <span class="font-mono-cf text-4xl text-white">$5</span>
                    <span class="text-slate-500 text-sm">/month</span>
                </div>
                <p class="text-slate-400 text-sm">Everything automated — for businesses that don't want to waste time on admin.</p>
            </div>
            <ul class="space-y-3 mb-8">
                @foreach(['Everything in Free','Unlimited businesses','Unlimited team members','Scan receipts — AI fills in the details','AI sorts your expenses automatically','Plain-English summary of your cash flow','Visual reports & charts','Expenses that repeat enter themselves','Download as PDF or spreadsheet','Priority support'] as $f)
                <li class="flex items-center gap-3 text-sm text-slate-300">
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M2 7l3 3 7-7" stroke="#3b82f6" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    {{ $f }}
                </li>
                @endforeach
            </ul>
            <a href="/register" class="block w-full text-center py-3 rounded-xl bg-primary hover:bg-blue-600 transition-colors text-white text-sm font-medium">
                Start Pro free trial
            </a>
        </div>
    </div>

    <p class="text-center text-xs text-slate-600 mt-8">
        Cancel anytime · No contracts · Stripe-secured payments
    </p>
</div>
</section>


<!-- ══════════════════════════════════════
     FAQ
══════════════════════════════════════ -->
<section id="faq" class="px-6" style="padding-top:8rem;padding-bottom:8rem;">
<div class="max-w-2xl mx-auto">

    <div class="text-center sr" style="margin-bottom:4rem;">
        <h2 class="font-display font-extrabold text-white mb-4" style="font-size:clamp(2rem,4vw,3rem);">Frequently asked</h2>
    </div>

    <div class="space-y-0 sr">

        @php
        $faqs = [
            ['Is it really free?', 'Yes — the Free plan costs nothing, forever. No credit card needed to sign up. You get one business, unlimited books and entries, up to 2 team members, the ability to attach receipts, and a full history of changes.'],
            ['What currency does it work with?', 'Any currency. You set your own currency when you create a business — dollar, pound, euro, rupee, dirham, whatever you use. The receipt scanner even detects the currency on the receipt automatically and converts it if needed.'],
            ['How does the receipt scanning work?', 'When adding an entry, tap "Scan Receipt" and take a photo or upload a file. The app reads the receipt and fills in the amount, date, shop name, and what category it belongs to — automatically. You just check it looks right and save. Pro plan includes up to 200 scans per month.'],
            ['Can I invite my accountant or staff?', 'Yes. Just enter their email and choose what they can do: full editing access, or view-only. They get an invitation link. Free plan supports 2 people total; Pro is unlimited.'],
            ['Is my data safe?', 'Yes. Everything is encrypted, and your data is completely separate from other users — nobody else can see it. Receipt photos are stored privately and only accessible when you\'re logged in. Payments are handled by Stripe; we never see your card details.'],
            ['Can I download my records?', 'Pro users can export any book as a clean PDF (great for your accountant or tax filing) or as a spreadsheet file. Both include every entry and the running total.'],
            ['What happens if I stop paying for Pro?', 'You keep Pro access until your current billing period ends. After that, you switch to Free automatically: your first business stays fully active, any extra businesses are frozen (not deleted), and your automatic entries pause. All your data is safe.'],
        ];
        @endphp

        @foreach($faqs as $faq)
        <div class="faq-item" x-data="{ open: false }">
            <button class="faq-btn" @click="open = !open">
                <span class="font-heading font-semibold text-white text-base pr-8">{{ $faq[0] }}</span>
                <svg class="faq-icon flex-shrink-0 text-slate-500 w-5 h-5" :style="open ? 'transform:rotate(45deg)' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
            </button>
            <div class="faq-body" x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" style="display:none">
                <p class="text-slate-400 text-sm leading-relaxed pb-6">{{ $faq[1] }}</p>
            </div>
        </div>
        @endforeach

    </div>
</div>
</section>


<!-- ══════════════════════════════════════
     FINAL CTA
══════════════════════════════════════ -->
<section class="cta-section px-6 text-center relative overflow-hidden" style="padding-top:8rem;padding-bottom:8rem;background:linear-gradient(180deg,#0a0f1e 0%,rgba(26,86,219,.08) 50%,#0a0f1e 100%)">
    <div class="absolute inset-x-0 top-0 h-px" style="background:linear-gradient(90deg,transparent,rgba(26,86,219,.5),transparent)"></div>

    <div class="max-w-3xl mx-auto relative z-10">
        <div class="ai-badge mx-auto mb-8 inline-flex sr">
            <span class="ai-dot"></span>
            Start tracking in 2 minutes
        </div>
        <h2 class="font-display font-extrabold text-white mb-6 sr sr-delay-1" style="font-size:clamp(2.5rem,6vw,5rem);line-height:1.05">
            Always know where<br>your money stands.
        </h2>
        <p class="text-slate-400 text-lg mb-10 sr sr-delay-2">Start free. No setup fees, no complicated forms. Your first book is ready in two minutes.</p>

        <!-- Email capture -->
        <div class="flex justify-center mb-4 sr sr-delay-3">
            <form action="/register" method="GET" class="email-form w-full" style="max-width:460px">
                <input type="email" name="email" placeholder="Enter your work email" required autocomplete="email">
                <button type="submit">Get started free →</button>
            </form>
        </div>
        <p class="text-xs text-slate-600 sr sr-delay-4">Free forever · No credit card required</p>
    </div>
</section>


<!-- ══════════════════════════════════════
     FOOTER
══════════════════════════════════════ -->
<footer class="site-footer" style="border-top:1px solid rgba(255,255,255,.06);padding:5rem 1.5rem 3rem;background:#070b18;">
<div class="max-w-6xl mx-auto">

    <!-- Top row: brand + links -->
    <div style="display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:3rem;margin-bottom:4rem;" class="footer-grid">

        <!-- Brand -->
        <div>
            <a href="/" style="display:inline-flex;align-items:center;gap:10px;text-decoration:none;margin-bottom:1rem;">
                <svg width="30" height="30" viewBox="0 0 28 28" fill="none"><rect width="28" height="28" rx="7" fill="#1a56db"/><path d="M8 14h12M8 10h7M8 18h10" stroke="#fff" stroke-width="2" stroke-linecap="round"/></svg>
                <span class="footer-brand-name" style="font-family:'Bricolage Grotesque',sans-serif;font-weight:700;font-size:18px;color:#f8fafc;">CashFlow</span>
            </a>
            <p style="font-size:14px;color:#94a3b8;line-height:1.7;max-width:260px;">
                AI-powered cash flow tracking for small businesses, freelancers, and finance teams worldwide.
            </p>
            <div style="display:flex;align-items:center;gap:6px;margin-top:1.25rem;font-size:12px;color:#64748b;">
                <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#22c55e;"></span>
                All systems operational
            </div>
        </div>

        <!-- Product -->
        <div>
            <p style="font-size:11px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:#64748b;margin-bottom:1.25rem;font-family:'Outfit',sans-serif;">Product</p>
            <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:.75rem;">
                <li><a href="#features" style="font-size:14px;color:#64748b;text-decoration:none;transition:color .15s;" onmouseover="this.style.color='#e2e8f0'" onmouseout="this.style.color='#94a3b8'">Features</a></li>
                <li><a href="#ai"       style="font-size:14px;color:#94a3b8;text-decoration:none;" onmouseover="this.style.color='#e2e8f0'" onmouseout="this.style.color='#94a3b8'">AI Tools</a></li>
                <li><a href="#pricing"  style="font-size:14px;color:#94a3b8;text-decoration:none;" onmouseover="this.style.color='#e2e8f0'" onmouseout="this.style.color='#94a3b8'">Pricing</a></li>
                <li><a href="#faq"      style="font-size:14px;color:#94a3b8;text-decoration:none;" onmouseover="this.style.color='#e2e8f0'" onmouseout="this.style.color='#94a3b8'">FAQ</a></li>
            </ul>
        </div>

        <!-- Account -->
        <div>
            <p style="font-size:11px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:#64748b;margin-bottom:1.25rem;font-family:'Outfit',sans-serif;">Account</p>
            <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:.75rem;">
                <li><a href="/login"    style="font-size:14px;color:#94a3b8;text-decoration:none;" onmouseover="this.style.color='#e2e8f0'" onmouseout="this.style.color='#94a3b8'">Sign in</a></li>
                <li><a href="/register" style="font-size:14px;color:#94a3b8;text-decoration:none;" onmouseover="this.style.color='#e2e8f0'" onmouseout="this.style.color='#94a3b8'">Create account</a></li>
                <li><a href="/register" style="font-size:14px;color:#94a3b8;text-decoration:none;" onmouseover="this.style.color='#e2e8f0'" onmouseout="this.style.color='#94a3b8'">Free trial</a></li>
            </ul>
        </div>

        <!-- Legal -->
        <div>
            <p style="font-size:11px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:#64748b;margin-bottom:1.25rem;font-family:'Outfit',sans-serif;">Legal</p>
            <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:.75rem;">
                <li><span style="font-size:14px;color:#64748b;">Privacy Policy</span></li>
                <li><span style="font-size:14px;color:#64748b;">Terms of Service</span></li>
                <li><span style="font-size:14px;color:#64748b;">Cookie Policy</span></li>
            </ul>
        </div>

    </div>

    <!-- Bottom bar -->
    <div style="border-top:1px solid rgba(255,255,255,.05);padding-top:2rem;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:1rem;">
        <p style="font-size:13px;color:#64748b;">© {{ date('Y') }} CashFlow. Built for small businesses worldwide.</p>
        <div style="display:flex;align-items:center;gap:1.5rem;">
            <span style="font-size:12px;color:#64748b;">🔒 Secured by Stripe</span>
            <span style="font-size:12px;color:#64748b;">🤖 AI-powered insights</span>
        </div>
    </div>

</div>
</footer>

<style>
@media (max-width: 768px) {
    .footer-grid { grid-template-columns: 1fr 1fr !important; gap: 2rem !important; }
    .footer-grid > div:first-child { grid-column: 1 / -1; }
}
</style>


<!-- ══════════════════════════════════════
     SCRIPTS
══════════════════════════════════════ -->
<script>
/* ── Theme: init from localStorage, default = light ── */
(function() {
    // Landing page always starts in light mode
})();

function toggleTheme() {
    var html = document.documentElement;
    var isDark = html.getAttribute('data-theme') === 'dark';
    if (isDark) {
        html.removeAttribute('data-theme');
        localStorage.setItem('cashflow_theme', 'light');
    } else {
        html.setAttribute('data-theme', 'dark');
        localStorage.setItem('cashflow_theme', 'dark');
    }
}

/* ── Navbar: shrink + float on scroll ── */
(function() {
    var nav = document.querySelector('.nav-root');
    if (!nav) return;
    window.addEventListener('scroll', function() {
        if (window.scrollY > 72) {
            nav.classList.add('nav-scrolled');
        } else {
            nav.classList.remove('nav-scrolled');
        }
    }, { passive: true });
})();

/* ── Scroll reveal ── */
(function() {
    var els = document.querySelectorAll('.sr');
    if (!els.length) return;
    var io = new IntersectionObserver(function(entries) {
        entries.forEach(function(e) {
            if (e.isIntersecting) { e.target.classList.add('on'); io.unobserve(e.target); }
        });
    }, { threshold: 0.06, rootMargin: '0px 0px -30px 0px' });
    els.forEach(function(el) { io.observe(el); });
})();
</script>

</body>
</html>
