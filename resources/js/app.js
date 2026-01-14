import "./bootstrap.js";

import Alpine from "alpinejs";

window.Alpine = Alpine;

Alpine.start();

window.__VITE_ACTIVE__ = true;
console.info("Vite carregado: resources/js/app.js ativo");

const page = document.body.dataset.page || "default";
(async () => {
  try {
    const mod = await import(`./pages/${page}.js`);
    if (mod && typeof mod.default === "function") mod.default();
  } catch (e) {}
})();

import "./modules/easy-alert.js";
