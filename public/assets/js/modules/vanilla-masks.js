/**
 * Sistema de Máscaras Vanilla JavaScript
 * Substitui jQuery Mask Plugin com performance superior e zero dependências
 */

// ========================================
// FUNÇÕES DE FORMATAÇÃO
// ========================================

/**
 * Remove caracteres não numéricos
 */
function removeNonDigits(value) {
   return value.replace(/\D/g, "");
}

/**
 * Aplica máscara de CNPJ: 00.000.000/0000-00
 */
function formatCNPJ(value) {
   const digits = removeNonDigits(value).substring(0, 14);

   if (digits.length <= 2) return digits;
   if (digits.length <= 5) return digits.replace(/(\d{2})(\d{3})/, "$1.$2");
   if (digits.length <= 8)
      return digits.replace(/(\d{2})(\d{3})(\d{3})/, "$1.$2.$3");
   if (digits.length <= 12)
      return digits.replace(/(\d{2})(\d{3})(\d{3})(\d{4})/, "$1.$2.$3/$4");

   return digits.replace(
      /(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/,
      "$1.$2.$3/$4-$5"
   );
}

/**
 * Aplica máscara de CPF: 000.000.000-00
 */
function formatCPF(value) {
   const digits = removeNonDigits(value).substring(0, 11);

   if (digits.length <= 3) return digits;
   if (digits.length <= 6) return digits.replace(/(\d{3})(\d{3})/, "$1.$2");
   if (digits.length <= 9)
      return digits.replace(/(\d{3})(\d{3})(\d{3})/, "$1.$2.$3");

   return digits.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, "$1.$2.$3-$4");
}

/**
 * Aplica máscara de CEP: 00000-000
 */
function formatCEP(value) {
   const digits = removeNonDigits(value).substring(0, 8);

   if (digits.length <= 5) return digits;

   return digits.replace(/(\d{5})(\d{3})/, "$1-$2");
}

/**
 * Aplica máscara de telefone: (00) 00000-0000
 */
function formatPhone(value) {
   const digits = removeNonDigits(value).substring(0, 11);

   if (digits.length <= 2) return `(${digits}`;
   if (digits.length <= 6) return digits.replace(/(\d{2})(\d{4})/, "($1) $2");
   if (digits.length <= 10)
      return digits.replace(/(\d{2})(\d{4})(\d{4})/, "($1) $2-$3");

   return digits.replace(/(\d{2})(\d{5})(\d{4})/, "($1) $2-$3");
}

/**
 * Aplica máscara de data: 00/00/0000
 */
function formatDate(value) {
   const digits = removeNonDigits(value).substring(0, 8);

   if (digits.length <= 2) return digits;
   if (digits.length <= 4) return digits.replace(/(\d{2})(\d{2})/, "$1/$2");

   return digits.replace(/(\d{2})(\d{2})(\d{4})/, "$1/$2/$3");
}

// ========================================
// VALIDADORES
// ========================================

/**
 * Valida CPF
 */
function validateCPF(value) {
   const digits = removeNonDigits(value);

   if (digits.length !== 11) return false;
   if (/^(\d)\1{10}$/.test(digits)) return false; // Todos os dígitos iguais

   let sum = 0;
   for (let i = 0; i < 9; i++) {
      sum += parseInt(digits.charAt(i)) * (10 - i);
   }

   let remainder = (sum * 10) % 11;
   if (remainder === 10 || remainder === 11) remainder = 0;
   if (remainder !== parseInt(digits.charAt(9))) return false;

   sum = 0;
   for (let i = 0; i < 10; i++) {
      sum += parseInt(digits.charAt(i)) * (11 - i);
   }

   remainder = (sum * 10) % 11;
   if (remainder === 10 || remainder === 11) remainder = 0;
   return remainder === parseInt(digits.charAt(10));
}

/**
 * Valida CNPJ
 */
function validateCNPJ(value) {
   const digits = removeNonDigits(value);

   if (digits.length !== 14) return false;
   if (/^(\d)\1{13}$/.test(digits)) return false; // Todos os dígitos iguais

   // Validar primeiro dígito verificador
   let sum = 0;
   let weight = 5;
   for (let i = 0; i < 12; i++) {
      sum += parseInt(digits.charAt(i)) * weight;
      weight = weight === 2 ? 9 : weight - 1;
   }

   let remainder = sum % 11;
   let checkDigit = remainder < 2 ? 0 : 11 - remainder;

   if (checkDigit !== parseInt(digits.charAt(12))) return false;

   // Validar segundo dígito verificador
   sum = 0;
   weight = 6;
   for (let i = 0; i < 13; i++) {
      sum += parseInt(digits.charAt(i)) * weight;
      weight = weight === 2 ? 9 : weight - 1;
   }

   remainder = sum % 11;
   checkDigit = remainder < 2 ? 0 : 11 - remainder;

   return checkDigit === parseInt(digits.charAt(13));
}

// ========================================
// CLASSE PRINCIPAL
// ========================================

class VanillaMask {
   constructor(elementId, type, options = {}) {
      this.element = document.getElementById(elementId);
      this.type = type;
      this.options = {
         clearIfNotMatch: true,
         placeholder: "",
         validator: null,
         ...options,
      };

      if (!this.element) {
         console.warn(`Elemento com ID '${elementId}' não encontrado`);
         return;
      }

      this.init();
   }

   init() {
      // Aplicar maxlength baseado no tipo
      this.applyMaxLength();

      // Adicionar event listeners
      this.element.addEventListener("input", this.handleInput.bind(this));
      this.element.addEventListener("keypress", this.handleKeyPress.bind(this));
      this.element.addEventListener("blur", this.handleBlur.bind(this));
   }

   applyMaxLength() {
      const maxLengths = {
         cpf: 14,
         cnpj: 18,
         cep: 9,
         phone: 15,
         date: 10,
      };

      if (maxLengths[this.type]) {
         this.element.setAttribute("maxlength", maxLengths[this.type]);
      }
   }

   handleInput(event) {
      const input = event.target;
      let value = input.value;

      // Aplicar máscara
      const formattedValue = this.format(value);
      input.value = formattedValue;

      // Validar se necessário
      if (this.options.validator) {
         this.validateField(formattedValue);
      }
   }

   handleKeyPress(event) {
      // Permitir teclas especiais
      const allowedKeys = [
         "Backspace",
         "Delete",
         "Tab",
         "Escape",
         "Enter",
         "ArrowLeft",
         "ArrowRight",
         "ArrowUp",
         "ArrowDown",
         "Home",
         "End",
      ];

      if (allowedKeys.includes(event.key) || event.ctrlKey || event.metaKey) {
         return;
      }

      // Validar se é número (quando necessário)
      if (this.requiresNumbers() && !/[0-9]/.test(event.key)) {
         event.preventDefault();
      }
   }

   handleBlur(event) {
      // Limpar campo se não corresponde ao padrão
      if (this.options.clearIfNotMatch && this.options.validator) {
         const value = event.target.value;
         if (value && !this.options.validator(value)) {
            event.target.value = "";
            this.showError("Formato inválido");
         }
      }
   }

   requiresNumbers() {
      return ["cpf", "cnpj", "cep", "phone"].includes(this.type);
   }

   format(value) {
      switch (this.type) {
         case "cnpj":
            return formatCNPJ(value);
         case "cpf":
            return formatCPF(value);
         case "cep":
            return formatCEP(value);
         case "phone":
            return formatPhone(value);
         case "date":
            return formatDate(value);
         default:
            return value;
      }
   }

   validateField(value) {
      const isValid = this.options.validator(value);

      if (isValid) {
         this.clearError();
         this.element.classList.remove("is-invalid");
      } else if (value.length > 0) {
         this.element.classList.add("is-invalid");
      }
   }

   showError(message) {
      this.clearError();
      const errorDiv = document.createElement("div");
      errorDiv.className = "invalid-feedback";
      errorDiv.textContent = message;
      this.element.parentNode.insertBefore(errorDiv, this.element.nextSibling);
   }

   clearError() {
      const existingError =
         this.element.parentNode.querySelector(".invalid-feedback");
      if (existingError) {
         existingError.remove();
      }
   }
}

// ========================================
// FUNÇÕES AUXILIARES
// ========================================

/**
 * Inicializa todas as máscaras de uma página
 */
function initializeMasks() {
   // CNPJ
   if (document.getElementById("cnpj")) {
      new VanillaMask("cnpj", "cnpj", {
         validator: validateCNPJ,
      });
   }

   // CPF
   if (document.getElementById("cpf")) {
      new VanillaMask("cpf", "cpf", {
         validator: validateCPF,
      });
   }

   // CEP
   if (document.getElementById("cep")) {
      new VanillaMask("cep", "cep");
   }

   // Telefones
   if (document.getElementById("phone_personal")) {
      new VanillaMask("phone_personal", "phone");
   }

   if (document.getElementById("phone_business")) {
      new VanillaMask("phone_business", "phone");
   }

   // Data
   if (document.getElementById("birth_date")) {
      new VanillaMask("birth_date", "date");
   }
}

// ========================================
// EXPORTS (se usar módulos)
// ========================================

if (typeof module !== "undefined" && module.exports) {
   module.exports = {
      VanillaMask,
      initializeMasks,
      validateCPF,
      validateCNPJ,
      formatCNPJ,
      formatCPF,
      formatCEP,
      formatPhone,
      formatDate,
   };
}

// ========================================
// AUTO-INICIALIZAÇÃO
// ========================================

// Aguardar DOM estar pronto
if (document.readyState === "loading") {
   document.addEventListener("DOMContentLoaded", initializeMasks);
} else {
   initializeMasks();
}
