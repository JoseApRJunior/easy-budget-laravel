import { PATTERNS } from "./patterns.js";

export const maskOptions = {
   phone: {
      onKeyPress: function (val, e, field, options) {
         field.mask(PATTERNS.PHONE.pattern.apply({}, arguments), options);
      },
      clearIfNotMatch: true,
      placeholder: PATTERNS.PHONE.placeholder,
   },
   cpf: {
      clearIfNotMatch: true,
      placeholder: PATTERNS.CPF.placeholder,
      reverse: true,
   },
   cnpj: {
      clearIfNotMatch: true,
      placeholder: PATTERNS.CNPJ.placeholder,
      reverse: true,
   },
   cep: {
      clearIfNotMatch: true,
      placeholder: PATTERNS.CEP.placeholder,
   },
};
