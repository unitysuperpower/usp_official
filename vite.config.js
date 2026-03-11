import {
    defineConfig
} from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from "@tailwindcss/vite";
import autoprefixer from 'autoprefixer';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    css: {
        postcss: {
            plugins: [
                autoprefixer({
                    overrideBrowserslist: [
                        'last 3 versions',
                        '> 1%',
                        'ie >= 11',
                        'iOS >= 10',
                        'Safari >= 10'
                    ]
                })
            ]
        }
    },
    server: {
        cors: true,
    },
});