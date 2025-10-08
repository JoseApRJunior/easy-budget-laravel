import { PATTERNS } from "./patterns.js";

import { maskOptions } from "./config.js";

import { validators } from "./validators.js";

export const initializeMasks = () => {
   // Inicializa as máscaras com validação
   const initializeField = (selector, pattern, options, validator) => {
      const field = $(selector);
      if (field.length) {
         field.mask(pattern, options);

         // Adiciona validação no blur
         field.on("blur", function () {
            const value = $(this).val();
            if (value && !validator(value)) {
               $(this).addClass("is-invalid");
               // Adiciona mensagem de erro se necessário
               const feedback = $(this).siblings(".invalid-feedback");
               if (!feedback.length) {
                  $(this).after(
                     '<div class="invalid-feedback">Formato inválido</div>'
                  );
               }
            } else {
               $(this).removeClass("is-invalid");
            }
         });
      }
   };

   // Inicializa cada campo com sua respectiva máscara e validação
   initializeField(
      "#phone",
      PATTERNS.PHONE.pattern,
      maskOptions.phone,
      validators.phone
   );
   initializeField(
      "#phone_business",
      PATTERNS.PHONE.pattern,
      maskOptions.phone,
      validators.phone
   );
   initializeField(
      "#cpf",
      PATTERNS.CPF.pattern,
      maskOptions.cpf,
      validators.cpf
   );
   initializeField(
      "#cnpj",
      PATTERNS.CNPJ.pattern,
      maskOptions.cnpj,
      validators.cnpj
   );
   initializeField(
      "#cep",
      PATTERNS.CEP.pattern,
      maskOptions.cep,
      validators.cep
   );
};

// Exporta funções auxiliares se necessário
export const formatters = {
   removeNonDigits: (value) => value.replace(/[^\d]/g, ""),
   formatPhone: (value) => {
      const cleaned = value.replace(/[^\d]/g, "");
      if (cleaned.length === 11) {
         return `(${cleaned.substr(0, 2)}) ${cleaned.substr(
            2,
            5
         )}-${cleaned.substr(7)}`;
      }
      return `(${cleaned.substr(0, 2)}) ${cleaned.substr(
         2,
         4
      )}-${cleaned.substr(6)}`;
   },
};
