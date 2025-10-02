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
                brand: {
                    DEFAULT: '#6F3CC3',
                },
                accent: {
                    DEFAULT: '#10BCE0',
                },
                surface: {
                    100: '#121A29',
                    200: '#151E31',
                },
                text: {
                    primary: '#E6EEF8',
                    muted: '#9FB0C8',
                },
            },
        },
    },
};
