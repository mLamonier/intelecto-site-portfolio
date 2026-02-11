import { defineConfig } from "vite";
import react from "@vitejs/plugin-react";
import { visualizer } from "rollup-plugin-visualizer";

export default defineConfig(({ mode }) => {
  const analyzeMode = mode === "analyze";

  return {
    plugins: [
      react(),
      ...(analyzeMode
        ? [
            visualizer({
              filename: "dist/bundle-report.html",
              template: "treemap",
              gzipSize: true,
              brotliSize: true,
              open: false,
            }),
            visualizer({
              filename: "dist/bundle-stats.json",
              template: "raw-data",
              gzipSize: true,
              brotliSize: true,
            }),
          ]
        : []),
    ],
    build: {
      modulePreload: false,
      rollupOptions: {
        output: {
          manualChunks(id) {
            if (!id.includes("node_modules")) {
              return undefined;
            }

            if (id.includes("swiper")) {
              return "vendor-swiper";
            }

            if (id.includes("react-router")) {
              return "vendor-router";
            }

            if (
              id.includes("react-dom") ||
              id.match(/[\\/]react[\\/]/) ||
              id.includes("scheduler")
            ) {
              return "vendor-react";
            }
            return undefined;
          },
        },
      },
    },
    server: {
      proxy: {
        "/api": {
          target: "http://localhost:80",
          changeOrigin: true,
          rewrite: (requestPath) =>
            requestPath.replace(/^\/api/, "/intelecto-site/api"),
        },
      },
    },
  };
});
