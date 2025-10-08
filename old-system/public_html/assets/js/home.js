import { SPMaskBehavior, spOptions } from "./modules/masks/masks.js";
import {
   handleFormSubmit,
   initTheme,
   initializePasswordValidation,
   scrollToElement,
   togglePassword,
   updatePlanSelection,
} from "./modules/utils.js";

document.addEventListener("DOMContentLoaded", function () {
   // Inicializar elementos
   initializeElements();

   // Inicializar máscaras e validações
   initializeFormBehavior();

   // Inicializar validação de senha
   initializePasswordValidation();

   // Inicializar tema
   initTheme();

   // Inicializar seleção de plano
   selectPlan();

   // Inicializar validação dos termos
   initializeTermsValidation();
});

function initializeElements() {
   // Event listeners para planos
   const planButtons = document.querySelectorAll(".select-plan");
   const planSelect = document.getElementById("planSelect");
   const conhecaPlanos = document.getElementById("conhecaPlanos");

   conhecaPlanos.addEventListener("click", (e) => {
      e.preventDefault();
      scrollToElement("plans");
   });

   planButtons.forEach((button) => {
      button.addEventListener("click", () => {
         const selectedPlan = button.getAttribute("data-plan");
         updatePlanSelection(selectedPlan, planButtons, planSelect);
         scrollToElement("preCadastroForm");
      });
   });

   // Event listeners para toggle de senha
   document.querySelectorAll(".password-toggle").forEach((button) => {
      button.addEventListener("click", () => {
         togglePassword(button.getAttribute("data-input"));
      });
   });
}

function initializeFormBehavior() {
   // Máscara de telefone
   $("#phone").mask(SPMaskBehavior, spOptions);

   // Validação do formulário
   const form = document.querySelector("#preRegisterForm");
   form.addEventListener("submit", (e) => {
      // Verifica os termos antes de chamar handleFormSubmit
      if (!validateTerms()) {
         e.preventDefault();
         return;
      }
      handleFormSubmit(e);
   });
}

function initializeTermsValidation() {
   const checkbox = document.getElementById("terms_accepted");

   // Remove mensagem de erro quando o checkbox é marcado
   checkbox.addEventListener("change", function () {
      if (this.checked) {
         removeTermsError();
      }
   });
}

function validateTerms() {
   const checkbox = document.getElementById("terms_accepted");
   if (!checkbox.checked) {
      showTermsError();
      return false;
   }
   return true;
}
function showTermsError() {
   const checkbox = document.getElementById("terms_accepted");
   removeTermsError(); // Remove mensagem anterior se existir

   const errorDiv = document.createElement("div");
   errorDiv.className = "alert alert-danger mt-2";
   errorDiv.id = "terms-error";
   errorDiv.setAttribute("role", "alert");
   errorDiv.innerHTML =
      '<i class="bi bi-exclamation-triangle-fill me-2"></i>Você precisa aceitar os termos e a política de privacidade.';

   // Adiciona a mensagem após o label do checkbox
   checkbox.closest(".form-check").appendChild(errorDiv);

   // Adiciona classes de erro ao checkbox
   checkbox.classList.add("is-invalid");

   // Foca no checkbox
   checkbox.focus();
}

function removeTermsError() {
   const errorDiv = document.getElementById("terms-error");
   const checkbox = document.getElementById("terms_accepted");

   if (errorDiv) {
      errorDiv.remove();
   }

   checkbox.classList.remove("is-invalid");
}
function selectPlan() {
   const planButtons = document.querySelectorAll(".select-plan");
   const planSelect = document.getElementById("planSelect");

   planButtons.forEach((button) => {
      button.addEventListener("click", () => {
         const selectedPlan = button.getAttribute("data-plan");
         updatePlanSelection(selectedPlan, planButtons, planSelect);

         // Scroll para o formulário de pré-cadastro
         const preCadastroForm = document.getElementById("preCadastroForm");
         if (preCadastroForm) {
            preCadastroForm.scrollIntoView({ behavior: "smooth" });
         }
      });
   });
}
