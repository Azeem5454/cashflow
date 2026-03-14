import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
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
