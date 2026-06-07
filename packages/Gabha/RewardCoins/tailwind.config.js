/**
 * Tailwind config stub for the Gabha RewardCoins package.
 *
 * Brand palette is exposed here so package-scoped builds (and any future
 * standalone compilation) resolve `coins-yellow` / `coins-black` utility
 * classes. When these views are compiled by the Shop theme instead, add this
 * package's blade glob to the Shop tailwind `content` array (same pattern the
 * FAQ package uses).
 *
 * @type {import('tailwindcss').Config}
 */
export default {
    content: [
        "./resources/views/**/*.blade.php",
    ],
    theme: {
        extend: {
            colors: {
                "coins-yellow": "#c7eb31",
                "coins-black": "#000000",
            },
        },
    },
    plugins: [],
};
