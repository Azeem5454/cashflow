{{--
    Dashboard "Continue in" card — scoped to ONE book so the balance and the
    Cash In / Cash Out buttons always refer to the same, named ledger.

    Vars: $currentBook (array|null, see Dashboard::bookChoices), $bookChoices,
          $createBookBusiness (Business|null), $totals, $unlockedCount
--}}
@php
    $cb          = $currentBook;
    $pickerLimit = \App\Livewire\Dashboard::PICKER_RECENT;
    $showSearch  = $bookChoices->count() > 6;
@endphp

<section data-testid="continue-card"
         aria-labelledby="continue-title"
         class="relative rounded-2xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-dark shadow-sm">

    {{-- Texture: faint dot grid, clipped to the card --}}
    <div class="absolute inset-0 rounded-2xl overflow-hidden pointer-events-none" aria-hidden="true">
        <div class="absolute inset-0 opacity-60 dark:opacity-40
                    bg-[radial-gradient(circle,_rgba(59,130,246,0.14)_1px,_transparent_1px)] bg-[length:18px_18px]
                    [mask-image:linear-gradient(to_left,black,transparent_70%)]"></div>
        @if($cb)
            <div class="absolute inset-y-0 left-0 w-1
                        {{ $cb['net'] < 0 ? 'bg-red-500' : ($cb['net'] > 0 ? 'bg-emerald-500' : 'bg-primary') }}"></div>
        @endif
    </div>

    <div class="relative px-5 sm:px-7 py-5 sm:py-6">

        {{-- Eyebrow + Change picker --}}
        <div class="flex items-center gap-3">
            <p class="text-[11px] font-body font-medium uppercase tracking-widest text-gray-500 dark:text-slate-400">
                Continue in
            </p>

            @if($bookChoices->count() > 1)
                <div class="relative" data-current="{{ $cb['id'] ?? '' }}"
                     x-data="{
                         open: false,
                         q: '',
                         active: null,
                         current() { return this.$root.dataset.current || null; },
                         limit: {{ $pickerLimit }},
                         visible(el) {
                             const q = this.q.trim().toLowerCase();
                             return q ? el.dataset.label.includes(q) : Number(el.dataset.index) < this.limit;
                         },
                         options() {
                             return [...this.$refs.list.querySelectorAll('[data-option]')].filter(el => this.visible(el));
                         },
                         get noMatches() { return this.open && this.options().length === 0; },
                         openMenu() {
                             this.open = true;
                             this.q = '';
                             this.active = this.current();
                             this.$nextTick(() => {
                                 (this.$refs.search ?? this.$refs.list).focus();
                                 this.scrollActive();
                             });
                         },
                         close(focusTrigger) {
                             if (! this.open) return;
                             this.open = false;
                             if (focusTrigger) this.$nextTick(() => this.$refs.trigger.focus());
                         },
                         move(d) {
                             const opts = this.options();
                             if (! opts.length) return;
                             const i = opts.findIndex(el => el.dataset.id === this.active);
                             const next = i === -1 ? (d > 0 ? 0 : opts.length - 1) : (i + d + opts.length) % opts.length;
                             this.active = opts[next].dataset.id;
                             this.scrollActive();
                         },
                         edge(first) {
                             const opts = this.options();
                             if (! opts.length) return;
                             this.active = (first ? opts[0] : opts[opts.length - 1]).dataset.id;
                             this.scrollActive();
                         },
                         scrollActive() {
                             this.$nextTick(() => document.getElementById('book-opt-' + this.active)?.scrollIntoView({ block: 'nearest' }));
                         },
                         choose() {
                             const el = this.options().find(el => el.dataset.id === this.active);
                             if (el) this.pick(el.dataset.id);
                         },
                         pick(id) {
                             this.close(true);
                             if (id !== this.current()) $wire.selectBook(id);
                         },
                     }"
                     x-effect="if (open) { const o = options(); if (o.length && ! o.some(el => el.dataset.id === active)) active = o[0].dataset.id; }"
                     @click.outside="close(false)"
                     @keydown.escape.prevent.stop="close(true)">

                    <button type="button" x-ref="trigger"
                            @click="open ? close(false) : openMenu()"
                            @keydown.arrow-down.prevent="openMenu()"
                            @keydown.arrow-up.prevent="openMenu()"
                            aria-haspopup="listbox"
                            aria-controls="book-picker-list"
                            :aria-expanded="open.toString()"
                            aria-label="Change book"
                            data-testid="continue-change"
                            class="inline-flex items-center gap-1 px-2 py-1 -my-1 rounded-md text-xs font-semibold font-body
                                   text-primary dark:text-blue-light hover:bg-blue-xlight dark:hover:bg-slate-800
                                   focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary
                                   transition-colors duration-150">
                        Change
                        <svg class="w-3.5 h-3.5 transition-transform duration-200" :class="open && 'rotate-180'"
                             fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
                        </svg>
                    </button>

                    <div x-show="open" x-cloak
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         class="absolute left-0 top-full mt-2 z-30 w-[min(24rem,calc(100vw-3rem))] origin-top-left
                                rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-900
                                shadow-xl shadow-black/10 dark:shadow-black/50 overflow-hidden">

                        @if($showSearch)
                            <div class="p-2 border-b border-gray-100 dark:border-slate-800">
                                <label for="book-picker-search" class="sr-only">Search books or businesses</label>
                                <input id="book-picker-search" type="search" x-ref="search" x-model="q"
                                       placeholder="Search books or businesses"
                                       autocomplete="off"
                                       role="combobox"
                                       aria-autocomplete="list"
                                       aria-controls="book-picker-list"
                                       aria-expanded="true"
                                       :aria-activedescendant="active ? 'book-opt-' + active : null"
                                       @keydown.arrow-down.prevent="move(1)"
                                       @keydown.arrow-up.prevent="move(-1)"
                                       @keydown.enter.prevent="choose()"
                                       @keydown.tab="close(false)"
                                       class="w-full px-3 py-2 rounded-lg text-sm font-body
                                              bg-gray-50 dark:bg-slate-800 border border-gray-200 dark:border-slate-700
                                              text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-slate-500
                                              focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                        @endif

                        <ul id="book-picker-list" x-ref="list" role="listbox" tabindex="-1"
                            aria-label="Recent books"
                            :aria-activedescendant="active ? 'book-opt-' + active : null"
                            @keydown.arrow-down.prevent="move(1)"
                            @keydown.arrow-up.prevent="move(-1)"
                            @keydown.home.prevent="edge(true)"
                            @keydown.end.prevent="edge(false)"
                            @keydown.enter.prevent="choose()"
                            @keydown.space.prevent="choose()"
                            @keydown.tab="close(false)"
                            class="max-h-80 overflow-y-auto py-1 focus:outline-none">
                            @foreach($bookChoices as $i => $choice)
                                @php $isCurrent = $cb && $choice['id'] === $cb['id']; @endphp
                                <li id="book-opt-{{ $choice['id'] }}"
                                    role="option"
                                    data-option
                                    data-id="{{ $choice['id'] }}"
                                    data-index="{{ $i }}"
                                    data-label="{{ mb_strtolower($choice['business']->name . ' ' . $choice['book']->name) }}"
                                    aria-selected="{{ $isCurrent ? 'true' : 'false' }}"
                                    x-show="visible($el)"
                                    @click="pick('{{ $choice['id'] }}')"
                                    @mousemove="active = '{{ $choice['id'] }}'"
                                    :class="active === '{{ $choice['id'] }}' ? 'bg-gray-100 dark:bg-slate-800' : ''"
                                    class="flex items-center gap-3 px-3 py-2.5 cursor-pointer border-l-2
                                           {{ $isCurrent ? 'border-primary' : 'border-transparent' }}">
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-body truncate text-gray-900 dark:text-white">
                                            <span class="text-gray-500 dark:text-slate-400">{{ $choice['business']->name }}</span>
                                            <span class="text-gray-400 dark:text-slate-500" aria-hidden="true">›</span>
                                            <span class="sr-only">,</span>
                                            <span class="font-medium">{{ $choice['book']->name }}</span>
                                        </p>
                                        @unless($choice['canEdit'])
                                            <p class="text-[11px] text-gray-500 dark:text-slate-400 font-body">View only</p>
                                        @endunless
                                    </div>
                                    <x-amount :value="$choice['net']" :symbol="$choice['symbol']" tone="neutral" :decimals="0" class="text-xs flex-shrink-0" />
                                    @if($isCurrent)
                                        <svg class="w-4 h-4 text-primary dark:text-blue-light flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                                        </svg>
                                    @else
                                        <span class="w-4 flex-shrink-0" aria-hidden="true"></span>
                                    @endif
                                </li>
                            @endforeach
                            <li x-show="noMatches" role="presentation" class="px-3 py-4 text-center text-sm text-gray-500 dark:text-slate-400 font-body">
                                No books match “<span x-text="q"></span>”.
                            </li>
                        </ul>

                        @if($bookChoices->count() > $pickerLimit)
                            <p x-show="! q" class="px-3 py-2 border-t border-gray-100 dark:border-slate-800 text-[11px] text-gray-500 dark:text-slate-400 font-body">
                                Showing {{ $pickerLimit }} most recent · type to search all {{ $bookChoices->count() }}
                            </p>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Saving indicator while Livewire swaps the book --}}
            <svg wire:loading wire:target="selectBook" class="w-3.5 h-3.5 animate-spin text-gray-400" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"></circle>
                <path fill="currentColor" class="opacity-75" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z"></path>
            </svg>
        </div>

        @if($cb)
            @php
                $bookUrl  = route('businesses.books.show', [$cb['business'], $cb['book']]);
                $target   = $cb['business']->name . ' › ' . $cb['book']->name;
                $isNeg    = $cb['net'] < 0;
                $noMoney  = $cb['book']->entries_count === 0 && (float) ($cb['book']->opening_balance ?? 0) === 0.0;
            @endphp

            <div wire:key="continue-{{ $cb['id'] }}"
                 x-data="{ shown: false }" x-init="requestAnimationFrame(() => shown = true)"
                 :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-1'"
                 class="mt-2 flex flex-col md:flex-row md:items-end justify-between gap-5 transition-all duration-300 ease-out">

                {{-- Book identity + money --}}
                <div class="min-w-0">
                    <h2 id="continue-title" class="font-heading font-bold text-lg sm:text-xl leading-snug text-gray-900 dark:text-white truncate">
                        <a href="{{ $bookUrl }}" wire:navigate class="hover:text-primary dark:hover:text-blue-light transition-colors">
                            {{ $cb['book']->name }}<span class="text-gray-400 dark:text-slate-500 font-body font-normal"> · </span><span class="font-body font-medium text-gray-600 dark:text-slate-300">{{ $cb['business']->name }}</span>
                        </a>
                    </h2>

                    <div class="mt-3 flex flex-wrap items-end gap-x-3 gap-y-1">
                        <p class="inline-flex items-center gap-2 text-3xl sm:text-4xl font-bold leading-none tracking-tight" data-testid="continue-balance">
                            @if($isNeg)
                                <svg class="w-6 h-6 text-red-600 dark:text-red-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6 9 12.75l4.306-4.306a11.95 11.95 0 0 1 5.814 5.518l2.74 1.22m0 0-5.94 2.281m5.94-2.28-2.28-5.941"/>
                                </svg>
                            @endif
                            <x-amount :value="$cb['net']" :symbol="$cb['symbol']" tone="net" />
                        </p>
                        @if($isNeg)
                            <span class="mb-0.5 inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold font-body
                                         bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-300">
                                below zero
                            </span>
                        @else
                            <span class="mb-0.5 text-xs font-body text-gray-500 dark:text-slate-400">
                                {{ $noMoney ? 'No entries yet' : 'net balance' }}
                            </span>
                        @endif
                    </div>

                    <p class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs font-body text-gray-500 dark:text-slate-400">
                        <span class="inline-flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500" aria-hidden="true"></span>
                            In <x-amount :value="$cb['in']" :symbol="$cb['symbol']" tone="in" />
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-red-500" aria-hidden="true"></span>
                            Out <x-amount :value="$cb['out']" :symbol="$cb['symbol']" tone="out" />
                        </span>
                        @if((float) ($cb['book']->opening_balance ?? 0) != 0)
                            <span>Opening <x-amount :value="$cb['book']->opening_balance" :symbol="$cb['symbol']" tone="neutral" /></span>
                        @endif
                    </p>
                </div>

                {{-- Actions --}}
                <div class="flex flex-col md:items-end gap-1.5 flex-shrink-0">
                    @if($cb['canEdit'])
                        <div class="flex items-center gap-2" role="group" aria-label="Add entry to {{ $target }}">
                            <a href="{{ $bookUrl }}?addEntry=in" wire:navigate
                               data-testid="continue-cash-in"
                               aria-label="Add cash in to {{ $target }}"
                               class="flex-1 md:flex-none inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-lg border text-sm font-semibold font-body
                                      bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100 hover:border-emerald-300
                                      dark:bg-emerald-950 dark:text-emerald-300 dark:border-emerald-800 dark:hover:bg-emerald-900
                                      hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 dark:focus-visible:ring-offset-navy
                                      transition-all duration-150">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                                </svg>
                                Cash In
                            </a>
                            <a href="{{ $bookUrl }}?addEntry=out" wire:navigate
                               data-testid="continue-cash-out"
                               aria-label="Add cash out to {{ $target }}"
                               class="flex-1 md:flex-none inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-lg border text-sm font-semibold font-body
                                      bg-red-50 text-red-700 border-red-200 hover:bg-red-100 hover:border-red-300
                                      dark:bg-red-950 dark:text-red-300 dark:border-red-800 dark:hover:bg-red-900
                                      hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 dark:focus-visible:ring-offset-navy
                                      transition-all duration-150">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14"/>
                                </svg>
                                Cash Out
                            </a>
                        </div>
                        <p class="text-[11px] font-body text-gray-500 dark:text-slate-400 truncate max-w-full md:max-w-[20rem]" data-testid="continue-target">
                            Adds to <span class="font-medium text-gray-700 dark:text-slate-300">{{ $target }}</span>
                        </p>
                    @else
                        <p class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-medium font-body
                                  bg-gray-100 text-gray-600 dark:bg-slate-800 dark:text-slate-300" data-testid="continue-view-only">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                            </svg>
                            View only — you can't add entries to this book
                        </p>
                    @endif
                </div>
            </div>
        @else
            {{-- Businesses exist but no books yet --}}
            <div class="mt-2 flex flex-col sm:flex-row sm:items-center justify-between gap-4" data-testid="continue-empty">
                <div>
                    <h2 id="continue-title" class="font-heading font-bold text-lg sm:text-xl text-gray-900 dark:text-white">No books yet</h2>
                    <p class="mt-1 text-sm font-body text-gray-500 dark:text-slate-400 max-w-md">
                        @if($createBookBusiness)
                            A book holds your entries for a month, quarter or project. Create one to start recording cash.
                        @else
                            Books shared with you will appear here once the owner creates them.
                        @endif
                    </p>
                </div>
                @if($createBookBusiness)
                    <a href="{{ route('businesses.show', $createBookBusiness) }}?createBook=1" wire:navigate
                       class="self-start sm:self-auto inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-semibold font-body
                              bg-primary text-white shadow-lg shadow-primary/25 hover:brightness-110 hover:shadow-xl
                              focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 dark:focus-visible:ring-offset-navy
                              transition-all duration-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                        </svg>
                        Create your first book
                    </a>
                @endif
            </div>
        @endif
    </div>

    {{-- Secondary: totals across businesses, one figure per currency (never mixed) --}}
    @if($unlockedCount > 1 && $totals->isNotEmpty())
        <div class="relative px-5 sm:px-7 py-2.5 border-t border-gray-100 dark:border-slate-800
                    flex flex-wrap items-center gap-x-3 gap-y-1 text-xs font-body text-gray-500 dark:text-slate-400"
             data-testid="across-all">
            <span>Across all businesses{{ $totals->count() > 1 ? ', by currency' : '' }}:</span>
            @foreach($totals as $t)
                <span class="inline-flex items-center gap-1">
                    @if($totals->count() > 1)
                        <span class="text-[10px] font-semibold tracking-wide text-gray-400 dark:text-slate-500">{{ $t['currency'] }}</span>
                    @endif
                    <x-amount :value="$t['balance']" :symbol="$t['symbol']" tone="neutral" />
                </span>
                @unless($loop->last)<span class="text-gray-300 dark:text-slate-600" aria-hidden="true">·</span>@endunless
            @endforeach
        </div>
    @endif
</section>
