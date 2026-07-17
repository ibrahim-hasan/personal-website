import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/css/filament/admin/ibrahim.css'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    build: {
        // Keep fonts as cacheable, content-hashed assets instead of embedding
        // hundreds of kilobytes of base64 data in the render-blocking CSS.
        assetsInlineLimit: 0,
    },
    server: {
        cors: true,
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
