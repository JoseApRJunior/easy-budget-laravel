import { initializeCepService } from "./modules/cep-service.js";
import { initializeCharacterCounter } from "./modules/character-counter.js";
import { initializeFormValidation } from "./modules/form-validation.js";
import { setupImagePreview } from "./modules/image-preview.js";

// Função nativa para máscaras
function initializeNativeMasks() {
   // Máscara de telefone
   const phoneInputs = document.querySelectorAll("#phone, #phone_business");
   phoneInputs.forEach(input => {
      input.addEventListener("input", function(e) {
         let value = e.target.value.replace(/\D/g, "");
         if (value.length <= 11) {
            if (value.length === 11) {
               value = value.replace(/(\d{2})(\d{5})(\d{4})/, "($1) $2-$3");
            } else if (value.length === 10) {
               value = value.replace(/(\d{2})(\d{4})(\d{4})/, "($1) $2-$3");
            } else if (value.length > 6) {
               value = value.replace(/(\d{2})(\d{4})(\d+)/, "($1) $2-$3");
            } else if (value.length > 2) {
               value = value.replace(/(\d{2})(\d+)/, "($1) $2");
            }
         }
         e.target.value = value;
      });
   });

   // Máscara de CPF
   const cpfInput = document.getElementById("cpf");
   if (cpfInput) {
      cpfInput.addEventListener("input", function(e) {
         let value = e.target.value.replace(/\D/g, "");
         if (value.length <= 11) {
            value = value.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, "$1.$2.$3-$4");
         }
         e.target.value = value;
      });
   }

   // Máscara de CNPJ
   const cnpjInput = document.getElementById("cnpj");
   if (cnpjInput) {
      cnpjInput.addEventListener("input", function(e) {
         let value = e.target.value.replace(/\D/g, "");
         if (value.length <= 14) {
            value = value.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, "$1.$2.$3/$4-$5");
         }
         e.target.value = value;
      });
   }

   // Máscara de CEP
   const cepInput = document.getElementById("cep");
   if (cepInput) {
      cepInput.addEventListener("input", function(e) {
         let value = e.target.value.replace(/\D/g, "");
         if (value.length <= 8) {
            value = value.replace(/(\d{2})(\d{3})(\d{3})/, "$1.$2-$3");
         }
         e.target.value = value;
      });
   }
}

$(document).ready(function () {
   initializeNativeMasks();
   initializeFormValidation();
   initializeCepService();
   // Inicializa o preview de imagem
   setupImagePreview({
      inputId: "logo",
      previewId: "imagePreview",
      buttonId: "uploadButton",
      maxSize: 2 * 1024 * 1024, // 2MB
      allowedTypes: ["image/jpeg", "image/png"],
      onError: (errorMsg) => {
         showAlert(errorMsg, "danger");
      },
   });
   initializeCharacterCounter();
});
