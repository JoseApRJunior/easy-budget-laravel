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
            "public/assets/css/layout.css",
            "public/assets/css/components/alerts.css",
         ],
         refresh: true,
      }),
   ],
});
