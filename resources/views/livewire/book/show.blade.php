<div class="min-h-full" x-data>

    {{-- ===== STICKY HEADER ===== --}}
    <div class="sticky top-0 z-30 px-6 lg:px-8 py-4
                dark:bg-navy/95 bg-white/95 backdrop-blur-md
                dark:border-b dark:border-slate-800 border-b border-gray-200/80">
        <div class="max-w-5xl mx-auto flex items-center gap-4">

            <a href="{{ route('businesses.show', $business) }}" wire:navigate
               class="p-2 rounded-xl dark:text-slate-500 text-gray-400
                      dark:hover:bg-slate-800 hover:bg-gray-100
                      dark:hover:text-white hover:text-gray-700
                      transition-all duration-150 flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                </svg>
            </a>

            <div class="flex-1 min-w-0">
                <h1 class="font-display font-extrabold text-xl dark:text-white text-gray-900 tracking-tight leading-none truncate">
                    {{ $book->name }}
                </h1>
                <div class="flex items-center gap-2 mt-0.5 flex-wrap">
                    <span class="text-xs dark:text-slate-500 text-gray-400 font-body">{{ $business->name }}</span>
                    <span class="w-1 h-1 rounded-full dark:bg-slate-700 bg-gray-300 flex-shrink-0"></span>
                    <span class="text-xs font-mono dark:text-slate-500 text-gray-400 uppercase tracking-wider">{{ $business->currency }}</span>
                    @if($book->period_starts_at || $book->period_ends_at)
                        <span class="w-1 h-1 rounded-full dark:bg-slate-700 bg-gray-300 flex-shrink-0"></span>
                        <span class="text-xs dark:text-slate-500 text-gray-400 font-body">
                            @if($book->period_starts_at && $book->period_ends_at)
                                {{ $book->period_starts_at->format('d M') }} – {{ $book->period_ends_at->format('d M Y') }}
                            @elseif($book->period_starts_at)
                                from {{ $book->period_starts_at->format('d M Y') }}
                            @else
                                until {{ $book->period_ends_at->format('d M Y') }}
                            @endif
                        </span>
                    @endif
                    @if($userRole !== 'owner')
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wider
                                     {{ $userRole === 'editor'
                                         ? 'dark:bg-blue-500/10 dark:text-blue-400 bg-blue-50 text-blue-600'
                                         : 'dark:bg-slate-800 dark:text-slate-500 bg-gray-100 text-gray-500' }}">
                            {{ $userRole }}
                        </span>
                    @endif
                </div>
            </div>

            {{-- Settings gear (owner/editor only) --}}
            @if($userRole !== 'viewer')
                <div x-data="{ open: false }" class="relative flex-shrink-0">
                    <button @click="open = !open" @click.outside="open = false"
                            class="p-2 rounded-xl dark:text-slate-500 text-gray-400
                                   dark:hover:bg-slate-800 hover:bg-gray-100
                                   dark:hover:text-white hover:text-gray-700
                                   transition-all duration-150">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                        </svg>
                    </button>

                    <div x-show="open"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 -translate-y-1"
                         class="absolute top-full right-0 mt-1 w-52 z-40
                                dark:bg-slate-800 bg-white
                                dark:border dark:border-slate-700 border border-gray-200
                                rounded-xl shadow-xl shadow-black/20 overflow-hidden py-1"
                         style="display:none;">

                        <button @click="$wire.openRenameBook(); open = false"
                                class="w-full flex items-center gap-3 px-4 py-2.5 text-sm font-body
                                       dark:text-slate-300 text-gray-700
                                       dark:hover:bg-slate-700/50 hover:bg-gray-50 transition-colors">
                            <svg class="w-4 h-4 dark:text-slate-500 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/>
                            </svg>
                            Rename Book
                        </button>

                        <button @click="$wire.duplicateBook(); open = false"
                                class="w-full flex items-center gap-3 px-4 py-2.5 text-sm font-body
                                       dark:text-slate-300 text-gray-700
                                       dark:hover:bg-slate-700/50 hover:bg-gray-50 transition-colors">
                            <svg class="w-4 h-4 dark:text-slate-500 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H9.75"/>
                            </svg>
                            Duplicate Book
                        </button>

                        @if($userRole === 'owner')
                            <div class="my-1 dark:border-t dark:border-slate-700 border-t border-gray-100"></div>
                            <button @click="$wire.openDeleteBook(); open = false"
                                    class="w-full flex items-center gap-3 px-4 py-2.5 text-sm font-body
                                           text-red-400
                                           dark:hover:bg-red-500/10 hover:bg-red-50 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                </svg>
                                Delete Book
                            </button>
                        @endif
                    </div>
                </div>
            @endif

        </div>
    </div>

    {{-- ===== RENAME BOOK MODAL ===== --}}
    @if($showRenameBook)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-navy/70 backdrop-blur-sm" wire:click="$set('showRenameBook', false)"></div>
            <div class="relative w-full max-w-md dark:bg-dark bg-white rounded-2xl shadow-2xl
                        dark:border dark:border-slate-700 border border-gray-200 p-6">
                <h3 class="font-heading font-bold text-lg dark:text-white text-gray-900 mb-4">Rename Book</h3>
                <div class="mb-4">
                    <label class="block text-xs font-semibold uppercase tracking-wider dark:text-slate-500 text-gray-500 font-body mb-1.5">
                        Book Name
                    </label>
                    <input type="text"
                           wire:model="renameBookName"
                           wire:keydown.enter="renameBook"
                           autofocus
                           class="w-full px-4 py-2.5 text-sm font-body
                                  dark:bg-slate-800 bg-white
                                  dark:border dark:border-slate-700 border border-gray-300
                                  dark:text-white text-gray-900 rounded-xl
                                  focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary/50
                                  transition-all duration-150">
                    @error('renameBookName')<p class="text-xs text-red-400 mt-1 font-body">{{ $message }}</p>@enderror
                </div>
                <div class="flex gap-2">
                    <button wire:click="$set('showRenameBook', false)"
                            class="flex-1 py-2.5 text-sm font-semibold font-body
                                   dark:bg-slate-800 bg-gray-100 dark:text-slate-300 text-gray-700
                                   dark:hover:bg-slate-700 hover:bg-gray-200
                                   rounded-xl transition-all duration-200">
                        Cancel
                    </button>
                    <button wire:click="renameBook"
                            class="flex-1 py-2.5 text-sm font-semibold font-body
                                   bg-primary text-white hover:bg-accent
                                   rounded-xl transition-all duration-200">
                        Save
                    </button>
                </div>
            </div>
        </div>
    @endif

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
                    This will permanently delete <strong class="dark:text-white text-gray-900">{{ $book->name }}</strong>
                    and all its entries. This action cannot be undone.
                </p>
                <div class="mb-4">
                    <label class="block text-xs font-semibold uppercase tracking-wider dark:text-slate-500 text-gray-500 font-body mb-1.5">
                        Type <span class="dark:text-red-400 text-red-500 normal-case tracking-normal">{{ $book->name }}</span> to confirm
                    </label>
                    <input type="text"
                           wire:model="deleteConfirmName"
                           wire:keydown.enter="deleteBook"
                           placeholder="{{ $book->name }}"
                           class="w-full px-4 py-2.5 text-sm font-body
                                  dark:bg-slate-800 bg-white
                                  dark:border dark:border-slate-700 border border-gray-300
                                  dark:text-white text-gray-900 rounded-xl
                                  placeholder:dark:text-slate-600 placeholder:text-gray-400
                                  focus:outline-none focus:ring-2 focus:ring-red-500/40 focus:border-red-500/50
                                  transition-all duration-150">
                    @error('deleteConfirmName')<p class="text-xs text-red-400 mt-1 font-body">{{ $message }}</p>@enderror
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

    {{-- ===== PAGE BODY ===== --}}
    <div class="px-6 lg:px-8 py-6">
        <div class="max-w-5xl mx-auto space-y-3">

            {{-- ===== FILTER BAR ===== --}}
            <div class="flex items-center gap-2 flex-wrap">

                {{-- Types filter --}}
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" @click.outside="open = false" type="button"
                            class="flex items-center gap-1.5 px-3 py-1.5
                                   dark:bg-dark bg-white
                                   dark:border dark:border-slate-700 border border-gray-200
                                   dark:text-slate-300 text-gray-700
                                   text-xs font-semibold font-body rounded-lg
                                   hover:dark:border-slate-600 hover:border-gray-300
                                   transition-all duration-150">
                        <span>Types:
                            <span class="{{ $filterType !== 'all' ? 'text-primary' : '' }}">
                                {{ $filterType === 'all' ? 'All' : ($filterType === 'in' ? 'Cash In' : 'Cash Out') }}
                            </span>
                        </span>
                        <svg class="w-3 h-3 dark:text-slate-500 text-gray-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
                        </svg>
                    </button>

                    <div x-show="open"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 -translate-y-1"
                         class="absolute top-full left-0 mt-1 w-44 z-20
                                dark:bg-slate-800 bg-white
                                dark:border dark:border-slate-700 border border-gray-200
                                rounded-xl shadow-xl shadow-black/20 overflow-hidden"
                         style="display:none;">
                        @foreach(['all' => 'All', 'in' => 'Cash In', 'out' => 'Cash Out'] as $val => $label)
                            <button @click="$wire.set('filterType', '{{ $val }}'); open = false"
                                    class="w-full flex items-center gap-3 px-4 py-2.5 text-sm font-body
                                           dark:hover:bg-slate-700/50 hover:bg-gray-50 transition-colors
                                           {{ $filterType === $val ? 'dark:text-white text-gray-900 font-semibold' : 'dark:text-slate-400 text-gray-600' }}">
                                <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center flex-shrink-0
                                            {{ $filterType === $val ? 'border-primary' : 'dark:border-slate-600 border-gray-300' }}">
                                    @if($filterType === $val)
                                        <div class="w-2 h-2 rounded-full bg-primary"></div>
                                    @endif
                                </div>
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </div>

            </div>

            {{-- ===== BALANCE SUMMARY STRIP ===== --}}
            @php $isPositive = bccomp((string)$balance, '0', 2) >= 0; @endphp
            <div class="dark:bg-dark bg-white rounded-2xl
                        dark:border dark:border-slate-700 border border-gray-200 overflow-hidden">
                <div class="flex divide-x dark:divide-slate-700 divide-gray-200">

                    <div class="flex-1 px-3 py-3 sm:px-5 sm:py-4 flex items-center gap-2 sm:gap-3">
                        <div class="hidden sm:flex w-9 h-9 rounded-full bg-emerald-500/10 items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] sm:text-xs font-semibold uppercase tracking-wider dark:text-slate-500 text-gray-500 font-body">Cash In</p>
                            <p class="font-mono font-bold text-base sm:text-xl text-emerald-400 leading-tight mt-0.5 truncate">
                                {{ number_format((float)$totalIn, 0) }}
                            </p>
                        </div>
                    </div>

                    <div class="flex-1 px-3 py-3 sm:px-5 sm:py-4 flex items-center gap-2 sm:gap-3">
                        <div class="hidden sm:flex w-9 h-9 rounded-full bg-red-500/10 items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] sm:text-xs font-semibold uppercase tracking-wider dark:text-slate-500 text-gray-500 font-body">Cash Out</p>
                            <p class="font-mono font-bold text-base sm:text-xl text-red-400 leading-tight mt-0.5 truncate">
                                {{ number_format((float)$totalOut, 0) }}
                            </p>
                        </div>
                    </div>

                    <div class="flex-1 px-3 py-3 sm:px-5 sm:py-4 flex items-center gap-2 sm:gap-3">
                        <div class="hidden sm:flex w-9 h-9 rounded-full {{ $isPositive ? 'bg-primary/10' : 'bg-red-500/10' }} items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 {{ $isPositive ? 'text-blue-light' : 'text-red-400' }}" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.499 8.248h15m-15 7.501h15"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] sm:text-xs font-semibold uppercase tracking-wider dark:text-slate-500 text-gray-500 font-body">Net Balance</p>
                            <p class="font-mono font-bold text-base sm:text-xl leading-tight mt-0.5 truncate
                                      {{ $isPositive ? 'dark:text-blue-light text-primary' : 'text-red-400' }}">
                                @if(!$isPositive)−@endif{{ number_format(abs((float)$balance), 0) }}
                            </p>
                        </div>
                    </div>

                </div>
            </div>

            {{-- ===== SEARCH + ADD BUTTONS ===== --}}
            <div class="flex items-center gap-2 sm:gap-3">
                <div class="flex-1 relative">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none">
                        <svg class="w-4 h-4 dark:text-slate-600 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                        </svg>
                    </div>
                    <input type="text"
                           wire:model.live.debounce.300ms="search"
                           placeholder="Search by description, category…"
                           class="w-full pl-10 pr-4 py-2.5 text-sm font-body
                                  dark:bg-dark bg-white
                                  dark:border dark:border-slate-700 border border-gray-200
                                  dark:text-white text-gray-900 rounded-xl
                                  placeholder:dark:text-slate-600 placeholder:text-gray-400
                                  focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary/50
                                  transition-all duration-150">
                </div>
                @if($userRole !== 'viewer')
                    <button wire:click="openAddEntry('in')"
                            class="flex items-center gap-1.5 px-4 py-2.5 flex-shrink-0
                                   bg-emerald-500/10 text-emerald-400
                                   hover:bg-emerald-500 hover:text-white
                                   border border-emerald-500/25 hover:border-emerald-500
                                   text-sm font-semibold font-body rounded-xl transition-all duration-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                        </svg>
                        <span class="hidden sm:block">Cash In</span>
                    </button>
                    <button wire:click="openAddEntry('out')"
                            class="flex items-center gap-1.5 px-4 py-2.5 flex-shrink-0
                                   bg-red-500/10 text-red-400
                                   hover:bg-red-500 hover:text-white
                                   border border-red-500/25 hover:border-red-500
                                   text-sm font-semibold font-body rounded-xl transition-all duration-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14"/>
                        </svg>
                        <span class="hidden sm:block">Cash Out</span>
                    </button>
                @endif
            </div>

            {{-- ===== ENTRIES TABLE / EMPTY STATE ===== --}}
            @if($entries->isEmpty() && $search === '' && $filterType === 'all')

                <div class="dark:bg-dark bg-white dark:border dark:border-slate-700 border border-dashed border-gray-200
                            rounded-2xl px-8 py-20 text-center">
                    <div class="w-12 h-12 rounded-2xl bg-primary/10 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                        </svg>
                    </div>
                    <h2 class="font-heading font-bold text-base dark:text-white text-gray-900 mb-1.5">No entries yet</h2>
                    <p class="text-sm dark:text-slate-500 text-gray-500 font-body mb-6 max-w-xs mx-auto">
                        Record your first transaction to start tracking this book's balance.
                    </p>
                    @if($userRole !== 'viewer')
                        <div class="flex items-center justify-center gap-3">
                            <button wire:click="openAddEntry('in')"
                                    class="flex items-center gap-2 px-4 py-2.5
                                           bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500 hover:text-white
                                           border border-emerald-500/25 text-sm font-semibold font-body rounded-xl transition-all duration-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                Cash In
                            </button>
                            <button wire:click="openAddEntry('out')"
                                    class="flex items-center gap-2 px-4 py-2.5
                                           bg-red-500/10 text-red-400 hover:bg-red-500 hover:text-white
                                           border border-red-500/25 text-sm font-semibold font-body rounded-xl transition-all duration-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14"/></svg>
                                Cash Out
                            </button>
                        </div>
                    @endif
                </div>

            @else

                <div class="dark:bg-dark bg-white rounded-2xl
                            dark:border dark:border-slate-700 border border-gray-200 overflow-hidden">

                    {{-- Column headers --}}
                    <div class="hidden md:grid md:grid-cols-[120px_1fr_120px_110px_140px_130px_56px]
                                px-5 py-3
                                dark:border-b dark:border-slate-700 border-b border-gray-100
                                dark:bg-navy/50 bg-gray-50/70">
                        <span class="text-[10px] font-bold uppercase tracking-widest dark:text-slate-600 text-gray-400 font-body">Date</span>
                        <span class="text-[10px] font-bold uppercase tracking-widest dark:text-slate-600 text-gray-400 font-body">Description</span>
                        <span class="text-[10px] font-bold uppercase tracking-widest dark:text-slate-600 text-gray-400 font-body">Category</span>
                        <span class="text-[10px] font-bold uppercase tracking-widest dark:text-slate-600 text-gray-400 font-body">Mode</span>
                        <span class="text-[10px] font-bold uppercase tracking-widest dark:text-slate-600 text-gray-400 font-body text-right pr-4">Amount</span>
                        <span class="text-[10px] font-bold uppercase tracking-widest dark:text-slate-600 text-gray-400 font-body text-right pr-2">Balance</span>
                        <span></span>
                    </div>

                    <div class="divide-y dark:divide-slate-700/40 divide-gray-100">
                        @forelse($entries as $entry)
                            @php
                                $rb    = $entry->running_balance ?? '0.00';
                                $rbPos = bccomp((string)$rb, '0', 2) >= 0;
                            @endphp
                            <div wire:key="{{ $entry->id }}"
                                 x-data="{ hovered: false, confirming: false }"
                                 @mouseenter="hovered = true"
                                 @mouseleave="hovered = false; confirming = false"
                                 class="transition-colors duration-100 dark:hover:bg-slate-800/30 hover:bg-gray-50/80">

                                {{-- Desktop --}}
                                <div class="hidden md:grid md:grid-cols-[120px_1fr_120px_110px_140px_130px_56px] items-center px-5 py-3.5">

                                    <span class="text-sm dark:text-slate-400 text-gray-600 font-body">
                                        {{ $entry->date->format('d M Y') }}
                                    </span>

                                    <div class="min-w-0 pr-3">
                                        <p class="text-sm font-medium dark:text-white text-gray-900 font-body truncate">
                                            {{ $entry->description }}
                                        </p>
                                        @if($entry->reference)
                                            <p class="text-[11px] font-mono dark:text-slate-600 text-gray-400 mt-0.5 truncate">
                                                {{ $entry->reference }}
                                            </p>
                                        @endif
                                    </div>

                                    <span class="text-xs dark:text-slate-500 text-gray-500 font-body truncate pr-2">
                                        {{ $entry->category ?: '—' }}
                                    </span>

                                    <span class="text-xs dark:text-slate-500 text-gray-500 font-body truncate pr-2">
                                        {{ $entry->payment_mode ?: '—' }}
                                    </span>

                                    <div class="text-right pr-4">
                                        @if($entry->type === 'in')
                                            <span class="font-mono text-sm font-semibold text-emerald-400">
                                                +{{ number_format((float)$entry->amount, 2) }}
                                            </span>
                                        @else
                                            <span class="font-mono text-sm font-semibold text-red-400">
                                                −{{ number_format((float)$entry->amount, 2) }}
                                            </span>
                                        @endif
                                    </div>

                                    <div class="text-right pr-2">
                                        <span class="font-mono text-sm font-semibold
                                                     {{ $rbPos ? 'dark:text-slate-300 text-gray-700' : 'text-red-400' }}">
                                            @if(!$rbPos)−@endif{{ number_format(abs((float)$rb), 2) }}
                                        </span>
                                    </div>

                                    <div class="flex items-center justify-end gap-0.5"
                                         :class="(hovered || confirming) ? 'opacity-100' : 'opacity-0'"
                                         style="transition: opacity 0.15s;">
                                        <template x-if="!confirming">
                                            <div class="flex gap-0.5">
                                                @if($userRole !== 'viewer')
                                                    <button @click.stop="$wire.openEditEntry('{{ $entry->id }}')"
                                                            class="p-1.5 rounded-lg dark:text-slate-500 text-gray-400
                                                                   dark:hover:bg-slate-700 hover:bg-gray-200
                                                                   dark:hover:text-white hover:text-gray-700 transition-colors duration-150">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/>
                                                        </svg>
                                                    </button>
                                                    <button @click.stop="confirming = true"
                                                            class="p-1.5 rounded-lg dark:text-slate-500 text-gray-400
                                                                   dark:hover:bg-red-500/10 hover:bg-red-50
                                                                   dark:hover:text-red-400 hover:text-red-500 transition-colors duration-150">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                                        </svg>
                                                    </button>
                                                @endif
                                            </div>
                                        </template>
                                        <template x-if="confirming">
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-[10px] dark:text-slate-400 text-gray-500 font-body whitespace-nowrap">Delete?</span>
                                                <button @click.stop="$wire.deleteEntry('{{ $entry->id }}'); confirming = false"
                                                        class="px-2 py-1 text-[11px] font-semibold font-body bg-red-500/10 text-red-400 hover:bg-red-500/20 rounded-lg transition-colors">Yes</button>
                                                <button @click.stop="confirming = false"
                                                        class="px-2 py-1 text-[11px] font-semibold font-body dark:bg-slate-700 bg-gray-200 dark:text-slate-300 text-gray-600 dark:hover:bg-slate-600 rounded-lg transition-colors">No</button>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                {{-- Mobile --}}
                                <div class="md:hidden flex items-center gap-3 px-4 py-3.5">
                                    <div class="w-2 h-2 rounded-full flex-shrink-0 {{ $entry->type === 'in' ? 'bg-emerald-400' : 'bg-red-400' }}"></div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium dark:text-white text-gray-900 font-body truncate">{{ $entry->description }}</p>
                                        <div class="flex items-center gap-2 mt-0.5 flex-wrap">
                                            <span class="text-xs dark:text-slate-500 text-gray-400 font-body">{{ $entry->date->format('d M Y') }}</span>
                                            @if($entry->category)<span class="text-xs dark:text-slate-600 text-gray-400 font-body">· {{ $entry->category }}</span>@endif
                                        </div>
                                    </div>
                                    <div class="text-right flex-shrink-0">
                                        @if($entry->type === 'in')
                                            <p class="font-mono text-sm font-semibold text-emerald-400">+{{ number_format((float)$entry->amount, 2) }}</p>
                                        @else
                                            <p class="font-mono text-sm font-semibold text-red-400">−{{ number_format((float)$entry->amount, 2) }}</p>
                                        @endif
                                        <p class="font-mono text-xs {{ $rbPos ? 'dark:text-slate-500 text-gray-400' : 'text-red-400/70' }} mt-0.5">
                                            @if(!$rbPos)−@endif{{ number_format(abs((float)$rb), 2) }}
                                        </p>
                                    </div>
                                </div>

                            </div>
                        @empty
                            <div class="px-6 py-14 text-center">
                                <p class="text-sm dark:text-slate-500 text-gray-500 font-body">
                                    No entries match your current filters.
                                </p>
                            </div>
                        @endforelse
                    </div>

                    @if($entries->isNotEmpty())
                        <div class="px-5 py-3
                                    dark:border-t dark:border-slate-700/40 border-t border-gray-100
                                    dark:bg-navy/30 bg-gray-50/50
                                    flex items-center justify-between">
                            <span class="text-xs dark:text-slate-600 text-gray-400 font-body">
                                {{ $entries->count() }} {{ Str::plural('entry', $entries->count()) }}
                                @if($search !== '' || $filterType !== 'all') shown @else total @endif
                            </span>
                            <span class="text-xs font-mono dark:text-slate-600 text-gray-400">{{ $business->currency }}</span>
                        </div>
                    @endif

                </div>

            @endif

        </div>
    </div>

    {{-- ===== ENTRY SLIDE-OVER ===== --}}
    <div x-data="{ show: $wire.entangle('showEntryPanel').live }">

    {{-- Backdrop --}}
    <div x-show="show"
         @click="show = false"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-navy/70 backdrop-blur-sm z-40"></div>

    {{-- Panel --}}
    <div x-show="show"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         class="fixed inset-y-0 right-0 w-full max-w-lg z-50 overflow-y-auto
                dark:bg-dark bg-white
                dark:border-l dark:border-slate-700 border-l border-gray-200
                shadow-2xl dark:shadow-black/60 shadow-black/20">

        {{-- Panel header --}}
        <div class="px-6 py-5
                    dark:border-b dark:border-slate-700 border-b border-gray-100
                    sticky top-0 dark:bg-dark bg-white z-10">
            <div class="flex items-center justify-between mb-4">
                <p class="text-xs dark:text-slate-500 text-gray-400 font-body">{{ $book->name }} · {{ $business->currency }}</p>
                <button @click="show = false"
                        class="p-1.5 rounded-xl dark:text-slate-500 text-gray-400
                               dark:hover:bg-slate-800 hover:bg-gray-100 dark:hover:text-white hover:text-gray-700
                               transition-all duration-150">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Type toggle (always visible) --}}
            <div class="grid grid-cols-2 gap-1.5 p-1.5 dark:bg-slate-900 bg-gray-100 rounded-xl">
                <button type="button" wire:click="$set('entryType', 'in')"
                        class="flex items-center justify-center gap-2 py-2.5 px-4 rounded-lg text-sm font-semibold font-body transition-all duration-200
                               {{ $entryType === 'in'
                                   ? 'bg-emerald-500 text-white'
                                   : 'dark:text-slate-400 text-gray-600 dark:hover:text-white hover:text-gray-900' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                    Cash In
                </button>
                <button type="button" wire:click="$set('entryType', 'out')"
                        class="flex items-center justify-center gap-2 py-2.5 px-4 rounded-lg text-sm font-semibold font-body transition-all duration-200
                               {{ $entryType === 'out'
                                   ? 'bg-red-500 text-white'
                                   : 'dark:text-slate-400 text-gray-600 dark:hover:text-white hover:text-gray-900' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14"/>
                    </svg>
                    Cash Out
                </button>
            </div>
        </div>

        {{-- Form fields --}}
        <div class="px-6 py-5 space-y-4">

            {{-- Date --}}
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider dark:text-slate-500 text-gray-500 font-body mb-1.5">
                    Date <span class="text-red-400">*</span>
                </label>
                <input type="date"
                       wire:model="entryDate"
                       class="w-full px-4 py-2.5 text-sm font-body
                              dark:bg-slate-800 bg-white
                              dark:border dark:border-slate-700 border border-gray-300
                              dark:text-white text-gray-900 rounded-xl
                              focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary/50
                              transition-all duration-150 dark:[color-scheme:dark]">
                @error('entryDate')<p class="text-xs text-red-400 mt-1 font-body">{{ $message }}</p>@enderror
            </div>

            {{-- Amount --}}
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider dark:text-slate-500 text-gray-500 font-body mb-1.5">
                    Amount <span class="text-red-400">*</span>
                </label>
                <div class="flex">
                    <div class="flex items-center px-3.5
                                dark:bg-slate-800 bg-gray-100
                                dark:border dark:border-slate-700 border border-gray-300 border-r-0
                                rounded-l-xl">
                        <span class="text-xs font-mono dark:text-slate-400 text-gray-500 font-semibold">{{ $business->currency }}</span>
                    </div>
                    <input type="number"
                           wire:model="entryAmount"
                           placeholder="0.00"
                           step="0.01" min="0.01"
                           class="flex-1 px-4 py-2.5 font-mono text-lg font-semibold
                                  dark:bg-slate-800 bg-white
                                  dark:border dark:border-slate-700 border border-gray-300
                                  dark:text-white text-gray-900 rounded-r-xl
                                  placeholder:dark:text-slate-700 placeholder:text-gray-300 placeholder:font-normal
                                  focus:outline-none focus:ring-2
                                  {{ $entryType === 'in' ? 'focus:ring-emerald-500/30 focus:border-emerald-500/50' : 'focus:ring-red-500/30 focus:border-red-500/50' }}
                                  transition-all duration-150">
                </div>
                @error('entryAmount')<p class="text-xs text-red-400 mt-1 font-body">{{ $message }}</p>@enderror
            </div>

            {{-- Description --}}
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider dark:text-slate-500 text-gray-500 font-body mb-1.5">
                    Description <span class="text-red-400">*</span>
                </label>
                <input type="text"
                       wire:model="entryDescription"
                       placeholder="e.g. Client payment, Office rent…"
                       maxlength="255"
                       class="w-full px-4 py-2.5 text-sm font-body
                              dark:bg-slate-800 bg-white
                              dark:border dark:border-slate-700 border border-gray-300
                              dark:text-white text-gray-900 rounded-xl
                              placeholder:dark:text-slate-600 placeholder:text-gray-400
                              focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary/50
                              transition-all duration-150">
                @error('entryDescription')<p class="text-xs text-red-400 mt-1 font-body">{{ $message }}</p>@enderror
            </div>

            {{-- Category --}}
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider dark:text-slate-500 text-gray-500 font-body mb-1.5">Category</label>
                @if($showAddCategory)
                    <div class="dark:bg-slate-800 bg-gray-50 rounded-xl p-3 space-y-2 dark:border dark:border-slate-700 border border-gray-200">
                        <p class="text-xs font-semibold dark:text-slate-300 text-gray-700 font-body">Add New Category</p>
                        <input type="text"
                               wire:model="newCategoryName"
                               wire:keydown.enter="addCategory"
                               placeholder="e.g. Expenses, Staff Salary, Utilities"
                               autofocus
                               class="w-full px-3 py-2 text-sm font-body
                                      dark:bg-slate-900 bg-white
                                      dark:border dark:border-slate-600 border border-gray-300
                                      dark:text-white text-gray-900 rounded-lg
                                      placeholder:dark:text-slate-600 placeholder:text-gray-400
                                      focus:outline-none focus:ring-2 focus:ring-primary/40">
                        <div class="flex gap-2">
                            <button wire:click="$set('showAddCategory', false)"
                                    class="flex-1 py-1.5 text-xs font-semibold font-body dark:bg-slate-700 bg-gray-200 dark:text-slate-300 text-gray-600 rounded-lg hover:dark:bg-slate-600 transition-colors">
                                Cancel
                            </button>
                            <button wire:click="addCategory"
                                    class="flex-1 py-1.5 text-xs font-semibold font-body bg-primary text-white rounded-lg hover:bg-accent transition-colors">
                                Save
                            </button>
                        </div>
                    </div>
                @else
                    <div x-data="{ open: false, search: '' }" class="relative">
                        <button type="button"
                                @click="open = !open"
                                @click.outside="open = false; search = ''"
                                class="w-full flex items-center justify-between px-4 py-2.5 text-sm font-body
                                       dark:bg-slate-800 bg-white
                                       dark:border dark:border-slate-700 border border-gray-300
                                       dark:text-white text-gray-900 rounded-xl
                                       focus:outline-none transition-all duration-150
                                       {{ $entryCategory ? '' : 'dark:text-slate-500 text-gray-400' }}">
                            <span>{{ $entryCategory ?: 'Select category' }}</span>
                            <svg class="w-4 h-4 dark:text-slate-500 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
                            </svg>
                        </button>
                        <div x-show="open"
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="opacity-0 -translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             class="absolute bottom-full left-0 right-0 mb-1 z-20
                                    dark:bg-slate-800 bg-white
                                    dark:border dark:border-slate-700 border border-gray-200
                                    rounded-xl shadow-2xl shadow-black/30 overflow-hidden"
                             style="display:none;">
                            <div class="p-2 dark:border-b dark:border-slate-700 border-b border-gray-100">
                                <input x-model="search" type="text" placeholder="Search categories…"
                                       class="w-full px-3 py-1.5 text-sm font-body
                                              dark:bg-slate-800 bg-gray-50
                                              dark:border dark:border-slate-600 border border-gray-200
                                              dark:text-white text-gray-900 rounded-lg
                                              placeholder:dark:text-slate-600 placeholder:text-gray-400
                                              focus:outline-none focus:ring-1 focus:ring-primary/40">
                            </div>
                            <div class="max-h-44 overflow-y-auto py-1">
                                @if($categories->isEmpty())
                                    <p class="text-xs dark:text-slate-600 text-gray-400 text-center py-4 font-body">No categories yet</p>
                                @else
                                    @foreach($categories as $cat)
                                        <button type="button"
                                                x-show="search === '' || '{{ strtolower(addslashes($cat->name)) }}'.includes(search.toLowerCase())"
                                                @click.stop="$wire.set('entryCategory', '{{ addslashes($cat->name) }}'); open = false; search = ''"
                                                class="w-full flex items-center gap-3 px-4 py-2.5 text-sm font-body
                                                       dark:hover:bg-slate-700/50 hover:bg-gray-50 transition-colors text-left
                                                       {{ $entryCategory === $cat->name ? 'dark:text-white text-gray-900 font-semibold' : 'dark:text-slate-300 text-gray-700' }}">
                                            <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center flex-shrink-0
                                                        {{ $entryCategory === $cat->name ? 'border-primary' : 'dark:border-slate-600 border-gray-300' }}">
                                                @if($entryCategory === $cat->name)
                                                    <div class="w-2 h-2 rounded-full bg-primary"></div>
                                                @endif
                                            </div>
                                            {{ $cat->name }}
                                        </button>
                                    @endforeach
                                @endif
                            </div>
                            <div class="dark:border-t dark:border-slate-700 border-t border-gray-100">
                                <button type="button"
                                        @click.stop="$wire.set('showAddCategory', true); open = false"
                                        class="w-full flex items-center gap-2 px-4 py-3 text-sm font-semibold font-body text-primary
                                               dark:hover:bg-slate-700/50 hover:bg-gray-50 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                                    </svg>
                                    Add New Category
                                </button>
                            </div>
                        </div>
                    </div>
                    @if($entryCategory)
                        <button wire:click="$set('entryCategory', '')" class="mt-1 text-[11px] dark:text-slate-600 text-gray-400 hover:text-red-400 font-body transition-colors">
                            Clear selection
                        </button>
                    @endif
                @endif
            </div>

            {{-- Payment Mode --}}
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider dark:text-slate-500 text-gray-500 font-body mb-1.5">Payment Mode</label>
                @if($showAddPaymentMode)
                    <div class="dark:bg-slate-800 bg-gray-50 rounded-xl p-3 space-y-2 dark:border dark:border-slate-700 border border-gray-200">
                        <p class="text-xs font-semibold dark:text-slate-300 text-gray-700 font-body">Add New Payment Mode</p>
                        <input type="text"
                               wire:model="newPaymentModeName"
                               wire:keydown.enter="addPaymentMode"
                               placeholder="e.g. Cash, Online, Bank Transfer"
                               autofocus
                               class="w-full px-3 py-2 text-sm font-body
                                      dark:bg-slate-900 bg-white
                                      dark:border dark:border-slate-600 border border-gray-300
                                      dark:text-white text-gray-900 rounded-lg
                                      placeholder:dark:text-slate-600 placeholder:text-gray-400
                                      focus:outline-none focus:ring-2 focus:ring-primary/40">
                        <div class="flex gap-2">
                            <button wire:click="$set('showAddPaymentMode', false)"
                                    class="flex-1 py-1.5 text-xs font-semibold font-body dark:bg-slate-700 bg-gray-200 dark:text-slate-300 text-gray-600 rounded-lg hover:dark:bg-slate-600 transition-colors">
                                Cancel
                            </button>
                            <button wire:click="addPaymentMode"
                                    class="flex-1 py-1.5 text-xs font-semibold font-body bg-primary text-white rounded-lg hover:bg-accent transition-colors">
                                Save
                            </button>
                        </div>
                    </div>
                @else
                    <div x-data="{ open: false, search: '' }" class="relative">
                        <button type="button"
                                @click="open = !open"
                                @click.outside="open = false; search = ''"
                                class="w-full flex items-center justify-between px-4 py-2.5 text-sm font-body
                                       dark:bg-slate-800 bg-white
                                       dark:border dark:border-slate-700 border border-gray-300
                                       dark:text-white text-gray-900 rounded-xl
                                       focus:outline-none transition-all duration-150
                                       {{ $entryPaymentMode ? '' : 'dark:text-slate-500 text-gray-400' }}">
                            <span>{{ $entryPaymentMode ?: 'Select payment mode' }}</span>
                            <svg class="w-4 h-4 dark:text-slate-500 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
                            </svg>
                        </button>
                        <div x-show="open"
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="opacity-0 -translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             class="absolute bottom-full left-0 right-0 mb-1 z-20
                                    dark:bg-slate-800 bg-white
                                    dark:border dark:border-slate-700 border border-gray-200
                                    rounded-xl shadow-2xl shadow-black/30 overflow-hidden"
                             style="display:none;">
                            <div class="p-2 dark:border-b dark:border-slate-700 border-b border-gray-100">
                                <input x-model="search" type="text" placeholder="Search modes…"
                                       class="w-full px-3 py-1.5 text-sm font-body
                                              dark:bg-slate-800 bg-gray-50
                                              dark:border dark:border-slate-600 border border-gray-200
                                              dark:text-white text-gray-900 rounded-lg
                                              placeholder:dark:text-slate-600 placeholder:text-gray-400
                                              focus:outline-none focus:ring-1 focus:ring-primary/40">
                            </div>
                            <div class="max-h-44 overflow-y-auto py-1">
                                @if($paymentModes->isEmpty())
                                    <p class="text-xs dark:text-slate-600 text-gray-400 text-center py-4 font-body">No payment modes yet</p>
                                @else
                                    @foreach($paymentModes as $mode)
                                        <button type="button"
                                                x-show="search === '' || '{{ strtolower(addslashes($mode->name)) }}'.includes(search.toLowerCase())"
                                                @click.stop="$wire.set('entryPaymentMode', '{{ addslashes($mode->name) }}'); open = false; search = ''"
                                                class="w-full flex items-center gap-3 px-4 py-2.5 text-sm font-body
                                                       dark:hover:bg-slate-700/50 hover:bg-gray-50 transition-colors text-left
                                                       {{ $entryPaymentMode === $mode->name ? 'dark:text-white text-gray-900 font-semibold' : 'dark:text-slate-300 text-gray-700' }}">
                                            <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center flex-shrink-0
                                                        {{ $entryPaymentMode === $mode->name ? 'border-primary' : 'dark:border-slate-600 border-gray-300' }}">
                                                @if($entryPaymentMode === $mode->name)
                                                    <div class="w-2 h-2 rounded-full bg-primary"></div>
                                                @endif
                                            </div>
                                            {{ $mode->name }}
                                        </button>
                                    @endforeach
                                @endif
                            </div>
                            <div class="dark:border-t dark:border-slate-700 border-t border-gray-100">
                                <button type="button"
                                        @click.stop="$wire.set('showAddPaymentMode', true); open = false"
                                        class="w-full flex items-center gap-2 px-4 py-3 text-sm font-semibold font-body text-primary
                                               dark:hover:bg-slate-700/50 hover:bg-gray-50 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                                    </svg>
                                    Add New Payment Mode
                                </button>
                            </div>
                        </div>
                    </div>
                    @if($entryPaymentMode)
                        <button wire:click="$set('entryPaymentMode', '')" class="mt-1 text-[11px] dark:text-slate-600 text-gray-400 hover:text-red-400 font-body transition-colors">
                            Clear selection
                        </button>
                    @endif
                @endif
            </div>

            {{-- Reference (optional) --}}
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider dark:text-slate-500 text-gray-500 font-body mb-1.5">
                    Reference
                    <span class="normal-case tracking-normal font-normal dark:text-slate-600 text-gray-400 ml-1">· optional</span>
                </label>
                <input type="text"
                       wire:model="entryReference"
                       placeholder="Invoice, receipt, or PO number"
                       maxlength="100"
                       class="w-full px-4 py-2.5 text-sm font-mono
                              dark:bg-slate-800 bg-white
                              dark:border dark:border-slate-700 border border-gray-300
                              dark:text-white text-gray-900 rounded-xl
                              placeholder:dark:text-slate-600 placeholder:text-gray-400 placeholder:font-body
                              focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary/50
                              transition-all duration-150">
                @error('entryReference')<p class="text-xs text-red-400 mt-1 font-body">{{ $message }}</p>@enderror
            </div>

        </div>

        {{-- Panel footer --}}
        <div class="sticky bottom-0 px-6 py-4 flex gap-2
                    dark:bg-dark bg-white
                    dark:border-t dark:border-slate-700 border-t border-gray-100">

            @if(!$editingEntryId)
                {{-- Save & Add New --}}
                <button type="button"
                        wire:click="saveAndAddNew"
                        wire:loading.attr="disabled"
                        class="flex-1 py-2.5 text-sm font-semibold font-body
                               dark:bg-slate-800 bg-gray-100 dark:text-slate-300 text-gray-700
                               dark:hover:bg-slate-700 hover:bg-gray-200
                               rounded-xl transition-all duration-200 disabled:opacity-50">
                    <span wire:loading.remove wire:target="saveAndAddNew">Save &amp; Add New</span>
                    <span wire:loading wire:target="saveAndAddNew">Saving…</span>
                </button>
            @else
                <button type="button"
                        @click="show = false"
                        class="flex-1 py-2.5 text-sm font-semibold font-body
                               dark:bg-slate-800 bg-gray-100 dark:text-slate-300 text-gray-700
                               dark:hover:bg-slate-700 hover:bg-gray-200
                               rounded-xl transition-all duration-200">
                    Cancel
                </button>
            @endif

            {{-- Save --}}
            <button type="button"
                    wire:click="saveEntry"
                    wire:loading.attr="disabled"
                    class="flex-1 py-2.5 text-sm font-semibold font-body
                           text-white rounded-xl transition-all duration-200 shadow-lg
                           {{ $entryType === 'in'
                               ? 'bg-emerald-500 hover:bg-emerald-400 shadow-emerald-500/20'
                               : 'bg-red-500 hover:bg-red-400 shadow-red-500/20' }}
                           disabled:opacity-50 disabled:cursor-not-allowed">
                <span wire:loading.remove wire:target="saveEntry">
                    {{ $editingEntryId ? 'Save Changes' : 'Save' }}
                </span>
                <span wire:loading wire:target="saveEntry" class="flex items-center justify-center gap-2">
                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    Saving…
                </span>
            </button>

        </div>

    </div>

    </div>{{-- end entangle wrapper --}}

</div>
