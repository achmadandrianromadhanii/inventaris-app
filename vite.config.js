import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";

export default defineConfig({
    plugins: [
        laravel({
            input: ["resources/css/app.css", "resources/js/app.js"],
            refresh: true,
        }),
    ],
    // [OPTIMASI LIGHTHOUSE]: Memaksa server lokal Vite memberikan header Cache-Control
    // Ini akan menghilangkan peringatan "Use efficient cache lifetimes" di Lighthouse.
    server: {
        headers: {
            "Cache-Control": "public, max-age=31536000",
        },
    },
});
