// Módulo de utilidades JavaScript
// Contém funções auxiliares para toda a aplicação

// Classe principal de utilidades
class Utils {
   constructor() {
      this.initialized = false;
   }

   // Inicializar módulo
   init() {
      if (this.initialized) return;

      this.initialized = true;
   }

   // Formatação de moeda
   formatCurrency(value, locale = "pt-BR", currency = "BRL") {
      return new Intl.NumberFormat(locale, {
         style: "currency",
         currency: currency,
      }).format(value);
   }

   // Formatação de data
   formatDate(date, options = {}) {
      const defaultOptions = {
         year: "numeric",
         month: "2-digit",
         day: "2-digit",
      };

      const formatOptions = { ...defaultOptions, ...options };

      if (typeof date === "string") {
         date = new Date(date);
      }

      return new Intl.DateTimeFormat("pt-BR", formatOptions).format(date);
   }

   // Formatação de data e hora
   formatDateTime(date, options = {}) {
      const defaultOptions = {
         year: "numeric",
         month: "2-digit",
         day: "2-digit",
         hour: "2-digit",
         minute: "2-digit",
      };

      const formatOptions = { ...defaultOptions, ...options };

      if (typeof date === "string") {
         date = new Date(date);
      }

      return new Intl.DateTimeFormat("pt-BR", formatOptions).format(date);
   }

   // Validação de email
   isValidEmail(email) {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      return emailRegex.test(email);
   }

   // Validação de CPF
   isValidCPF(cpf) {
      cpf = cpf.replace(/[^\d]+/g, "");

      if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) {
         return false;
      }

      let sum = 0;
      let remainder;

      for (let i = 1; i <= 9; i++) {
         sum += parseInt(cpf.substring(i - 1, i)) * (11 - i);
      }

      remainder = (sum * 10) % 11;
      if (remainder === 10 || remainder === 11) remainder = 0;
      if (remainder !== parseInt(cpf.substring(9, 10))) return false;

      sum = 0;
      for (let i = 1; i <= 10; i++) {
         sum += parseInt(cpf.substring(i - 1, i)) * (12 - i);
      }

      remainder = (sum * 10) % 11;
      if (remainder === 10 || remainder === 11) remainder = 0;
      if (remainder !== parseInt(cpf.substring(10, 11))) return false;

      return true;
   }

   // Validação de CNPJ
   isValidCNPJ(cnpj) {
      cnpj = cnpj.replace(/[^\d]+/g, "");

      if (cnpj.length !== 14 || /^(\d)\1{13}$/.test(cnpj)) {
         return false;
      }

      let sum = 0;
      let pos = 0;

      // Cálculo do primeiro dígito verificador
      for (let i = 5; i >= 2; i--) {
         sum += parseInt(cnpj.charAt(pos++)) * i;
      }
      for (let i = 9; i >= 2; i--) {
         sum += parseInt(cnpj.charAt(pos++)) * i;
      }

      let digit = sum % 11 < 2 ? 0 : 11 - (sum % 11);
      if (parseInt(cnpj.charAt(12)) !== digit) return false;

      sum = 0;
      pos = 0;

      // Cálculo do segundo dígito verificador
      for (let i = 6; i >= 2; i--) {
         sum += parseInt(cnpj.charAt(pos++)) * i;
      }
      for (let i = 9; i >= 2; i--) {
         sum += parseInt(cnpj.charAt(pos++)) * i;
      }

      digit = sum % 11 < 2 ? 0 : 11 - (sum % 11);
      if (parseInt(cnpj.charAt(13)) !== digit) return false;

      return true;
   }

   // Sanitização de strings
   sanitizeString(str) {
      if (typeof str !== "string") return "";

      return str.trim().replace(/[<>]/g, "").substring(0, 1000); // Limitar tamanho
   }

   // Debounce function
   debounce(func, wait, immediate) {
      let timeout;
      return function executedFunction(...args) {
         const later = () => {
            timeout = null;
            if (!immediate) func.apply(this, args);
         };
         const callNow = immediate && !timeout;
         clearTimeout(timeout);
         timeout = setTimeout(later, wait);
         if (callNow) func.apply(this, args);
      };
   }

   // Throttle function
   throttle(func, limit) {
      let inThrottle;
      return function (...args) {
         if (!inThrottle) {
            func.apply(this, args);
            inThrottle = true;
            setTimeout(() => (inThrottle = false), limit);
         }
      };
   }

   // Copy to clipboard
   async copyToClipboard(text) {
      try {
         await navigator.clipboard.writeText(text);
         return true;
      } catch (err) {
         // Fallback para navegadores mais antigos
         const textArea = document.createElement("textarea");
         textArea.value = text;
         document.body.appendChild(textArea);
         textArea.select();

         try {
            document.execCommand("copy");
            return true;
         } catch (fallbackErr) {
            return false;
         } finally {
            document.body.removeChild(textArea);
         }
      }
   }

   // Download de arquivo
   downloadFile(data, filename, type = "text/plain") {
      const blob = new Blob([data], { type });
      const url = window.URL.createObjectURL(blob);

      const link = document.createElement("a");
      link.href = url;
      link.download = filename;
      document.body.appendChild(link);
      link.click();

      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);
   }

   // Geração de ID único
   generateId() {
      return Date.now().toString(36) + Math.random().toString(36).substr(2);
   }

   // Deep clone de objeto
   deepClone(obj) {
      if (obj === null || typeof obj !== "object") return obj;
      if (obj instanceof Date) return new Date(obj.getTime());
      if (obj instanceof Array) return obj.map((item) => this.deepClone(item));

      const clonedObj = {};
      for (const key in obj) {
         if (obj.hasOwnProperty(key)) {
            clonedObj[key] = this.deepClone(obj[key]);
         }
      }

      return clonedObj;
   }

   // Verificar se objeto está vazio
   isEmpty(obj) {
      if (obj === null || obj === undefined) return true;
      if (typeof obj === "string" && obj.trim() === "") return true;
      if (Array.isArray(obj) && obj.length === 0) return true;
      if (typeof obj === "object" && Object.keys(obj).length === 0) return true;
      return false;
   }

   // Capitalizar primeira letra
   capitalize(str) {
      if (typeof str !== "string") return "";
      return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
   }

   // Converter para slug
   slugify(str) {
      if (typeof str !== "string") return "";

      return str
         .toLowerCase()
         .trim()
         .normalize("NFD")
         .replace(/[\u0300-\u036f]/g, "")
         .replace(/[^\w\s-]/g, "")
         .replace(/[\s_-]+/g, "-")
         .replace(/^-+|-+$/g, "");
   }

   // Funções de tema
   toggleTheme() {
      const body = document.body;

      if (body.classList.contains("theme-dark")) {
         body.classList.replace("theme-dark", "theme-light");
         localStorage.setItem("theme", "light");
      } else {
         body.classList.replace("theme-light", "theme-dark");
         localStorage.setItem("theme", "dark");
      }

      // Disparar evento personalizado para outros módulos
      const themeChangedEvent = new CustomEvent("themeChanged", {
         detail: { theme: localStorage.getItem("theme") },
      });
      document.dispatchEvent(themeChangedEvent);
   }

   initTheme() {
      const savedTheme = localStorage.getItem("theme") || "light";
      document.body.classList.add(`theme-${savedTheme}`);
   }

   // Animação suave de scroll
   smoothScrollTo(element, duration = 800) {
      const targetPosition = element.offsetTop;
      const startPosition = window.pageYOffset;
      const distance = targetPosition - startPosition;
      let startTime = null;

      function animation(currentTime) {
         if (startTime === null) startTime = currentTime;
         const timeElapsed = currentTime - startTime;
         const run = ease(timeElapsed, startPosition, distance, duration);
         window.scrollTo(0, run);
         if (timeElapsed < duration) requestAnimationFrame(animation);
      }

      function ease(t, b, c, d) {
         t /= d / 2;
         if (t < 1) return (c / 2) * t * t + b;
         t--;
         return (-c / 2) * (t * (t - 2) - 1) + b;
      }

      requestAnimationFrame(animation);
   }
}

// Criar instância global
const utils = new Utils();

// Inicializar quando DOM estiver pronto
document.addEventListener("DOMContentLoaded", () => {
   utils.init();
});

// Exportar para uso em outros módulos
export { Utils };
export default utils;

// Tornar disponível globalmente
window.Utils = Utils;
window.utils = utils;

// Tornar função toggleTheme disponível globalmente
window.toggleTheme = () => utils.toggleTheme();
