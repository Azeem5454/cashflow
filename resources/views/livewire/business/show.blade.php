<div class="min-h-full">

    {{-- ===== PAGE HEADER ===== --}}
    <div class="px-6 lg:px-8 py-7
                dark:bg-[#080d1a] bg-white
                dark:border-b dark:border-slate-800 border-b border-gray-200
                sticky top-0 z-10 backdrop-blur-sm">
        <div class="max-w-6xl mx-auto flex items-center justify-between gap-4">

            <div class="flex items-center gap-3 min-w-0">
                <a href="{{ route('dashboard') }}"
                   class="p-2 rounded-xl dark:text-slate-500 text-gray-400
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
                    <a href="{{ route('businesses.settings', $business) }}"
                       title="Business settings"
                       class="p-2 rounded-xl dark:text-slate-500 text-gray-400
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
                    <a href="{{ route('businesses.books.create', $business) }}"
                       class="inline-flex items-center gap-2 px-4 py-2.5
                              bg-primary hover:bg-accent
                              text-white text-sm font-semibold rounded-xl
                              transition-all duration-200
                              shadow-lg shadow-primary/25 hover:shadow-accent/30">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                        </svg>
                        <span class="hidden sm:inline">New Book</span>
                        <span class="sm:hidden">New</span>
                    </a>
                @endif
            </div>

        </div>
    </div>

    {{-- ===== CONTENT ===== --}}
    <div class="px-6 lg:px-8 py-7 max-w-6xl mx-auto space-y-5">

        {{-- Search + sort --}}
        <div class="flex items-stretch gap-3">

            {{-- Search: flex wrapper so icon and input share natural gap --}}
            <div class="flex items-center gap-3 flex-1 max-w-sm
                        px-4 py-3 rounded-xl
                        dark:bg-navy bg-gray-50
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
                       class="flex-1 appearance-none border-none ring-0 outline-none focus:ring-0 focus:outline-none focus:border-none p-0 text-sm font-body
                              dark:bg-navy bg-gray-50
                              dark:text-white text-gray-900
                              dark:placeholder-slate-600 placeholder-gray-400"
                       style="-webkit-appearance: none; box-shadow: none !important; border: none !important; outline: none !important;">
            </div>

            {{-- Sort: Alpine custom dropdown, no native select double-arrow --}}
            <div class="relative flex-shrink-0"
                 x-data="{ open: false, label: 'Last Updated' }">
                <button @click="open = !open" @click.outside="open = false"
                        type="button"
                        class="flex items-center gap-2.5 px-4 h-full rounded-xl text-sm font-body
                               dark:bg-navy bg-gray-50
                               dark:border-slate-700 border-gray-200 border
                               dark:text-slate-300 text-gray-700
                               focus:outline-none focus:ring-2 focus:ring-primary/50
                               transition-all duration-150 whitespace-nowrap">
                    <span>Sort: <span x-text="label"></span></span>
                    <svg class="w-3.5 h-3.5 dark:text-slate-500 text-gray-400 transition-transform duration-150"
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
                            dark:border-slate-700/60 border-gray-200 border
                            rounded-xl shadow-xl shadow-black/20 overflow-hidden"
                     style="display: none;">
                    @foreach([['updated_at', 'Last Updated'], ['created_at', 'Date Created'], ['name', 'Name A–Z']] as [$val, $lbl])
                        <button type="button"
                                @click="$wire.set('sortBy', '{{ $val }}'); label = '{{ $lbl }}'; open = false"
                                class="w-full text-left px-4 py-2.5 text-sm font-body
                                       dark:text-slate-300 text-gray-700
                                       dark:hover:bg-slate-800 hover:bg-gray-50
                                       transition-colors duration-100
                                       {{ $sortBy === $val ? 'dark:text-primary text-primary font-semibold' : '' }}">
                            {{ $lbl }}
                        </button>
                    @endforeach
                </div>
            </div>

        </div>

        {{-- Books list --}}
        @if($books->isEmpty())

            <div class="dark:bg-[#1e293b] bg-white dark:border-slate-700/60 border-2 border-dashed border-gray-200
                        rounded-2xl px-8 py-16 text-center relative overflow-hidden">
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
                                       text-sm font-semibold rounded-xl transition-all duration-150">
                            Clear search
                        </button>
                    @else
                        <h3 class="font-heading font-bold text-xl dark:text-white text-gray-900 mb-2">No books yet</h3>
                        <p class="text-sm dark:text-slate-400 text-gray-500 mb-7 max-w-sm mx-auto leading-relaxed">
                            Create your first book to start organising cash entries for this business.
                        </p>
                        @if($userRole !== 'viewer')
                            <a href="{{ route('businesses.books.create', $business) }}"
                               class="inline-flex items-center gap-2 px-6 py-3
                                      bg-primary hover:bg-accent text-white
                                      text-sm font-semibold rounded-xl
                                      transition-all duration-200
                                      shadow-xl shadow-primary/30 hover:shadow-accent/30">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                                </svg>
                                Create First Book
                            </a>
                        @endif
                    @endif
                </div>
            </div>

        @else

            <div class="dark:bg-[#1e293b] bg-white dark:border-slate-700/60 border border-gray-200
                        rounded-2xl overflow-hidden">

                {{-- Column headings --}}
                <div class="hidden md:grid grid-cols-12 gap-4 px-5 py-3
                            dark:border-b dark:border-slate-700/40 border-b border-gray-100
                            dark:bg-slate-800/30 bg-gray-50/80">
                    <div class="col-span-5">
                        <p class="text-xs font-semibold uppercase tracking-wider dark:text-slate-500 text-gray-400 font-body">Book</p>
                    </div>
                    <div class="col-span-2 text-right">
                        <p class="text-xs font-semibold uppercase tracking-wider text-green-600 dark:text-green-500/80 font-body">Cash In</p>
                    </div>
                    <div class="col-span-2 text-right">
                        <p class="text-xs font-semibold uppercase tracking-wider text-red-500 dark:text-red-500/80 font-body">Cash Out</p>
                    </div>
                    <div class="col-span-2 text-right">
                        <p class="text-xs font-semibold uppercase tracking-wider dark:text-slate-500 text-gray-400 font-body">Balance</p>
                    </div>
                    <div class="col-span-1"></div>
                </div>

                {{-- Rows --}}
                <div class="divide-y dark:divide-slate-700/40 divide-gray-100">
                    @foreach($books as $book)
                        <a href="{{ route('businesses.books.show', [$business, $book]) }}"
                           class="grid grid-cols-12 gap-4 items-center px-5 py-4
                                  dark:hover:bg-slate-800/40 hover:bg-gray-50
                                  group transition-colors duration-150">

                            {{-- Book identity --}}
                            <div class="col-span-8 md:col-span-5 flex items-center gap-3 min-w-0">
                                <div class="w-9 h-9 rounded-xl bg-primary/10 dark:bg-primary/15 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4.5 h-4.5 text-primary w-[18px] h-[18px]" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"/>
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold dark:text-white text-gray-900 truncate font-body
                                              group-hover:text-primary transition-colors duration-150">
                                        {{ $book->name }}
                                    </p>
                                    <p class="text-xs dark:text-slate-500 text-gray-400 mt-0.5 font-body">
                                        @if($book->period_starts_at)
                                            <span class="font-mono">{{ $book->period_starts_at->format('d M Y') }}@if($book->period_ends_at) – {{ $book->period_ends_at->format('d M Y') }}@endif</span>
                                            ·
                                        @endif
                                        Updated {{ $book->updated_at->diffForHumans() }}
                                    </p>
                                </div>
                            </div>

                            {{-- Cash In --}}
                            <div class="hidden md:block col-span-2 text-right">
                                <p class="font-mono text-sm font-semibold text-green-500 dark:text-green-400">
                                    {{ number_format((float) ($book->total_in ?? 0), 2) }}
                                </p>
                            </div>

                            {{-- Cash Out --}}
                            <div class="hidden md:block col-span-2 text-right">
                                <p class="font-mono text-sm font-semibold text-red-500 dark:text-red-400">
                                    {{ number_format((float) ($book->total_out ?? 0), 2) }}
                                </p>
                            </div>

                            {{-- Balance --}}
                            <div class="col-span-3 md:col-span-2 text-right">
                                <p class="font-mono text-sm font-bold
                                          {{ (float) $book->balance_calculated >= 0 ? 'text-green-500 dark:text-green-400' : 'text-red-500 dark:text-red-400' }}">
                                    @if((float) $book->balance_calculated < 0)−@endif{{ number_format(abs((float) $book->balance_calculated), 2) }}
                                </p>
                            </div>

                            {{-- Arrow --}}
                            <div class="col-span-1 flex justify-end">
                                <svg class="w-4 h-4 dark:text-slate-600 text-gray-300
                                            group-hover:text-primary group-hover:translate-x-0.5
                                            transition-all duration-150"
                                     fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>
                                </svg>
                            </div>

                        </a>
                    @endforeach
                </div>

            </div>

        @endif

    </div>
</div>
