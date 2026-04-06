import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
        vue(),
        VitePWA({
            registerType: 'autoUpdate',
            includeAssets: [
                'favicon.ico',
                'robots.txt',
                'icons/pwa-192x192.png',
                'icons/pwa-512x512.png',
                'icons/maskable-icon-512x512.png',
            ],
            manifest: {
                id: '/?source=pwa',
                name: 'AgriFlow',
                short_name: 'AgriFlow',
                description: 'AgriFlow operations app',
                start_url: '/',
                scope: '/',
                display: 'standalone',
                orientation: 'portrait-primary',
                background_color: '#f6f6ef',
                theme_color: '#2f6e4a',
                icons: [
                    {
                        src: '/icons/pwa-192x192.png',
                        sizes: '192x192',
                        type: 'image/png',
                    },
                    {
                        src: '/icons/pwa-512x512.png',
                        sizes: '512x512',
                        type: 'image/png',
                    },
                    {
                        src: '/icons/maskable-icon-512x512.png',
                        sizes: '512x512',
                        type: 'image/png',
                        purpose: 'maskable',
                    },
                ],
                screenshots: [
                    {
                        src: '/screenshots/desktop-home.png',
                        sizes: '1280x720',
                        type: 'image/png',
                        form_factor: 'wide',
                        label: 'AgriFlow desktop dashboard',
                    },
                    {
                        src: '/screenshots/mobile-home.png',
                        sizes: '720x1280',
                        type: 'image/png',
                        form_factor: 'narrow',
                        label: 'AgriFlow mobile dashboard',
                    },
                ],
            },
            workbox: {
                cleanupOutdatedCaches: true,
                navigateFallback: '/',
                runtimeCaching: [
                    {
                        urlPattern: /^https:\/\/fonts\.bunny\.net\/.*$/i,
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'bunny-fonts-cache',
                            expiration: {
                                maxEntries: 20,
                                maxAgeSeconds: 60 * 60 * 24 * 30,
                            },
                        },
                    },
                ],
            },
            devOptions: {
                enabled: true,
            },
        }),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
