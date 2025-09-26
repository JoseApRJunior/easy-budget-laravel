import laravel from "laravel-vite-plugin";
import { defineConfig } from "vite";

export default defineConfig({
   plugins: [
      laravel({
         input: [
            // Assets CSS
            "resources/css/variables.css",
            "resources/css/alerts.css",
            "resources/css/layout.css",
            "resources/css/navigation-improvements.css",
            // Assets JavaScript
            "resources/js/app.js",
            "resources/js/main.js",
            "resources/js/login.js",
            "resources/js/home.js",
            "resources/js/budget.js",
            "resources/js/invoice.js",
            "resources/js/customer.js",
            "resources/js/product.js",
            "resources/js/service.js",
            "resources/js/monitoring.js",
            "resources/js/settings.js",
            "resources/js/alert/alert.js",
            "resources/js/modules/utils.js",
            "resources/js/modules/auth.js",
            "resources/js/modules/form-validation.js",
            "resources/js/modules/masks/index.js",
         ],
         refresh: true,
      }),
   ],
});
