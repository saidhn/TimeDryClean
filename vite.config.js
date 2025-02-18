import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app_ltr.css',
                'resources/css/app_rtl.css',
                'resources/css/shared_all.css',
                'resources/js/app.js'
            ],
            refresh: true,
        }),
    ],
});
