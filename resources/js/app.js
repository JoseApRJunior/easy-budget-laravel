// Arquivo principal de JavaScript da aplicação
// Este arquivo serve como ponto de entrada para o Vite

// Importar CSS files
import "../css/variables.css";
import "../css/layout.css";
import "../css/alerts.css";

// Importações globais podem ser adicionadas aqui conforme necessário
import "./main.js";
import "./modules/utils.js";

// Função para inicializar a aplicação
document.addEventListener("DOMContentLoaded", function () {
   // Inicializar funcionalidades globais
   initializeGlobalFeatures();
});

// Função para inicializar funcionalidades globais
function initializeGlobalFeatures() {
   // Configuração de tema
   initializeTheme();

   // Configuração de tooltips
   initializeTooltips();

   // Configuração de modals
   initializeModals();
}

// Inicializar sistema de temas
function initializeTheme() {
   // Inicializar tema usando a função do módulo utils
   if (window.utils && window.utils.initTheme) {
      window.utils.initTheme();
   }

   const theme = localStorage.getItem("theme") || "light";
   document.documentElement.setAttribute("data-theme", theme);

   // Listener para mudanças de tema
   document.addEventListener("themeChanged", function (e) {
      localStorage.setItem("theme", e.detail.theme);
      document.documentElement.setAttribute("data-theme", e.detail.theme);
   });
}

// Inicializar tooltips
function initializeTooltips() {
   const tooltipTriggerList = [].slice.call(
      document.querySelectorAll('[data-bs-toggle="tooltip"]')
   );
   tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl);
   });
}

// Inicializar modals
function initializeModals() {
   const modalList = [].slice.call(document.querySelectorAll(".modal"));
   modalList.map(function (modalEl) {
      return new bootstrap.Modal(modalEl);
   });
}

// Exportar funções para uso global
window.EasyBudget = {
   initializeTheme,
   initializeTooltips,
   initializeModals,
};
