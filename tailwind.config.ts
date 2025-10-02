import preset from '@filament/support/tailwind.config.preset';

export default {
    presets: [preset],
    darkMode: 'class',
    content: [
        './app/Filament/**/*.php',
        './app/Forms/**/*.php',
        './app/Tables/**/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
        './vendor/filament/**/*.blade.php',
    ],
    theme: {
        extend: {
            colors: {
                // Brand color system
                brand: {
                    DEFAULT: '#2563eb', // primary blue
                    50: '#dbeafe',
                    100: '#bfdbfe',
                    200: '#93c5fd',
                    300: '#60a5fa',
                    400: '#3b82f6',
                    500: '#2563eb',
                    600: '#1d4ed8',
                    700: '#1e40af',
                    800: '#1e3a8a',
                    900: '#1e3a8a',
                },
                accent: {
                    DEFAULT: '#10b981', // emerald green
                    50: '#ecfdf5',
                    100: '#d1fae5',
                    200: '#a7f3d0',
                    300: '#6ee7b7',
                    400: '#34d399',
                    500: '#10b981',
                    600: '#059669',
                    700: '#047857',
                    800: '#065f46',
                    900: '#064e3b',
                },
                ink: {
                    DEFAULT: '#0f172a', // darkest slate
                    50: '#f8fafc',
                    100: '#f1f5f9',
                    200: '#e2e8f0',
                    300: '#cbd5e1',
                    400: '#94a3b8',
                    500: '#64748b',
                    600: '#475569',
                    700: '#334155',
                    800: '#1e293b',
                    900: '#0f172a',
                },
                'muted-ink': '#334155',
                surface: '#f8fafc',
                card: '#ffffff',
                // Dark mode colors
                dark: {
                    surface: '#0b1220',
                    card: '#0f172a',
                    ink: '#e2e8f0',
                },
                // Status colors
                success: {
                    DEFAULT: '#22c55e',
                    50: '#f0fdf4',
                    100: '#dcfce7',
                    200: '#bbf7d0',
                    300: '#86efac',
                    400: '#4ade80',
                    500: '#22c55e',
                    600: '#16a34a',
                    700: '#15803d',
                    800: '#166534',
                    900: '#14532d',
                },
                warning: {
                    DEFAULT: '#f59e0b',
                    50: '#fffbeb',
                    100: '#fef3c7',
                    200: '#fde68a',
                    300: '#fcd34d',
                    400: '#fbbf24',
                    500: '#f59e0b',
                    600: '#d97706',
                    700: '#b45309',
                    800: '#92400e',
                    900: '#78350f',
                },
                danger: {
                    DEFAULT: '#ef4444',
                    50: '#fef2f2',
                    100: '#fee2e2',
                    200: '#fecaca',
                    300: '#fca5a5',
                    400: '#f87171',
                    500: '#ef4444',
                    600: '#dc2626',
                    700: '#b91c1c',
                    800: '#991b1b',
                    900: '#7f1d1d',
                },
                info: {
                    DEFAULT: '#06b6d4',
                    50: '#f0f9ff',
                    100: '#e0f2fe',
                    200: '#bae6fd',
                    300: '#7dd3fc',
                    400: '#38bdf8',
                    500: '#06b6d4',
                    600: '#0891b2',
                    700: '#0e7490',
                    800: '#155e75',
                    900: '#164e63',
                },
            },
            borderRadius: {
                xl: '0.75rem',
                '2xl': '1rem',
                '3xl': '1.5rem',
            },
            fontFamily: {
                sans: ['Inter', 'system-ui', 'sans-serif'],
                arabic: ['Cairo', 'IBM Plex Sans Arabic', 'sans-serif'],
            },
        },
    },
    plugins: [
        // Add a plugin to handle RTL-specific styles
        function({ addVariant }) {
            addVariant('rtl', '.rtl &');
            addVariant('dark:rtl', '.dark.rtl &');
        }
    ],
};