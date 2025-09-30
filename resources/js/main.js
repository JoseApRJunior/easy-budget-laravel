// Arquivo principal de funcionalidades JavaScript
// Este arquivo contém as funcionalidades principais da aplicação

// Importar dependências necessárias
import "./modules/utils.js";

// Configurações globais
window.EasyBudgetConfig = {
   apiBaseUrl: "/api",
   csrfToken: document
      .querySelector('meta[name="csrf-token"]')
      ?.getAttribute("content"),
   theme: localStorage.getItem("theme") || "light",
};

// Classe principal da aplicação
class EasyBudgetApp {
   constructor() {
      this.initialized = false;
      this.modules = {};
   }

   // Inicializar aplicação
   async init() {
      if (this.initialized) return;

      try {
         // Inicializar módulos
         await this.initializeModules();

         // Configurar eventos globais
         this.setupGlobalEvents();

         // Configurar AJAX global
         this.setupAjaxConfig();

         this.initialized = true;
      } catch (error) {
         console.error("Erro ao inicializar aplicação:", error);
      }
   }

   // Inicializar módulos
   async initializeModules() {
      // Módulo de autenticação
      if (window.AuthModule) {
         this.modules.auth = new window.AuthModule();
         await this.modules.auth.init();
      }

      // Módulo de orçamento
      if (window.BudgetModule) {
         this.modules.budget = new window.BudgetModule();
         await this.modules.budget.init();
      }

      // Módulo de produtos
      if (window.ProductModule) {
         this.modules.product = new window.ProductModule();
         await this.modules.product.init();
      }
   }

   // Configurar eventos globais
   setupGlobalEvents() {
      // Evento de mudança de tema
      document.addEventListener("themeChanged", (e) => {
         window.EasyBudgetConfig.theme = e.detail.theme;
      });

      // Evento de erro global
      window.addEventListener("error", (e) => {
         console.error("Erro global capturado:", e.error);
      });

      // Evento de unhandled promise rejection
      window.addEventListener("unhandledrejection", (e) => {
         console.error("Promise rejeitada não tratada:", e.reason);
      });
   }

   // Configurar AJAX global
   setupAjaxConfig() {
      // Configurar headers padrão para fetch
      const originalFetch = window.fetch;
      window.fetch = function (...args) {
         const [resource, config = {}] = args;

         // Adicionar headers padrão
         config.headers = {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": window.EasyBudgetConfig.csrfToken,
            Accept: "application/json",
            ...config.headers,
         };

         return originalFetch(resource, config);
      };
   }
}

// Inicializar aplicação quando DOM estiver pronto
document.addEventListener("DOMContentLoaded", async () => {
   const app = new EasyBudgetApp();
   await app.init();

   // Tornar app globalmente acessível
   window.EasyBudgetApp = app;
});

// Exportar para uso em outros módulos
export { EasyBudgetApp };
