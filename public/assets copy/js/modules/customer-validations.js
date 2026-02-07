/**
 * JavaScript de validações para Customer
 * Implementa validações em tempo real para CPF/CNPJ, máscaras e busca de endereço
 */

// Helpers para validação CPF/CNPJ
const CustomerValidation = {
   // Validação de CPF
   validateCPF: function (cpf) {
      cpf = cpf.replace(/\D/g, "");

      if (cpf.length !== 11) return false;
      if (/^(\d)\1{10}$/.test(cpf)) return false;

      let sum = 0;
      for (let i = 0; i < 9; i++) {
         sum += parseInt(cpf.charAt(i)) * (10 - i);
      }
      let remainder = 11 - (sum % 11);
      if (remainder === 10 || remainder === 11) remainder = 0;
      if (remainder !== parseInt(cpf.charAt(9))) return false;

      sum = 0;
      for (let i = 0; i < 10; i++) {
         sum += parseInt(cpf.charAt(i)) * (11 - i);
      }
      remainder = 11 - (sum % 11);
      if (remainder === 10 || remainder === 11) remainder = 0;
      if (remainder !== parseInt(cpf.charAt(10))) return false;

      return true;
   },

   // Validação de CNPJ
   validateCNPJ: function (cnpj) {
      cnpj = cnpj.replace(/\D/g, "");

      if (cnpj.length !== 14) return false;
      if (/^(\d)\1{13}$/.test(cnpj)) return false;

      const weights1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
      const weights2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

      let sum = 0;
      for (let i = 0; i < 12; i++) {
         sum += parseInt(cnpj.charAt(i)) * weights1[i];
      }
      let remainder = sum % 11;
      const digit1 = remainder < 2 ? 0 : 11 - remainder;
      if (digit1 !== parseInt(cnpj.charAt(12))) return false;

      sum = 0;
      for (let i = 0; i < 13; i++) {
         sum += parseInt(cnpj.charAt(i)) * weights2[i];
      }
      remainder = sum % 11;
      const digit2 = remainder < 2 ? 0 : 11 - remainder;
      if (digit2 !== parseInt(cnpj.charAt(13))) return false;

      return true;
   },

   // Máscara para CPF
   maskCPF: function (cpf) {
      return cpf
         .replace(/\D/g, "")
         .replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, "$1.$2.$3-$4");
   },

   // Máscara para CNPJ
   maskCNPJ: function (cnpj) {
      return cnpj
         .replace(/\D/g, "")
         .replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, "$1.$2.$3/$4-$5");
   },

   // Máscara para telefone
   maskPhone: function (phone) {
      const cleaned = phone.replace(/\D/g, "");
      if (cleaned.length <= 10) {
         return cleaned.replace(/(\d{2})(\d{4})(\d{4})/, "($1) $2-$3");
      } else {
         return cleaned.replace(/(\d{2})(\d{5})(\d{4})/, "($1) $2-$3");
      }
   },

   // Máscara para CEP
   maskCEP: function (cep) {
      return cep.replace(/\D/g, "").replace(/(\d{5})(\d{3})/, "$1-$2");
   },
};

// Funções de validação em tempo real
window.validateCPF = function (excludeId = null) {
   const cpfInput = document.getElementById("cpf");
   const errorDiv = document.getElementById("cpf-error");

   if (!cpfInput || !errorDiv) return true;

   const cpf = cpfInput.value.replace(/\D/g, "");

   if (!cpf) {
      cpfInput.classList.remove("is-invalid");
      errorDiv.textContent = "";
      return true;
   }

   if (!CustomerValidation.validateCPF(cpf)) {
      cpfInput.classList.add("is-invalid");
      errorDiv.textContent = "CPF inválido";
      return false;
   }

   // Verificar unicidade no servidor se informado
   if (excludeId) {
      checkUniqueness("cpf", cpf, excludeId, errorDiv, cpfInput);
      return true; // Retornar true por enquanto, verificação será assíncrona
   }

   cpfInput.classList.remove("is-invalid");
   errorDiv.textContent = "";
   return true;
};

window.validateCNPJ = function (excludeId = null) {
   const cnpjInput = document.getElementById("cnpj");
   const errorDiv = document.getElementById("cnpj-error");

   if (!cnpjInput || !errorDiv) return true;

   const cnpj = cnpjInput.value.replace(/\D/g, "");

   if (!cnpj) {
      cnpjInput.classList.remove("is-invalid");
      errorDiv.textContent = "";
      return true;
   }

   if (!CustomerValidation.validateCNPJ(cnpj)) {
      cnpjInput.classList.add("is-invalid");
      errorDiv.textContent = "CNPJ inválido";
      return false;
   }

   // Verificar unicidade no servidor se informado
   if (excludeId) {
      checkUniqueness("cnpj", cnpj, excludeId, errorDiv, cnpjInput);
      return true;
   }

   cnpjInput.classList.remove("is-invalid");
   errorDiv.textContent = "";
   return true;
};

window.validateEmail = function (excludeId = null) {
   const emailInput = document.getElementById("email");
   const errorDiv = document.getElementById("email-error");

   if (!emailInput || !errorDiv) return true;

   const email = emailInput.value;

   if (!email) {
      emailInput.classList.remove("is-invalid");
      errorDiv.textContent = "";
      return true;
   }

   // Validação básica de email
   const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
   if (!emailRegex.test(email)) {
      emailInput.classList.add("is-invalid");
      errorDiv.textContent = "E-mail inválido";
      return false;
   }

   // Verificar unicidade no servidor se informado
   if (excludeId) {
      checkUniqueness("email", email, excludeId, errorDiv, emailInput);
      return true;
   }

   emailInput.classList.remove("is-invalid");
   errorDiv.textContent = "";
   return true;
};

window.validateEmailBusiness = function (excludeId = null) {
   const emailInput = document.getElementById("email_business");
   const errorDiv = document.getElementById("email-business-error");

   if (!emailInput || !errorDiv) return true;

   const email = emailInput.value;

   if (!email) {
      emailInput.classList.remove("is-invalid");
      errorDiv.textContent = "";
      return true;
   }

   // Validação básica de email
   const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
   if (!emailRegex.test(email)) {
      emailInput.classList.add("is-invalid");
      errorDiv.textContent = "E-mail inválido";
      return false;
   }

   // Verificar unicidade no servidor se informado
   if (excludeId) {
      checkUniqueness("email_business", email, excludeId, errorDiv, emailInput);
      return true;
   }

   emailInput.classList.remove("is-invalid");
   errorDiv.textContent = "";
   return true;
};

// Função para verificar unicidade via AJAX
function checkUniqueness(field, value, excludeId, errorDiv, input) {
   if (!value || !excludeId) return;

   // Debounce para evitar muitas requisições
   clearTimeout(window[field + "Timeout"]);
   window[field + "Timeout"] = setTimeout(() => {
      fetch("/provider/customers/check-unique", {
         method: "POST",
         headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')
               .content,
         },
         body: JSON.stringify({
            field: field,
            value: value,
            exclude_id: excludeId,
         }),
      })
         .then((response) => response.json())
         .then((data) => {
            if (!data.unique) {
               input.classList.add("is-invalid");
               errorDiv.textContent = `Este ${
                  field === "email" ? "e-mail" : field.replace("_", " ")
               } já está em uso`;
            } else {
               input.classList.remove("is-invalid");
               errorDiv.textContent = "";
            }
         })
         .catch((error) => {
            console.error("Erro ao verificar unicidade:", error);
         });
   }, 500);
}

// Aplicar máscaras
window.applyMasks = function () {
   // CPF
   const cpfInput = document.getElementById("cpf");
   if (cpfInput) {
      cpfInput.addEventListener("input", function () {
         this.value = CustomerValidation.maskCPF(this.value);
      });
   }

   // CNPJ
   const cnpjInput = document.getElementById("cnpj");
   if (cnpjInput) {
      cnpjInput.addEventListener("input", function () {
         this.value = CustomerValidation.maskCNPJ(this.value);
      });
   }

   // Telefones
   const phoneInputs = document.querySelectorAll(
      "#phone, #phone_personal, #phone_business"
   );
   phoneInputs.forEach((input) => {
      input.addEventListener("input", function () {
         this.value = CustomerValidation.maskPhone(this.value);
      });
   });

   // CEP
   const cepInput = document.getElementById("cep");
   if (cepInput) {
      cepInput.addEventListener("input", function () {
         this.value = CustomerValidation.maskCEP(this.value);
      });
   }

   // Estado (UF)
   const stateInput = document.getElementById("state");
   if (stateInput) {
      stateInput.addEventListener("input", function () {
         this.value = this.value
            .toUpperCase()
            .replace(/[^A-Z]/g, "")
            .substring(0, 2);
      });
   }
};

// Buscar endereço por CEP
window.fetchAddressByCEP = function (cep) {
   if (cep.length !== 8) return;

   const addressInput = document.getElementById("address");
   const neighborhoodInput = document.getElementById("neighborhood");
   const cityInput = document.getElementById("city");
   const stateInput = document.getElementById("state");

   if (!addressInput || !neighborhoodInput || !cityInput || !stateInput) return;

   // Mostrar indicador de carregamento
   [addressInput, neighborhoodInput, cityInput, stateInput].forEach((input) => {
      input.setAttribute("disabled", "disabled");
   });

   // Fazer requisição para API de CEP
   fetch(`https://viacep.com.br/ws/${cep}/json/`)
      .then((response) => response.json())
      .then((data) => {
         if (data.erro) {
            alert("CEP não encontrado");
            return;
         }

         // Preencher campos
         addressInput.value = data.logradouro || "";
         neighborhoodInput.value = data.bairro || "";
         cityInput.value = data.localidade || "";
         stateInput.value = data.uf || "";

         // Focar no próximo campo
         const numberInput = document.getElementById("address_number");
         if (numberInput) {
            numberInput.focus();
         }
      })
      .catch((error) => {
         console.error("Erro ao buscar CEP:", error);
         alert("Erro ao buscar endereço. Tente novamente.");
      })
      .finally(() => {
         // Remover indicador de carregamento
         [addressInput, neighborhoodInput, cityInput, stateInput].forEach(
            (input) => {
               input.removeAttribute("disabled");
            }
         );
      });
};

// Validação de email em tempo real com debounce
function setupEmailValidation() {
   const emailInputs = document.querySelectorAll(
      "#email, #email_personal, #email_business"
   );

   emailInputs.forEach((input) => {
      let timeout;
      input.addEventListener("input", function () {
         clearTimeout(timeout);
         timeout = setTimeout(() => {
            const isBusiness = this.id === "email_business";
            const excludeId = window.currentCustomerId;

            if (isBusiness) {
               validateEmailBusiness(excludeId);
            } else {
               validateEmail(excludeId);
            }
         }, 300);
      });
   });
}

// Validação de CPF/CNPJ em tempo real
function setupDocumentValidation() {
   const cpfInput = document.getElementById("cpf");
   const cnpjInput = document.getElementById("cnpj");

   if (cpfInput) {
      let timeout;
      cpfInput.addEventListener("input", function () {
         clearTimeout(timeout);
         timeout = setTimeout(() => {
            validateCPF(window.currentCustomerId);
         }, 300);
      });
   }

   if (cnpjInput) {
      let timeout;
      cnpjInput.addEventListener("input", function () {
         clearTimeout(timeout);
         timeout = setTimeout(() => {
            validateCNPJ(window.currentCustomerId);
         }, 300);
      });
   }
}

// Inicialização quando o DOM estiver pronto
document.addEventListener("DOMContentLoaded", function () {
   applyMasks();
   setupEmailValidation();
   setupDocumentValidation();

   // Definir ID do cliente atual para validações de unicidade
   const customerEditForm =
      document.getElementById("pessoaFisicaEditForm") ||
      document.getElementById("pessoaJuridicaEditForm");
   if (customerEditForm) {
      // Extrair ID da URL ou do formulário
      const urlParts = window.location.pathname.split("/");
      const customerId = urlParts[urlParts.length - 2]; // Assumindo que está na penúltima posição
      if (!isNaN(customerId)) {
         window.currentCustomerId = parseInt(customerId);
      }
   }
});

// Exportar para uso global
window.CustomerValidation = CustomerValidation;
