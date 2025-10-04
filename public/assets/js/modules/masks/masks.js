// masks.js - Vanilla JavaScript implementation
export const SPMaskBehavior = function (val) {
    return val.replace(/\D/g, "").length === 11
       ? "(00) 00000-0000"
       : "(00) 0000-00009";
};

export const spOptions = {
    onKeyPress: function (val, e, field, options) {
       field.mask(SPMaskBehavior.apply({}, arguments), options);
    },
    clearIfNotMatch: true,
};

// Character limits for masked fields
export const FIELD_LIMITS = {
    phone: 15,      // (00) 00000-0000 or (00) 0000-0000
    cpf: 14,        // 000.000.000-00
    cnpj: 18,       // 00.000.000/0000-00
    cep: 9          // 00.000-000
};

// Vanilla JavaScript mask utilities
class VanillaMask {
    constructor(element, mask, options = {}) {
       this.element = element;
       this.mask = mask;
       this.options = options;

       // Get field ID to determine character limit
       this.fieldId = element.id;
       this.maxLength = this.getMaxLength();

       this.init();
    }

    getMaxLength() {
       // Determine max length based on field ID
       if (this.fieldId.includes('phone')) {
          return FIELD_LIMITS.phone;
       } else if (this.fieldId.includes('cpf')) {
          return FIELD_LIMITS.cpf;
       } else if (this.fieldId.includes('cnpj')) {
          return FIELD_LIMITS.cnpj;
       } else if (this.fieldId.includes('cep')) {
          return FIELD_LIMITS.cep;
       }
       return null; // No limit for other fields
    }

    init() {
       this.element.addEventListener("input", (e) => this.handleInput(e));
       this.element.addEventListener("keydown", (e) => this.handleKeyDown(e));
       this.element.addEventListener("focus", () => this.applyMask());
       this.element.addEventListener("blur", () => this.applyMask());

       // Set maxlength attribute if limit exists
       if (this.maxLength) {
          this.element.setAttribute('maxlength', this.maxLength);
       }
    }

    handleKeyDown(e) {
       // Prevent input if field is at max length
       if (this.maxLength && this.element.value.length >= this.maxLength) {
          // Allow backspace, delete, tab, arrows, etc.
          const allowedKeys = [
             'Backspace', 'Delete', 'Tab', 'Escape', 'Enter',
             'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown',
             'Home', 'End'
          ];

          if (!allowedKeys.includes(e.key) && !e.ctrlKey && !e.metaKey) {
             e.preventDefault();
             return false;
          }
       }
    }

    handleInput(e) {
       // Apply character limit before masking
       if (this.maxLength && this.element.value.length > this.maxLength) {
          this.element.value = this.element.value.substring(0, this.maxLength);
       }

       this.applyMask(e);
    }

   applyMask(e) {
      let value = this.element.value.replace(/\D/g, "");

      if (this.options.clearIfNotMatch && value.length === 0) {
         this.element.value = "";
         return;
      }

      // Apply specific mask logic based on the mask pattern
      if (this.mask === SPMaskBehavior) {
         value = this.applySPMask(value);
      } else if (this.mask === "000.000.000-00") {
         value = this.applyCPFMasks(value);
      } else if (this.mask === "00.000.000/0000-00") {
         value = this.applyCNPJMask(value);
      } else if (this.mask === "00.000-000") {
         value = this.applyCEPMask(value);
      } else {
         value = this.applyGenericMask(value);
      }

      this.element.value = value;
   }

   applySPMask(value) {
      if (value.length === 11) {
         return value.replace(/(\d{2})(\d{5})(\d{4})/, "($1) $2-$3");
      } else if (value.length === 10) {
         return value.replace(/(\d{2})(\d{4})(\d{4})/, "($1) $2-$3");
      }
      return value;
   }

   applyCPFMasks(value) {
      return value.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, "$1.$2.$3-$4");
   }

   applyCNPJMask(value) {
      return value.replace(
         /(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/,
         "$1.$2.$3/$4-$5"
      );
   }

   applyCEPMask(value) {
      return value.replace(/(\d{2})(\d{3})(\d{3})/, "$1.$2-$3");
   }

   applyGenericMask(value) {
      // For custom masks, you can extend this method
      return value;
   }
}

// Function to apply mask to a specific element
export const applyMaskToElement = (selector, mask, options = {}) => {
   const element = document.querySelector(selector);
   if (element) {
      return new VanillaMask(element, mask, options);
   }
   return null;
};

// Vanilla JavaScript initialization function
export const initializeMasks = () => {
   // Phone mask
   applyMaskToElement("#phone", SPMaskBehavior, spOptions);

   // CPF mask
   applyMaskToElement("#cpf", "000.000.000-00", {
      placeholder: "___.___.___-__",
   });

   // Business phone mask
   applyMaskToElement("#phone_business", SPMaskBehavior, spOptions);

   // CNPJ mask
   applyMaskToElement("#cnpj", "00.000.000/0000-00", {
      placeholder: "__.___.___/____-__",
   });

   // CEP mask
   applyMaskToElement("#cep", "00.000-000", {});
};
