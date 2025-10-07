export const PATTERNS = {
   PHONE: {
      pattern: function (val) {
         return val.replace(/\D/g, "").length === 11
            ? "(00) 00000-0000"
            : "(00) 0000-00009";
      },
      placeholder: "(__) _____-____",
   },
   CPF: {
      pattern: "000.000.000-00",
      placeholder: "___.___.___-__",
   },
   CNPJ: {
      pattern: "00.000.000/0000-00",
      placeholder: "__.___.___/____-__",
   },
   CEP: {
      pattern: "00.000-000",
      placeholder: "__.___-___",
   },
};
