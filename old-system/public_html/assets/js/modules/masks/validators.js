export const validators = {
   cpf: (value) => {
      value = value.replace(/[^\d]/g, "");

      if (value.length !== 11) return false;

      // Validação do CPF
      let sum = 0;
      let rest;

      if (value === "00000000000") return false;

      for (let i = 1; i <= 9; i++) {
         sum = sum + parseInt(value.substring(i - 1, i)) * (11 - i);
      }

      rest = (sum * 10) % 11;
      if (rest === 10 || rest === 11) rest = 0;
      if (rest !== parseInt(value.substring(9, 10))) return false;

      sum = 0;
      for (let i = 1; i <= 10; i++) {
         sum = sum + parseInt(value.substring(i - 1, i)) * (12 - i);
      }

      rest = (sum * 10) % 11;
      if (rest === 10 || rest === 11) rest = 0;
      if (rest !== parseInt(value.substring(10, 11))) return false;

      return true;
   },

   cnpj: (value) => {
      value = value.replace(/[^\d]/g, "");

      if (value.length !== 14) return false;

      // Validação do CNPJ
      if (value === "00000000000000") return false;

      // Valida DVs
      let length = value.length - 2;
      let numbers = value.substring(0, length);
      let digits = value.substring(length);
      let sum = 0;
      let pos = length - 7;

      for (let i = length; i >= 1; i--) {
         sum += numbers.charAt(length - i) * pos--;
         if (pos < 2) pos = 9;
      }

      let result = sum % 11 < 2 ? 0 : 11 - (sum % 11);
      if (result !== parseInt(digits.charAt(0))) return false;

      length = length + 1;
      numbers = value.substring(0, length);
      sum = 0;
      pos = length - 7;

      for (let i = length; i >= 1; i--) {
         sum += numbers.charAt(length - i) * pos--;
         if (pos < 2) pos = 9;
      }

      result = sum % 11 < 2 ? 0 : 11 - (sum % 11);
      if (result !== parseInt(digits.charAt(1))) return false;

      return true;
   },

   phone: (value) => {
      const cleaned = value.replace(/[^\d]/g, "");
      return cleaned.length >= 10 && cleaned.length <= 11;
   },

   cep: (value) => {
      const cleaned = value.replace(/[^\d]/g, "");
      return cleaned.length === 8;
   },
};
