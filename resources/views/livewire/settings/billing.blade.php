<div class="min-h-full">

    {{-- ===== STICKY HEADER ===== --}}
    <div class="px-6 lg:px-8 py-5
                dark:bg-navy/95 bg-white/95
                dark:border-b dark:border-slate-800 border-b border-gray-200
                sticky top-0 z-10 backdrop-blur-md">
        <div class="max-w-2xl mx-auto">
            <h1 class="font-display font-extrabold text-2xl dark:text-white text-gray-900 tracking-tight leading-none">
                Billing & Plans
            </h1>
            <p class="text-sm dark:text-slate-500 text-gray-400 mt-1">Manage your subscription and billing details</p>
        </div>
    </div>

    {{-- ===== CONTENT ===== --}}
    <div class="px-6 lg:px-8 py-7 max-w-2xl mx-auto space-y-6">

        {{-- Flash messages --}}
        @if($flash === 'success')
            <div class="flex items-center gap-3 px-4 py-3 rounded-xl bg-green-500/10 border border-green-500/30 text-green-500 text-sm font-medium">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
                You're now on the Pro plan. Welcome aboard!
            </div>
        @elseif($flash === 'canceled')
            <div class="flex items-center gap-3 px-4 py-3 rounded-xl dark:bg-slate-800/60 bg-gray-100 border dark:border-slate-700 border-gray-200 dark:text-slate-300 text-gray-600 text-sm font-medium">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"/>
                </svg>
                Checkout canceled. You're still on the Free plan.
            </div>
        @elseif($flash === 'resumed')
            <div class="flex items-center gap-3 px-4 py-3 rounded-xl bg-green-500/10 border border-green-500/30 text-green-500 text-sm font-medium">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
                Your Pro subscription has been resumed.
            </div>
        @endif

        {{-- Grace period warning --}}
        @if($subscription && $subscription->onGracePeriod())
            <div class="flex items-start justify-between gap-4 px-4 py-3.5 rounded-xl bg-amber-500/10 border border-amber-500/30">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-amber-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
                    </svg>
                    <div>
                        <p class="text-sm font-semibold text-amber-400">Pro plan cancels on {{ $subscription->ends_at->format('M j, Y') }}</p>
                        <p class="text-xs text-amber-400/70 mt-0.5">After this date your extra businesses will be locked and you'll revert to the Free plan.</p>
                    </div>
                </div>
                <button
                    wire:click="resume"
                    wire:loading.attr="disabled"
                    wire:target="resume"
                    class="flex-shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold
                           bg-amber-400 hover:bg-amber-300 text-navy rounded-lg
                           transition-all duration-150 disabled:opacity-70">
                    <span wire:loading.remove wire:target="resume">Resume Plan</span>
                    <span wire:loading wire:target="resume">Resuming…</span>
                </button>
            </div>
        @endif

        {{-- Stripe errors --}}
        @error('stripe')
            <div class="flex items-center gap-3 px-4 py-3 rounded-xl bg-red-500/10 border border-red-500/30 text-red-400 text-sm">
                {{ $message }}
            </div>
        @enderror

        {{-- ===== CURRENT STATUS CARD ===== --}}
        <div class="dark:bg-[#1e293b] bg-white dark:border dark:border-slate-700/60 border border-gray-200 rounded-2xl overflow-hidden shadow-sm">
            <div class="p-6 flex items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0
                                {{ $user->isPro()
                                    ? 'bg-primary/15 dark:bg-primary/20'
                                    : 'dark:bg-slate-800 bg-gray-100' }}">
                        @if($user->isPro())
                            <svg class="w-6 h-6 text-primary dark:text-blue-light" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 0 0 .95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 0 0-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 0 0-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 0 0-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 0 0 .951-.69l1.07-3.292Z"/>
                            </svg>
                        @else
                            <svg class="w-6 h-6 dark:text-slate-400 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z"/>
                            </svg>
                        @endif
                    </div>
                    <div>
                        <p class="font-heading font-bold text-base dark:text-white text-gray-900">
                            {{ $user->isPro() ? 'Pro Plan' : 'Free Plan' }}
                        </p>
                        <p class="text-sm dark:text-slate-400 text-gray-500 mt-0.5">
                            @if($user->isPro())
                                @if($subscription && $subscription->ends_at)
                                    Cancels {{ $subscription->ends_at->format('M j, Y') }}
                                @elseif($subscription)
                                    Active subscription — {{ config('cashier.currency', 'USD') === 'usd' ? '$3' : config('cashier.currency', 'USD') . ' 3' }}/month
                                @else
                                    Pro features unlocked
                                @endif
                            @else
                                Free forever — upgrade anytime
                            @endif
                        </p>
                    </div>
                </div>

                @if($user->isPro())
                    <button
                        wire:click="openPortal"
                        wire:loading.attr="disabled"
                        wire:target="openPortal"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold
                               dark:border-slate-600 border-gray-300 border rounded-xl
                               dark:text-slate-300 text-gray-700
                               dark:hover:border-slate-500 hover:border-gray-400
                               dark:hover:text-white hover:text-gray-900
                               transition-all duration-150 flex-shrink-0">
                        <svg wire:loading.remove wire:target="openPortal" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/>
                        </svg>
                        <svg wire:loading wire:target="openPortal" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span wire:loading.remove wire:target="openPortal">Manage Billing</span>
                        <span wire:loading wire:target="openPortal">Opening…</span>
                    </button>
                @endif
            </div>
        </div>

        {{-- ===== PLAN COMPARISON ===== --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

            {{-- FREE PLAN --}}
            <div class="dark:bg-[#1e293b] bg-white dark:border dark:border-slate-700/60 border border-gray-200 rounded-2xl overflow-hidden flex flex-col">
                <div class="p-6 flex-1">
                    <div class="flex items-center justify-between mb-1">
                        <p class="text-xs font-bold uppercase tracking-widest dark:text-slate-500 text-gray-400">Free</p>
                        @if(!$user->isPro())
                            <span class="text-xs px-2 py-0.5 rounded font-semibold dark:bg-slate-700/60 bg-gray-100 dark:text-slate-400 text-gray-500">
                                Current
                            </span>
                        @endif
                    </div>
                    <div class="flex items-end gap-1 mt-3 mb-6">
                        <span class="font-mono text-4xl font-bold dark:text-white text-gray-900">$0</span>
                        <span class="text-sm dark:text-slate-500 text-gray-400 mb-1">/month</span>
                    </div>
                    <ul class="space-y-2.5">
                        @foreach([
                            ['✓', '1 business'],
                            ['✓', 'Unlimited books'],
                            ['✓', 'Unlimited entries'],
                            ['✓', 'Up to 2 team members'],
                            ['✗', 'PDF & CSV export'],
                            ['✗', 'Priority support'],
                        ] as [$icon, $label])
                            <li class="flex items-center gap-2.5 text-sm">
                                <span class="{{ $icon === '✓' ? 'text-green-500' : 'dark:text-slate-600 text-gray-300' }} font-bold w-4 text-center flex-shrink-0">
                                    @if($icon === '✓')
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                                        </svg>
                                    @else
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                                        </svg>
                                    @endif
                                </span>
                                <span class="{{ $icon === '✓' ? 'dark:text-slate-300 text-gray-700' : 'dark:text-slate-500 text-gray-400 line-through' }}">
                                    {{ $label }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
                <div class="px-6 pb-6">
                    <div class="w-full py-2.5 text-center text-sm font-semibold
                                dark:text-slate-500 text-gray-400
                                dark:border dark:border-slate-700/60 border border-gray-200 rounded-xl
                                cursor-default">
                        {{ $user->isPro() ? 'Free Plan' : 'Current Plan' }}
                    </div>
                </div>
            </div>

            {{-- PRO PLAN --}}
            <div class="relative dark:bg-[#1e293b] bg-white
                        dark:border border
                        dark:border-primary/40 border-primary/30
                        dark:shadow-lg dark:shadow-primary/10
                        rounded-2xl overflow-hidden flex flex-col
                        transition-shadow duration-300 dark:hover:shadow-primary/20 dark:hover:shadow-xl">

                {{-- Glow effect --}}
                <div class="absolute inset-0 dark:bg-gradient-to-b dark:from-primary/5 dark:to-transparent pointer-events-none rounded-2xl"></div>

                <div class="relative p-6 flex-1">
                    <div class="flex items-center justify-between mb-1">
                        <p class="text-xs font-bold uppercase tracking-widest text-primary dark:text-blue-light">Pro</p>
                        <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded font-semibold bg-primary/15 text-primary dark:text-blue-light">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 0 0 .95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 0 0-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 0 0-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 0 0-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 0 0 .951-.69l1.07-3.292Z"/>
                            </svg>
                            {{ $user->isPro() ? 'Current Plan' : 'Most Popular' }}
                        </span>
                    </div>
                    <div class="flex items-end gap-1 mt-3 mb-6">
                        <span class="font-mono text-4xl font-bold dark:text-white text-gray-900">$3</span>
                        <span class="text-sm dark:text-slate-500 text-gray-400 mb-1">/month</span>
                    </div>
                    <ul class="space-y-2.5">
                        @foreach([
                            ['✓', 'Unlimited businesses'],
                            ['✓', 'Unlimited books'],
                            ['✓', 'Unlimited entries'],
                            ['✓', 'Unlimited team members'],
                            ['✓', 'PDF & CSV export'],
                            ['✓', 'Priority support'],
                        ] as [$icon, $label])
                            <li class="flex items-center gap-2.5 text-sm">
                                <span class="text-green-500 font-bold w-4 text-center flex-shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                                    </svg>
                                </span>
                                <span class="dark:text-slate-300 text-gray-700">{{ $label }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="relative px-6 pb-6">
                    @if($user->isPro())
                        <div class="w-full py-2.5 text-center text-sm font-semibold
                                    text-primary dark:text-blue-light
                                    border border-primary/30 rounded-xl
                                    cursor-default">
                            Current Plan
                        </div>
                    @else
                        <button
                            wire:click="subscribe"
                            wire:loading.attr="disabled"
                            wire:target="subscribe"
                            class="w-full inline-flex items-center justify-center gap-2 py-2.5 text-sm font-semibold
                                   bg-primary hover:bg-accent text-white rounded-xl
                                   transition-all duration-200 shadow-md shadow-primary/30
                                   disabled:opacity-70 disabled:cursor-wait">
                            <svg wire:loading.remove wire:target="subscribe" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7Z"/>
                            </svg>
                            <svg wire:loading wire:target="subscribe" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span wire:loading.remove wire:target="subscribe">Upgrade to Pro</span>
                            <span wire:loading wire:target="subscribe">Redirecting…</span>
                        </button>
                        <p class="text-center text-xs dark:text-slate-500 text-gray-400 mt-2">
                            Secure checkout via Stripe · Cancel anytime
                        </p>
                    @endif
                </div>
            </div>

        </div>

        {{-- ===== PRO: BILLING MANAGEMENT ===== --}}
        @if($user->isPro())
            <div class="dark:bg-[#1e293b] bg-white dark:border dark:border-slate-700/60 border border-gray-200 rounded-2xl overflow-hidden shadow-sm">
                <div class="px-6 py-4 dark:border-b dark:border-slate-700/40 border-b border-gray-100 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-primary/10 dark:bg-primary/20 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-primary dark:text-blue-light" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="font-heading font-bold text-base dark:text-white text-gray-900">Billing Management</h2>
                        <p class="text-xs dark:text-slate-400 text-gray-500 mt-0.5">Update payment method, view invoices, or cancel</p>
                    </div>
                </div>
                <div class="p-6 flex items-center justify-between gap-4">
                    @if($user->pm_type && $user->pm_last_four)
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-7 dark:bg-slate-700 bg-gray-100 rounded flex items-center justify-center">
                                <span class="text-xs font-bold uppercase dark:text-slate-300 text-gray-600">{{ $user->pm_type }}</span>
                            </div>
                            <div>
                                <p class="text-sm font-mono dark:text-white text-gray-900">•••• {{ $user->pm_last_four }}</p>
                                <p class="text-xs dark:text-slate-500 text-gray-400">Default payment method</p>
                            </div>
                        </div>
                    @else
                        <p class="text-sm dark:text-slate-400 text-gray-500">Payment info managed via Stripe</p>
                    @endif

                    <button
                        wire:click="openPortal"
                        wire:loading.attr="disabled"
                        wire:target="openPortal"
                        class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold
                               bg-primary hover:bg-accent text-white rounded-xl
                               transition-all duration-200 shadow-md shadow-primary/25
                               disabled:opacity-70 disabled:cursor-wait flex-shrink-0">
                        <svg wire:loading.remove wire:target="openPortal" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/>
                        </svg>
                        <svg wire:loading wire:target="openPortal" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span wire:loading.remove wire:target="openPortal">Open Billing Portal</span>
                        <span wire:loading wire:target="openPortal">Opening…</span>
                    </button>
                </div>
            </div>
        @endif

    </div>

</div>
