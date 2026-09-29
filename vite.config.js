import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/face-register.js',
                'resources/js/face-match.js',
            ],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
    ],
    build: {
        // Human（約2MB）をまとめるとサイズ警告が出るため、上限を上げておく
        chunkSizeWarningLimit: 2500,
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});