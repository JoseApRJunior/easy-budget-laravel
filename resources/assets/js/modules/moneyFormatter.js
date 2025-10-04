export const MoneyFormatter = {
   format(value) {
      if (!value) return "R$ 0,00";
      return `R$ ${parseFloat(value).toLocaleString("pt-BR", {
         minimumFractionDigits: 2,
         maximumFractionDigits: 2,
      })}`;
   },

   parse(value) {
      return value.replace(/\D/g, "");
   },

   setupInput(input) {
      function formatValue(value) {
         if (!value) return "";
         value = value.replace(/\D/g, "");
         value = (parseInt(value) / 100).toFixed(2);
         value = value.replace(".", ",");
         value = value.replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1.");
         return `R$ ${value}`;
      }

      // Formata valor inicial
      if (input.value) {
         input.value = formatValue(input.value.replace(/[^\d]/g, ""));
      }

      input.addEventListener("input", function (e) {
         let value = e.target.value.replace(/\D/g, "");
         const position = e.target.selectionStart;
         const oldLength = e.target.value.length;

         e.target.value = formatValue(value);

         const newLength = e.target.value.length;
         const newPosition = position + (newLength - oldLength);
         e.target.setSelectionRange(newPosition, newPosition);
      });

      input.addEventListener("focus", function (e) {
         const value = e.target.value.replace(/\D/g, "");
         if (value === "0") {
            e.target.value = "";
         }
      });

      input.addEventListener("blur", function (e) {
         const value = e.target.value.replace(/\D/g, "");
         if (value) {
            e.target.value = formatValue(value);
         }
      });
   },
};
