<div class="min-h-full">

    {{-- ══════════════════════════════════════════════════════
         HEADER — Total balance + quick add
    ══════════════════════════════════════════════════════ --}}
    <div class="px-4 sm:px-6 lg:px-8 py-4 sm:py-5
                dark:bg-navy bg-white
                border-b border-gray-200 dark:border-slate-800
                sticky top-0 z-10 backdrop-blur-sm">
        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row sm:items-center justify-between gap-4">

            {{-- Totals --}}
            <div class="min-w-0" data-testid="dashboard-totals">
                @if($totals->isEmpty())
                    <h1 class="font-display font-extrabold text-2xl sm:text-3xl dark:text-white text-gray-900 tracking-tight leading-none">
                        Welcome, {{ explode(' ', auth()->user()->name)[0] }}
                    </h1>
                @else
                    <h1 class="text-[11px] font-body font-medium uppercase tracking-widest text-gray-500 dark:text-slate-400">
                        Total balance
                    </h1>
                    <div class="mt-1 flex flex-wrap items-end gap-x-6 gap-y-2">
                        @foreach($totals as $t)
                            <div class="min-w-0">
                                <p class="inline-flex items-center gap-1.5 font-bold leading-none tracking-tight
                                          {{ $totals->count() > 1 ? 'text-xl sm:text-2xl' : 'text-2xl sm:text-3xl' }}">
                                    @if($t['balance'] < 0)
                                        <svg class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" role="img" aria-label="Negative balance">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6 9 12.75l4.306-4.306a11.95 11.95 0 0 1 5.814 5.518l2.74 1.22m0 0-5.94 2.281m5.94-2.28-2.28-5.941"/>
                                        </svg>
                                    @endif
                                    <x-amount :value="$t['balance']" :symbol="$t['symbol']" tone="net" />
                                </p>
                                <p class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-0.5 text-xs font-body text-gray-500 dark:text-slate-400">
                                    <span>In <x-amount :value="$t['in']" :symbol="$t['symbol']" tone="in" /></span>
                                    <span>Out <x-amount :value="$t['out']" :symbol="$t['symbol']" tone="out" /></span>
                                </p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Actions --}}
            <div class="flex items-center flex-wrap gap-2 flex-shrink-0">
                @if($quickAddBook)
                    @php $qaUrl = route('businesses.books.show', [$quickAddBook['business'], $quickAddBook['book']]); @endphp
                    <div class="flex flex-col gap-1">
                        <div class="flex items-center gap-2">
                            <a href="{{ $qaUrl }}?addEntry=in" wire:navigate
                               class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-lg
                                      bg-emerald-600 text-white text-sm font-semibold
                                      shadow-md shadow-emerald-600/20 hover:brightness-110 hover:shadow-lg
                                      transition-all duration-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                                </svg>
                                Cash In
                            </a>
                            <a href="{{ $qaUrl }}?addEntry=out" wire:navigate
                               class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-lg
                                      bg-red-600 text-white text-sm font-semibold
                                      shadow-md shadow-red-600/20 hover:brightness-110 hover:shadow-lg
                                      transition-all duration-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14"/>
                                </svg>
                                Cash Out
                            </a>
                        </div>
                        <p class="text-[11px] font-body text-gray-500 dark:text-slate-400 truncate max-w-[16rem]">
                            Adds to <span class="font-medium text-gray-700 dark:text-slate-300">{{ $quickAddBook['book']->name }}</span>
                        </p>
                    </div>
                @endif

                @if($businessLimitReached)
                    {{-- Free plan already owns a business: upgrade modal in place, no page load --}}
                    <button type="button" @click="$dispatch('open-business-upgrade')"
                            class="self-start inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-semibold
                                   {{ $quickAddBook
                                       ? 'border border-gray-300 dark:border-slate-700 text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800'
                                       : 'bg-primary text-white shadow-lg shadow-primary/25 hover:brightness-110 hover:shadow-xl' }}
                                   transition-all duration-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.25" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                        </svg>
                        New business
                        <x-pro-badge />
                    </button>
                @else
                    <a href="{{ route('businesses.create') }}" wire:navigate
                       class="self-start inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-semibold
                              {{ $quickAddBook
                                  ? 'border border-gray-300 dark:border-slate-700 text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800'
                                  : 'bg-primary text-white shadow-lg shadow-primary/25 hover:brightness-110 hover:shadow-xl' }}
                              transition-all duration-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                        </svg>
                        New business
                    </a>
                @endif
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         ANNOUNCEMENT BANNER
    ══════════════════════════════════════════════════════ --}}
    @php
        $announcementRaw    = \App\Helpers\Setting::get('announcement', '{}');
        $announcement       = json_decode($announcementRaw, true) ?: [];
        $announcementActive = !empty($announcement['is_active'])
            && !empty($announcement['message'])
            && (empty($announcement['expires_at']) || \Carbon\Carbon::parse($announcement['expires_at'])->isFuture());
        $announcementKey    = $announcement['updated_at'] ?? '';
    @endphp
    @if($announcementActive)
        @php
            $aType  = $announcement['type'] ?? 'info';
            $aStyle = match($aType) {
                'warning' => 'bg-amber-50 dark:bg-amber-900/30 border-amber-200 dark:border-amber-700 text-amber-800 dark:text-amber-200',
                'success' => 'bg-emerald-50 dark:bg-emerald-900/30 border-emerald-200 dark:border-emerald-700 text-emerald-800 dark:text-emerald-200',
                default   => 'bg-blue-50 dark:bg-blue-900/30 border-blue-200 dark:border-blue-700 text-blue-800 dark:text-blue-200',
            };
            $aIcon  = match($aType) {
                'warning' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z',
                'success' => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
                default   => 'm11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z',
            };
        @endphp
        <div class="px-6 lg:px-8 pt-5 max-w-7xl mx-auto">
            <div x-data="{ dismissed: localStorage.getItem('announcement_dismissed') === '{{ $announcementKey }}' }"
                 x-show="!dismissed"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="flex items-center gap-3 px-4 py-3 rounded-xl border {{ $aStyle }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $aIcon }}"/>
                </svg>
                <p class="text-sm font-body flex-1">{{ $announcement['message'] }}</p>
                <button type="button" aria-label="Dismiss announcement" @click="dismissed = true; localStorage.setItem('announcement_dismissed', '{{ $announcementKey }}')"
                        class="flex-shrink-0 opacity-50 hover:opacity-100 transition-opacity p-1 rounded-lg">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════════
         MAIN CONTENT — Books + Activity feed
    ══════════════════════════════════════════════════════ --}}
    <div class="px-4 sm:px-6 lg:px-8 py-5 sm:py-7 max-w-7xl mx-auto">
        <div class="flex flex-col lg:flex-row gap-7">

            {{-- ── LEFT: Books grouped by business ────────────── --}}
            <div class="flex-1 min-w-0 space-y-8">

                @php
                    $allGrouped = $ownedBusinesses->map(fn($b) => ['business' => $b, 'isOwner' => true])
                        ->concat($sharedBusinesses->map(fn($b) => ['business' => $b, 'isOwner' => false]));
                @endphp

                @if($allGrouped->isEmpty())
                    {{-- ===== ONBOARDING EMPTY STATE ===== --}}
                    <div x-data="{ step: 0 }" class="space-y-5">

                        {{-- Welcome banner --}}
                        <div class="dark:bg-[#1e293b] bg-white dark:border-slate-700/60 border border-gray-100
                                    rounded-xl px-6 sm:px-8 py-8 relative overflow-hidden">
                            {{-- Glow --}}
                            <div class="absolute -top-10 -right-10 w-48 h-48 bg-primary/10 rounded-full blur-3xl pointer-events-none"></div>
                            <div class="absolute -bottom-8 -left-8 w-32 h-32 bg-accent/5 rounded-full blur-2xl pointer-events-none"></div>

                            <div class="relative flex flex-col sm:flex-row sm:items-center gap-6">
                                <div class="flex-1">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full
                                                 bg-primary/10 text-primary text-xs font-semibold mb-3">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                        Getting started
                                    </span>
                                    <h2 class="font-display font-extrabold text-2xl dark:text-white text-gray-900 mb-2 leading-tight">
                                        Welcome to {{ config('app.name', 'TheCashFox') }}.
                                    </h2>
                                    <p class="text-sm dark:text-slate-400 text-gray-500 leading-relaxed max-w-lg">
                                        {{ config('app.name', 'TheCashFox') }} helps you track money coming in and going out — for any business, project, or personal budget. Here's how to get set up in under a minute.
                                    </p>
                                </div>
                                <a href="{{ route('businesses.create') }}" wire:navigate
                                   class="flex-shrink-0 inline-flex items-center gap-2 px-5 py-3
                                          bg-primary hover:bg-accent text-white text-sm font-semibold
                                          rounded-lg transition-all duration-200 shadow-lg shadow-primary/25 self-start sm:self-center">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                                    </svg>
                                    Create Your First Business
                                </a>
                            </div>
                        </div>

                        {{-- How it works — 3 steps --}}
                        <div class="dark:bg-[#1e293b] bg-white dark:border-slate-700/60 border border-gray-100 rounded-xl p-6 sm:p-8">
                            <p class="text-xs font-semibold uppercase tracking-widest dark:text-slate-500 text-gray-400 mb-5">How it works</p>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6 relative">
                                {{-- Connector line (desktop only) — spans icon-1 center to icon-3 center, icons stack above via z-10 --}}
                                <div class="hidden sm:block absolute top-6 left-[calc(16.666%+24px)] right-[calc(16.666%+24px)] h-px bg-gradient-to-r from-primary/40 via-accent/40 to-emerald-500/40 z-0"></div>

                                {{-- Step 1 --}}
                                <div class="flex flex-col items-start gap-3 relative z-10">
                                    <div class="w-12 h-12 rounded-xl bg-primary flex items-center justify-center shadow-lg shadow-primary/30 flex-shrink-0">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="text-[10px] font-bold uppercase tracking-wider text-primary">Step 1</span>
                                        </div>
                                        <h4 class="font-heading font-bold text-sm dark:text-white text-gray-900 mb-1">Create a Business</h4>
                                        <p class="text-xs dark:text-slate-400 text-gray-500 leading-relaxed">
                                            A business is your top-level container. It could be your company, a freelance project, or even your personal finances.
                                        </p>
                                    </div>
                                </div>

                                {{-- Step 2 --}}
                                <div class="flex flex-col items-start gap-3">
                                    <div class="w-12 h-12 rounded-xl bg-accent/15 border border-accent/20 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v8.25m19.5 0A2.25 2.25 0 0 1 19.5 16.5h-15a2.25 2.25 0 0 1-2.25-2.25V6.75"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="text-[10px] font-bold uppercase tracking-wider text-accent">Step 2</span>
                                        </div>
                                        <h4 class="font-heading font-bold text-sm dark:text-white text-gray-900 mb-1">Open a Book</h4>
                                        <p class="text-xs dark:text-slate-400 text-gray-500 leading-relaxed">
                                            Books organise entries by time period or project — think "March 2026" or "Website Project". One business can have many books.
                                        </p>
                                    </div>
                                </div>

                                {{-- Step 3 --}}
                                <div class="flex flex-col items-start gap-3">
                                    <div class="w-12 h-12 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-500">Step 3</span>
                                        </div>
                                        <h4 class="font-heading font-bold text-sm dark:text-white text-gray-900 mb-1">Log Entries</h4>
                                        <p class="text-xs dark:text-slate-400 text-gray-500 leading-relaxed">
                                            Record every Cash In and Cash Out. Your running balance and reports update instantly — no spreadsheet needed.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Use-case tiles --}}
                        <div class="dark:bg-[#1e293b] bg-white dark:border-slate-700/60 border border-gray-100 rounded-xl p-6 sm:p-8">
                            <p class="text-xs font-semibold uppercase tracking-widest dark:text-slate-500 text-gray-400 mb-4">What do you want to track?</p>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <a href="{{ route('businesses.create') }}" wire:navigate
                                   class="group flex items-start gap-3 p-4 rounded-xl
                                          dark:bg-slate-800 bg-gray-50
                                          dark:border-slate-700 border border-gray-100
                                          dark:hover:border-primary hover:border-primary/30
                                          dark:hover:bg-slate-700 hover:bg-primary/5
                                          transition-all duration-200 cursor-pointer text-left">
                                    <div class="w-9 h-9 rounded-lg bg-blue-500/10 flex items-center justify-center flex-shrink-0 mt-0.5
                                                group-hover:bg-blue-500/20 transition-colors">
                                        <svg class="w-[18px] h-[18px] text-blue-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" >
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 0 0 .75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 0 0-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0 1 12 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 0 1-.673-.38m0 0A2.18 2.18 0 0 1 3 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 0 1 3.413-.387m7.5 0V5.25A2.25 2.25 0 0 0 13.5 3h-3a2.25 2.25 0 0 0-2.25 2.25v.894m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold dark:text-white text-gray-900 mb-0.5">My Business</p>
                                        <p class="text-xs dark:text-slate-400 text-gray-500">Shop, agency, clinic, or any registered business. Separate books per month or project.</p>
                                    </div>
                                </a>

                                <a href="{{ route('businesses.create') }}" wire:navigate
                                   class="group flex items-start gap-3 p-4 rounded-xl
                                          dark:bg-slate-800 bg-gray-50
                                          dark:border-slate-700 border border-gray-100
                                          dark:hover:border-primary hover:border-primary/30
                                          dark:hover:bg-slate-700 hover:bg-primary/5
                                          transition-all duration-200 cursor-pointer text-left">
                                    <div class="w-9 h-9 rounded-lg bg-primary/10 flex items-center justify-center flex-shrink-0 mt-0.5
                                                group-hover:bg-primary/20 transition-colors">
                                        <svg class="w-[18px] h-[18px] text-primary dark:text-blue-light" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" >
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold dark:text-white text-gray-900 mb-0.5">Freelance / Personal</p>
                                        <p class="text-xs dark:text-slate-400 text-gray-500">Track client payments, salary, household expenses, or a side hustle in one place.</p>
                                    </div>
                                </a>

                                <a href="{{ route('businesses.create') }}" wire:navigate
                                   class="group flex items-start gap-3 p-4 rounded-xl
                                          dark:bg-slate-800 bg-gray-50
                                          dark:border-slate-700 border border-gray-100
                                          dark:hover:border-primary hover:border-primary/30
                                          dark:hover:bg-slate-700 hover:bg-primary/5
                                          transition-all duration-200 cursor-pointer text-left">
                                    <div class="w-9 h-9 rounded-lg bg-amber-500/10 flex items-center justify-center flex-shrink-0 mt-0.5
                                                group-hover:bg-amber-500/20 transition-colors">
                                        <svg class="w-[18px] h-[18px] text-amber-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" >
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold dark:text-white text-gray-900 mb-0.5">Team or Partnership</p>
                                        <p class="text-xs dark:text-slate-400 text-gray-500">Invite partners or an accountant with editor or viewer access. Everyone sees the same numbers.</p>
                                    </div>
                                </a>
                            </div>
                        </div>

                    </div>
                @else
                    {{-- ── Recently edited books quick-jump ────────── --}}
                    @php
                        $recentBooks = collect();
                        foreach ($allGrouped as $grp) {
                            foreach ($grp['business']->books as $b) {
                                $b->_business = $grp['business'];
                                $recentBooks->push($b);
                            }
                        }
                        $recentBooks = $recentBooks
                            ->sortByDesc(fn($b) => $b->last_entry_at ?? $b->updated_at ?? $b->created_at)
                            ->take(3);
                    @endphp
                    @if($recentBooks->isNotEmpty())
                        <div class="mb-7">
                            <p class="text-[10px] font-semibold uppercase tracking-widest dark:text-slate-400 text-gray-500 mb-2.5 flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-primary inline-block"></span>
                                Recently edited
                            </p>
                            <div class="grid grid-cols-3 gap-2">
                                @foreach($recentBooks as $rb)
                                    @php
                                        $rbIn   = (float)($rb->cash_in  ?? 0);
                                        $rbOut  = (float)($rb->cash_out ?? 0);
                                        $rbNet  = (float)($rb->opening_balance ?? 0) + $rbIn - $rbOut;
                                        $rbSym  = $rb->_business->currencySymbol();
                                        $rbLast = $rb->last_entry_at
                                            ? \Carbon\Carbon::parse($rb->last_entry_at)->diffForHumans(null, true)
                                            : null;
                                    @endphp
                                    <a href="{{ route('businesses.books.show', [$rb->_business, $rb]) }}" wire:navigate
                                       class="group flex items-center justify-between gap-2 px-4 py-3
                                              dark:bg-[#1e293b] bg-white
                                              dark:border-slate-700 border border-gray-200
                                              dark:hover:border-primary/40 hover:border-primary/30
                                              dark:hover:bg-primary/5 hover:bg-blue-50/50
                                              rounded-xl transition-all duration-150 min-w-0">
                                        <div class="min-w-0 flex-1">
                                            <p class="text-[10px] font-body font-medium uppercase tracking-wide dark:text-slate-500 text-gray-400 truncate leading-none mb-1">
                                                {{ $rb->_business->name }}
                                            </p>
                                            <p class="font-heading font-bold text-sm dark:text-white text-gray-900 truncate">
                                                {{ $rb->name }}
                                            </p>
                                            <p class="text-[11px] dark:text-slate-500 text-gray-400 mt-0.5 truncate">
                                                @if($rb->entries_count > 0)
                                                    <x-amount :value="$rbNet" :symbol="$rbSym" tone="neutral" :decimals="0" />
                                                @else
                                                    <span>Empty</span>
                                                @endif
                                                @if($rbLast)
                                                    · {{ $rbLast }}
                                                @endif
                                            </p>
                                        </div>
                                        <svg class="w-3 h-3 flex-shrink-0 dark:text-slate-700 text-gray-300 group-hover:text-primary group-hover:translate-x-0.5 transition-all duration-150"
                                             fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>
                                        </svg>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @foreach($allGrouped as $group)
                        @php
                            $business = $group['business'];
                            $isOwner  = $group['isOwner'];
                            $role     = $business->pivot->role ?? 'viewer';
                            $isLocked = !auth()->user()->isPro() && $isOwner && $business->id !== $firstOwnedId;
                            // Sort books: most recently active first
                            $sortedBooks = $business->books->sortByDesc(fn($b) => $b->last_entry_at ?? $b->created_at)->values();
                        @endphp

                        <div x-data="{ shown: false }"
                             x-init="setTimeout(() => shown = true, {{ $loop->index * 60 }})"
                             :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-3'"
                             class="transition-all duration-500 ease-out">

                            {{-- Section divider between businesses --}}
                            @if(!$loop->first)
                                <div class="border-t dark:border-slate-800 border-gray-100 -mt-2 mb-8"></div>
                            @endif

                            {{-- Business header --}}
                            @php
                                $businessCashIn  = $business->books->sum(fn($b) => (float)($b->cash_in ?? 0));
                                $businessCashOut = $business->books->sum(fn($b) => (float)($b->cash_out ?? 0));
                                $businessNet     = $business->books->sum(fn($b) => (float)($b->opening_balance ?? 0)) + $businessCashIn - $businessCashOut;
                            @endphp
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0
                                                {{ $isOwner ? 'bg-primary/15 dark:bg-primary/20' : 'dark:bg-slate-700 bg-gray-100' }}">
                                        <span class="text-xs font-bold {{ $isOwner ? 'text-primary' : 'dark:text-slate-400 text-gray-500' }}">
                                            {{ strtoupper(substr($business->name, 0, 1)) }}
                                        </span>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <a href="{{ route('businesses.show', $business) }}" wire:navigate
                                               class="font-heading font-bold text-sm dark:text-white text-gray-900
                                                      hover:text-primary dark:hover:text-primary transition-colors truncate">
                                                {{ $business->name }}
                                            </a>
                                            @if($business->books->isNotEmpty() && ($businessCashIn > 0 || $businessCashOut > 0))
                                                <span class="inline-flex items-center gap-1 text-xs font-bold flex-shrink-0">
                                                    @if($businessNet < 0)<svg class="w-3.5 h-3.5 text-red-600 dark:text-red-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" role="img" aria-label="Negative balance"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6 9 12.75l4.306-4.306a11.95 11.95 0 0 1 5.814 5.518l2.74 1.22m0 0-5.94 2.281m5.94-2.28-2.28-5.941"/></svg>@endif
                                                    <x-amount :value="$businessNet" :symbol="$business->currencySymbol()" tone="net" />
                                                </span>
                                            @endif
                                        </div>
                                        <p class="text-[10px] dark:text-slate-500 text-gray-400 mt-0.5">
                                            @if(!$isOwner)
                                                <span class="{{ $role === 'editor' ? 'text-emerald-500' : '' }}">{{ ucfirst($role) }}</span> · Owned by {{ $business->owner->name ?? 'Unknown' }}
                                            @else
                                                {{ $business->books_count }} {{ Str::plural('book', $business->books_count) }} · {{ $business->members_count }} {{ Str::plural('member', $business->members_count) }}
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1.5 sm:gap-2 flex-shrink-0">
                                    @if($isOwner)
                                        {{-- Settings icon --}}
                                        <a href="{{ route('businesses.settings', $business) }}" wire:navigate
                                           title="Business settings"
                                           aria-label="{{ $business->name }} settings"
                                           class="p-2 rounded-lg dark:text-slate-400 text-gray-500
                                                  dark:hover:text-white hover:text-gray-700
                                                  dark:hover:bg-slate-700 hover:bg-gray-100
                                                  transition-all duration-150">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                            </svg>
                                        </a>
                                    @endif
                                    @if(in_array($role, ['owner', 'editor'], true) && !$isLocked)
                                        <a href="{{ route('businesses.show', $business) }}?createBook=1" wire:navigate
                                           class="inline-flex items-center gap-1 px-2 sm:px-3 py-1.5
                                                  dark:bg-slate-800 bg-gray-100
                                                  dark:hover:bg-primary/15 hover:bg-primary/10
                                                  dark:text-slate-400 text-gray-500 hover:text-primary dark:hover:text-primary
                                                  text-xs font-semibold rounded-lg transition-all duration-150">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                                            </svg>
                                            <span class="hidden sm:inline">Add Book</span>
                                            <span class="sr-only sm:hidden">Add book</span>
                                        </a>
                                    @endif
                                    <a href="{{ route('businesses.show', $business) }}" wire:navigate
                                       class="text-xs dark:text-slate-400 text-gray-500 hover:text-primary dark:hover:text-primary transition-colors hidden sm:inline">
                                        All Books →
                                    </a>
                                    <a href="{{ route('businesses.show', $business) }}" wire:navigate
                                       aria-label="All books in {{ $business->name }}"
                                       class="p-2 rounded-lg dark:text-slate-400 text-gray-500
                                              dark:hover:text-primary hover:text-primary
                                              dark:hover:bg-slate-700 hover:bg-gray-100
                                              transition-all duration-150 sm:hidden">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>
                                        </svg>
                                    </a>
                                </div>
                            </div>

                            {{-- Locked state --}}
                            @if($isLocked)
                                <div class="dark:bg-[#1e293b] bg-white dark:border-slate-700 border border-gray-200
                                            rounded-xl p-6 flex flex-col sm:flex-row items-center justify-between gap-4 relative overflow-hidden">
                                    <div class="absolute inset-0 bg-gradient-to-r from-primary/5 to-transparent pointer-events-none"></div>
                                    <div class="flex items-center gap-3 relative">
                                        <div class="w-9 h-9 rounded-full dark:bg-slate-800 bg-gray-100 flex items-center justify-center flex-shrink-0">
                                            <svg class="w-[18px] h-[18px] dark:text-slate-400 text-gray-500" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold dark:text-white text-gray-900">Pro plan required</p>
                                            <p class="text-xs dark:text-slate-400 text-gray-500 mt-0.5">Resubscribe to unlock this business</p>
                                        </div>
                                    </div>
                                    <a href="{{ route('billing') }}" wire:navigate
                                       class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold
                                              bg-primary hover:bg-accent text-white rounded-lg
                                              transition-all duration-200 shadow-lg shadow-primary/30 relative flex-shrink-0">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7Z"/>
                                        </svg>
                                        Resubscribe
                                    </a>
                                </div>

                            @elseif($sortedBooks->isEmpty())
                                {{-- No books --}}
                                <div class="dark:bg-slate-800 bg-gray-50 dark:border-slate-800 border border-dashed border-gray-200
                                            rounded-xl px-6 py-8 text-center">
                                    <p class="text-sm dark:text-slate-500 text-gray-400">No books yet.</p>
                                    @if(in_array($role, ['owner', 'editor'], true))
                                        <a href="{{ route('businesses.show', $business) }}?createBook=1" wire:navigate
                                           class="inline-flex items-center gap-1.5 mt-3 px-4 py-2 text-sm font-semibold
                                                  bg-primary/10 hover:bg-primary text-primary hover:text-white
                                                  rounded-lg transition-all duration-200">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                                            </svg>
                                            Create First Book
                                        </a>
                                    @endif
                                </div>

                            @else
                                {{-- Books grid — cap at 3, most recently active --}}
                                @php
                                    $displayBooks    = $sortedBooks->take(3);
                                    $remainingCount  = max(0, $sortedBooks->count() - 3);
                                @endphp
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                    @foreach($displayBooks as $book)
                                        @php
                                            $cashIn   = (float)($book->cash_in ?? 0);
                                            $cashOut  = (float)($book->cash_out ?? 0);
                                            $bookNet  = (float)($book->opening_balance ?? 0) + $cashIn - $cashOut;
                                            $isEmpty  = $book->entries_count === 0;
                                            $isNeg    = $bookNet < 0 && !$isEmpty;
                                            $isPos    = $bookNet > 0 && !$isEmpty;
                                            $borderColor = $isPos ? 'border-l-emerald-500' : ($isNeg ? 'border-l-red-500' : 'dark:border-l-slate-700 border-l-gray-200');
                                            $lastEntry   = $book->last_entry_at ? \Carbon\Carbon::parse($book->last_entry_at) : null;
                                            $canEdit     = in_array($role, ['owner', 'editor']);
                                        @endphp

                                        <div class="group dark:bg-dark bg-white
                                                    dark:border-slate-700 border border-gray-200 border-l-4 {{ $borderColor }}
                                                    rounded-xl overflow-hidden flex flex-col
                                                    dark:hover:border-primary/40 hover:border-primary/30
                                                    hover:shadow-lg hover:shadow-primary/5 hover:-translate-y-0.5
                                                    transition-all duration-200
                                                    {{ $isEmpty ? 'opacity-70 hover:opacity-100' : '' }}">

                                            {{-- Card body — clickable to open book --}}
                                            <a href="{{ route('businesses.books.show', [$business, $book]) }}" wire:navigate
                                               class="block p-4 flex-1">

                                                {{-- Book name + last active --}}
                                                <div class="flex items-start justify-between gap-2 mb-3">
                                                    <div class="min-w-0">
                                                        <p class="font-heading font-bold text-sm dark:text-white text-gray-900
                                                                   group-hover:text-primary transition-colors truncate">
                                                            {{ $book->name }}
                                                        </p>
                                                        @if($book->period_starts_at && $book->period_ends_at)
                                                            <p class="text-[11px] dark:text-slate-500 text-gray-400 mt-0.5 font-body">
                                                                {{ \Carbon\Carbon::parse($book->period_starts_at)->format('M j') }}
                                                                – {{ \Carbon\Carbon::parse($book->period_ends_at)->format('M j, Y') }}
                                                            </p>
                                                        @endif
                                                    </div>
                                                    {{-- Last active badge --}}
                                                    @if($lastEntry)
                                                        <span class="text-[10px] dark:text-slate-400 text-gray-500 font-body flex-shrink-0 whitespace-nowrap">
                                                            {{ $lastEntry->diffForHumans() }}
                                                        </span>
                                                    @elseif($isEmpty)
                                                        <span class="text-[10px] dark:text-slate-600 text-gray-300 font-body flex-shrink-0">
                                                            Empty
                                                        </span>
                                                    @endif
                                                </div>

                                                @if($isEmpty)
                                                    {{-- Empty book state --}}
                                                    <div class="py-3 text-center">
                                                        <p class="text-xs dark:text-slate-500 text-gray-400 italic">No entries yet</p>
                                                    </div>
                                                @else
                                                    {{-- Net balance --}}
                                                    @php $bookSym = $business->currencySymbol(); @endphp
                                                    <div class="mb-3">
                                                        <p class="inline-flex items-center gap-1.5 font-bold text-xl leading-none">
                                                            @if($isNeg)<svg class="w-3.5 h-3.5 text-red-600 dark:text-red-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" role="img" aria-label="Negative balance"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6 9 12.75l4.306-4.306a11.95 11.95 0 0 1 5.814 5.518l2.74 1.22m0 0-5.94 2.281m5.94-2.28-2.28-5.941"/></svg>@endif
                                                            <x-amount :value="$bookNet" :symbol="$bookSym" tone="net" />
                                                        </p>
                                                        <p class="text-[10px] dark:text-slate-400 text-gray-500 mt-0.5 font-body">net balance</p>
                                                    </div>

                                                    {{-- In / Out mini breakdown --}}
                                                    <div class="flex items-center gap-3 pt-3 border-t dark:border-slate-800 border-gray-100">
                                                        <div class="flex items-center gap-1">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 flex-shrink-0"></span>
                                                            <x-amount :value="$cashIn" :symbol="$bookSym" tone="in" class="text-[11px]" />
                                                        </div>
                                                        <div class="flex items-center gap-1">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-red-400 flex-shrink-0"></span>
                                                            <x-amount :value="$cashOut" :symbol="$bookSym" tone="out" class="text-[11px]" />
                                                        </div>
                                                        <div class="ml-auto">
                                                            <svg class="w-3.5 h-3.5 dark:text-slate-600 text-gray-300
                                                                         group-hover:text-primary dark:group-hover:text-primary
                                                                         group-hover:translate-x-0.5 transition-all duration-200"
                                                                 fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>
                                                            </svg>
                                                        </div>
                                                    </div>
                                                @endif
                                            </a>

                                            {{-- Quick-add footer (editors/owners only) --}}
                                            @if($canEdit)
                                                <div class="flex border-t dark:border-slate-800 border-gray-100">
                                                    <a href="{{ route('businesses.books.show', [$business, $book]) . '?addEntry=in' }}" wire:navigate
                                                       class="flex-1 flex items-center justify-center gap-1.5 py-2.5
                                                              text-xs font-semibold
                                                              dark:text-emerald-400 text-emerald-600
                                                              dark:hover:bg-emerald-500/10 hover:bg-emerald-50
                                                              transition-colors duration-150 border-r dark:border-slate-800 border-gray-100">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                                                        </svg>
                                                        Cash In
                                                    </a>
                                                    <a href="{{ route('businesses.books.show', [$business, $book]) . '?addEntry=out' }}" wire:navigate
                                                       class="flex-1 flex items-center justify-center gap-1.5 py-2.5
                                                              text-xs font-semibold
                                                              dark:text-red-400 text-red-600
                                                              dark:hover:bg-red-500/10 hover:bg-red-50
                                                              transition-colors duration-150">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14"/>
                                                        </svg>
                                                        Cash Out
                                                    </a>
                                                </div>
                                            @endif

                                        </div>
                                    @endforeach

                                    {{-- Overflow tile: "+ N more books" --}}
                                    @if($remainingCount > 0)
                                        <a href="{{ route('businesses.show', $business) }}" wire:navigate
                                           class="group flex items-center justify-center
                                                  dark:bg-slate-800/30 bg-gray-50
                                                  dark:border-slate-700 border border-gray-200
                                                  dark:hover:border-primary/40 hover:border-primary/30
                                                  dark:hover:bg-primary/5 hover:bg-primary/5
                                                  rounded-xl p-6 min-h-[130px]
                                                  transition-all duration-200">
                                            <div class="text-center">
                                                <div class="w-10 h-10 rounded-full dark:bg-slate-700 bg-gray-200
                                                             group-hover:bg-primary/15 dark:group-hover:bg-primary/20
                                                             flex items-center justify-center mx-auto mb-2 transition-colors duration-150">
                                                    <span class="font-mono font-bold text-sm dark:text-slate-400 text-gray-500 group-hover:text-primary transition-colors duration-150">
                                                        +{{ $remainingCount }}
                                                    </span>
                                                </div>
                                                <p class="text-xs font-medium dark:text-slate-500 text-gray-400 group-hover:text-primary transition-colors duration-150">
                                                    {{ Str::plural('more book', $remainingCount) }}
                                                </p>
                                            </div>
                                        </a>
                                    @endif
                                </div>
                            @endif

                        </div>
                    @endforeach

                @endif

            </div>

            {{-- ── RIGHT: Recent Activity feed ──────────────────── --}}
            <div class="lg:w-64 xl:w-72 flex-shrink-0">
                <div class="lg:sticky lg:top-[132px]">
                    <div class="dark:bg-dark bg-white dark:border-slate-700 border border-gray-200 rounded-xl overflow-hidden">

                        <div class="px-5 py-4 dark:border-b dark:border-slate-800 border-b border-gray-100 flex items-center justify-between">
                            <div>
                                <h3 class="font-heading font-bold text-sm dark:text-white text-gray-900">Recent Activity</h3>
                                <p class="text-[11px] dark:text-slate-400 text-gray-500 mt-0.5">Latest entries, all books</p>
                            </div>
                            <div class="w-2 h-2 rounded-full bg-emerald-500" aria-hidden="true"></div>
                        </div>

                        @if($recentEntries->isEmpty())
                            <div class="px-5 py-10 text-center">
                                <div class="w-10 h-10 rounded-full dark:bg-slate-800 bg-gray-100 flex items-center justify-center mx-auto mb-3">
                                    <svg class="w-5 h-5 dark:text-slate-500 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                    </svg>
                                </div>
                                <p class="text-sm dark:text-slate-400 text-gray-500">No entries yet</p>
                                <p class="text-xs dark:text-slate-600 text-gray-300 mt-1">Add entries to see activity</p>
                            </div>
                        @else
                            <div class="divide-y dark:divide-slate-800 divide-gray-100">
                                @php $lastDate = null; @endphp
                                @foreach($recentEntries as $entry)
                                    @php
                                        $addedAt = $entry->created_at instanceof \Carbon\Carbon
                                            ? $entry->created_at
                                            : \Carbon\Carbon::parse($entry->created_at);
                                        $dateKey = $addedAt->toDateString();
                                    @endphp

                                    @if($dateKey !== $lastDate)
                                        @php $lastDate = $dateKey; @endphp
                                        <div class="px-5 py-1.5 dark:bg-slate-800/40 bg-gray-50">
                                            <p class="text-[10px] font-bold uppercase tracking-widest dark:text-slate-500 text-gray-400">
                                                @if($addedAt->isToday()) Today
                                                @elseif($addedAt->isYesterday()) Yesterday
                                                @else {{ $addedAt->format('M j') }}
                                                @endif
                                            </p>
                                        </div>
                                    @endif

                                    <a href="{{ route('businesses.books.show', [$entry->book->business_id, $entry->book_id]) }}" wire:navigate
                                       class="flex items-start gap-3 px-5 py-3
                                              dark:hover:bg-slate-800/40 hover:bg-gray-50 transition-colors duration-100">
                                        <div class="mt-1.5 flex-shrink-0">
                                            <div class="w-2 h-2 rounded-full {{ $entry->type === 'in' ? 'bg-emerald-500' : 'bg-red-400' }}"></div>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            @php $feedSym = $entry->book->business?->currencySymbol() ?? ''; @endphp
                                            <div class="flex items-baseline justify-between gap-2">
                                                <x-amount :value="$entry->amount" :symbol="$feedSym" :type="$entry->type" class="font-bold text-sm leading-none" />
                                                <span class="text-[10px] dark:text-slate-400 text-gray-500 flex-shrink-0 font-body">
                                                    {{ $addedAt->diffForHumans(null, true) }}
                                                </span>
                                            </div>
                                            <p class="text-xs dark:text-slate-300 text-gray-700 mt-0.5 truncate">
                                                {{ $entry->description ?: '—' }}
                                            </p>
                                            <p class="text-[10px] dark:text-slate-400 text-gray-500 mt-0.5 truncate">
                                                {{ $entry->book->name ?? '—' }} · {{ $entry->book->business->name ?? '—' }}
                                            </p>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        @endif

                        @if($recentEntries->isNotEmpty())
                            <div class="px-5 py-3 dark:border-t dark:border-slate-800 border-t border-gray-100">
                                <p class="text-[10px] dark:text-slate-400 text-gray-500 text-center">
                                    Last 8 entries across all books
                                </p>
                            </div>
                        @endif

                    </div>
                </div>
            </div>

        </div>
    </div>

</div>
