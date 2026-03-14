<x-app-layout>
    <div class="min-h-full">
        <div class="px-6 lg:px-8 py-7
                    dark:bg-navy bg-white
                    dark:border-b dark:border-slate-800 border-b border-gray-200
                    sticky top-0 z-10 backdrop-blur-sm">
            <div class="max-w-3xl mx-auto">
                <h1 class="font-display font-extrabold text-2xl dark:text-white text-gray-900 tracking-tight leading-none">
                    Billing & Plans
                </h1>
                <p class="text-sm dark:text-slate-500 text-gray-400 mt-1 font-body">
                    Manage your subscription
                </p>
            </div>
        </div>

        <div class="px-6 lg:px-8 py-16 max-w-3xl mx-auto">
            <div class="dark:bg-dark bg-white dark:border-slate-700/60 border-2 border-dashed border-gray-200
                        rounded-2xl px-8 py-20 text-center relative overflow-hidden">
                <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                    <div class="w-80 h-80 bg-amber-400/5 rounded-full blur-3xl"></div>
                </div>
                <div class="relative">
                    <div class="w-16 h-16 rounded-2xl bg-amber-400/10 flex items-center justify-center mx-auto mb-5">
                        <svg class="w-8 h-8 text-amber-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z"/>
                        </svg>
                    </div>
                    <h2 class="font-heading font-bold text-xl dark:text-white text-gray-900 mb-2">Billing coming soon</h2>
                    <p class="text-sm dark:text-slate-400 text-gray-500 mb-8 max-w-sm mx-auto">
                        Stripe integration and plan management will be built here.
                    </p>
                    <a href="{{ route('dashboard') }}"
                       class="inline-flex items-center gap-2 px-6 py-3
                              dark:bg-primary/10 bg-primary/5 hover:bg-primary
                              dark:text-blue-light text-primary hover:text-white
                              text-sm font-semibold rounded-xl transition-all duration-200">
                        ← Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
