/**
 * Home.js - Versão 2024.2 - NOVA IMPLEMENTAÇÃO SEM jQuery Mask
 */
import {
   handleFormSubmit,
   initTheme,
   initializePasswordValidation,
   scrollToElement,
   togglePassword,
   updatePlanSelection,
} from "./modules/utils.js";

// Inicialização principal - VERSÃO ATUALIZADA
document.addEventListener("DOMContentLoaded", function () {
   console.log('Home.js NOVA VERSÃO carregada - SEM jQuery Mask');
   
   setupElements();
   setupFormMasks();
   initializePasswordValidation();
   initTheme();
   setupPlanSelection();
   setupTermsValidation();
});

function setupElements() {
   const planButtons = document.querySelectorAll(".select-plan");
   const planSelect = document.getElementById("planSelect");
   const conhecaPlanos = document.getElementById("conhecaPlanos");

   if (conhecaPlanos) {
      conhecaPlanos.addEventListener("click", (e) => {
         e.preventDefault();
         scrollToElement("plans");
      });
   }

   planButtons.forEach((button) => {
      button.addEventListener("click", () => {
         const selectedPlan = button.getAttribute("data-plan");
         updatePlanSelection(selectedPlan, planButtons, planSelect);
         scrollToElement("preCadastroForm");
      });
   });

   document.querySelectorAll(".password-toggle").forEach((button) => {
      button.addEventListener("click", () => {
         togglePassword(button.getAttribute("data-input"));
      });
   });
}

function setupFormMasks() {
   console.log('Configurando máscaras nativas - SEM jQuery');
   
   const phoneInput = document.getElementById("phone");
   if (phoneInput) {
      console.log('Aplicando máscara nativa ao telefone');
      phoneInput.addEventListener("input", function(e) {
         // Remove tudo que não é dígito
         let digits = e.target.value.replace(/\D/g, "");
         
         // Limita rigorosamente a 11 dígitos
         digits = digits.substring(0, 11);
         
         // Aplica formatação baseada no número de dígitos
         let formatted = "";
         if (digits.length >= 11) {
            formatted = digits.replace(/(\d{2})(\d{5})(\d{4})/, "($1) $2-$3");
         } else if (digits.length >= 10) {
            formatted = digits.replace(/(\d{2})(\d{4})(\d{4})/, "($1) $2-$3");
         } else if (digits.length > 6) {
            formatted = digits.replace(/(\d{2})(\d{4})(\d+)/, "($1) $2-$3");
         } else if (digits.length > 2) {
            formatted = digits.replace(/(\d{2})(\d+)/, "($1) $2");
         } else {
            formatted = digits;
         }
         
         e.target.value = formatted;
      });
   }

   const form = document.querySelector("#preRegisterForm");
   if (form) {
      form.addEventListener("submit", (e) => {
         if (!checkTerms()) {
            e.preventDefault();
            return;
         }
         handleFormSubmit(e);
      });
   }
}

function setupTermsValidation() {
   const checkbox = document.getElementById("terms_accepted");
   if (checkbox) {
      checkbox.addEventListener("change", function () {
         if (this.checked) {
            clearTermsError();
         }
      });
   }
}

function checkTerms() {
   const checkbox = document.getElementById("terms_accepted");
   if (!checkbox.checked) {
      displayTermsError();
      return false;
   }
   return true;
}

function displayTermsError() {
   const checkbox = document.getElementById("terms_accepted");
   clearTermsError();

   const errorDiv = document.createElement("div");
   errorDiv.className = "alert alert-danger mt-2";
   errorDiv.id = "terms-error";
   errorDiv.setAttribute("role", "alert");
   errorDiv.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-2"></i>Você precisa aceitar os termos e a política de privacidade.';

   checkbox.closest(".form-check").appendChild(errorDiv);
   checkbox.classList.add("is-invalid");
   checkbox.focus();
}

function clearTermsError() {
   const errorDiv = document.getElementById("terms-error");
   const checkbox = document.getElementById("terms_accepted");

   if (errorDiv) {
      errorDiv.remove();
   }
   if (checkbox) {
      checkbox.classList.remove("is-invalid");
   }
}

function setupPlanSelection() {
   const planButtons = document.querySelectorAll(".select-plan");
   const planSelect = document.getElementById("planSelect");

   planButtons.forEach((button) => {
      button.addEventListener("click", () => {
         const selectedPlan = button.getAttribute("data-plan");
         updatePlanSelection(selectedPlan, planButtons, planSelect);

         const preCadastroForm = document.getElementById("preCadastroForm");
         if (preCadastroForm) {
            preCadastroForm.scrollIntoView({ behavior: "smooth" });
         }
      });
   });
}