import {
   handleFormSubmit,
   initializePasswordValidation,
   togglePassword,
} from "./modules/utils.js";

document.addEventListener("DOMContentLoaded", function () {
   // Inicializar elementos
   initializeElements();

   // Inicializar validação de senha
   initializePasswordValidation();

   // Inicializar máscaras e validações
   initializeFormBehavior();
});

function initializeElements() {
   // Event listeners para toggle de senha
   document.querySelectorAll(".password-toggle").forEach((button) => {
      button.addEventListener("click", () => {
         togglePassword(button.getAttribute("data-input"));
      });
   });
}

function initializeFormBehavior() {
   // Validação do formulário
   const form = document.querySelector("#changePasswordForm");
   form.addEventListener("submit", handleFormSubmit);
}
