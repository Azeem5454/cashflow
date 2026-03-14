<div class="min-h-full">

    {{-- ===== PAGE HEADER ===== --}}
    <div class="px-6 lg:px-8 py-7
                dark:bg-[#080d1a] bg-white
                dark:border-b dark:border-slate-800 border-b border-gray-200
                sticky top-0 z-10 backdrop-blur-sm">
        <div class="max-w-6xl mx-auto flex items-center justify-between gap-4">
            <div>
                @php
                    $hour = now()->hour;
                    if ($hour < 12)      $greeting = 'Good morning';
                    elseif ($hour < 17) $greeting = 'Good afternoon';
                    else                $greeting = 'Good evening';
                    $firstName = explode(' ', auth()->user()->name)[0];
                @endphp
                <h1 class="font-display font-extrabold text-2xl lg:text-3xl dark:text-white text-gray-900 tracking-tight leading-none">
                    {{ $greeting }}, {{ $firstName }}
                </h1>
                <div class="flex items-center gap-2 mt-1.5">
                    <p class="text-sm dark:text-slate-500 text-gray-400 font-body">
                        {{ now()->format('l, F j, Y') }}
                    </p>
                    <span class="dark:text-slate-700 text-gray-300">·</span>
                    @if(auth()->user()->isPro())
                        <span class="inline-flex items-center gap-1 text-xs font-semibold text-amber-400 dark:bg-amber-400/10 bg-amber-50 px-2 py-0.5 rounded-full">
                            <svg class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z"/>
                            </svg>
                            Pro Plan
                        </span>
                    @else
                        <a href="{{ route('billing') }}"
                           class="inline-flex items-center gap-1 text-xs font-semibold dark:text-slate-500 text-gray-400 hover:text-primary dark:hover:text-primary transition-colors">
                            Free Plan
                            <span class="text-primary">· Upgrade →</span>
                        </a>
                    @endif
                </div>
            </div>

            <a href="{{ route('businesses.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5
                      bg-primary hover:bg-accent
                      text-white text-sm font-semibold rounded-xl
                      transition-all duration-200
                      shadow-lg shadow-primary/25 hover:shadow-accent/30
                      flex-shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                <span class="hidden sm:inline">New Business</span>
                <span class="sm:hidden">New</span>
            </a>
        </div>
    </div>

    {{-- ===== CONTENT ===== --}}
    <div class="px-6 lg:px-8 py-7 max-w-6xl mx-auto space-y-8">

        {{-- Stats Strip --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4"
             x-data="{ shown: false }"
             x-init="requestAnimationFrame(() => shown = true)">

            {{-- Businesses --}}
            <div class="dark:bg-[#1e293b] bg-white dark:border-slate-700/60 border border-gray-200
                        rounded-2xl p-5 flex items-center gap-4
                        transition-all duration-500 ease-out"
                 :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-3'">
                <div class="w-12 h-12 rounded-xl bg-primary/10 dark:bg-primary/15 flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/>
                    </svg>
                </div>
                <div>
                    <p class="font-mono font-bold text-2xl dark:text-white text-gray-900 leading-none">{{ $businesses->count() }}</p>
                    <p class="text-sm dark:text-slate-400 text-gray-500 mt-0.5">
                        {{ Str::plural('Business', $businesses->count()) }}
                    </p>
                </div>
            </div>

            {{-- Books --}}
            <div class="dark:bg-[#1e293b] bg-white dark:border-slate-700/60 border border-gray-200
                        rounded-2xl p-5 flex items-center gap-4
                        transition-all duration-500 ease-out delay-75"
                 :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-3'">
                <div class="w-12 h-12 rounded-xl bg-accent/10 dark:bg-accent/15 flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 text-accent" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"/>
                    </svg>
                </div>
                <div>
                    <p class="font-mono font-bold text-2xl dark:text-white text-gray-900 leading-none">{{ $totalBooks }}</p>
                    <p class="text-sm dark:text-slate-400 text-gray-500 mt-0.5">
                        {{ Str::plural('Book', $totalBooks) }}
                    </p>
                </div>
            </div>

            {{-- Plan --}}
            <div class="dark:bg-[#1e293b] bg-white dark:border-slate-700/60 border border-gray-200
                        rounded-2xl p-5 flex items-center gap-4
                        transition-all duration-500 ease-out delay-150"
                 :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-3'">
                @if(auth()->user()->isPro())
                    <div class="w-12 h-12 rounded-xl bg-amber-400/10 flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6 text-amber-400" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-mono font-bold text-2xl text-amber-400 leading-none">Pro</p>
                        <p class="text-sm dark:text-slate-400 text-gray-500 mt-0.5">Active subscription</p>
                    </div>
                @else
                    <div class="w-12 h-12 rounded-xl dark:bg-slate-700/50 bg-gray-100 flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6 dark:text-slate-500 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.348 14.652a3.75 3.75 0 0 1 0-5.304m5.304 0a3.75 3.75 0 0 1 0 5.304m-7.425 2.121a6.75 6.75 0 0 1 0-9.546m9.546 0a6.75 6.75 0 0 1 0 9.546M5.106 18.894c-3.808-3.807-3.808-9.98 0-13.788m13.788 0c3.808 3.807 3.808 9.98 0 13.788M12 12h.008v.008H12V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-mono font-bold text-2xl dark:text-white text-gray-900 leading-none">Free</p>
                        <a href="{{ route('billing') }}"
                           class="text-sm text-primary hover:text-accent transition-colors mt-0.5 inline-block">
                            Upgrade to Pro →
                        </a>
                    </div>
                @endif
            </div>
        </div>

        {{-- ===== BUSINESSES SECTION ===== --}}
        <div>
            <div class="flex items-center justify-between mb-5">
                <h2 class="font-heading font-bold text-lg dark:text-white text-gray-900">Your Businesses</h2>
                @if($businesses->isNotEmpty())
                    <a href="{{ route('businesses.create') }}"
                       class="inline-flex items-center gap-1.5 text-sm font-medium text-primary hover:text-accent transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                        </svg>
                        Add Business
                    </a>
                @endif
            </div>

            @if($businesses->isEmpty())
                {{-- ===== EMPTY STATE ===== --}}
                <div class="dark:bg-[#1e293b] bg-white dark:border-slate-700/60 border-2 border-dashed border-gray-200
                            rounded-2xl px-8 py-16 text-center
                            relative overflow-hidden">
                    {{-- Subtle glow --}}
                    <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                        <div class="w-64 h-64 bg-primary/5 rounded-full blur-3xl"></div>
                    </div>

                    <div class="relative">
                        <div class="w-16 h-16 rounded-2xl bg-primary/10 dark:bg-primary/15 flex items-center justify-center mx-auto mb-5 shadow-lg shadow-primary/10">
                            <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/>
                            </svg>
                        </div>
                        <h3 class="font-heading font-bold text-xl dark:text-white text-gray-900 mb-2">No businesses yet</h3>
                        <p class="text-sm dark:text-slate-400 text-gray-500 mb-7 max-w-sm mx-auto leading-relaxed">
                            Create your first business to start tracking cash flow, organising books, and collaborating with your team.
                        </p>
                        <a href="{{ route('businesses.create') }}"
                           class="inline-flex items-center gap-2 px-6 py-3
                                  bg-primary hover:bg-accent text-white
                                  text-sm font-semibold rounded-xl
                                  transition-all duration-200
                                  shadow-xl shadow-primary/30 hover:shadow-accent/30">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                            </svg>
                            Create Your First Business
                        </a>
                    </div>
                </div>

            @else
                {{-- ===== BUSINESS GRID ===== --}}
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                    @foreach($businesses as $business)
                        @php $role = $business->pivot->role ?? 'member'; @endphp

                        <div x-data="{ shown: false }"
                             x-init="setTimeout(() => shown = true, {{ $loop->index * 80 }})"
                             :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                             class="transition-all duration-500 ease-out">

                            <div class="dark:bg-[#1e293b] bg-white
                                        dark:border-slate-700/60 border border-gray-200
                                        rounded-2xl overflow-hidden
                                        hover:dark:border-primary/40 hover:border-primary/30
                                        hover:shadow-xl hover:shadow-primary/5
                                        transition-all duration-200 group
                                        flex flex-col h-full">

                                {{-- Card body --}}
                                <div class="p-5 flex-1">
                                    <div class="flex items-start justify-between mb-4">
                                        <div class="w-10 h-10 rounded-xl bg-primary/10 dark:bg-primary/15 flex items-center justify-center flex-shrink-0">
                                            <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/>
                                            </svg>
                                        </div>

                                        {{-- Role badge --}}
                                        <span class="text-[11px] font-semibold px-2.5 py-1 rounded-full
                                            @if($role === 'owner')
                                                dark:bg-primary/20 bg-primary/10 text-primary
                                            @elseif($role === 'editor')
                                                dark:bg-green-500/20 bg-green-50 text-green-600 dark:text-green-400
                                            @else
                                                dark:bg-slate-700 bg-gray-100 dark:text-slate-400 text-gray-500
                                            @endif">
                                            {{ ucfirst($role) }}
                                        </span>
                                    </div>

                                    <h3 class="font-heading font-bold text-base dark:text-white text-gray-900 mb-1.5 leading-snug
                                               group-hover:text-primary transition-colors duration-150">
                                        {{ $business->name }}
                                    </h3>

                                    @if($business->description)
                                        <p class="text-sm dark:text-slate-400 text-gray-500 line-clamp-2 leading-relaxed">
                                            {{ $business->description }}
                                        </p>
                                    @else
                                        <p class="text-sm dark:text-slate-600 text-gray-300 italic">No description added</p>
                                    @endif
                                </div>

                                {{-- Stats bar --}}
                                <div class="px-5 py-3 dark:bg-slate-800/40 bg-gray-50
                                            dark:border-y dark:border-slate-700/40 border-y border-gray-100
                                            flex items-center gap-4 text-xs">
                                    <div class="flex items-center gap-1.5 dark:text-slate-400 text-gray-500">
                                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"/>
                                        </svg>
                                        <span class="font-mono font-semibold dark:text-slate-300 text-gray-700">{{ $business->books_count }}</span>
                                        {{ Str::plural('book', $business->books_count) }}
                                    </div>
                                    <span class="dark:text-slate-700 text-gray-300">·</span>
                                    <div class="flex items-center gap-1.5 dark:text-slate-400 text-gray-500">
                                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/>
                                        </svg>
                                        <span class="font-mono font-semibold dark:text-slate-300 text-gray-700">{{ $business->members_count }}</span>
                                        {{ Str::plural('member', $business->members_count) }}
                                    </div>
                                    <div class="ml-auto font-mono font-bold text-[11px] uppercase tracking-wider dark:text-slate-500 text-gray-400">
                                        {{ $business->currency ?? 'PKR' }}
                                    </div>
                                </div>

                                {{-- Actions --}}
                                <div class="px-5 py-4 flex items-center gap-2.5">
                                    <a href="{{ route('businesses.show', $business) }}"
                                       class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5
                                              dark:bg-primary/10 bg-primary/5
                                              hover:bg-primary
                                              dark:text-blue-light text-primary
                                              hover:text-white
                                              text-sm font-semibold rounded-xl
                                              transition-all duration-200 group/btn">
                                        Open Business
                                        <svg class="w-4 h-4 transition-transform duration-200 group-hover/btn:translate-x-0.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>
                                        </svg>
                                    </a>

                                    @if($role === 'owner')
                                        <a href="{{ route('businesses.settings', $business) }}"
                                           class="p-2.5 rounded-xl transition-all duration-150
                                                  dark:text-slate-500 text-gray-400
                                                  dark:hover:text-white hover:text-gray-700
                                                  dark:hover:bg-slate-700 hover:bg-gray-100"
                                           title="Business settings">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                            </svg>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Free plan nudge (only if Free + has businesses) --}}
        @if(!auth()->user()->isPro() && $businesses->isNotEmpty())
            <div class="dark:bg-[#1e293b] bg-white dark:border-slate-700/60 border border-gray-200
                        rounded-2xl p-5 flex items-center gap-4
                        relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-r from-primary/5 to-transparent pointer-events-none"></div>
                <div class="w-10 h-10 rounded-xl bg-amber-400/10 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-amber-400" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold dark:text-white text-gray-900">Unlock unlimited businesses & PDF exports</p>
                    <p class="text-xs dark:text-slate-400 text-gray-500 mt-0.5">Upgrade to Pro for just $3/month.</p>
                </div>
                <a href="{{ route('billing') }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold
                          dark:bg-amber-400/10 bg-amber-50 text-amber-500 dark:text-amber-400
                          hover:bg-amber-400 hover:text-white
                          rounded-xl transition-all duration-200 flex-shrink-0">
                    Upgrade
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>
                    </svg>
                </a>
            </div>
        @endif

    </div>
</div>
