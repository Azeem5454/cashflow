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
        { pattern: /^divide-slate-700$/, variants: ['dark'] },
        // text colours — dark variant
        { pattern: /^text-slate-(300|400|500|600)$/, variants: ['dark'] },
        { pattern: /^text-white$/, variants: ['dark'] },
        // layout (always needed)
        'flex', 'flex-1',
        'grid', 'grid-cols-2', 'grid-cols-3',
        'divide-x', 'divide-gray-200',
        // bg colours used directly (no variant)
        'bg-emerald-500', 'bg-red-500',
        'hover:bg-emerald-500', 'hover:bg-red-500',
    ],

    theme: {
        extend: {
            colors: {
                navy:           '#0a0f1e',
                dark:           '#111827',
                primary:        '#1a56db',
                accent:         '#3b82f6',
                'blue-light':   '#93c5fd',
                'blue-xlight':  '#dbeafe',
            },
            fontFamily: {
                sans:    ['Outfit', ...defaultTheme.fontFamily.sans],
                display: ['Bricolage Grotesque', 'sans-serif'],
                heading: ['Plus Jakarta Sans', 'sans-serif'],
                body:    ['Outfit', 'sans-serif'],
                mono:    ['Geist Mono', 'monospace'],
            },
        },
    },

    plugins: [forms],
};
