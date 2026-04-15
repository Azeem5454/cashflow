<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="{
          darkMode: (localStorage.getItem('cashflow_theme') ?? 'light') === 'dark',
          sidebarOpen: false,
          toggleTheme() {
              document.documentElement.classList.add('theme-transition');
              this.darkMode = !this.darkMode;
              localStorage.setItem('cashflow_theme', this.darkMode ? 'dark' : 'light');
              document.documentElement.classList.toggle('dark', this.darkMode);
              window.setTimeout(() => document.documentElement.classList.remove('theme-transition'), 300);
          }
      }"
      x-on:livewire:navigated.window="sidebarOpen = false"
      x-on:popstate.window="sidebarOpen = false">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Prevent dark mode flash: runs synchronously before first paint --}}
    <script>if ((localStorage.getItem('cashflow_theme') ?? 'light') === 'dark') { document.documentElement.classList.add('dark'); }</script>
    {{-- Re-apply dark class after wire:navigate page swaps --}}
    <script>document.addEventListener('livewire:navigated', function() { document.documentElement.classList.toggle('dark', (localStorage.getItem('cashflow_theme') ?? 'light') === 'dark'); });</script>

    <title>{{ config('app.name', 'CashFlow') }}</title>
    <meta name="description" content="{{ config('app.tagline') ?: 'Track every transaction, scan receipts with AI, and get cash flow insights.' }}">
    <meta name="robots" content="noindex,nofollow">
    <meta name="theme-color" content="#0a0f1e">
    @php $appFaviconSrc = \App\Models\UploadedAsset::has('favicon') ? route('brand-asset', 'favicon') . '?v=' . \App\Models\UploadedAsset::cacheBuster('favicon') : asset('favicon.png'); @endphp
    <link rel="icon" type="image/png" href="{{ $appFaviconSrc }}">
    <link rel="apple-touch-icon" href="{{ $appFaviconSrc }}">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="{{ \App\Helpers\Setting::get('google_fonts_url', 'https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..60,400;12..60,700;12..60,800&family=Plus+Jakarta+Sans:wght@400;600;700&family=Outfit:wght@300;400;500&family=Geist+Mono:wght@400&display=swap') }}" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @if(file_exists(public_path('brand/theme.css')))
        <link rel="stylesheet" href="{{ asset('brand/theme.css') }}?v={{ filemtime(public_path('brand/theme.css')) }}">
    @endif
    @livewireStyles
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="font-body antialiased dark:bg-navy bg-slate-50 dark:text-white text-gray-900 transition-colors duration-300">

<div class="flex h-screen overflow-hidden">

    {{-- Mobile overlay --}}
    <div x-show="sidebarOpen"
         x-transition:enter="transition-opacity ease-linear duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="sidebarOpen = false"
         class="fixed inset-0 z-40 bg-black/60 backdrop-blur-sm lg:hidden"
         style="display:none;"></div>

    {{-- ===== SIDEBAR ===== --}}
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
           class="fixed inset-y-0 left-0 z-50 w-64 flex flex-col transition-transform duration-300 ease-in-out
                  lg:static lg:translate-x-0
                  dark:bg-dark bg-white
                  dark:border-slate-800 border-r border-gray-200">

        {{-- Logo --}}
        <div class="flex items-center justify-between h-16 px-5 flex-shrink-0 dark:border-slate-800 border-b border-gray-200">
            <a href="{{ route('dashboard') }}" wire:navigate>
                <x-app-logo />
            </a>
            <button @click="sidebarOpen = false"
                    class="lg:hidden p-2 rounded-lg dark:text-slate-500 text-gray-400
                           dark:hover:bg-slate-800 hover:bg-gray-100 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Business Switcher --}}
        @auth
            @php
                $userBusinesses = auth()->user()->businesses()->orderBy('name')->get();
                $currentBusiness = request()->route('business');
                if ($currentBusiness && !$currentBusiness instanceof \App\Models\Business) {
                    $currentBusiness = \App\Models\Business::find($currentBusiness);
                }
                // One efficient query: net balance per business
                $sidebarBalances = \App\Models\Entry::query()
                    ->selectRaw('books.business_id, SUM(CASE WHEN entries.type = \'in\' THEN entries.amount ELSE -entries.amount END) as net')
                    ->join('books', 'entries.book_id', '=', 'books.id')
                    ->whereIn('books.business_id', $userBusinesses->pluck('id'))
                    ->groupBy('books.business_id')
                    ->pluck('net', 'business_id');
            @endphp
            @if($userBusinesses->count() > 0)
                <div class="px-3 pt-4 pb-2" x-data="{ switcher: false }">
                    <p class="text-[10px] font-bold uppercase tracking-widest dark:text-slate-600 text-gray-400 px-3 pb-2">Business</p>
                    <div class="relative">
                        <button @click="switcher = !switcher" @click.outside="switcher = false"
                                class="flex items-center gap-3 w-full px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150
                                       dark:bg-slate-800/60 bg-gray-50 dark:hover:bg-slate-800 hover:bg-gray-100
                                       dark:border-slate-700/60 border-gray-200 border">
                            <div class="w-7 h-7 rounded-lg bg-primary/15 flex items-center justify-center flex-shrink-0">
                                <svg class="w-3.5 h-3.5 text-primary" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/>
                                </svg>
                            </div>
                            <span class="flex-1 text-left truncate dark:text-white text-gray-900">
                                {{ $currentBusiness ? $currentBusiness->name : 'All Businesses' }}
                            </span>
                            <svg class="w-4 h-4 dark:text-slate-500 text-gray-400 flex-shrink-0 transition-transform duration-150"
                                 :class="switcher ? 'rotate-180' : ''"
                                 fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
                            </svg>
                        </button>

                        <div x-show="switcher"
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 -translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100 translate-y-0"
                             x-transition:leave-end="opacity-0 -translate-y-1"
                             class="absolute left-0 right-0 mt-1.5 z-30
                                    dark:bg-[#1e293b] bg-white
                                    dark:border-slate-700 border border-gray-200
                                    rounded-xl shadow-2xl shadow-black/20 overflow-hidden"
                             style="display: none;">

                            @if($userBusinesses->count() > 4)
                                <div class="px-3 pt-3 pb-1">
                                    <input type="text" placeholder="Search business…"
                                           x-ref="businessSearch"
                                           x-on:input="
                                               let val = $el.value.toLowerCase();
                                               $el.closest('[x-show]').querySelectorAll('[data-business]').forEach(el => {
                                                   el.style.display = el.dataset.business.toLowerCase().includes(val) ? '' : 'none';
                                               });
                                           "
                                           class="w-full px-3 py-2 text-xs font-body rounded-lg
                                                  dark:bg-slate-800 bg-gray-50
                                                  dark:border-slate-600 border-gray-200 border
                                                  dark:text-white text-gray-900
                                                  dark:placeholder-slate-500 placeholder-gray-400
                                                  focus:outline-none focus:ring-2 focus:ring-primary/40">
                                </div>
                            @endif

                            @php
                                $ownedBizList  = $userBusinesses->where('pivot.role', 'owner')->values();
                                $sharedBizList = $userBusinesses->whereIn('pivot.role', ['editor', 'viewer'])->values();
                            @endphp
                            <div class="max-h-56 overflow-y-auto py-1.5">
                                {{-- Mine --}}
                                @if($ownedBizList->isNotEmpty())
                                    <p class="px-4 pt-1.5 pb-1 text-[9px] font-bold uppercase tracking-widest dark:text-slate-600 text-gray-400">Mine</p>
                                    @foreach($ownedBizList as $biz)
                                        @php
                                            $bizNet      = isset($sidebarBalances[$biz->id]) ? (float)$sidebarBalances[$biz->id] : null;
                                            $bizCurrency = $biz->currency ?? 'PKR';
                                            $isActive    = $currentBusiness && $currentBusiness->id === $biz->id;
                                        @endphp
                                        <a href="{{ route('businesses.show', $biz) }}" wire:navigate
                                           @click="switcher = false"
                                           data-business="{{ $biz->name }}"
                                           class="flex items-center gap-2.5 px-4 py-2 text-sm transition-colors duration-100
                                                  {{ $isActive
                                                      ? 'dark:bg-primary/10 bg-primary/5 dark:text-blue-light text-primary font-semibold'
                                                      : 'dark:text-slate-300 text-gray-700 dark:hover:bg-slate-700/50 hover:bg-gray-50' }}">
                                            <div class="w-5 h-5 rounded-md flex items-center justify-center flex-shrink-0 text-[10px] font-bold
                                                        {{ $isActive ? 'bg-primary text-white' : 'dark:bg-slate-700 bg-gray-200 dark:text-slate-400 text-gray-500' }}">
                                                {{ strtoupper(substr($biz->name, 0, 1)) }}
                                            </div>
                                            <span class="flex-1 truncate text-xs">{{ $biz->name }}</span>
                                            @if($bizNet !== null)
                                                <span class="text-[10px] font-mono font-bold flex-shrink-0 {{ $bizNet < 0 ? 'text-red-400' : 'text-emerald-500' }}">
                                                    {{ $bizNet < 0 ? '−' : '+' }}{{ $bizCurrency }} {{ abs($bizNet) >= 1000000 ? number_format(abs($bizNet)/1000000,1).'M' : (abs($bizNet) >= 1000 ? number_format(abs($bizNet)/1000,1).'K' : number_format(abs($bizNet),2)) }}
                                                </span>
                                            @endif
                                        </a>
                                    @endforeach
                                @endif

                                {{-- Shared with Me --}}
                                @if($sharedBizList->isNotEmpty())
                                    <div class="mx-3 my-1.5 border-t dark:border-slate-700 border-gray-100"></div>
                                    <p class="px-4 pb-1 text-[9px] font-bold uppercase tracking-widest dark:text-slate-600 text-gray-400">Shared with Me</p>
                                    @foreach($sharedBizList as $biz)
                                        @php
                                            $bizNet      = isset($sidebarBalances[$biz->id]) ? (float)$sidebarBalances[$biz->id] : null;
                                            $bizCurrency = $biz->currency ?? 'PKR';
                                            $isActive    = $currentBusiness && $currentBusiness->id === $biz->id;
                                            $bizRole     = $biz->pivot->role ?? 'viewer';
                                        @endphp
                                        <a href="{{ route('businesses.show', $biz) }}" wire:navigate
                                           @click="switcher = false"
                                           data-business="{{ $biz->name }}"
                                           class="flex items-center gap-2.5 px-4 py-2 text-sm transition-colors duration-100
                                                  {{ $isActive
                                                      ? 'dark:bg-primary/10 bg-primary/5 dark:text-blue-light text-primary font-semibold'
                                                      : 'dark:text-slate-300 text-gray-700 dark:hover:bg-slate-700/50 hover:bg-gray-50' }}">
                                            <div class="w-5 h-5 rounded-md flex items-center justify-center flex-shrink-0 text-[10px] font-bold
                                                        {{ $isActive ? 'bg-primary text-white' : 'dark:bg-slate-600 bg-gray-200 dark:text-slate-500 text-gray-500' }}">
                                                {{ strtoupper(substr($biz->name, 0, 1)) }}
                                            </div>
                                            <span class="flex-1 truncate text-xs">{{ $biz->name }}</span>
                                            @if($bizNet !== null)
                                                <span class="text-[10px] font-mono font-bold flex-shrink-0 {{ $bizNet < 0 ? 'text-red-400' : 'text-emerald-500' }}">
                                                    {{ $bizNet < 0 ? '−' : '+' }}{{ $bizCurrency }} {{ abs($bizNet) >= 1000000 ? number_format(abs($bizNet)/1000000,1).'M' : (abs($bizNet) >= 1000 ? number_format(abs($bizNet)/1000,1).'K' : number_format(abs($bizNet),2)) }}
                                                </span>
                                            @endif
                                        </a>
                                    @endforeach
                                @endif
                            </div>

                            <div class="dark:border-slate-700 border-t border-gray-100">
                                <a href="{{ route('businesses.create') }}" wire:navigate @click="switcher = false"
                                   class="flex items-center gap-2.5 px-4 py-2.5 text-sm dark:text-primary text-primary
                                          dark:hover:bg-slate-700/50 hover:bg-gray-50 transition-colors font-medium">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                                    </svg>
                                    Add New Business
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endauth

        {{-- Navigation --}}
        <nav class="flex-1 px-3 py-5 space-y-0.5 overflow-y-auto">
            <p class="text-[10px] font-bold uppercase tracking-widest dark:text-slate-600 text-gray-400 px-3 pb-2">Main</p>

            <a href="{{ route('dashboard') }}" wire:navigate
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150
                      {{ request()->routeIs('dashboard')
                          ? 'bg-primary/10 text-primary dark:bg-primary/20 dark:text-blue-light'
                          : 'dark:text-slate-400 text-gray-600 dark:hover:bg-slate-800/80 hover:bg-gray-100 dark:hover:text-white hover:text-gray-900' }}">
                <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>
                </svg>
                Dashboard
            </a>


            <div class="pt-4">
                <p class="text-[10px] font-bold uppercase tracking-widest dark:text-slate-600 text-gray-400 px-3 pb-2">Account</p>

                <a href="{{ route('profile.edit') }}" wire:navigate
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150
                          {{ request()->routeIs('profile.*')
                              ? 'bg-primary/10 text-primary dark:bg-primary/20 dark:text-blue-light'
                              : 'dark:text-slate-400 text-gray-600 dark:hover:bg-slate-800/80 hover:bg-gray-100 dark:hover:text-white hover:text-gray-900' }}">
                    <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                    </svg>
                    Settings
                </a>

                <a href="{{ route('billing') }}" wire:navigate
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150
                          {{ request()->routeIs('billing') ? 'bg-primary/10 text-primary dark:bg-primary/20 dark:text-blue-light' : 'dark:text-slate-400 text-gray-600 dark:hover:bg-slate-800/80 hover:bg-gray-100 dark:hover:text-white hover:text-gray-900' }}">
                    <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z"/>
                    </svg>
                    Billing
                </a>
            </div>
        </nav>

        {{-- Bottom: Notification bell + Theme toggle + User --}}
        <div class="p-3 flex-shrink-0 dark:border-slate-800 border-t border-gray-200 space-y-1">

            {{-- Notification bell --}}
            <livewire:notification-bell wire:key="notification-bell" :sidebar="true" />

            {{-- Theme toggle --}}
            <button @click="toggleTheme()"
                    class="flex items-center gap-3 w-full px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150
                           dark:text-slate-400 text-gray-600 dark:hover:bg-slate-800/80 hover:bg-gray-100 dark:hover:text-white hover:text-gray-900">
                {{-- Sun icon: visible in dark mode --}}
                <svg class="w-[18px] h-[18px] text-amber-400 flex-shrink-0 hidden dark:block" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z"/>
                </svg>
                {{-- Moon icon: visible in light mode --}}
                <svg class="w-[18px] h-[18px] text-slate-500 flex-shrink-0 dark:hidden" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z"/>
                </svg>
                <span class="flex-1 text-left">
                    <span class="dark:hidden">Dark Mode</span>
                    <span class="hidden dark:inline">Light Mode</span>
                </span>
                {{-- Toggle pill: CSS-only, no Alpine bindings --}}
                <div class="relative w-9 h-5 rounded-full transition-colors duration-200 flex-shrink-0 bg-gray-300 dark:bg-primary">
                    <div class="absolute top-0.5 w-4 h-4 bg-white rounded-full shadow-sm transition-transform duration-200 translate-x-0.5 dark:translate-x-4"></div>
                </div>
            </button>

            {{-- User profile --}}
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" @click.outside="open = false"
                        class="flex items-center gap-3 w-full px-3 py-2.5 rounded-xl text-sm transition-all duration-150
                               dark:hover:bg-slate-800/80 hover:bg-gray-100">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-primary to-accent flex items-center justify-center text-white text-sm font-bold flex-shrink-0 shadow">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div class="flex-1 text-left min-w-0">
                        <div class="flex items-center gap-1.5">
                            <p class="text-sm font-semibold dark:text-white text-gray-900 truncate leading-tight">{{ auth()->user()->name }}</p>
                            @if(auth()->user()->isPro())
                                <span class="text-[9px] font-bold tracking-wider px-1.5 py-0.5 rounded flex-shrink-0"
                                      style="background:rgba(245,158,11,0.15);color:#f59e0b">PRO</span>
                            @else
                                <span class="text-[9px] font-bold tracking-wider px-1.5 py-0.5 rounded flex-shrink-0 dark:bg-slate-700 bg-gray-200 dark:text-slate-300 text-gray-600">FREE</span>
                            @endif
                        </div>
                        <p class="text-xs dark:text-slate-500 text-gray-400 truncate leading-tight">{{ auth()->user()->email }}</p>
                    </div>
                    <svg class="w-4 h-4 dark:text-slate-500 text-gray-400 flex-shrink-0 transition-transform duration-150"
                         :class="open ? 'rotate-180' : ''"
                         fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
                    </svg>
                </button>

                <div x-show="open"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 -translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-2"
                     class="absolute bottom-full left-0 right-0 mb-1 dark:bg-[#1e293b] bg-white dark:border-slate-700 border border-gray-200 rounded-xl shadow-2xl overflow-hidden z-50"
                     style="display:none;">
                    <a href="{{ route('profile.edit') }}" wire:navigate
                       class="flex items-center gap-2.5 px-4 py-3 text-sm dark:text-slate-300 text-gray-700 dark:hover:bg-slate-700/50 hover:bg-gray-50 transition-colors">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/>
                        </svg>
                        Your Profile
                    </a>
                    <a href="{{ route('billing') }}" wire:navigate
                       class="flex items-center gap-2.5 px-4 py-3 text-sm dark:text-slate-300 text-gray-700 dark:hover:bg-slate-700/50 hover:bg-gray-50 transition-colors">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z"/>
                        </svg>
                        Billing & Plans
                        @if(! auth()->user()->isPro())
                            <span class="ml-auto text-[9px] font-bold tracking-wider px-1.5 py-0.5 rounded"
                                  style="background:rgba(245,158,11,0.15);color:#f59e0b">UPGRADE</span>
                        @endif
                    </a>
                    <div class="dark:border-slate-700 border-t border-gray-100 mx-3"></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="flex items-center gap-2.5 w-full px-4 py-3 text-sm text-red-500 dark:hover:bg-slate-700/50 hover:bg-gray-50 transition-colors">
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9V5.25A2.25 2.25 0 0 1 10.5 3h6a2.25 2.25 0 0 1 2.25 2.25v13.5A2.25 2.25 0 0 1 16.5 20.25h-6a2.25 2.25 0 0 1-2.25-2.25V15m-3 0-3-3m0 0 3-3m-3 3H15"/>
                            </svg>
                            Sign Out
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </aside>

    {{-- ===== MAIN AREA ===== --}}
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

        {{-- Mobile top bar --}}
        <header class="lg:hidden flex items-center justify-between h-14 px-4 flex-shrink-0
                       dark:bg-dark bg-white dark:border-slate-800 border-b border-gray-200">
            <button @click="sidebarOpen = true"
                    class="p-2 rounded-lg dark:text-slate-400 text-gray-600 dark:hover:bg-slate-800 hover:bg-gray-100 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                </svg>
            </button>
            <a href="{{ route('dashboard') }}" wire:navigate class="flex-shrink-0">
                <x-app-logo size="sm" />
            </a>
            <livewire:notification-bell wire:key="notification-bell-mobile" position="down" />
        </header>

        {{-- Impersonation banner --}}
        @if(session('impersonating_admin_id'))
            <div class="flex items-center justify-between gap-3 px-4 py-2 bg-amber-500 text-navy">
                <div class="flex items-center gap-2 text-sm font-semibold font-body min-w-0">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/>
                    </svg>
                    <span class="truncate">Impersonating <span class="font-bold">{{ auth()->user()->name }}</span></span>
                </div>
                <form method="POST" action="{{ route('admin.stop-impersonating') }}" class="flex-shrink-0">
                    @csrf
                    <button type="submit"
                            class="px-3 py-1 text-xs font-bold bg-navy text-white rounded-lg hover:bg-dark transition-colors whitespace-nowrap">
                        Stop
                    </button>
                </form>
            </div>
        @endif

        {{-- Page content --}}
        <main class="flex-1 overflow-y-auto dark:bg-navy bg-slate-50">
            {{ $slot }}
        </main>
    </div>
</div>

@livewireScripts
</body>
</html>
