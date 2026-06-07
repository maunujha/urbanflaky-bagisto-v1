/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        "./src/Resources/**/*.blade.php",
        "./src/Resources/**/*.js",
        "../FAQ/src/Resources/**/*.blade.php",
        "../../Gabha/RewardCoins/resources/**/*.blade.php",
        "../../Gabha/Blog/src/Resources/**/*.blade.php",
    ],

    theme: {
        container: {
            center: true,

            screens: {
                "2xl": "1440px",
            },

            padding: {
                DEFAULT: "90px",
            },
        },

        screens: {
            sm: "525px",
            md: "768px",
            lg: "1024px",
            xl: "1240px",
            "2xl": "1440px",
            1180: "1180px",
            1060: "1060px",
            991: "991px",
            868: "868px",
        },

        extend: {
            colors: {
                navyBlue: "#060C3B",
                lightOrange: "#F6F2EB",
                darkGreen: '#40994A',
                darkBlue: '#0044F2',
                darkPink: '#F85156',

                /* ── Urbanflaky Dark Theme palette ── */
                uf: {
                    bg:       '#0a0a0a',  /* page background — near black */
                    surface:  '#141414',  /* cards, sections one level up */
                    surface2: '#1c1c1c',  /* inputs, hover states */
                    border:   '#262626',  /* subtle dividers */
                    muted:    '#888888',  /* secondary text */
                    text:     '#f5f5f5',  /* primary text on dark */
                    accent:   '#c7eb31',  /* neon yellow-green CTA */
                    accentHover: '#d4f04a',
                },
            },

            fontFamily: {
                poppins: ["Poppins", "sans-serif"],
                dmserif: ["DM Serif Display", "serif"],
            },
        }
    },

    plugins: [],

    safelist: [
        {
            pattern: /icon-/,
        }
    ]
};
