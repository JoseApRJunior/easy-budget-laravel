// masks.js
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

export const initializeMasks = () => {
   $("#phone").mask(SPMaskBehavior, spOptions);

   $("#cpf").mask("000.000.000-00", {
      placeholder: "___.___.___-__",
   });

   $("#phone_business").mask(SPMaskBehavior, spOptions);

   $("#cnpj").mask("00.000.000/0000-00", {
      placeholder: "__.___.___/____-__",
   });

   $("#cep").mask("00.000-000", {
      placeholder: "__.___-___",
   });
};