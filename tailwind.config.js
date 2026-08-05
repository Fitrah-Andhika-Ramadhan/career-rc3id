import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],
    
    darkMode: 'class',

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                "headline-xl": ["Inter"],
                "label-md": ["Inter"],
                "headline-md": ["Inter"],
                "headline-lg": ["Inter"],
                "label-sm": ["Inter"],
                "body-sm": ["Inter"],
                "body-lg": ["Inter"],
                "data-tabular": ["Inter"],
                "body-md": ["Inter"]
            },
            colors: {
                "warning": "#F9AB00",
                "surface": "#f7f9ff",
                "outline-variant": "#c1c6d6",
                "primary-fixed": "#d8e2ff",
                "outline": "#727785",
                "inverse-surface": "#2d3135",
                "on-primary-container": "#ffffff",
                "info": "#1A73E8",
                "on-error": "#ffffff",
                "surface-variant": "#dfe3e8",
                "on-tertiary-container": "#ffffff",
                "surface-dim": "#d7dae0",
                "inverse-on-surface": "#eef1f7",
                "tertiary-fixed": "#e1e3e4",
                "success": "#1E8E3E",
                "on-background": "#181c20",
                "background": "#f7f9ff",
                "secondary-container": "#dde0e3",
                "on-secondary-container": "#5f6366",
                "surface-container-lowest": "#ffffff",
                "tertiary-container": "#757778",
                "on-tertiary": "#ffffff",
                "on-primary-fixed": "#001a41",
                "secondary-fixed": "#e0e3e6",
                "on-tertiary-fixed-variant": "#454748",
                "error-container": "#ffdad6",
                "on-primary": "#ffffff",
                "secondary": "#5b5f62",
                "on-secondary-fixed": "#181c1f",
                "surface-container": "#ebeef4",
                "surface-border": "#DADCE0",
                "on-secondary": "#ffffff",
                "surface-container-low": "#f1f4fa",
                "on-tertiary-fixed": "#191c1d",
                "on-secondary-fixed-variant": "#43474a",
                "on-primary-fixed-variant": "#004493",
                "tertiary": "#5c5e60",
                "secondary-fixed-dim": "#c4c7ca",
                "surface-bg": "#FFFFFF",
                "primary-fixed-dim": "#adc7ff",
                "on-surface-variant": "#414754",
                "tertiary-fixed-dim": "#c5c7c8",
                "inverse-primary": "#adc7ff",
                "error": "#D93025",
                "on-surface": "#181c20",
                "primary-container": "#1a73e8",
                "primary": "rgb(var(--color-primary-rgb) / <alpha-value>)",
                "surface-container-highest": "#dfe3e8",
                "on-error-container": "#93000a",
                "surface-bright": "#f7f9ff",
                "surface-tint": "#005bc0",
                "surface-container-high": "#e5e8ee"
            },
            borderRadius: {
                "DEFAULT": "0.125rem",
                "lg": "0.25rem",
                "xl": "0.5rem",
                "full": "0.75rem"
            },
            spacing: {
                "stack-lg": "32px",
                "gutter": "16px",
                "stack-md": "16px",
                "container-max": "1920px",
                "unit": "4px",
                "stack-sm": "8px",
                "margin": "24px"
            },
            maxWidth: {
                "container-max": "1920px",
            },
            fontSize: {
                "headline-xl": ["32px", {"lineHeight": "40px", "letterSpacing": "-0.02em", "fontWeight": "600"}],
                "label-md": ["12px", {"lineHeight": "16px", "fontWeight": "600"}],
                "headline-md": ["20px", {"lineHeight": "28px", "fontWeight": "600"}],
                "headline-lg": ["24px", {"lineHeight": "32px", "letterSpacing": "-0.01em", "fontWeight": "600"}],
                "label-sm": ["11px", {"lineHeight": "14px", "fontWeight": "500"}],
                "body-sm": ["13px", {"lineHeight": "18px", "fontWeight": "400"}],
                "body-lg": ["16px", {"lineHeight": "24px", "fontWeight": "400"}],
                "data-tabular": ["14px", {"lineHeight": "20px", "fontWeight": "400"}],
                "body-md": ["14px", {"lineHeight": "20px", "fontWeight": "400"}]
            }
        },
    },

    plugins: [forms],
};
