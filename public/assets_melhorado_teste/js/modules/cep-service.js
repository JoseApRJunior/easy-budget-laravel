export const initializeCepService = () => {
   $("#cep").on("blur", function () {
      const cep = $(this).val().replace(/\D/g, "");

      if (cep.length === 8) {
         $.ajax({
            url: "/cep",
            type: "POST",
            data: { cep },
            dataType: "json",
            success: function (data) {
               if (!data.error) {
                  $("#city").val(data.city);
                  $("#state").val(data.state);
                  $("#address").val(data.street);
                  $("#neighborhood").val(data.neighborhood);
               } else {
                  alert("CEP não encontrado: " + data.error);
               }
            },
            error: function () {
               alert("Erro ao consultar CEP.");
            },
         });
      }
   });
};
