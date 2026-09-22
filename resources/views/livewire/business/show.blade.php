<div class="min-h-full"
     x-init="
         const p = new URLSearchParams(window.location.search);
         if (p.get('createBook') === '1') {
             $nextTick(() => $wire.call('openCreateBook'));
         }
     ">

    {{-- ===== TOAST ===== --}}
    <div wire:ignore
         x-data="{ show: false, message: '' }"
         x-on:book-saved.window="message = $event.detail.message; show = true; setTimeout(() => show = false, 3000)"
         x-show="show"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-2"
         style="display:none"
         class="fixed bottom-6 left-1/2 -translate-x-1/2 z-[200]
                flex items-center gap-3 px-5 py-3 rounded-xl
                bg-slate-900 dark:bg-slate-800 border border-slate-700
                shadow-2xl shadow-black/40 text-white text-sm font-body font-medium
                whitespace-nowrap pointer-events-none">
        <svg class="w-4 h-4 text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
        </svg>
        <span x-text="message"></span>
    </div>

    {{-- ===== PAGE HEADER ===== --}}
    <div class="px-4 sm:px-6 lg:px-8 py-4 sm:py-5
                dark:bg-navy/80 bg-white/90
                dark:border-b dark:border-white/5 border-b border-gray-200/70
                sticky top-0 z-10 backdrop-blur-xl">
        <div class="max-w-7xl mx-auto flex items-center justify-between gap-4">

            <div class="flex items-center gap-3 min-w-0">
                <a href="{{ route('dashboard') }}" wire:navigate
                   aria-label="Back to dashboard"
                   class="p-2 rounded-lg dark:text-slate-400 text-gray-500
                          dark:hover:bg-slate-800 hover:bg-gray-100
                          dark:hover:text-white hover:text-gray-900
                          transition-all duration-150 flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                    </svg>
                </a>
                <div class="min-w-0">
                    <div class="flex items-center gap-2.5">
                        <h1 class="font-display font-extrabold text-2xl dark:text-white text-gray-900 tracking-tight leading-none truncate">
                            {{ $business->name }}
                        </h1>
                        <span class="text-[11px] font-semibold px-2.5 py-1 rounded-full flex-shrink-0
                                     @if($userRole === 'owner') dark:bg-primary/20 bg-primary/10 text-primary
                                     @elseif($userRole === 'editor') dark:bg-green-500/20 bg-green-50 text-green-600 dark:text-green-400
                                     @else dark:bg-slate-700 bg-gray-100 dark:text-slate-400 text-gray-500 @endif">
                            {{ ucfirst($userRole) }}
                        </span>
                    </div>
                    <p class="text-sm dark:text-slate-500 text-gray-400 font-body mt-1">
                        {{ $business->currency }} · {{ $books->count() }} {{ \Illuminate\Support\Str::plural('book', $books->count()) }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2 flex-shrink-0">
                @if($userRole === 'owner')
                    <a href="{{ route('businesses.settings', $business) }}" wire:navigate
                       title="Business settings"
                       aria-label="Business settings"
                       class="p-2 rounded-lg dark:text-slate-400 text-gray-500
                              dark:hover:bg-slate-800 hover:bg-gray-100
                              dark:hover:text-white hover:text-gray-900
                              transition-all duration-150">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.43l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                        </svg>
                    </a>
                @endif
                @if($userRole !== 'viewer')
                    <button wire:click="openCreateBook"
                            class="inline-flex items-center gap-2 px-4 py-2.5
                                   bg-primary hover:bg-accent
                                   text-white text-sm font-semibold rounded-lg
                                   transition-all duration-200
                                   shadow-lg shadow-primary/25 hover:shadow-accent/30">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                        </svg>
                        <span class="hidden sm:inline">New Book</span>
                        <span class="sm:hidden">New</span>
                    </button>
                @endif
            </div>

        </div>
    </div>

    {{-- ===== CONTEXT STRIP ===== --}}
    @php
        $activeBookCount  = $books->filter(fn($b) => $b->period_starts_at && $b->period_ends_at && now()->between($b->period_starts_at, $b->period_ends_at))->count();
        $totalEntryCount  = $books->sum('entries_count');
        $lastActivityAt   = $books->max('updated_at');
        $teamCount        = $business->members()->count();
    @endphp
    <div class="dark:bg-slate-900 bg-gray-50
                dark:border-b dark:border-slate-800 border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-wrap divide-x dark:divide-slate-800 divide-gray-200">
                <div class="flex items-center gap-2.5 px-4 sm:px-6 py-3 min-w-0">
                    <div class="w-7 h-7 rounded-lg bg-primary/10 flex items-center justify-center flex-shrink-0">
                        <svg class="w-3.5 h-3.5 text-primary" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.966 8.966 0 0 0-6 2.292m0-14.25v14.25"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-[10px] font-semibold uppercase tracking-widest dark:text-slate-400 text-gray-500 font-body leading-none">Books</p>
                        <p class="text-sm font-bold dark:text-white text-gray-900 font-mono mt-0.5 leading-none">{{ $books->count() }}</p>
                    </div>
                </div>

                @if($activeBookCount > 0)
                    <div class="flex items-center gap-2.5 px-4 sm:px-6 py-3 min-w-0">
                        <div class="w-7 h-7 rounded-lg bg-emerald-500/10 flex items-center justify-center flex-shrink-0">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        </div>
                        <div>
                            <p class="text-[10px] font-semibold uppercase tracking-widest dark:text-slate-400 text-gray-500 font-body leading-none">Active</p>
                            <p class="text-sm font-bold text-emerald-600 dark:text-emerald-400 font-mono mt-0.5 leading-none">{{ $activeBookCount }} {{ \Illuminate\Support\Str::plural('book', $activeBookCount) }}</p>
                        </div>
                    </div>
                @endif

                <div class="flex items-center gap-2.5 px-4 sm:px-6 py-3 min-w-0">
                    <div class="w-7 h-7 rounded-lg bg-accent/10 flex items-center justify-center flex-shrink-0">
                        <svg class="w-3.5 h-3.5 text-accent" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-[10px] font-semibold uppercase tracking-widest dark:text-slate-400 text-gray-500 font-body leading-none">Entries</p>
                        <p class="text-sm font-bold dark:text-white text-gray-900 font-mono mt-0.5 leading-none">{{ number_format($totalEntryCount) }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-2.5 px-4 sm:px-6 py-3 min-w-0">
                    <div class="w-7 h-7 rounded-lg bg-primary/10 flex items-center justify-center flex-shrink-0">
                        <svg class="w-3.5 h-3.5 text-primary dark:text-blue-light" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-[10px] font-semibold uppercase tracking-widest dark:text-slate-400 text-gray-500 font-body leading-none">Team</p>
                        <p class="text-sm font-bold dark:text-white text-gray-900 font-mono mt-0.5 leading-none">{{ $teamCount }} {{ \Illuminate\Support\Str::plural('member', $teamCount) }}</p>
                    </div>
                </div>

                @if($lastActivityAt)
                    <div class="flex items-center gap-2.5 px-4 sm:px-6 py-3 min-w-0">
                        <div class="w-7 h-7 rounded-lg bg-slate-500/10 flex items-center justify-center flex-shrink-0">
                            <svg class="w-3.5 h-3.5 dark:text-slate-400 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-[10px] font-semibold uppercase tracking-widest dark:text-slate-400 text-gray-500 font-body leading-none">Last Activity</p>
                            <p class="text-sm font-semibold dark:text-slate-300 text-gray-600 font-body mt-0.5 leading-none whitespace-nowrap">{{ \Carbon\Carbon::parse($lastActivityAt)->diffForHumans() }}</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ===== CONTENT ===== --}}
    <div class="px-4 sm:px-6 lg:px-8 py-5 sm:py-7 max-w-7xl mx-auto">

        {{-- Search + sort --}}
        <div class="flex items-stretch gap-3 mb-5">
            <div class="flex items-center gap-3 flex-1 max-w-xs sm:max-w-sm
                        px-3 sm:px-4 py-2.5 rounded-lg
                        dark:bg-dark bg-white
                        dark:border-slate-700 border-gray-200 border
                        focus-within:ring-2 focus-within:ring-primary/50 focus-within:border-primary
                        transition-all duration-150">
                <svg class="w-4 h-4 flex-shrink-0 dark:text-slate-500 text-gray-400"
                     fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                </svg>
                <input wire:model.live.debounce.200ms="search"
                       type="text"
                       placeholder="Search books…"
                       aria-label="Search books"
                       class="flex-1 appearance-none border-none ring-0 outline-none focus:ring-0 focus:outline-none focus:border-none p-0 text-sm font-body
                              dark:bg-dark bg-white
                              dark:text-white text-gray-900
                              dark:placeholder-slate-600 placeholder-gray-400"
                       style="-webkit-appearance: none; box-shadow: none !important; border: none !important; outline: none !important;">
            </div>

            <div class="relative flex-shrink-0"
                 x-data="{ open: false, label: 'Last Updated' }">
                <button @click="open = !open" @click.outside="open = false"
                        type="button"
                        aria-label="Sort books"
                        class="flex items-center gap-2 sm:gap-2.5 px-3 sm:px-4 h-full rounded-lg text-sm font-body
                               dark:bg-dark bg-white
                               dark:border-slate-700 border-gray-200 border
                               dark:text-slate-300 text-gray-700
                               focus:outline-none focus:ring-2 focus:ring-primary/50
                               transition-all duration-150 whitespace-nowrap">
                    <svg class="w-4 h-4 dark:text-slate-500 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5 7.5 3m0 0L12 7.5M7.5 3v13.5m13.5 0L16.5 21m0 0L12 16.5m4.5 4.5V7.5"/>
                    </svg>
                    <span class="hidden sm:inline">Sort: <span x-text="label"></span></span>
                    <svg class="w-3 h-3 dark:text-slate-500 text-gray-400 transition-transform duration-150"
                         :class="open ? 'rotate-180' : ''"
                         fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
                    </svg>
                </button>

                <div x-show="open"
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     class="absolute right-0 mt-1.5 w-44 z-20
                            dark:bg-dark bg-white
                            dark:border-slate-700 border-gray-200 border
                            rounded-xl shadow-xl shadow-black/20 overflow-hidden"
                     style="display: none;">
                    @foreach([['updated_at', 'Last Updated'], ['created_at', 'Date Created'], ['name', 'Name A–Z']] as [$val, $lbl])
                        <button type="button"
                                @click="$wire.set('sortBy', '{{ $val }}'); label = '{{ $lbl }}'; open = false"
                                class="w-full text-left px-4 py-2.5 text-sm font-body
                                       dark:text-slate-300 text-gray-700
                                       dark:hover:bg-slate-800 hover:bg-gray-50
                                       transition-colors duration-100
                                       {{ $sortBy === $val ? 'text-primary font-semibold' : '' }}">
                            {{ $lbl }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Books table --}}
        @if($books->isEmpty())

            <div class="dark:bg-dark bg-white dark:border-slate-800 border-2 border-dashed border-gray-200
                        rounded-xl px-8 py-16 text-center relative overflow-hidden">
                <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                    <div class="w-64 h-64 bg-primary/5 rounded-full blur-3xl"></div>
                </div>
                <div class="relative">
                    <div class="w-16 h-16 rounded-2xl bg-primary/10 dark:bg-primary/15 flex items-center justify-center mx-auto mb-5 shadow-lg shadow-primary/10">
                        <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"/>
                        </svg>
                    </div>
                    @if($search)
                        <h3 class="font-heading font-bold text-lg dark:text-white text-gray-900 mb-2">No results for "{{ $search }}"</h3>
                        <p class="text-sm dark:text-slate-400 text-gray-500 mb-6">Try a different search term.</p>
                        <button wire:click="$set('search', '')"
                                class="inline-flex items-center gap-2 px-5 py-2.5
                                       dark:bg-slate-800 bg-gray-100
                                       dark:text-slate-300 text-gray-700
                                       dark:hover:bg-slate-700 hover:bg-gray-200
                                       text-sm font-semibold rounded-lg transition-all duration-150">
                            Clear search
                        </button>
                    @else
                        <h3 class="font-heading font-bold text-xl dark:text-white text-gray-900 mb-2">No books yet</h3>
                        <p class="text-sm dark:text-slate-400 text-gray-500 mb-7 max-w-sm mx-auto leading-relaxed">
                            Create your first book to start organising cash entries for this business.
                        </p>
                        @if($userRole !== 'viewer')
                            <button wire:click="openCreateBook"
                                    class="inline-flex items-center gap-2 px-6 py-3
                                           bg-primary hover:bg-accent text-white
                                           text-sm font-semibold rounded-lg
                                           transition-all duration-200
                                           shadow-xl shadow-primary/30 hover:shadow-accent/30">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                                </svg>
                                Create First Book
                            </button>
                        @endif
                    @endif
                </div>
            </div>

        @else

            {{-- ── TABLE CARD ── --}}
            <div class="dark:bg-dark bg-white dark:border-slate-800 border border-gray-200 rounded-xl overflow-hidden shadow-sm">

                {{-- Column headers — desktop only (grid matches data rows exactly) --}}
                <div class="biz-table-row hidden md:grid px-5 py-2.5
                            dark:bg-slate-900 bg-gray-50
                            dark:border-b dark:border-slate-800 border-b border-gray-100">
                    <p class="text-[10px] font-semibold uppercase tracking-widest dark:text-slate-400 text-gray-500 font-body pl-12">Book</p>
                    <p class="text-[10px] font-semibold uppercase tracking-widest dark:text-slate-400 text-gray-500 font-body hidden lg:block">Period</p>
                    <p class="text-[10px] font-semibold uppercase tracking-widest dark:text-slate-400 text-gray-500 font-body text-right pr-3">Entries</p>
                    <p class="text-[10px] font-semibold uppercase tracking-widest dark:text-slate-400 text-gray-500 font-body text-right pr-3 hidden lg:block">Cash In</p>
                    <p class="text-[10px] font-semibold uppercase tracking-widest dark:text-slate-400 text-gray-500 font-body text-right pr-3 hidden lg:block">Cash Out</p>
                    <p class="text-[10px] font-semibold uppercase tracking-widest dark:text-slate-400 text-gray-500 font-body text-right pr-3">Balance</p>
                    @if($userRole !== 'viewer')<div></div>@endif
                </div>

                {{-- Rows --}}
                <div class="divide-y dark:divide-slate-800 divide-gray-100">
                    @foreach($books as $book)
                        @php
                            $balance = (float) $book->balance_calculated;
                            $now     = now();
                            $status  = null;
                            if ($book->period_starts_at && $book->period_ends_at) {
                                if ($now->between($book->period_starts_at, $book->period_ends_at)) {
                                    $status = 'active';
                                } elseif ($book->period_ends_at->lt($now)) {
                                    $status = 'archived';
                                } else {
                                    $status = 'upcoming';
                                }
                            }
                            $iconBg    = match($status) {
                                'active'   => 'bg-emerald-500/10 dark:bg-emerald-500/15',
                                'upcoming' => 'bg-blue-500/10 dark:bg-blue-500/15',
                                'archived' => 'bg-gray-100 dark:bg-slate-800',
                                default    => 'bg-primary/10 dark:bg-primary/15',
                            };
                            $iconColor = match($status) {
                                'active'   => 'text-emerald-500',
                                'upcoming' => 'text-blue-400',
                                'archived' => 'text-gray-400 dark:text-slate-500',
                                default    => 'text-primary',
                            };
                            // Clean period label
                            $periodLabel = null;
                            if ($book->period_starts_at && $book->period_ends_at) {
                                $pS = $book->period_starts_at;
                                $pE = $book->period_ends_at;
                                if ($pS->format('M Y') === $pE->format('M Y')) {
                                    $periodLabel = $pS->format('M Y');          // "Mar 2026"
                                } elseif ($pS->format('Y') === $pE->format('Y')) {
                                    $periodLabel = $pS->format('j M').' – '.$pE->format('j M Y'); // "1 Mar – 31 Dec 2026"
                                } else {
                                    $periodLabel = $pS->format('M Y').' – '.$pE->format('M Y');   // "Jan 2025 – Dec 2026"
                                }
                            }
                        @endphp

                        <div wire:key="{{ $book->id }}"
                             x-data="{ shown: false }"
                             x-init="setTimeout(() => shown = true, {{ $loop->index * 35 }})"
                             :class="shown ? 'opacity-100' : 'opacity-0'"
                             class="group transition-opacity duration-300 relative">

                            {{-- Status border — permanent colour, transitions to primary on hover --}}
                            <div class="absolute inset-y-0 left-0 w-[3px] rounded-r-full transition-colors duration-200
                                        @if($status === 'active') bg-emerald-500 group-hover:bg-primary
                                        @elseif($status === 'upcoming') bg-blue-400 group-hover:bg-primary
                                        @else bg-transparent group-hover:bg-primary @endif"></div>

                            {{-- Desktop row (md+): CSS grid div — buttons safe from wire:navigate interception --}}
                            <div @click="if(!$event.target.closest('button')) Livewire.navigate('{{ route('businesses.books.show', [$business, $book]) }}')"
                               class="biz-table-row hidden md:grid px-5 py-4 cursor-pointer
                                      {{ $status === 'active' ? 'dark:bg-emerald-500/[0.03]' : '' }}
                                      dark:hover:bg-slate-800/30 hover:bg-gray-50
                                      transition-colors duration-150">

                                {{-- Col 1: Book icon + name + meta --}}
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-9 h-9 rounded-xl {{ $iconBg }}
                                                flex items-center justify-center flex-shrink-0">
                                        <svg class="w-4 h-4 {{ $iconColor }}" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"/>
                                        </svg>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5 min-w-0">
                                            <p class="text-sm font-heading font-bold dark:text-white text-gray-900 truncate leading-tight">
                                                {{ $book->name }}
                                            </p>
                                            @if($book->updated_at->gt(now()->subHours(24)))
                                                <span class="relative flex h-1.5 w-1.5 flex-shrink-0">
                                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary opacity-75"></span>
                                                    <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-primary"></span>
                                                </span>
                                            @endif
                                        </div>
                                        <p class="text-[11px] dark:text-slate-500 text-gray-400 font-body mt-0.5 truncate">
                                            {{ $book->updated_at->diffForHumans() }}
                                            @if($book->opening_balance > 0)
                                                <span class="dark:text-slate-600 text-gray-300 mx-0.5">·</span>
                                                Opening <x-amount :value="$book->opening_balance" :symbol="$business->currencySymbol()" tone="plain" :decimals="0" />
                                            @endif
                                        </p>
                                    </div>
                                </div>

                                {{-- Col 2: Period + status (lg+ only — hidden at md) --}}
                                <div class="hidden lg:block min-w-0">
                                    @if($periodLabel)
                                        <p class="text-xs font-mono dark:text-slate-300 text-gray-600 leading-tight truncate">
                                            {{ $periodLabel }}
                                        </p>
                                        @if($status)
                                            <div class="flex items-center gap-1.5 mt-1">
                                                <span class="w-1.5 h-1.5 rounded-full flex-shrink-0
                                                    @if($status === 'active') bg-emerald-500
                                                    @elseif($status === 'upcoming') bg-blue-400
                                                    @else bg-slate-400 @endif"></span>
                                                <span class="text-[10px] font-medium font-body
                                                    @if($status === 'active') text-emerald-600 dark:text-emerald-400
                                                    @elseif($status === 'upcoming') text-blue-600 dark:text-blue-400
                                                    @else dark:text-slate-500 text-gray-400 @endif">
                                                    {{ ucfirst($status) }}
                                                </span>
                                            </div>
                                        @endif
                                    @else
                                        <span class="text-sm font-mono dark:text-slate-700 text-gray-300">—</span>
                                    @endif
                                </div>

                                {{-- Col 3: Entries --}}
                                <div class="text-right pr-3">
                                    <span class="text-sm font-mono dark:text-slate-400 text-gray-500">{{ $book->entries_count }}</span>
                                    {{-- At md (no period column), show in/out hint below entries --}}
                                    <div class="flex items-center justify-end gap-1 mt-0.5 lg:hidden">
                                        <x-amount :value="$book->total_in ?? 0" tone="in" :decimals="0" class="text-[10px]" />
                                        <x-amount :value="$book->total_out ?? 0" tone="out" :decimals="0" class="text-[10px]" />
                                    </div>
                                </div>

                                {{-- Col 4: Cash In (lg+ only) --}}
                                <div class="hidden lg:block text-right pr-3">
                                    <x-amount :value="$book->total_in ?? 0" :symbol="$business->currencySymbol()" tone="in" :decimals="0" class="text-sm" />
                                </div>

                                {{-- Col 5: Cash Out (lg+ only) --}}
                                <div class="hidden lg:block text-right pr-3">
                                    <x-amount :value="$book->total_out ?? 0" :symbol="$business->currencySymbol()" tone="out" :decimals="0" class="text-sm" />
                                </div>

                                {{-- Col 6: Balance --}}
                                <div class="text-right pr-3">
                                    <p class="inline-flex items-center justify-end gap-1 font-bold text-base leading-tight">
                                        @if($balance < 0)
                                            <svg class="w-3.5 h-3.5 text-red-600 dark:text-red-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-label="Negative balance" role="img">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6 9 12.75l4.306-4.306a11.95 11.95 0 0 1 5.814 5.518l2.74 1.22m0 0-5.94 2.281m5.94-2.28-2.28-5.941"/>
                                            </svg>
                                        @endif
                                        <x-amount :value="$balance" :symbol="$business->currencySymbol()" tone="net" :decimals="0" />
                                    </p>
                                </div>

                                {{-- Col 7: Actions --}}
                                @if($userRole !== 'viewer')
                                    <div class="flex items-center justify-end gap-0.5
                                                opacity-0 group-hover:opacity-100 group-focus-within:opacity-100 transition-opacity duration-150">
                                        <button wire:click.stop="openEditBook('{{ $book->id }}')"
                                                @click.stop
                                                title="Edit book"
                                                aria-label="Edit {{ $book->name }}"
                                                class="p-1.5 rounded-lg transition-all duration-150
                                                       dark:text-slate-500 text-gray-400
                                                       dark:hover:text-primary hover:text-primary
                                                       dark:hover:bg-primary/10 hover:bg-primary/5">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/>
                                            </svg>
                                        </button>
                                        <button wire:click.stop="openDuplicateBook('{{ $book->id }}')"
                                                @click.stop
                                                title="Duplicate book"
                                                aria-label="Duplicate {{ $book->name }}"
                                                class="p-1.5 rounded-lg transition-all duration-150
                                                       dark:text-slate-500 text-gray-400
                                                       dark:hover:text-primary hover:text-primary
                                                       dark:hover:bg-primary/10 hover:bg-primary/5">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H9.75"/>
                                            </svg>
                                        </button>
                                        @if($userRole === 'owner')
                                        <button wire:click.stop="openDeleteBook('{{ $book->id }}')"
                                                @click.stop
                                                title="Delete book"
                                                aria-label="Delete {{ $book->name }}"
                                                class="p-1.5 rounded-lg transition-all duration-150
                                                       dark:text-slate-500 text-gray-400
                                                       dark:hover:text-red-400 hover:text-red-500
                                                       dark:hover:bg-red-500/10 hover:bg-red-50">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                            </svg>
                                        </button>
                                        @endif
                                    </div>
                                @endif

                            </div>{{-- end desktop row --}}

                            {{-- Mobile row (< md) --}}
                            <a href="{{ route('businesses.books.show', [$business, $book]) }}" wire:navigate
                               class="md:hidden flex items-center gap-3 px-4 py-4
                                      dark:hover:bg-slate-800/25 hover:bg-gray-50
                                      transition-colors duration-150">
                                <div class="w-9 h-9 rounded-xl {{ $iconBg }}
                                            flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 {{ $iconColor }}" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"/>
                                    </svg>
                                </div>

                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <p class="text-sm font-heading font-bold dark:text-white text-gray-900 truncate">{{ $book->name }}</p>
                                        @if($status === 'active')
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 flex-shrink-0"></span>
                                        @endif
                                    </div>
                                    <p class="text-[11px] dark:text-slate-500 text-gray-400 font-body mt-0.5 truncate">
                                        @if($periodLabel){{ $periodLabel }} · @endif{{ $book->entries_count }} {{ \Illuminate\Support\Str::plural('entry', $book->entries_count) }}
                                    </p>
                                </div>

                                <div class="flex-shrink-0 text-right">
                                    <p class="inline-flex items-center justify-end gap-1 font-bold text-base leading-tight">
                                        @if($balance < 0)
                                            <svg class="w-3.5 h-3.5 text-red-600 dark:text-red-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-label="Negative balance" role="img">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6 9 12.75l4.306-4.306a11.95 11.95 0 0 1 5.814 5.518l2.74 1.22m0 0-5.94 2.281m5.94-2.28-2.28-5.941"/>
                                            </svg>
                                        @endif
                                        <x-amount :value="$balance" :symbol="$business->currencySymbol()" tone="net" :decimals="0" />
                                    </p>
                                    <div class="flex items-center justify-end gap-1.5 mt-0.5">
                                        <x-amount :value="$book->total_in ?? 0" tone="in" :decimals="0" class="text-[10px]" />
                                        <x-amount :value="$book->total_out ?? 0" tone="out" :decimals="0" class="text-[10px]" />
                                    </div>
                                </div>

                                @if($userRole !== 'viewer')
                                    <button wire:click.prevent="openEditBook('{{ $book->id }}')"
                                            @click.stop
                                            aria-label="Edit {{ $book->name }}"
                                            class="flex-shrink-0 p-2 rounded-lg
                                                   dark:text-slate-400 text-gray-500
                                                   dark:hover:text-primary hover:text-primary
                                                   dark:hover:bg-primary/10 hover:bg-primary/5
                                                   transition-all duration-150">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/>
                                        </svg>
                                    </button>
                                    @if($userRole === 'owner')
                                    <button wire:click.prevent="openDeleteBook('{{ $book->id }}')"
                                            @click.stop
                                            aria-label="Delete {{ $book->name }}"
                                            class="flex-shrink-0 p-2 rounded-lg
                                                   dark:text-slate-400 text-gray-500
                                                   dark:hover:text-red-400 hover:text-red-500
                                                   dark:hover:bg-red-500/10 hover:bg-red-50
                                                   transition-all duration-150">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                        </svg>
                                    </button>
                                    @endif
                                @endif
                            </a>


                        </div>
                    @endforeach

                    {{-- Totals footer row — desktop, only when >1 book --}}
                    @if($books->count() > 1)
                        @php
                            $footerIn  = $books->sum(fn($b) => (float)($b->total_in  ?? 0));
                            $footerOut = $books->sum(fn($b) => (float)($b->total_out ?? 0));
                            $footerNet = $books->sum(fn($b) => (float)$b->balance_calculated);
                        @endphp
                        <div class="biz-table-row hidden md:grid px-5 py-3
                                    dark:bg-slate-900 bg-gray-50
                                    dark:border-t dark:border-slate-800 border-t border-gray-100">
                            <div class="min-w-0">
                                <p class="text-xs font-semibold dark:text-slate-400 text-gray-500 font-body">
                                    Total — {{ $books->count() }} books · {{ $books->sum('entries_count') }} entries
                                </p>
                            </div>
                            <div class="hidden lg:block"></div>
                            <div class="text-right pr-3">
                                <span class="text-xs font-mono font-semibold dark:text-slate-400 text-gray-500">{{ $books->sum('entries_count') }}</span>
                            </div>
                            <div class="text-right pr-3 hidden lg:block">
                                <x-amount :value="$footerIn" :symbol="$business->currencySymbol()" tone="in" :decimals="0" class="text-xs font-semibold" />
                            </div>
                            <div class="text-right pr-3 hidden lg:block">
                                <x-amount :value="$footerOut" :symbol="$business->currencySymbol()" tone="out" :decimals="0" class="text-xs font-semibold" />
                            </div>
                            <div class="text-right pr-3">
                                <span class="inline-flex items-center justify-end gap-1 text-xs font-bold">
                                    @if($footerNet < 0)
                                        <svg class="w-3 h-3 text-red-600 dark:text-red-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-label="Negative balance" role="img">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6 9 12.75l4.306-4.306a11.95 11.95 0 0 1 5.814 5.518l2.74 1.22m0 0-5.94 2.281m5.94-2.28-2.28-5.941"/>
                                        </svg>
                                    @endif
                                    <x-amount :value="$footerNet" :symbol="$business->currencySymbol()" tone="net" :decimals="0" />
                                </span>
                            </div>
                            @if($userRole !== 'viewer')
                                <div></div>
                            @endif
                        </div>
                    @endif

                </div>
            </div>

        @endif

    </div>

    {{-- ===== TABLE GRID STYLES ===== --}}
    <style>
        /* CSS Grid for pixel-perfect column alignment between header and data rows */
        .biz-table-row {
            grid-template-columns: minmax(0, 1fr) 5rem 8.5rem 8.5rem 9.5rem 5.5rem;
            align-items: center;
        }
        @media (min-width: 1024px) {
            .biz-table-row {
                /* lg: adds Period column before Entries */
                grid-template-columns: minmax(0, 1fr) 10.5rem 5rem 8.5rem 8.5rem 9.5rem 5.5rem;
            }
        }
    </style>

    {{-- ===== CREATE / EDIT / DUPLICATE BOOK MODALS (shared) ===== --}}
    @include('livewire.partials.book-modals')

    {{-- ===== DELETE BOOK MODAL ===== --}}
    @if($showDeleteBook)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-navy/70 backdrop-blur-sm" wire:click="$set('showDeleteBook', false)"></div>
            <div class="relative w-full max-w-md dark:bg-dark bg-white rounded-2xl shadow-2xl
                        dark:border dark:border-slate-700 border border-gray-200 p-6">
                <div class="w-12 h-12 rounded-2xl bg-red-500/10 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
                    </svg>
                </div>
                <h3 class="font-heading font-bold text-lg dark:text-white text-gray-900 text-center mb-1">Delete Book</h3>
                <p class="text-sm dark:text-slate-400 text-gray-500 font-body text-center mb-4">
                    This will permanently delete <strong class="dark:text-white text-gray-900">{{ $deletingBookName }}</strong>
                    and all its entries. This action cannot be undone.
                </p>
                <div class="mb-4">
                    <label class="block text-xs font-semibold uppercase tracking-wider dark:text-slate-500 text-gray-500 font-body mb-1.5">
                        Type <span class="dark:text-red-400 text-red-500 normal-case tracking-normal">{{ $deletingBookName }}</span> to confirm
                    </label>
                    <input type="text"
                           wire:model="deleteConfirmName"
                           wire:keydown.enter="deleteBook"
                           placeholder="{{ $deletingBookName }}"
                           class="w-full px-4 py-2.5 text-sm font-body
                                  dark:bg-slate-800 bg-white
                                  dark:border dark:border-slate-700 border border-gray-300
                                  dark:text-white text-gray-900 rounded-xl
                                  placeholder:dark:text-slate-600 placeholder:text-gray-400
                                  focus:outline-none focus:ring-2 focus:ring-red-500/40 focus:border-red-500/50
                                  transition-all duration-150">
                    @error('deleteConfirmName') <p class="text-xs text-red-400 mt-1 font-body">{{ $message }}</p> @enderror
                </div>
                <div class="flex gap-2">
                    <button wire:click="$set('showDeleteBook', false)"
                            class="flex-1 py-2.5 text-sm font-semibold font-body
                                   dark:bg-slate-800 bg-gray-100 dark:text-slate-300 text-gray-700
                                   dark:hover:bg-slate-700 hover:bg-gray-200
                                   rounded-xl transition-all duration-200">
                        Cancel
                    </button>
                    <button wire:click="deleteBook"
                            class="flex-1 py-2.5 text-sm font-semibold font-body
                                   bg-red-500 text-white hover:bg-red-400
                                   rounded-xl transition-all duration-200 shadow-lg shadow-red-500/20">
                        Delete Permanently
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
