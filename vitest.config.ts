import { defineConfig } from 'vitest/config';
import vue from '@vitejs/plugin-vue';
import { resolve } from 'path';

export default defineConfig({
    plugins: [vue()],
    test: {
        environment: 'happy-dom',
        globals: true,
        include: ['resources/ts/**/*.{test,spec}.ts'],
        coverage: {
            provider: 'v8',
            include: ['resources/ts/composables/**', 'resources/ts/lib/**', 'resources/ts/Components/**', 'resources/ts/Pages/**', 'resources/ts/Layouts/**'],
        },
    },
    resolve: {
        alias: { '@': resolve(__dirname, 'resources/ts') },
    },
});
