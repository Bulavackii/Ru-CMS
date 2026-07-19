import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css', 
                'resources/js/app.js',
                'resources/js/app.jsx', // React приложение (если нужно)
            ],
            refresh: true,
        }),
        react(), // Поддержка React для админки
    ],
    resolve: {
        alias: {
            '@': '/resources',
        },
    },
    build: {
        // Оптимизация сборки
        rollupOptions: {
            output: {
                manualChunks(id) {
                    // Разделение vendor chunks
                    if (id.includes('node_modules')) {
                        if (id.includes('react') || id.includes('react-dom')) {
                            return 'vendor-react';
                        }
                        if (id.includes('react-router')) {
                            return 'vendor-router';
                        }
                        return 'vendor';
                    }
                },
            },
        },
        // Увеличиваем лимит предупреждений
        chunkSizeWarningLimit: 1000,
    },
    // Настройки для разработки
    server: {
        hmr: {
            host: 'localhost',
        },
    },
});
