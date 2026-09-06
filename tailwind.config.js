import forms from '@tailwindcss/forms';
import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './app/**/*.php',
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    50: '#eef4ff',
                    100: '#d9e6ff',
                    200: '#bcd3ff',
                    300: '#8db6ff',
                    400: '#578dff',
                    500: '#3366ff',
                    600: '#1f47db',
                    700: '#1a37b0',
                    800: '#1b318c',
                    900: '#1c2f72',
                },
            },
        },
    },
    plugins: [forms],
};
