<div class="min-h-full">

    {{-- ══════════════════════════════════════════════════════
         HEADER — the search field is the whole page's hero
    ══════════════════════════════════════════════════════ --}}
    <div class="px-4 sm:px-6 lg:px-8 py-4
                dark:bg-navy bg-white
                border-b border-gray-200 dark:border-slate-800
                sticky top-0 z-10 backdrop-blur-sm">
        <div class="max-w-5xl mx-auto">
            <h1 class="font-display font-extrabold text-xl sm:text-2xl dark:text-white text-gray-900 tracking-tight leading-tight mb-3">
                Search
            </h1>

            <div class="relative">
                <svg class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 dark:text-slate-500 text-gray-400"
                     fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.85-4.65a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z"/>
                </svg>

                <input type="search"
                       id="global-search-input"
                       wire:model.live.debounce.400ms="q"
                       autocomplete="off"
                       autofocus
                       placeholder="Search every entry — description, category, reference or amount"
                       aria-label="Search entries"
                       class="w-full pl-12 pr-12 py-3.5 rounded-xl text-base font-body
                              dark:bg-slate-800 bg-white
                              border border-gray-300 dark:border-slate-700
                              dark:text-white text-gray-900
                              placeholder:text-gray-400 dark:placeholder:text-slate-500
                              focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent
                              transition-shadow">

                <div class="absolute right-4 top-1/2 -translate-y-1/2 flex items-center">
                    <svg wire:loading wire:target="q,type,from,to,businessId,setType,setBusiness"
                         class="w-5 h-5 animate-spin text-primary dark:text-blue-light" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/>
                        <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v3a5 5 0 0 0-5 5H4Z"/>
                    </svg>
                    @if($q !== '')
                        <button type="button" wire:click="clearAll"
                                wire:loading.remove wire:target="q,type,from,to,businessId,setType,setBusiness"
                                aria-label="Clear search"
                                class="p-1 rounded-lg dark:text-slate-500 text-gray-400 dark:hover:text-white hover:text-gray-900 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    @endif
                </div>
            </div>

            {{-- ── Filter chips ──────────────────────────────────── --}}
            @php
                $chipBase   = 'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium font-body border transition-colors whitespace-nowrap';
                $chipOff    = 'dark:bg-slate-800 bg-white dark:border-slate-700 border-gray-300 dark:text-slate-300 text-gray-600 dark:hover:border-slate-600 hover:border-gray-400';
                $chipOn     = 'bg-primary/10 dark:bg-primary/20 border-primary/40 text-primary dark:text-blue-light';
            @endphp

            <div class="mt-3 flex items-center gap-2 overflow-x-auto pb-1 -mb-1">
                <button type="button" wire:click="setType('in')"
                        class="{{ $chipBase }} {{ $type === 'in' ? $chipOn : $chipOff }}">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Cash In
                </button>
                <button type="button" wire:click="setType('out')"
                        class="{{ $chipBase }} {{ $type === 'out' ? $chipOn : $chipOff }}">
                    <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Cash Out
                </button>

                {{-- Date range --}}
                <div x-data="{ open: false }" class="relative flex-shrink-0">
                    <button type="button" @click="open = !open"
                            class="{{ $chipBase }} {{ ($from !== '' || $to !== '') ? $chipOn : $chipOff }}">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/>
                        </svg>
                        @if($from !== '' || $to !== '')
                            {{ $from ?: 'Any' }} → {{ $to ?: 'Any' }}
                        @else
                            Date range
                        @endif
                    </button>

                    <div x-show="open" x-cloak @click.outside="open = false"
                         x-transition.opacity.duration.150ms
                         class="absolute left-0 mt-2 z-30 w-64 p-3 rounded-xl shadow-xl
                                dark:bg-slate-900 bg-white border border-gray-200 dark:border-slate-700">
                        <label class="block text-[11px] font-semibold uppercase tracking-wide dark:text-slate-400 text-gray-500 mb-1">From</label>
                        <input type="date" wire:model.live="from"
                               class="w-full mb-3 px-3 py-2 rounded-lg text-sm dark:bg-slate-800 bg-white border border-gray-300 dark:border-slate-700 dark:text-white text-gray-900">
                        <label class="block text-[11px] font-semibold uppercase tracking-wide dark:text-slate-400 text-gray-500 mb-1">To</label>
                        <input type="date" wire:model.live="to"
                               class="w-full px-3 py-2 rounded-lg text-sm dark:bg-slate-800 bg-white border border-gray-300 dark:border-slate-700 dark:text-white text-gray-900">
                    </div>
                </div>

                {{-- Business --}}
                @if($businesses->count() > 1)
                    <div x-data="{ open: false }" class="relative flex-shrink-0">
                        @php $activeBiz = $businesses->firstWhere('id', $businessId); @endphp
                        <button type="button" @click="open = !open"
                                class="{{ $chipBase }} {{ $businessId !== '' ? $chipOn : $chipOff }}">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15"/>
                            </svg>
                            {{ $activeBiz?->name ?? 'All businesses' }}
                        </button>

                        <div x-show="open" x-cloak @click.outside="open = false"
                             x-transition.opacity.duration.150ms
                             class="absolute left-0 mt-2 z-30 w-60 max-h-64 overflow-y-auto py-1 rounded-xl shadow-xl
                                    dark:bg-slate-900 bg-white border border-gray-200 dark:border-slate-700">
                            @foreach($businesses as $biz)
                                <button type="button" wire:click="setBusiness('{{ $biz->id }}')" @click="open = false"
                                        class="w-full text-left px-3 py-2 text-sm font-body flex items-center justify-between gap-2
                                               dark:text-slate-300 text-gray-700 dark:hover:bg-slate-800 hover:bg-gray-100 transition-colors">
                                    <span class="truncate">{{ $biz->name }}</span>
                                    @if($businessId === $biz->id)
                                        <svg class="w-4 h-4 flex-shrink-0 text-primary dark:text-blue-light" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                                        </svg>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if($hasFilters)
                    <button type="button" wire:click="clearFilters"
                            class="flex-shrink-0 px-3 py-1.5 text-xs font-medium font-body dark:text-slate-400 text-gray-500 dark:hover:text-white hover:text-gray-900 transition-colors">
                        Clear filters
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         BODY
    ══════════════════════════════════════════════════════ --}}
    <div class="px-4 sm:px-6 lg:px-8 py-6">
        <div class="max-w-5xl mx-auto">

            {{-- ── Totals strip (per currency — amounts are never summed across currencies) ── --}}
            @if($searching && $total > 0)
                <div class="mb-5 space-y-2" wire:loading.class="opacity-50" wire:target="q,type,from,to,businessId,setType,setBusiness">
                    @foreach($totalsByCurrency as $code => $bucket)
                        <div class="flex items-stretch rounded-xl overflow-hidden
                                    dark:bg-dark bg-white border border-gray-200 dark:border-slate-700 divide-x divide-gray-200 dark:divide-slate-700">
                            @if(count($totalsByCurrency) > 1)
                                <div class="flex items-center px-3 sm:px-4 dark:bg-slate-800 bg-gray-50">
                                    <span class="font-mono text-[11px] font-bold tracking-wider dark:text-slate-300 text-gray-600">{{ $code }}</span>
                                </div>
                            @endif
                            <div class="flex-1 px-3 py-3 sm:px-5 sm:py-4">
                                <p class="text-[10px] sm:text-xs font-medium uppercase tracking-wide dark:text-slate-400 text-gray-500">Cash In</p>
                                <x-amount :value="$bucket['in']" :symbol="$bucket['currencySymbol']" tone="in" :sign="false" class="text-base sm:text-xl font-semibold" />
                            </div>
                            <div class="flex-1 px-3 py-3 sm:px-5 sm:py-4">
                                <p class="text-[10px] sm:text-xs font-medium uppercase tracking-wide dark:text-slate-400 text-gray-500">Cash Out</p>
                                <x-amount :value="$bucket['out']" :symbol="$bucket['currencySymbol']" tone="out" :sign="false" class="text-base sm:text-xl font-semibold" />
                            </div>
                            <div class="flex-1 px-3 py-3 sm:px-5 sm:py-4">
                                <p class="text-[10px] sm:text-xs font-medium uppercase tracking-wide dark:text-slate-400 text-gray-500">Net</p>
                                <x-amount :value="$bucket['net']" :symbol="$bucket['currencySymbol']" tone="net" class="text-base sm:text-xl font-semibold" />
                            </div>
                            <div class="hidden sm:flex flex-1 px-5 py-4 flex-col justify-center">
                                <p class="text-xs font-medium uppercase tracking-wide dark:text-slate-400 text-gray-500">Entries</p>
                                <p class="font-mono tabular-nums text-xl font-semibold dark:text-slate-300 text-gray-700">{{ number_format($bucket['count']) }}</p>
                            </div>
                        </div>
                    @endforeach

                    @if(count($totalsByCurrency) > 1)
                        <p class="text-[11px] font-body dark:text-slate-500 text-gray-500 px-1">
                            Results span {{ count($totalsByCurrency) }} currencies — totals are shown separately, never added together.
                        </p>
                    @endif
                </div>
            @endif

            {{-- ── Loading skeleton (first search only) ──────────── --}}
            <div wire:loading.flex wire:target="q" class="hidden flex-col gap-2">
                @for($i = 0; $i < 4; $i++)
                    <div class="h-16 rounded-xl dark:bg-slate-800 bg-gray-100 animate-pulse"></div>
                @endfor
            </div>

            <div wire:loading.remove wire:target="q">

                {{-- ── Idle / empty states ───────────────────────── --}}
                @if(! $searching)
                    <div class="py-16 text-center">
                        <div class="mx-auto w-14 h-14 rounded-2xl flex items-center justify-center mb-4
                                    bg-primary/10 dark:bg-primary/20">
                            <svg class="w-7 h-7 text-primary dark:text-blue-light" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.85-4.65a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z"/>
                            </svg>
                        </div>
                        <h2 class="font-heading font-bold text-lg dark:text-white text-gray-900">
                            {{ $tooShort ? 'Keep typing…' : 'Find any entry' }}
                        </h2>
                        <p class="mt-1.5 text-sm font-body dark:text-slate-400 text-gray-500 max-w-md mx-auto">
                            {{ $tooShort
                                ? 'Type at least two characters to search.'
                                : 'Search across every business and book at once — by description, category, payment mode, reference or amount.' }}
                        </p>
                        @unless($tooShort)
                            <p class="mt-4 text-xs font-body dark:text-slate-500 text-gray-400">
                                Tip: press <kbd class="px-1.5 py-0.5 rounded border dark:border-slate-700 border-gray-300 font-mono">/</kbd> anywhere to jump here.
                            </p>
                        @endunless
                    </div>

                @elseif($total === 0)
                    <div class="py-16 text-center">
                        <div class="mx-auto w-14 h-14 rounded-2xl flex items-center justify-center mb-4
                                    dark:bg-slate-800 bg-gray-100">
                            <svg class="w-7 h-7 dark:text-slate-500 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75h4.5m-8.25 10.5h12a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 18 4.5H6a2.25 2.25 0 0 0-2.25 2.25v11.25A2.25 2.25 0 0 0 6 20.25Z"/>
                            </svg>
                        </div>
                        <h2 class="font-heading font-bold text-lg dark:text-white text-gray-900">No entries found</h2>
                        <p class="mt-1.5 text-sm font-body dark:text-slate-400 text-gray-500">
                            Nothing matches{{ $q !== '' ? ' “' . $q . '”' : '' }}{{ $hasFilters ? ' with these filters' : '' }}.
                        </p>
                        @if($hasFilters)
                            <button type="button" wire:click="clearFilters"
                                    class="mt-4 px-4 py-2 rounded-lg text-sm font-semibold font-body bg-primary text-white hover:bg-accent transition-colors">
                                Clear filters
                            </button>
                        @endif
                    </div>

                {{-- ── Results, grouped by date ──────────────────── --}}
                @else
                    <p class="mb-3 text-xs font-body dark:text-slate-400 text-gray-500">
                        Showing <span class="font-semibold dark:text-slate-200 text-gray-700">{{ number_format($shown) }}</span>
                        of <span class="font-semibold dark:text-slate-200 text-gray-700">{{ number_format($total) }}</span>
                        {{ Str::plural('entry', $total) }}
                    </p>

                    <div class="space-y-6">
                        @foreach($grouped as $day => $entries)
                            <section>
                                <h2 class="sticky top-[136px] z-[5] -mx-1 px-1 py-1.5 mb-1
                                           text-[11px] font-bold uppercase tracking-widest
                                           dark:text-slate-400 text-gray-500 dark:bg-navy bg-slate-50 backdrop-blur-sm">
                                    {{ \Carbon\Carbon::parse($day)->isoFormat('ddd, D MMM YYYY') }}
                                </h2>

                                <div class="rounded-xl overflow-hidden dark:bg-dark bg-white
                                            border border-gray-200 dark:border-slate-700
                                            divide-y divide-gray-200 dark:divide-slate-800">
                                    @foreach($entries as $entry)
                                        @php
                                            $book     = $entry->book;
                                            $business = $book?->business;
                                        @endphp
                                        <a href="{{ $business && $book
                                                    ? route('businesses.books.show', [$business, $book]) . '?entry=' . $entry->id
                                                    : '#' }}"
                                           wire:navigate wire:key="hit-{{ $entry->id }}"
                                           class="group flex items-center gap-3 px-3 sm:px-4 py-3
                                                  border-l-2 border-transparent hover:border-primary
                                                  dark:hover:bg-slate-800 hover:bg-gray-50 transition-colors">

                                            <span class="flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center
                                                         {{ $entry->type === 'in' ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' : 'bg-red-500/10 text-red-600 dark:text-red-400' }}">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.25" viewBox="0 0 24 24">
                                                    @if($entry->type === 'in')
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 4.5 4.5 19.5m0 0h9m-9 0v-9"/>
                                                    @else
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 19.5 19.5 4.5m0 0h-9m9 0v9"/>
                                                    @endif
                                                </svg>
                                            </span>

                                            <div class="min-w-0 flex-1">
                                                <p class="truncate text-sm font-medium font-body dark:text-white text-gray-900">
                                                    {{ $entry->displayLabel() }}
                                                </p>
                                                <p class="truncate text-[11px] font-body dark:text-slate-500 text-gray-500 mt-0.5">
                                                    <span class="dark:text-slate-400 text-gray-600">{{ $business?->name }}</span>
                                                    <span class="mx-1">›</span>{{ $book?->name }}
                                                    @if($entry->category)
                                                        <span class="mx-1">·</span>{{ $entry->category }}
                                                    @endif
                                                    @if($entry->payment_mode)
                                                        <span class="mx-1">·</span>{{ $entry->payment_mode }}
                                                    @endif
                                                </p>
                                            </div>

                                            <x-amount :value="$entry->amount"
                                                      :symbol="$business?->currencySymbol() ?? ''"
                                                      :type="$entry->type"
                                                      class="text-sm sm:text-base font-semibold flex-shrink-0" />

                                            <svg class="w-4 h-4 flex-shrink-0 dark:text-slate-600 text-gray-300 group-hover:text-primary transition-colors"
                                                 fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
                                            </svg>
                                        </a>
                                    @endforeach
                                </div>
                            </section>
                        @endforeach
                    </div>

                    @if($hasMore)
                        <div class="mt-6 flex justify-center">
                            <button type="button" wire:click="loadMore" wire:loading.attr="disabled" wire:target="loadMore"
                                    class="px-5 py-2.5 rounded-lg text-sm font-semibold font-body
                                           border border-gray-300 dark:border-slate-700
                                           dark:text-slate-300 text-gray-700
                                           hover:bg-gray-50 dark:hover:bg-slate-800 transition-colors
                                           disabled:opacity-60">
                                <span wire:loading.remove wire:target="loadMore">Load more</span>
                                <span wire:loading wire:target="loadMore">Loading…</span>
                            </button>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>
