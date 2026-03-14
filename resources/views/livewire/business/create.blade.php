<div class="min-h-full">

    {{-- ===== UPGRADE MODAL (Free plan limit hit) ===== --}}
    @if($showUpgradeModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4"
             x-data
             x-init="document.body.style.overflow = 'hidden'"
             x-destroy="document.body.style.overflow = ''">

            {{-- Backdrop --}}
            <div class="absolute inset-0 bg-black/70 backdrop-blur-sm"></div>

            {{-- Modal --}}
            <div class="relative w-full max-w-md
                        dark:bg-dark bg-white
                        dark:border dark:border-slate-700/60
                        rounded-2xl shadow-2xl shadow-black/40
                        overflow-hidden"
                 x-data="{ shown: false }"
                 x-init="requestAnimationFrame(() => shown = true)"
                 :class="shown ? 'opacity-100 scale-100' : 'opacity-0 scale-95'"
                 style="transition: opacity 250ms ease, transform 250ms ease;">

                {{-- Top accent bar --}}
                <div class="h-1 w-full bg-gradient-to-r from-amber-400 to-amber-500"></div>

                <div class="p-8 text-center">
                    {{-- Icon --}}
                    <div class="w-16 h-16 rounded-2xl bg-amber-400/10 flex items-center justify-center mx-auto mb-5">
                        <svg class="w-8 h-8 text-amber-400" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z"/>
                        </svg>
                    </div>

                    <h2 class="font-display font-extrabold text-2xl dark:text-white text-gray-900 mb-2">
                        Upgrade to Pro
                    </h2>
                    <p class="text-sm dark:text-slate-400 text-gray-500 mb-1 leading-relaxed">
                        The Free plan includes <span class="font-semibold dark:text-slate-300 text-gray-700">1 business</span>.
                        Upgrade to Pro to manage unlimited businesses.
                    </p>
                    <p class="text-xs dark:text-slate-500 text-gray-400 mb-7">
                        Just $3/month — cancel anytime.
                    </p>

                    {{-- Feature list --}}
                    <ul class="text-left space-y-2.5 mb-7">
                        @foreach(['Unlimited businesses', 'Unlimited team members', 'PDF & CSV export', 'Priority support'] as $feature)
                            <li class="flex items-center gap-3 text-sm dark:text-slate-300 text-gray-700">
                                <span class="w-5 h-5 rounded-full bg-green-500/15 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-3 h-3 text-green-500" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                                    </svg>
                                </span>
                                {{ $feature }}
                            </li>
                        @endforeach
                    </ul>

                    <div class="flex flex-col gap-3">
                        <a href="{{ route('billing') }}"
                           class="inline-flex items-center justify-center gap-2 w-full px-6 py-3
                                  bg-amber-400 hover:bg-amber-300
                                  text-gray-900 text-sm font-bold rounded-xl
                                  transition-all duration-200 shadow-lg shadow-amber-400/25">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z"/>
                            </svg>
                            Upgrade to Pro — $3/mo
                        </a>
                        <a href="{{ route('dashboard') }}"
                           class="inline-flex items-center justify-center w-full px-6 py-3
                                  dark:text-slate-400 text-gray-500
                                  dark:hover:text-white hover:text-gray-900
                                  text-sm font-medium rounded-xl
                                  transition-colors duration-150">
                            ← Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif

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
                        rounded-2xl overflow-hidden shadow-xl shadow-black/10">

                {{-- Top accent --}}
                <div class="h-1 w-full bg-gradient-to-r from-primary to-accent"></div>

                <div class="p-8">
                    <form wire:submit="save" class="space-y-6">

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
                        <div>
                            <label for="currency" class="block text-sm font-semibold dark:text-slate-300 text-gray-700 mb-2 font-body">
                                Currency <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <select id="currency"
                                        wire:model="currency"
                                        class="w-full px-4 py-3 rounded-xl text-sm font-body appearance-none
                                               dark:bg-navy bg-gray-50
                                               dark:border-slate-700 border-gray-200 border
                                               dark:text-white text-gray-900
                                               focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary
                                               transition-all duration-150 cursor-pointer">
                                    <option value="PKR">PKR — Pakistani Rupee</option>
                                    <option value="USD">USD — US Dollar</option>
                                    <option value="EUR">EUR — Euro</option>
                                    <option value="GBP">GBP — British Pound</option>
                                    <option value="AED">AED — UAE Dirham</option>
                                    <option value="SAR">SAR — Saudi Riyal</option>
                                    <option value="CAD">CAD — Canadian Dollar</option>
                                    <option value="AUD">AUD — Australian Dollar</option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-4 flex items-center">
                                    <svg class="w-4 h-4 dark:text-slate-500 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
                                    </svg>
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
