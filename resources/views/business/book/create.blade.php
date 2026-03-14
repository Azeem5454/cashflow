<x-app-layout>
    <div class="min-h-full">
        <div class="px-6 lg:px-8 py-5 sticky top-0 z-10 backdrop-blur-sm
                    dark:bg-navy/90 bg-white/90
                    dark:border-b dark:border-slate-800/60 border-b border-gray-200">
            <div class="max-w-6xl mx-auto flex items-center gap-3">
                <a href="{{ route('businesses.show', $business) }}"
                   class="p-2 rounded-xl dark:text-slate-500 text-gray-400
                          dark:hover:bg-slate-800 hover:bg-gray-100
                          dark:hover:text-white hover:text-gray-700
                          transition-all duration-150 flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                    </svg>
                </a>
                <div>
                    <h1 class="font-display font-extrabold text-xl dark:text-white text-gray-900 tracking-tight leading-none">
                        New Book
                    </h1>
                    <p class="text-xs dark:text-slate-500 text-gray-400 font-mono mt-0.5">
                        {{ $business->name }}
                    </p>
                </div>
            </div>
        </div>
        <div class="px-6 lg:px-8 py-16 max-w-6xl mx-auto">
            <div class="dark:bg-dark bg-white dark:border-slate-700/60 border-2 border-dashed border-gray-200
                        rounded-2xl px-8 py-20 text-center relative overflow-hidden">
                <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                    <div class="w-72 h-72 bg-primary/5 rounded-full blur-3xl"></div>
                </div>
                <div class="relative">
                    <div class="w-14 h-14 rounded-2xl bg-primary/10 dark:bg-primary/15
                                flex items-center justify-center mx-auto mb-4 shadow-lg shadow-primary/10">
                        <svg class="w-7 h-7 text-primary" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"/>
                        </svg>
                    </div>
                    <h2 class="font-heading font-bold text-lg dark:text-white text-gray-900 mb-2">Create Book — coming next</h2>
                    <p class="text-sm dark:text-slate-400 text-gray-500 mb-6 max-w-sm mx-auto leading-relaxed">
                        Book creation is the next step in the build order.
                    </p>
                    <a href="{{ route('businesses.show', $business) }}"
                       class="inline-flex items-center gap-2 px-5 py-2.5
                              dark:bg-primary/10 bg-primary/5
                              hover:bg-primary dark:text-blue-light text-primary hover:text-white
                              text-sm font-semibold rounded-xl transition-all duration-200">
                        ← Back to {{ $business->name }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
