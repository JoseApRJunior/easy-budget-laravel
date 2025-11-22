import laravel from "laravel-vite-plugin";
import { defineConfig } from "vite";

export default defineConfig({
   server: {
      host: "localhost",
      port: 5173,
      hmr: {
         host: "localhost",
      },
   },
   plugins: [
      laravel({
         input: [
            "resources/css/app.css",
            "legacy-css/assets/css/layout.css",
            "legacy-css/assets/css/components/alerts.css",
            "resources/js/app.js",
         ],
         refresh: true,
      }),
   ],
});
