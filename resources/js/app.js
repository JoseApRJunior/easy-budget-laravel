import "../css/app.css";
import "./bootstrap";

// Alpine.js
import Alpine from "alpinejs";

window.Alpine = Alpine;

Alpine.start();

// Scripts personalizados
document.addEventListener("DOMContentLoaded", function () {
   // Toggle de senha
   const passwordToggles = document.querySelectorAll(".password-toggle");
   passwordToggles.forEach((toggle) => {
      toggle.addEventListener("click", function () {
         const input = document.querySelector(`#${this.dataset.input}`);
         const icon = this.querySelector("i");

         if (input.type === "password") {
            input.type = "text";
            icon.classList.remove("bi-eye");
            icon.classList.add("bi-eye-slash");
         } else {
            input.type = "password";
            icon.classList.remove("bi-eye-slash");
            icon.classList.add("bi-eye");
         }
      });
   });

   // Seleção de planos
   const selectPlanButtons = document.querySelectorAll(".select-plan");
   selectPlanButtons.forEach((button) => {
      button.addEventListener("click", function () {
         const planName = this.dataset.plan;
         const planSelect = document.querySelector("#planSelect");

         if (planSelect) {
            planSelect.value = planName.toLowerCase().replace(" ", "_");
            document.querySelector("#preCadastroForm").scrollIntoView({
               behavior: "smooth",
            });
         }
      });
   });

   // Rolagem suave para planos
   const conhecaPlanosBtn = document.querySelector("#conhecaPlanos");
   if (conhecaPlanosBtn) {
      conhecaPlanosBtn.addEventListener("click", function () {
         document.querySelector("#plans").scrollIntoView({
            behavior: "smooth",
         });
      });
   }

   // Validação de formulário
   const forms = document.querySelectorAll(".needs-validation");
   forms.forEach((form) => {
      form.addEventListener("submit", function (event) {
         if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
         }
         form.classList.add("was-validated");
      });
   });
});
