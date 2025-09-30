import laravel from "laravel-vite-plugin";

export default {
   plugins: [
      laravel({
         input: [
            "resources/css/variables.css",
            "resources/css/layout.css",
            "resources/css/alerts.css",
            "resources/js/app.js",
         ],
         refresh: true,
      }),
   ],
   server: {
      host: "localhost",
      port: 5173,
      cors: true,
      hmr: {
         host: "localhost",
      },
   },
   build: {
      outDir: "public/build",
   },
};
