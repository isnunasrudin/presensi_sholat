import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue(),
        tailwindcss(),
    ],
    resolve: {
        alias: {
            vue: 'vue/dist/vue.esm-bundler.js',
        },
    },
    css: {
        postcss: {
            plugins: [
                // Add any additional PostCSS plugins if needed
            ],
        },
        devSourcemap: true,
    },
    build: {
        cssCodeSplit: true,
        rollupOptions: {
            output: {
                manualChunks: {
                    'primevue': ['primevue'],
                    'vue': ['vue', 'vue-router', 'pinia'],
                },
            },
        },
    },
});
