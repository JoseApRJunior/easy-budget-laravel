import { initializeCepService } from "./modules/cep-service.js";
import { initializeCharacterCounter } from "./modules/character-counter.js";
import { initializeFormValidation } from "./modules/form-validation.js";
import { setupImagePreview } from "./modules/image-preview.js";
import { initializeMasks } from "./modules/masks/index.js";

$(document).ready(function () {
   initializeMasks();
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
