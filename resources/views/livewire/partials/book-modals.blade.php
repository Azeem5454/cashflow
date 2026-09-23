{{--
    Shared Create / Edit / Duplicate Book modals + the bookPeriodPicker Alpine
    factory. Included (not a class component) so the modals keep the parent
    Livewire component's variables and error bag. Used by livewire/business/show
    and livewire/book/show. Each modal only renders when its show-flag exists
    and is true, so book/show (no Create) simply never shows the Create modal.
--}}

    {{-- ===== bookPeriodPicker Alpine factory ===== --}}
    <script>
    function bookPeriodPicker(initStart, initEnd) {
        return {
            show:      false,
            preset:    '',
            startDate: initStart || '',
            endDate:   initEnd   || '',
            fpStart:   null,
            fpEnd:     null,
            initFlatpickr(startEl, endEl) {
                const self = this;
                const inputClass = 'w-full px-3 py-2.5 text-sm font-body rounded-lg cursor-pointer dark:bg-slate-800 bg-gray-50 dark:border-slate-700 border-gray-200 border dark:text-slate-300 text-gray-700 dark:placeholder-slate-600 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all duration-150';
                this.fpStart = flatpickr(startEl, {
                    dateFormat:    'Y-m-d',
                    altInput:      true,
                    altFormat:     'j M Y',
                    altInputClass: inputClass,
                    defaultDate:   initStart || null,
                    disableMobile: true,
                    onChange(dates, str) { if (!self._prog) { self.preset = 'custom'; } self.startDate = str; }
                });
                this.fpEnd = flatpickr(endEl, {
                    dateFormat:    'Y-m-d',
                    altInput:      true,
                    altFormat:     'j M Y',
                    altInputClass: inputClass,
                    defaultDate:   initEnd || null,
                    disableMobile: true,
                    onChange(dates, str) { if (!self._prog) { self.preset = 'custom'; } self.endDate = str; }
                });
                this.detectPreset();
            },
            detectPreset() {
                if (!this.startDate || !this.endDate) return;
                const now = new Date();
                const q = Math.floor(now.getMonth() / 3);
                const fmt = d => d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0');
                const candidates = {
                    this_month:   [new Date(now.getFullYear(), now.getMonth(), 1),     new Date(now.getFullYear(), now.getMonth() + 1, 0)],
                    last_month:   [new Date(now.getFullYear(), now.getMonth() - 1, 1), new Date(now.getFullYear(), now.getMonth(), 0)],
                    this_quarter: [new Date(now.getFullYear(), q * 3, 1),              new Date(now.getFullYear(), q * 3 + 3, 0)],
                    this_year:    [new Date(now.getFullYear(), 0, 1),                  new Date(now.getFullYear(), 11, 31)],
                };
                for (const [key, [s, e]] of Object.entries(candidates)) {
                    if (this.startDate === fmt(s) && this.endDate === fmt(e)) {
                        this.preset = key;
                        return;
                    }
                }
                this.preset = 'custom';
            },
            setPreset(p) {
                this.preset = p;
                if (p === 'custom') return;
                const now = new Date();
                let s, e;
                if (p === 'this_month') {
                    s = new Date(now.getFullYear(), now.getMonth(), 1);
                    e = new Date(now.getFullYear(), now.getMonth() + 1, 0);
                } else if (p === 'last_month') {
                    s = new Date(now.getFullYear(), now.getMonth() - 1, 1);
                    e = new Date(now.getFullYear(), now.getMonth(), 0);
                } else if (p === 'this_quarter') {
                    const q = Math.floor(now.getMonth() / 3);
                    s = new Date(now.getFullYear(), q * 3, 1);
                    e = new Date(now.getFullYear(), q * 3 + 3, 0);
                } else if (p === 'this_year') {
                    s = new Date(now.getFullYear(), 0, 1);
                    e = new Date(now.getFullYear(), 11, 31);
                }
                const fmt = d => d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0');
                this.startDate = fmt(s);
                this.endDate   = fmt(e);
                this._prog = true;
                if (this.fpStart) this.fpStart.setDate(this.startDate, true);
                if (this.fpEnd)   this.fpEnd.setDate(this.endDate,   true);
                this._prog = false;
            }
        };
    }
    </script>

    {{-- ===== CREATE BOOK MODAL ===== --}}
    @if($showCreateBook ?? false)
        <div class="fixed inset-0 z-50 flex items-end sm:items-center justify-center sm:p-4 bg-black/70 backdrop-blur-sm"
             x-data="bookPeriodPicker('','')"
             x-init="$nextTick(() => { show = true; initFlatpickr($refs.createStart, $refs.createEnd); })"
             @keydown.escape.window="$wire.set('showCreateBook', false)">

            <div :class="show ? 'opacity-100 scale-100 translate-y-0' : 'opacity-0 scale-95 translate-y-4'"
                 class="w-full sm:max-w-lg dark:bg-slate-900 bg-white
                        dark:border dark:border-slate-700 border-t sm:border border-gray-200
                        rounded-t-3xl sm:rounded-2xl overflow-hidden shadow-2xl shadow-black/40
                        transition-all duration-300 ease-out">

                {{-- Header --}}
                <div class="relative px-6 pt-6 pb-5 dark:border-b dark:border-slate-800 border-b border-gray-100">
                    {{-- Drag pill (mobile) --}}
                    <div class="absolute top-2.5 left-1/2 -translate-x-1/2 w-10 h-1 rounded-full dark:bg-slate-700 bg-gray-300 sm:hidden"></div>

                    <div class="flex items-center gap-4">
                        {{-- Icon --}}
                        <div class="flex-shrink-0 w-11 h-11 rounded-xl bg-primary/10 border border-primary/20 flex items-center justify-center">
                            <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.966 8.966 0 0 0-6 2.292m0-14.25v14.25"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h2 class="font-display font-extrabold text-xl dark:text-white text-gray-900 tracking-tight leading-none">
                                New Book
                            </h2>
                            <p class="text-sm dark:text-slate-400 text-gray-500 mt-0.5 font-body">
                                A book holds all entries for a period or project.
                            </p>
                        </div>
                        <button type="button" aria-label="Close" @click="$wire.set('showCreateBook', false)"
                                class="flex-shrink-0 p-2 rounded-lg dark:text-slate-500 text-gray-400
                                       dark:hover:text-white hover:text-gray-900
                                       dark:hover:bg-slate-800 hover:bg-gray-100
                                       transition-all duration-150">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Body --}}
                <div class="px-6 py-5 space-y-5">

                    {{-- Book Name --}}
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider dark:text-slate-500 text-gray-400 font-body mb-2">
                            Book Name <span class="text-red-500">*</span>
                        </label>
                        <input wire:model="bookName"
                               type="text"
                               placeholder="e.g. March 2026, Q1 Sales, Project Alpha"
                               autofocus
                               class="w-full px-4 py-3 text-base font-body rounded-lg
                                      dark:bg-slate-800 bg-gray-50
                                      dark:border-slate-700 border-gray-200 border
                                      dark:text-white text-gray-900
                                      dark:placeholder-slate-600 placeholder-gray-400
                                      focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary
                                      transition-all duration-150">
                        @error('bookName')
                            <p class="mt-1.5 text-xs text-red-500 font-body">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Period --}}
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider dark:text-slate-500 text-gray-400 font-body mb-2.5">
                            Period
                        </label>

                        {{-- Preset pills --}}
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mb-3">
                            @php
                                $presets = [
                                    'this_month'  => 'This Month',
                                    'last_month'  => 'Last Month',
                                    'this_quarter'=> 'This Quarter',
                                    'this_year'   => 'This Year',
                                ];
                            @endphp
                            @foreach($presets as $val => $label)
                                <button type="button"
                                        @click="setPreset('{{ $val }}')"
                                        :class="{
                                            'bg-primary/10 dark:bg-primary/15 border-primary/40 text-primary dark:text-primary font-semibold ring-2 ring-primary/20': preset === '{{ $val }}',
                                            'border-gray-200 dark:border-slate-700 text-gray-500 dark:text-slate-400 hover:border-gray-300 dark:hover:border-slate-600 hover:text-gray-700 dark:hover:text-slate-300': preset !== '{{ $val }}'
                                        }"
                                        class="px-2 py-2 rounded-lg text-xs font-body border transition-all duration-150 text-center leading-tight">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>

                        {{-- Custom date pickers --}}
                        <div class="grid grid-cols-2 gap-3" wire:ignore>
                            <div>
                                <p class="text-[10px] font-semibold uppercase tracking-wider dark:text-slate-600 text-gray-400 font-body mb-1.5">Start</p>
                                <input x-ref="createStart" type="text" placeholder="Select date" readonly
                                       class="w-full px-3 py-2.5 text-sm font-body rounded-lg cursor-pointer
                                              dark:bg-slate-800 bg-gray-50
                                              dark:border-slate-700 border-gray-200 border
                                              dark:text-slate-300 text-gray-700 dark:placeholder-slate-600 placeholder-gray-400
                                              focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary
                                              transition-all duration-150">
                            </div>
                            <div>
                                <p class="text-[10px] font-semibold uppercase tracking-wider dark:text-slate-600 text-gray-400 font-body mb-1.5">End</p>
                                <input x-ref="createEnd" type="text" placeholder="Select date" readonly
                                       class="w-full px-3 py-2.5 text-sm font-body rounded-lg cursor-pointer
                                              dark:bg-slate-800 bg-gray-50
                                              dark:border-slate-700 border-gray-200 border
                                              dark:text-slate-300 text-gray-700 dark:placeholder-slate-600 placeholder-gray-400
                                              focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary
                                              transition-all duration-150">
                            </div>
                        </div>
                        @error('bookPeriodEndsAt') <p class="mt-1.5 text-xs text-red-500 font-body">{{ $message }}</p> @enderror
                    </div>

                    {{-- Opening Balance + Description row --}}
                    <div class="grid grid-cols-2 gap-3 items-start">
                        {{-- Opening Balance --}}
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider dark:text-slate-500 text-gray-400 font-body mb-2">
                                Opening Balance
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-sm font-mono dark:text-slate-500 text-gray-400 pointer-events-none select-none">
                                    {{ $business->currencySymbol() }}
                                </span>
                                <input wire:model="bookOpeningBalance"
                                       type="number"
                                       step="0.01"
                                       placeholder="0.00"
                                       class="w-full pl-9 pr-3 py-2.5 text-sm font-mono rounded-lg
                                              dark:bg-slate-800 bg-gray-50
                                              dark:border-slate-700 border-gray-200 border
                                              dark:text-white text-gray-900
                                              dark:placeholder-slate-600 placeholder-gray-400
                                              focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary
                                              transition-all duration-150">
                            </div>
                            @error('bookOpeningBalance')
                                <p class="mt-1 text-xs text-red-500 font-body">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Description (collapsible) --}}
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider dark:text-slate-500 text-gray-400 font-body mb-2">
                                Description
                            </label>
                            <div x-data="{ open: false }">
                                <div x-show="!open">
                                    <button type="button"
                                            @click="open = true"
                                            class="w-full px-3 py-2.5 text-sm font-body rounded-lg border border-dashed text-left
                                                   dark:border-slate-700 border-gray-200
                                                   dark:text-slate-600 text-gray-400
                                                   dark:hover:border-slate-600 hover:border-gray-300
                                                   dark:hover:text-slate-400 hover:text-gray-500
                                                   transition-all duration-150">
                                        + Add note
                                    </button>
                                </div>
                                <div x-show="open" x-cloak>
                                    <textarea wire:model="bookDescription"
                                              rows="3"
                                              placeholder="What is this book for?"
                                              class="w-full px-3 py-2.5 text-sm font-body rounded-lg resize-none
                                                     dark:bg-slate-800 bg-gray-50
                                                     dark:border-slate-700 border-gray-200 border
                                                     dark:text-white text-gray-900
                                                     dark:placeholder-slate-600 placeholder-gray-400
                                                     focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary
                                                     transition-all duration-150"></textarea>
                                </div>
                            </div>
                            @error('bookDescription')
                                <p class="mt-1 text-xs text-red-500 font-body">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Carry forward the previous book's closing balance --}}
                    @if(($carryForwardAmount ?? null) !== null)
                        <label class="flex items-start gap-3 p-3 rounded-xl cursor-pointer
                                      dark:border-slate-700 border-gray-200 border
                                      dark:hover:bg-slate-800 hover:bg-gray-50
                                      transition-all duration-150">
                            <input type="checkbox" wire:model.live="carryForwardEnabled"
                                   class="mt-0.5 w-4 h-4 rounded border-gray-300 dark:border-slate-600 text-primary focus:ring-primary/30 dark:bg-slate-800 cursor-pointer flex-shrink-0">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold font-body dark:text-slate-200 text-gray-800">
                                    Start with <span class="dark:text-white text-gray-900">{{ $carryForwardBookName }}</span>'s closing balance
                                    (<x-amount :value="$carryForwardAmount" :symbol="$business->currencySymbol()" tone="plain" class="text-sm" />)
                                </p>
                                <p class="text-xs font-body dark:text-slate-500 text-gray-400 mt-0.5">
                                    Fills the opening balance above. You can still edit it.
                                </p>
                            </div>
                        </label>
                    @endif
                </div>

                {{-- Footer --}}
                <div class="px-6 pb-6 flex items-center justify-between gap-3">
                    <button @click="$wire.set('showCreateBook', false)"
                            class="px-4 py-2.5 text-sm font-body font-medium rounded-lg
                                   dark:text-slate-400 text-gray-500
                                   dark:hover:text-white hover:text-gray-900
                                   dark:hover:bg-slate-800 hover:bg-gray-100
                                   transition-all duration-150">
                        Cancel
                    </button>
                    <button @click="$wire.createBook(startDate, endDate)"
                            wire:loading.attr="disabled"
                            wire:target="createBook"
                            wire:loading.class="opacity-70 cursor-wait"
                            class="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-semibold font-body
                                   bg-primary hover:bg-accent text-white rounded-lg
                                   transition-all duration-200 shadow-lg shadow-primary/25
                                   disabled:opacity-70 disabled:cursor-wait">
                        <span wire:loading.remove wire:target="createBook" class="inline-flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                            </svg>
                            Create Book
                        </span>
                        <span wire:loading wire:target="createBook" class="inline-flex items-center gap-2">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            Creating…
                        </span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ===== EDIT BOOK MODAL ===== --}}
    @if($showEditBook ?? false)
        @php $editPresets = ['this_month' => 'This Month', 'last_month' => 'Last Month', 'this_quarter' => 'This Quarter', 'this_year' => 'This Year']; @endphp
        <div class="fixed inset-0 z-50 flex items-end sm:items-center justify-center sm:p-4 bg-black/70 backdrop-blur-sm"
             x-data="bookPeriodPicker('{{ $editBookPeriodStartsAt }}','{{ $editBookPeriodEndsAt }}')"
             x-init="$nextTick(() => { show = true; initFlatpickr($refs.editStart, $refs.editEnd); })"
             @keydown.escape.window="$wire.set('showEditBook', false)">

            <div :class="show ? 'opacity-100 scale-100 translate-y-0' : 'opacity-0 scale-95 translate-y-4'"
                 class="w-full sm:max-w-lg dark:bg-slate-900 bg-white
                        dark:border dark:border-slate-700 border-t sm:border border-gray-200
                        rounded-t-3xl sm:rounded-2xl overflow-hidden shadow-2xl shadow-black/40
                        transition-all duration-300 ease-out">

                {{-- Header --}}
                <div class="relative px-6 pt-6 pb-5 dark:border-b dark:border-slate-800 border-b border-gray-100">
                    <div class="absolute top-2.5 left-1/2 -translate-x-1/2 w-10 h-1 rounded-full dark:bg-slate-700 bg-gray-300 sm:hidden"></div>
                    <div class="flex items-center gap-4">
                        <div class="flex-shrink-0 w-11 h-11 rounded-xl bg-primary/10 border border-primary/20 flex items-center justify-center">
                            <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h2 class="font-display font-extrabold text-xl dark:text-white text-gray-900 tracking-tight leading-none">Edit Book</h2>
                            <p class="text-sm dark:text-slate-400 text-gray-500 mt-0.5 font-body">Update name, period, or opening balance.</p>
                        </div>
                        <button aria-label="Close" @click="$wire.set('showEditBook', false)"
                                class="flex-shrink-0 p-2 rounded-lg dark:text-slate-500 text-gray-400
                                       dark:hover:text-white hover:text-gray-900 dark:hover:bg-slate-800 hover:bg-gray-100 transition-all duration-150">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Body --}}
                <div class="px-6 py-5 space-y-5">
                    {{-- Name --}}
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider dark:text-slate-500 text-gray-400 font-body mb-2">
                            Book Name <span class="text-red-500">*</span>
                        </label>
                        <input wire:model="editBookName" type="text" placeholder="Book name" autofocus
                               class="w-full px-4 py-3 text-base font-body rounded-lg
                                      dark:bg-slate-800 bg-gray-50 dark:border-slate-700 border-gray-200 border
                                      dark:text-white text-gray-900 dark:placeholder-slate-600 placeholder-gray-400
                                      focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all duration-150">
                        @error('editBookName') <p class="mt-1.5 text-xs text-red-500 font-body">{{ $message }}</p> @enderror
                    </div>

                    {{-- Period --}}
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider dark:text-slate-500 text-gray-400 font-body mb-2.5">Period</label>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mb-3">
                            @foreach($editPresets as $val => $lbl)
                                <button type="button" @click="setPreset('{{ $val }}')"
                                        :class="{
                                            'bg-primary/10 dark:bg-primary/15 border-primary/40 text-primary dark:text-primary font-semibold ring-2 ring-primary/20': preset === '{{ $val }}',
                                            'border-gray-200 dark:border-slate-700 text-gray-500 dark:text-slate-400 hover:border-gray-300 dark:hover:border-slate-600': preset !== '{{ $val }}'
                                        }"
                                        class="px-2 py-2 rounded-lg text-xs font-body border transition-all duration-150 text-center leading-tight">
                                    {{ $lbl }}
                                </button>
                            @endforeach
                        </div>
                        <div class="grid grid-cols-2 gap-3" wire:ignore>
                            <div>
                                <p class="text-[10px] font-semibold uppercase tracking-wider dark:text-slate-600 text-gray-400 font-body mb-1.5">Start</p>
                                <input x-ref="editStart" type="text" placeholder="Select date" readonly
                                       class="w-full px-3 py-2.5 text-sm font-body rounded-lg cursor-pointer
                                              dark:bg-slate-800 bg-gray-50 dark:border-slate-700 border-gray-200 border
                                              dark:text-slate-300 text-gray-700 dark:placeholder-slate-600 placeholder-gray-400
                                              focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all duration-150">
                            </div>
                            <div>
                                <p class="text-[10px] font-semibold uppercase tracking-wider dark:text-slate-600 text-gray-400 font-body mb-1.5">End</p>
                                <input x-ref="editEnd" type="text" placeholder="Select date" readonly
                                       class="w-full px-3 py-2.5 text-sm font-body rounded-lg cursor-pointer
                                              dark:bg-slate-800 bg-gray-50 dark:border-slate-700 border-gray-200 border
                                              dark:text-slate-300 text-gray-700 dark:placeholder-slate-600 placeholder-gray-400
                                              focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all duration-150">
                            </div>
                        </div>
                        @error('editBookPeriodEndsAt') <p class="mt-1.5 text-xs text-red-500 font-body">{{ $message }}</p> @enderror
                    </div>

                    {{-- Opening Balance --}}
                    <div class="max-w-xs">
                        <label class="block text-xs font-semibold uppercase tracking-wider dark:text-slate-500 text-gray-400 font-body mb-2">Opening Balance</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-sm font-mono dark:text-slate-500 text-gray-400 pointer-events-none select-none">
                                {{ $business->currencySymbol() }}
                            </span>
                            <input wire:model="editBookOpeningBalance" type="number" step="0.01" placeholder="0.00"
                                   class="w-full pl-9 pr-3 py-2.5 text-sm font-mono rounded-lg
                                          dark:bg-slate-800 bg-gray-50 dark:border-slate-700 border-gray-200 border
                                          dark:text-white text-gray-900 dark:placeholder-slate-600 placeholder-gray-400
                                          focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all duration-150">
                        </div>
                        @error('editBookOpeningBalance') <p class="mt-1 text-xs text-red-500 font-body">{{ $message }}</p> @enderror
                    </div>

                    {{-- Description --}}
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider dark:text-slate-500 text-gray-400 font-body mb-2">Description</label>
                        <textarea wire:model="editBookDescription" rows="2" placeholder="Optional note"
                                  class="w-full px-3 py-2.5 text-sm font-body rounded-lg resize-none
                                         dark:bg-slate-800 bg-gray-50 dark:border-slate-700 border-gray-200 border
                                         dark:text-white text-gray-900 dark:placeholder-slate-600 placeholder-gray-400
                                         focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all duration-150"></textarea>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="px-6 pb-6 flex items-center justify-between gap-3">
                    <button @click="$wire.set('showEditBook', false)"
                            class="px-4 py-2.5 text-sm font-body font-medium rounded-lg
                                   dark:text-slate-400 text-gray-500 dark:hover:text-white hover:text-gray-900
                                   dark:hover:bg-slate-800 hover:bg-gray-100 transition-all duration-150">
                        Cancel
                    </button>
                    <button @click="$wire.saveEditBook(startDate, endDate)" wire:loading.attr="disabled" wire:target="saveEditBook"
                            wire:loading.class="opacity-70 cursor-wait"
                            class="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-semibold font-body
                                   bg-primary hover:bg-accent text-white rounded-lg
                                   transition-all duration-200 shadow-lg shadow-primary/25 disabled:opacity-70 disabled:cursor-wait">
                        <span wire:loading.remove wire:target="saveEditBook" class="inline-flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                            </svg>
                            Save Changes
                        </span>
                        <span wire:loading wire:target="saveEditBook" class="inline-flex items-center gap-2">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            Saving…
                        </span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ===== DUPLICATE BOOK MODAL ===== --}}
    @if($showDuplicateBook ?? false)
        @php $dupPresets = ['this_month' => 'This Month', 'last_month' => 'Last Month', 'this_quarter' => 'This Quarter', 'this_year' => 'This Year']; @endphp
        <div class="fixed inset-0 z-50 flex items-end sm:items-center justify-center sm:p-4 bg-black/70 backdrop-blur-sm"
             x-data="bookPeriodPicker('','')"
             x-init="$nextTick(() => { show = true; initFlatpickr($refs.dupStart, $refs.dupEnd); })"
             @keydown.escape.window="$wire.set('showDuplicateBook', false)">

            <div :class="show ? 'opacity-100 scale-100 translate-y-0' : 'opacity-0 scale-95 translate-y-4'"
                 class="w-full sm:max-w-lg dark:bg-slate-900 bg-white
                        dark:border dark:border-slate-700 border-t sm:border border-gray-200
                        rounded-t-3xl sm:rounded-2xl overflow-hidden shadow-2xl shadow-black/40
                        transition-all duration-300 ease-out">

                {{-- Header --}}
                <div class="relative px-6 pt-6 pb-5 dark:border-b dark:border-slate-800 border-b border-gray-100">
                    <div class="absolute top-2.5 left-1/2 -translate-x-1/2 w-10 h-1 rounded-full dark:bg-slate-700 bg-gray-300 sm:hidden"></div>
                    <div class="flex items-center gap-4">
                        <div class="flex-shrink-0 w-11 h-11 rounded-xl bg-primary/10 border border-primary/20 flex items-center justify-center">
                            <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H9.75"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h2 class="font-display font-extrabold text-xl dark:text-white text-gray-900 tracking-tight leading-none">Duplicate Book</h2>
                            <p class="text-sm dark:text-slate-400 text-gray-500 mt-0.5 font-body">Choose what to carry over into the new book.</p>
                        </div>
                        <button aria-label="Close" @click="$wire.set('showDuplicateBook', false)"
                                class="flex-shrink-0 p-2 rounded-lg dark:text-slate-500 text-gray-400
                                       dark:hover:text-white hover:text-gray-900 dark:hover:bg-slate-800 hover:bg-gray-100 transition-all duration-150">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Body --}}
                <div class="px-6 py-5 space-y-5">
                    {{-- New name --}}
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider dark:text-slate-500 text-gray-400 font-body mb-2">
                            New Book Name <span class="text-red-500">*</span>
                        </label>
                        <input wire:model="duplicateBookName" type="text" autofocus
                               class="w-full px-4 py-3 text-base font-body rounded-lg
                                      dark:bg-slate-800 bg-gray-50 dark:border-slate-700 border-gray-200 border
                                      dark:text-white text-gray-900 dark:placeholder-slate-600 placeholder-gray-400
                                      focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all duration-150">
                        @error('duplicateBookName') <p class="mt-1.5 text-xs text-red-500 font-body">{{ $message }}</p> @enderror
                    </div>

                    {{-- Period --}}
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider dark:text-slate-500 text-gray-400 font-body mb-2.5">Period for New Book</label>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mb-3">
                            @foreach($dupPresets as $val => $lbl)
                                <button type="button" @click="setPreset('{{ $val }}')"
                                        :class="{
                                            'bg-primary/10 dark:bg-primary/15 border-primary/40 text-primary dark:text-primary font-semibold ring-2 ring-primary/20': preset === '{{ $val }}',
                                            'border-gray-200 dark:border-slate-700 text-gray-500 dark:text-slate-400 hover:border-gray-300 dark:hover:border-slate-600': preset !== '{{ $val }}'
                                        }"
                                        class="px-2 py-2 rounded-lg text-xs font-body border transition-all duration-150 text-center leading-tight">
                                    {{ $lbl }}
                                </button>
                            @endforeach
                        </div>
                        <div class="grid grid-cols-2 gap-3" wire:ignore>
                            <div>
                                <p class="text-[10px] font-semibold uppercase tracking-wider dark:text-slate-600 text-gray-400 font-body mb-1.5">Start</p>
                                <input x-ref="dupStart" type="text" placeholder="Select date" readonly
                                       class="w-full px-3 py-2.5 text-sm font-body rounded-lg cursor-pointer
                                              dark:bg-slate-800 bg-gray-50 dark:border-slate-700 border-gray-200 border
                                              dark:text-slate-300 text-gray-700 dark:placeholder-slate-600 placeholder-gray-400
                                              focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all duration-150">
                            </div>
                            <div>
                                <p class="text-[10px] font-semibold uppercase tracking-wider dark:text-slate-600 text-gray-400 font-body mb-1.5">End</p>
                                <input x-ref="dupEnd" type="text" placeholder="Select date" readonly
                                       class="w-full px-3 py-2.5 text-sm font-body rounded-lg cursor-pointer
                                              dark:bg-slate-800 bg-gray-50 dark:border-slate-700 border-gray-200 border
                                              dark:text-slate-300 text-gray-700 dark:placeholder-slate-600 placeholder-gray-400
                                              focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all duration-150">
                            </div>
                        </div>
                        @error('duplicateBookPeriodEndsAt') <p class="mt-1.5 text-xs text-red-500 font-body">{{ $message }}</p> @enderror
                    </div>

                    {{-- What to copy --}}
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider dark:text-slate-500 text-gray-400 font-body mb-3">Carry Over</label>
                        <div class="space-y-2.5">
                            @foreach([
                                ['duplicateKeepCategories',   'Categories',      'Custom categories you\'ve created'],
                                ['duplicateKeepPaymentModes', 'Payment Methods', 'Bank, Cash, Card, etc.'],
                                ['duplicateKeepEntries',      'Entries',         'All cash in/out records (starts fresh if off)'],
                            ] as [$prop, $title, $desc])
                                <label class="flex items-start gap-3 p-3 rounded-xl cursor-pointer
                                              dark:border-slate-700 border-gray-200 border
                                              dark:hover:bg-slate-800 hover:bg-gray-50
                                              transition-all duration-150 group">
                                    <input type="checkbox" wire:model.live="{{ $prop }}"
                                           class="mt-0.5 w-4 h-4 rounded border-gray-300 dark:border-slate-600 text-primary focus:ring-primary/30 dark:bg-slate-800 cursor-pointer flex-shrink-0">
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold font-body dark:text-slate-200 text-gray-800">{{ $title }}</p>
                                        <p class="text-xs font-body dark:text-slate-500 text-gray-400 mt-0.5">{{ $desc }}</p>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="px-6 pb-6 flex items-center justify-between gap-3">
                    <button @click="$wire.set('showDuplicateBook', false)"
                            class="px-4 py-2.5 text-sm font-body font-medium rounded-lg
                                   dark:text-slate-400 text-gray-500 dark:hover:text-white hover:text-gray-900
                                   dark:hover:bg-slate-800 hover:bg-gray-100 transition-all duration-150">
                        Cancel
                    </button>
                    <button @click="$wire.executeDuplicate(startDate, endDate)" wire:loading.attr="disabled" wire:target="executeDuplicate"
                            wire:loading.class="opacity-70 cursor-wait"
                            class="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-semibold font-body
                                   bg-primary hover:bg-accent text-white rounded-lg
                                   transition-all duration-200 shadow-lg shadow-primary/25 disabled:opacity-70 disabled:cursor-wait">
                        <span wire:loading.remove wire:target="executeDuplicate" class="inline-flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H9.75"/>
                            </svg>
                            Create Copy
                        </span>
                        <span wire:loading wire:target="executeDuplicate" class="inline-flex items-center gap-2">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            Creating…
                        </span>
                    </button>
                </div>
            </div>
        </div>
    @endif

