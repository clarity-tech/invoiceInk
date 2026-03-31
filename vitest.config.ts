import { defineConfig } from 'vitest/config';
import vue from '@vitejs/plugin-vue';
import { resolve } from 'path';

export default defineConfig({
    plugins: [vue()],
    test: {
        environment: 'happy-dom',
        globals: true,
        include: ['resources/js/**/*.{test,spec}.ts'],
        coverage: {
            provider: 'v8',
            include: [
                'resources/js/composables/**',
                'resources/js/lib/**',
                'resources/js/Components/**',
                'resources/js/Pages/**',
                'resources/js/Layouts/**',
            ],
        },
    },
    resolve: {
        alias: { '@': resolve(__dirname, 'resources/js') },
    },
});
