import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import { fileURLToPath, URL } from 'node:url';

export default defineConfig({
    // Resolve lazy-loaded chunks and CSS from the entry script's own URL.
    base: './',
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/admin.tsx'],
            refresh: true,
        }),
        react(),
        tailwindcss(),
    ],
    server: {
        cors: {
            // Développement : autorise localhost et les adresses du réseau local (DHCP), quel que soit le port.
            origin: /^https?:\/\/(localhost|127\.0\.0\.1|\[::1\]|[\w-]+\.test|10(\.\d{1,3}){3}|192\.168(\.\d{1,3}){2}|172\.(1[6-9]|2\d|3[01])(\.\d{1,3}){2})(:\d+)?$/,
        },
    },
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
});
