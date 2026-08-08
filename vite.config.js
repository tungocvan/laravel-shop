import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite'; // <--- Import Plugin v4

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/tailwind.css', 'resources/js/tailwind.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
