import laravel from "laravel-vite-plugin";
import { defineConfig } from "vite";

export default defineConfig({
   plugins: [
      laravel({
         input: ["resources/css/app.css", "resources/js/app.js"],
         refresh: [
            "resources/views/**/*.blade.php",
            "resources/js/**/*.js",
            "resources/css/**/*.css",
         ],
      }),
   ],
   resolve: {
      alias: {
         "@": "/resources/js",
         "@css": "/resources/css",
      },
   },
   build: {
      manifest: "manifest.json",
      outDir: "public/build",
      rollupOptions: {
         output: {
            manualChunks: {
               alpine: ["alpinejs"],
            },
         },
      },
   },
   server: {
      hmr: {
         host: "localhost",
      },
   },
});
