<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center px-4 py-2.5 dark:bg-slate-700 bg-white dark:border-slate-600 border border-gray-300 rounded-xl font-semibold text-xs dark:text-slate-300 text-gray-700 uppercase tracking-widest shadow-sm dark:hover:bg-slate-600 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 dark:focus:ring-offset-slate-800 disabled:opacity-25 transition-all duration-150']) }}>
    {{ $slot }}
</button>
