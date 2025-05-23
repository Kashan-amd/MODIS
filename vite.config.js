import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    plugins: [
        laravel({
            input: ["resources/css/app.css", "resources/js/app.js"],
            refresh: [`resources/views/**/*`],
            publicDirectory: "public",
        }),
        tailwindcss(),
    ],
    build: {
        target: ["es2022", "edge95", "firefox95", "chrome95", "safari14"],
    },
});
