@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'dark:bg-slate-800/60 bg-white dark:border-slate-600 border-gray-300 dark:text-white text-gray-900 dark:placeholder-slate-500 placeholder-gray-400 focus:border-primary focus:ring-primary rounded-lg shadow-sm transition-colors duration-150']) }}>
