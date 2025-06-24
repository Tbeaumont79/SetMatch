import { defineConfig } from "vite";
import symfonyPlugin from "vite-plugin-symfony";
import tailwindcss from '@tailwindcss/vite'

/* if you're using React */
// import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        /* react(), // if you're using React */
        symfonyPlugin({
            stimulus: true,
            stimulus: "./assets/other-dir/controllers.json",
            stimulus: {
                fetchMode: "lazy",
            },
        }),
        tailwindcss(),
    ],
    build: {
        rollupOptions: {
            input: {
                app: "./assets/app.js",
            },
        },
    },
});
