import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            // Design tokens — see docs/specs/DESIGN.md
            colors: {
                cobalt: { DEFAULT: '#2338C4', ink: '#18248A' },
                cream: '#F3EEE3',
                paper: '#FBF8F1',
                brick: '#B3261E',
            },
            fontFamily: {
                sans: ['Anuphan', ...defaultTheme.fontFamily.sans],
                script: ['Sriracha', 'cursive'],
            },
            borderRadius: {
                card: '22px',
                hero: '28px',
            },
        },
    },

    plugins: [forms],
};
