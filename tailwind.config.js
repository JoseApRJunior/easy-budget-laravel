import forms from "@tailwindcss/forms";
import defaultTheme from "tailwindcss/defaultTheme";

/** @type {import('tailwindcss').Config} */
export default {
   content: [
      "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
      "./storage/framework/views/*.php",
      "./resources/views/**/*.blade.php",
   ],

   theme: {
      extend: {
         fontFamily: {
            sans: ["Figtree", ...defaultTheme.fontFamily.sans],
         },
         colors: {
            primary: "#093172",
            secondary: "#059669",
            "secondary-dark": "#047857",
            light: {
               primary: "#093172",
               secondary: "#94a3b8",
               background: "#c3d0dd",
               surface: "#9facb9",
               text: "#1e293b",
            },
            dark: {
               primary: "#093172",
               secondary: "#1e293b",
               background: "#58667a",
               surface: "#334155",
               text: "#f8fafc",
            },
            status: {
               success: "#059669",
               error: "#dc2626",
               info: "#163881",
               warning: "#d97706",
            },
         },
      },
   },

   plugins: [forms],
};
