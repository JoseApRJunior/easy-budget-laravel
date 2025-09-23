import laravel from "laravel-vite-plugin";
import { defineConfig } from "vite";

export default defineConfig({
   plugins: [
      laravel({
         input: [
            "resources/css/app.css",
            "resources/js/app.js",
            // Assets do sistema antigo
            "resources/css/oldsystem/main.css",
            "resources/css/oldsystem/layout.css",
            "resources/css/oldsystem/home.css",
            "resources/css/oldsystem/monitoring.css",
            "resources/css/oldsystem/timeline.css",
            "resources/css/oldsystem/alert/alert.css",
            "resources/js/oldsystem/main.js",
            "resources/js/oldsystem/login.js",
            "resources/js/oldsystem/home.js",
            "resources/js/oldsystem/budget.js",
            "resources/js/oldsystem/invoice.js",
            "resources/js/oldsystem/customer.js",
            "resources/js/oldsystem/product.js",
            "resources/js/oldsystem/service.js",
            "resources/js/oldsystem/monitoring.js",
            "resources/js/oldsystem/settings.js",
            "resources/js/oldsystem/alert/alert.js",
            "resources/js/oldsystem/modules/utils.js",
            "resources/js/oldsystem/modules/auth.js",
            "resources/js/oldsystem/modules/form-validation.js",
            "resources/js/oldsystem/modules/masks/index.js",
         ],
         refresh: true,
      }),
   ],
   css: {
      postcss: {
         plugins: [
            require('tailwindcss'),
            require('autoprefixer'),
         ],
      },
   },
   build: {
      rollupOptions: {
         output: {
            manualChunks: undefined,
         },
      },
   },
});
