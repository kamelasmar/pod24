import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                pod: ['Montserrat', 'system-ui', 'sans-serif'],
            },
            colors: {
                'pod-bg': '#FFFFFF',
                'pod-surface': '#F5F5F5',
                'pod-ink': '#1C2327',
                'pod-ink-deep': '#000000',
                'pod-accent': '#00B9E3',
                'pod-accent-deep': '#00B0F7',
                'pod-accent-soft': '#E6F7FC',
                'pod-border': '#CDCDD8',
                'pod-border-soft': '#E0E0E0',
                'pod-muted': '#9F9FAA',
            },
        },
    },
    plugins: [],
};
