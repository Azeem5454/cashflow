import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './app/Livewire/**/*.php',
    ],
    safelist: [
        // bg colours — dark variant
        { pattern: /^bg-slate-(700|800|900)$/, variants: ['dark', 'dark:hover'] },
        { pattern: /^bg-navy$/, variants: ['dark'] },
        { pattern: /^bg-dark$/, variants: ['dark'] },
        // border colours — dark variant
        { pattern: /^border-slate-(600|700|800)$/, variants: ['dark'] },
        // left accent borders for shared business cards
        'border-l-4', 'border-l-green-500', 'border-l-slate-500',
        { pattern: /^divide-slate-(700|800)$/, variants: ['dark'] },
        // text colours — dark variant
        { pattern: /^text-slate-(300|400|500|600)$/, variants: ['dark'] },
        { pattern: /^text-white$/, variants: ['dark'] },
        // layout (always needed)
        'flex', 'flex-1',
        'grid', 'grid-cols-2', 'grid-cols-3',
        'divide-x', 'divide-gray-200',
        // announcement banner colours
        'bg-blue-900', 'bg-amber-900', 'bg-emerald-900',
        'bg-blue-50', 'bg-amber-50', 'bg-emerald-50',
        'border-blue-700', 'border-amber-700', 'border-emerald-700',
        'border-blue-200', 'border-amber-200', 'border-emerald-200',
        'text-blue-200', 'text-amber-200', 'text-emerald-200',
        'text-blue-800', 'text-amber-800', 'text-emerald-800',
        { pattern: /^bg-(blue|amber|emerald)-900$/, variants: ['dark'] },
        { pattern: /^border-(blue|amber|emerald)-700$/, variants: ['dark'] },
        { pattern: /^text-(blue|amber|emerald)-200$/, variants: ['dark'] },
        // bg colours used directly (no variant)
        'bg-emerald-500', 'bg-red-500', 'bg-green-500', 'bg-blue-500', 'bg-amber-500', 'bg-orange-500', 'bg-slate-500',
        'hover:bg-emerald-500', 'hover:bg-red-500',
        // Health score card — colour tokens for all 6 grade levels (PHP match expressions)
        'dark:bg-blue-500/10', 'bg-blue-50', 'dark:border-blue-500/20', 'border-blue-200', 'text-blue-400', 'dark:text-blue-400/70', 'text-blue-700/70',
        'dark:bg-orange-500/10', 'bg-orange-50', 'dark:border-orange-500/20', 'border-orange-200', 'text-orange-500', 'dark:text-orange-400/70', 'text-orange-700/70',
        'dark:bg-emerald-500/10', 'bg-emerald-50', 'dark:border-emerald-500/20', 'border-emerald-200', 'text-emerald-500', 'dark:text-emerald-400/70', 'text-emerald-700/70',
        'dark:bg-amber-500/10', 'bg-amber-50', 'dark:border-amber-500/20', 'border-amber-200', 'text-amber-500', 'dark:text-amber-400/70', 'text-amber-700/70',
        'dark:bg-red-500/10', 'bg-red-50', 'dark:border-red-500/20', 'border-red-200', 'text-red-500', 'dark:text-red-400/70', 'text-red-700/70',
        // AI insights loading dots (dark:bg-blue-light used with animate-bounce)
        { pattern: /^bg-blue-light$/, variants: ['dark'] },
        // Book status icon colours (PHP match expressions — must be safelisted)
        'bg-emerald-500/10', 'dark:bg-emerald-500/15',
        'bg-blue-500/10', 'dark:bg-blue-500/15',
        'text-emerald-500', 'text-blue-400',
        'text-gray-400', 'dark:text-slate-500',
        // Active book row tint (arbitrary value)
        'dark:bg-emerald-500/[0.03]',
        // Book detail balance strip net column tint
        'dark:bg-primary/[0.04]', 'bg-primary/[0.02]',
        'dark:bg-red-500/[0.04]', 'bg-red-500/[0.02]',
        // Status border — permanent left border transitions to primary on hover
        'group-hover:bg-primary',
        // Period preset tile active states (in Alpine :class strings — must be safelisted)
        'bg-primary/10', 'dark:bg-primary/15', 'border-primary/40', 'text-primary',
        'ring-2', 'ring-primary/20', 'font-semibold',
        // Dark mode toggle pill (CSS dark: classes replacing Alpine bindings)
        'dark:bg-primary', 'dark:translate-x-4',
        // Notification bell dropdown positioning
        'top-full', 'mt-2', 'right-0', 'origin-top-right', 'bottom-full', 'mb-2', 'origin-bottom-left',
    ],

    theme: {
        extend: {
            colors: {
                navy:           'rgb(var(--color-navy) / <alpha-value>)',
                dark:           'rgb(var(--color-dark) / <alpha-value>)',
                primary:        'rgb(var(--color-primary) / <alpha-value>)',
                accent:         'rgb(var(--color-accent) / <alpha-value>)',
                'blue-light':   'rgb(var(--color-blue-light) / <alpha-value>)',
                'blue-xlight':  'rgb(var(--color-blue-xlight) / <alpha-value>)',
            },
            keyframes: {
                shimmer: {
                    '0%':   { transform: 'translateX(-100%)' },
                    '100%': { transform: 'translateX(200%)' },
                },
            },
            animation: {
                shimmer: 'shimmer 1.8s infinite',
            },
            fontFamily: {
                sans:    ['var(--font-body, Outfit)', ...defaultTheme.fontFamily.sans],
                display: ['var(--font-display, Bricolage Grotesque)', 'sans-serif'],
                heading: ['var(--font-heading, Plus Jakarta Sans)', 'sans-serif'],
                body:    ['var(--font-body, Outfit)', 'sans-serif'],
                mono:    ['var(--font-mono, Geist Mono)', 'monospace'],
            },
        },
    },

    plugins: [forms],
};
