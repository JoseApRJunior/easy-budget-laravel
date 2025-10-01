import focus from "@alpinejs/focus";
import mask from "@alpinejs/mask";
import Alpine from "alpinejs";
import "./bootstrap";

// Register Alpine plugins
Alpine.plugin(mask);
Alpine.plugin(focus);

// Global Alpine Components
Alpine.data("dropdown", () => ({
   open: false,
   toggle() {
      this.open = !this.open;
   },
   close() {
      this.open = false;
   },
}));

Alpine.data("modal", () => ({
   open: false,
   show() {
      this.open = true;
      document.body.style.overflow = "hidden";
   },
   hide() {
      this.open = false;
      document.body.style.overflow = "";
   },
}));

Alpine.data("tabs", (defaultTab = 0) => ({
   activeTab: defaultTab,
   setTab(index) {
      this.activeTab = index;
   },
   isActive(index) {
      return this.activeTab === index;
   },
}));

Alpine.data("passwordToggle", () => ({
   show: false,
   toggle() {
      this.show = !this.show;
   },
}));

Alpine.data("alert", () => ({
   show: true,
   dismiss() {
      this.show = false;
   },
}));

Alpine.data("sidebar", () => ({
   open: false,
   toggle() {
      this.open = !this.open;
   },
}));

// Initialize Alpine
window.Alpine = Alpine;
Alpine.start();

// CSRF Token Setup for Fetch
document.addEventListener("DOMContentLoaded", function () {
   const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

   if (csrfToken) {
      // Add CSRF token to all fetch requests
      const originalFetch = window.fetch;
      window.fetch = function () {
         let [resource, config] = arguments;

         if (!config) {
            config = {};
         }

         if (!config.headers) {
            config.headers = {};
         }

         // Add CSRF token if not already present
         if (!config.headers["X-CSRF-TOKEN"]) {
            config.headers["X-CSRF-TOKEN"] = csrfToken;
         }

         return originalFetch(resource, config);
      };
   }
});

// Utility Functions
window.copyToClipboard = async function (text) {
   try {
      await navigator.clipboard.writeText(text);
      return true;
   } catch (err) {
      console.error("Failed to copy:", err);
      return false;
   }
};

window.formatCurrency = function (value) {
   return new Intl.NumberFormat("pt-BR", {
      style: "currency",
      currency: "BRL",
   }).format(value);
};

window.formatDate = function (date, format = "short") {
   const options = {
      short: { year: "numeric", month: "2-digit", day: "2-digit" },
      long: { year: "numeric", month: "long", day: "numeric" },
      full: { weekday: "long", year: "numeric", month: "long", day: "numeric" },
   };

   return new Intl.DateTimeFormat(
      "pt-BR",
      options[format] || options.short
   ).format(new Date(date));
};

// Auto-hide alerts after 5 seconds
document.addEventListener("DOMContentLoaded", function () {
   const alerts = document.querySelectorAll('[x-data*="alert"]');
   alerts.forEach((alert) => {
      setTimeout(() => {
         const dismissButton = alert.querySelector('[x-on\\:click*="dismiss"]');
         if (dismissButton) {
            dismissButton.click();
         }
      }, 5000);
   });
});
