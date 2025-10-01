import forms from "@tailwindcss/forms";
import defaultTheme from "tailwindcss/defaultTheme";

/** @type {import('tailwindcss').Config} */
export default {
   content: [
      "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
      "./storage/framework/views/*.php",
      "./resources/views/**/*.blade.php",
      "./resources/js/**/*.js",
   ],
   theme: {
      extend: {
         fontFamily: {
            sans: ["Inter", ...defaultTheme.fontFamily.sans],
         },
         colors: {
            primary: {
               50: "#eff6ff",
               100: "#dbeafe",
               200: "#bfdbfe",
               300: "#93c5fd",
               400: "#60a5fa",
               500: "#3b82f6",
               600: "#2563eb",
               700: "#1d4ed8",
               800: "#1e40af",
               900: "#1e3a8a",
            },
            success: {
               DEFAULT: "#10b981",
               50: "#ecfdf5",
               100: "#d1fae5",
               500: "#10b981",
               600: "#059669",
               700: "#047857",
            },
            danger: {
               DEFAULT: "#ef4444",
               50: "#fef2f2",
               100: "#fee2e2",
               500: "#ef4444",
               600: "#dc2626",
               700: "#b91c1c",
            },
            warning: {
               DEFAULT: "#f59e0b",
               50: "#fffbeb",
               100: "#fef3c7",
               500: "#f59e0b",
               600: "#d97706",
               700: "#b45309",
            },
            info: {
               DEFAULT: "#3b82f6",
               50: "#eff6ff",
               100: "#dbeafe",
               500: "#3b82f6",
               600: "#2563eb",
               700: "#1d4ed8",
            },
         },
         spacing: {
            18: "4.5rem",
            88: "22rem",
            128: "32rem",
         },
      },
   },
   plugins: [forms],
};
