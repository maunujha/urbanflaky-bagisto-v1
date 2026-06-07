import { defineConfig, loadEnv } from "vite";
import laravel from "laravel-vite-plugin";

/**
 * Vite stub for the Gabha RewardCoins package.
 *
 * The package ships zero custom CSS today — every Blade view is built with
 * Tailwind utility classes only (see CLAUDE.md / project conventions). This
 * config exists so future package-scoped assets can be compiled in isolation
 * without touching the Shop theme's pipeline.
 *
 * To activate later: drop entry files under `resources/assets/{css,js}` and
 * uncomment them in `input` below, then run `npm run build` from this folder.
 */
export default defineConfig(({ mode }) => {
    const envDir = "../../../";

    Object.assign(process.env, loadEnv(mode, envDir));

    return {
        envDir,

        build: {
            emptyOutDir: true,
            minify: "esbuild",
        },

        server: {
            host: process.env.VITE_HOST || "localhost",
            port: process.env.VITE_PORT || 5174,
            cors: true,
        },

        plugins: [
            laravel({
                hotFile: "../../../public/reward-coins-vite.hot",
                publicDirectory: "../../../public",
                buildDirectory: "themes/reward-coins/build",
                input: [
                    // "resources/assets/css/app.css",
                    // "resources/assets/js/app.js",
                ],
                refresh: true,
                preload: false,
            }),
        ],
    };
});
