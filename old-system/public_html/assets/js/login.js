import { togglePassword } from "./modules/utils.js";

document.addEventListener("DOMContentLoaded", function () {
   // Inicializar elementos
   initializeElements();
});

function initializeElements() {
   // Event listeners para toggle de senha
   document.querySelectorAll(".password-toggle").forEach((button) => {
      button.addEventListener("click", () => {
         togglePassword(button.getAttribute("data-input"));
      });
   });
}
