import { defineConfig } from "vite";
import symfonyPlugin from "vite-plugin-symfony";
import tailwindcss from "@tailwindcss/vite";

/* if you're using React */
// import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        /* react(), // if you're using React */
        symfonyPlugin({
            stimulus: "./assets/other-dir/controllers.json",
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
    server: {
        proxy: {
            "/mercure": {
                target: "https://demo.mercure.rocks/.well-known/mercure",
                changeOrigin: true,
                rewrite: (path) => path.replace(/^\/mercure/, ""),
                configure: (proxy, options) => {
                    proxy.on("proxyReq", (proxyReq, req, res) => {
                        proxyReq.setHeader("Accept", "text/event-stream");
                        proxyReq.setHeader("Cache-Control", "no-cache");
                    });
                },
            },
        },
    },
});
