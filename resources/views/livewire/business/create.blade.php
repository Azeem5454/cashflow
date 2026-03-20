<div class="min-h-full">

    {{-- ===== UPGRADE MODAL ===== --}}
    <x-upgrade-modal :show="$upgradeModalFeature !== ''" :feature="$upgradeModalFeature" :dismiss-href="route('dashboard')" />

    {{-- ===== PAGE HEADER ===== --}}
    <div class="px-6 lg:px-8 py-7
                dark:bg-navy bg-white
                dark:border-b dark:border-slate-800 border-b border-gray-200
                sticky top-0 z-10 backdrop-blur-sm">
        <div class="max-w-2xl mx-auto flex items-center gap-4">
            <a href="{{ route('dashboard') }}"
               class="p-2 rounded-xl dark:text-slate-500 text-gray-400
                      dark:hover:bg-slate-800 hover:bg-gray-100
                      dark:hover:text-white hover:text-gray-900
                      transition-all duration-150 flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                </svg>
            </a>
            <div>
                <h1 class="font-display font-extrabold text-2xl dark:text-white text-gray-900 tracking-tight leading-none">
                    New Business
                </h1>
                <p class="text-sm dark:text-slate-500 text-gray-400 mt-1 font-body">
                    Set up a business to start tracking cash flow
                </p>
            </div>
        </div>
    </div>

    {{-- ===== CONTENT ===== --}}
    <div class="px-6 lg:px-8 py-10 max-w-2xl mx-auto">

        {{-- Subtle background glow --}}
        <div class="relative">
            <div class="absolute -top-20 left-1/2 -translate-x-1/2 w-96 h-96 bg-primary/5 rounded-full blur-3xl pointer-events-none"></div>

            <div class="relative dark:bg-dark bg-white
                        dark:border dark:border-slate-700/60 border border-gray-200
                        rounded-2xl shadow-xl shadow-black/10">

                {{-- Top accent --}}
                <div class="h-1 w-full bg-gradient-to-r from-primary to-accent rounded-t-2xl"></div>

                <div class="p-8">
                    <form wire:submit="save" class="space-y-6" @submit.prevent="open = false">

                        {{-- Business Name --}}
                        <div>
                            <label for="name" class="block text-sm font-semibold dark:text-slate-300 text-gray-700 mb-2 font-body">
                                Business Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   id="name"
                                   wire:model="name"
                                   placeholder="e.g. Eveso IT Company"
                                   autofocus
                                   class="w-full px-4 py-3 rounded-xl text-sm font-body
                                          dark:bg-navy bg-gray-50
                                          dark:border-slate-700 border-gray-200 border
                                          dark:text-white text-gray-900
                                          dark:placeholder-slate-600 placeholder-gray-400
                                          focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary
                                          transition-all duration-150">
                            @error('name')
                                <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Description --}}
                        <div>
                            <label for="description" class="block text-sm font-semibold dark:text-slate-300 text-gray-700 mb-2 font-body">
                                Description
                                <span class="font-normal dark:text-slate-500 text-gray-400 ml-1">(optional)</span>
                            </label>
                            <textarea id="description"
                                      wire:model="description"
                                      placeholder="What does this business do?"
                                      rows="3"
                                      class="w-full px-4 py-3 rounded-xl text-sm font-body resize-none
                                             dark:bg-navy bg-gray-50
                                             dark:border-slate-700 border-gray-200 border
                                             dark:text-white text-gray-900
                                             dark:placeholder-slate-600 placeholder-gray-400
                                             focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary
                                             transition-all duration-150"></textarea>
                            @error('description')
                                <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Currency --}}
                        <div class="relative" x-data="{
                            open: false,
                            search: '',
                            selected: $wire.entangle('currency').live,
                            currencies: [
                                { code: 'PKR', name: 'Pakistani Rupee' },
                                { code: 'USD', name: 'US Dollar' },
                                { code: 'EUR', name: 'Euro' },
                                { code: 'GBP', name: 'British Pound' },
                                { code: 'AED', name: 'UAE Dirham' },
                                { code: 'SAR', name: 'Saudi Riyal' },
                                { code: 'CAD', name: 'Canadian Dollar' },
                                { code: 'AUD', name: 'Australian Dollar' },
                                { code: 'INR', name: 'Indian Rupee' },
                                { code: 'BDT', name: 'Bangladeshi Taka' },
                                { code: 'LKR', name: 'Sri Lankan Rupee' },
                                { code: 'NPR', name: 'Nepalese Rupee' },
                                { code: 'AFN', name: 'Afghan Afghani' },
                                { code: 'OMR', name: 'Omani Rial' },
                                { code: 'KWD', name: 'Kuwaiti Dinar' },
                                { code: 'BHD', name: 'Bahraini Dinar' },
                                { code: 'QAR', name: 'Qatari Riyal' },
                                { code: 'JOD', name: 'Jordanian Dinar' },
                                { code: 'IQD', name: 'Iraqi Dinar' },
                                { code: 'EGP', name: 'Egyptian Pound' },
                                { code: 'TRY', name: 'Turkish Lira' },
                                { code: 'IRR', name: 'Iranian Rial' },
                                { code: 'CHF', name: 'Swiss Franc' },
                                { code: 'SEK', name: 'Swedish Krona' },
                                { code: 'NOK', name: 'Norwegian Krone' },
                                { code: 'DKK', name: 'Danish Krone' },
                                { code: 'PLN', name: 'Polish Zloty' },
                                { code: 'CZK', name: 'Czech Koruna' },
                                { code: 'HUF', name: 'Hungarian Forint' },
                                { code: 'RON', name: 'Romanian Leu' },
                                { code: 'BGN', name: 'Bulgarian Lev' },
                                { code: 'HRK', name: 'Croatian Kuna' },
                                { code: 'RUB', name: 'Russian Ruble' },
                                { code: 'UAH', name: 'Ukrainian Hryvnia' },
                                { code: 'JPY', name: 'Japanese Yen' },
                                { code: 'CNY', name: 'Chinese Yuan' },
                                { code: 'KRW', name: 'South Korean Won' },
                                { code: 'HKD', name: 'Hong Kong Dollar' },
                                { code: 'SGD', name: 'Singapore Dollar' },
                                { code: 'MYR', name: 'Malaysian Ringgit' },
                                { code: 'THB', name: 'Thai Baht' },
                                { code: 'IDR', name: 'Indonesian Rupiah' },
                                { code: 'PHP', name: 'Philippine Peso' },
                                { code: 'VND', name: 'Vietnamese Dong' },
                                { code: 'TWD', name: 'Taiwan Dollar' },
                                { code: 'NZD', name: 'New Zealand Dollar' },
                                { code: 'ZAR', name: 'South African Rand' },
                                { code: 'NGN', name: 'Nigerian Naira' },
                                { code: 'KES', name: 'Kenyan Shilling' },
                                { code: 'GHS', name: 'Ghanaian Cedi' },
                                { code: 'TZS', name: 'Tanzanian Shilling' },
                                { code: 'ETB', name: 'Ethiopian Birr' },
                                { code: 'UGX', name: 'Ugandan Shilling' },
                                { code: 'MAD', name: 'Moroccan Dirham' },
                                { code: 'DZD', name: 'Algerian Dinar' },
                                { code: 'TND', name: 'Tunisian Dinar' },
                                { code: 'BRL', name: 'Brazilian Real' },
                                { code: 'MXN', name: 'Mexican Peso' },
                                { code: 'ARS', name: 'Argentine Peso' },
                                { code: 'CLP', name: 'Chilean Peso' },
                                { code: 'COP', name: 'Colombian Peso' },
                                { code: 'PEN', name: 'Peruvian Sol' },
                            ],
                            get filtered() {
                                if (!this.search) return this.currencies;
                                const q = this.search.toLowerCase();
                                return this.currencies.filter(c =>
                                    c.code.toLowerCase().includes(q) || c.name.toLowerCase().includes(q)
                                );
                            },
                            get selectedLabel() {
                                const c = this.currencies.find(c => c.code === this.selected);
                                return c ? c.code + ' — ' + c.name : 'Select currency';
                            },
                            pick(code) {
                                this.selected = code;
                                this.open = false;
                                this.search = '';
                            }
                        }" @keydown.escape="open = false" @click.outside="open = false">
                            <label class="block text-sm font-semibold dark:text-slate-300 text-gray-700 mb-2 font-body">
                                Currency <span class="text-red-500">*</span>
                            </label>

                            {{-- Trigger button --}}
                            <button type="button" @click="open = !open; if(open) $nextTick(() => $refs.searchInput.focus())"
                                    class="w-full flex items-center justify-between px-4 py-3 rounded-xl text-sm font-body
                                           dark:bg-navy bg-gray-50
                                           dark:border-slate-700 border-gray-200 border
                                           dark:text-white text-gray-900
                                           focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary
                                           transition-all duration-150 cursor-pointer">
                                <span x-text="selectedLabel"></span>
                                <svg class="w-4 h-4 dark:text-slate-500 text-gray-400 transition-transform duration-150"
                                     :class="open ? 'rotate-180' : ''"
                                     fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
                                </svg>
                            </button>

                            {{-- Dropdown --}}
                            <div x-show="open" x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                                 class="absolute left-0 right-0 z-50 mt-1
                                        dark:bg-slate-800 bg-white
                                        dark:border-slate-700 border-gray-200 border
                                        rounded-xl shadow-2xl"
                                 style="display:none">

                                {{-- Search --}}
                                <div class="p-2 border-b dark:border-slate-700 border-gray-100">
                                    <div class="relative">
                                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 dark:text-slate-500 text-gray-400"
                                             fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                                        </svg>
                                        <input type="text" x-model="search" placeholder="Search currency…"
                                               @click.stop
                                               x-ref="searchInput"
                                               class="w-full pl-8 pr-3 py-2 text-sm rounded-lg
                                                      dark:bg-slate-700 bg-gray-50
                                                      dark:border-slate-600 border-gray-200 border
                                                      dark:text-white text-gray-900
                                                      dark:placeholder-slate-500 placeholder-gray-400
                                                      focus:outline-none focus:ring-2 focus:ring-primary/50">
                                    </div>
                                </div>

                                {{-- Options list — fixed height, scrolls inside --}}
                                <div class="overflow-y-auto overscroll-contain" style="max-height: 220px;">
                                    <template x-for="c in filtered" :key="c.code">
                                        <button type="button"
                                                @click="pick(c.code)"
                                                class="w-full flex items-center justify-between px-4 py-2 text-sm text-left
                                                       dark:text-slate-200 text-gray-800
                                                       dark:hover:bg-slate-700 hover:bg-gray-50
                                                       transition-colors duration-100">
                                            <span>
                                                <span class="font-mono font-semibold dark:text-white text-gray-900"
                                                      x-text="c.code"></span>
                                                <span class="ml-2 dark:text-slate-400 text-gray-500"
                                                      x-text="'— ' + c.name"></span>
                                            </span>
                                            <svg x-show="selected === c.code" class="w-4 h-4 text-primary flex-shrink-0"
                                                 fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                                            </svg>
                                        </button>
                                    </template>
                                    <div x-show="filtered.length === 0"
                                         class="px-4 py-6 text-center text-sm dark:text-slate-500 text-gray-400">
                                        No currencies match "<span x-text="search"></span>"
                                    </div>
                                </div>
                            </div>

                            @error('currency')
                                <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                            <p class="mt-1.5 text-xs dark:text-slate-500 text-gray-400 font-body">
                                Used for all cash entries in this business. Cannot be changed later.
                            </p>
                        </div>

                        {{-- Divider --}}
                        <div class="dark:border-slate-800 border-gray-100 border-t"></div>

                        {{-- Actions --}}
                        <div class="flex items-center gap-3">
                            <button type="submit"
                                    wire:loading.attr="disabled"
                                    wire:loading.class="opacity-70 cursor-wait"
                                    class="flex-1 inline-flex items-center justify-center gap-2 px-6 py-3
                                           bg-primary hover:bg-accent
                                           text-white text-sm font-semibold rounded-xl
                                           transition-all duration-200
                                           shadow-lg shadow-primary/25 hover:shadow-accent/30
                                           disabled:opacity-70 disabled:cursor-wait">
                                <span wire:loading.remove wire:target="save" class="inline-flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/>
                                    </svg>
                                    Create Business
                                </span>
                                <span wire:loading wire:target="save" class="inline-flex items-center gap-2">
                                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    Creating…
                                </span>
                            </button>

                            <a href="{{ route('dashboard') }}"
                               class="px-5 py-3 rounded-xl text-sm font-semibold
                                      dark:text-slate-400 text-gray-500
                                      dark:hover:bg-slate-800 hover:bg-gray-100
                                      dark:hover:text-white hover:text-gray-900
                                      transition-all duration-150">
                                Cancel
                            </a>
                        </div>

                    </form>
                </div>
            </div>
        </div>

    </div>
</div>
