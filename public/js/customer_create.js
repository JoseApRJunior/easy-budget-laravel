$(document).ready(function () {
   // Máscaras para campos
   $("#phone").mask("(00) 00000-0000", { placeholder: "(__) _____-____" });
   $("#cpf").mask("000.000.000-00", { placeholder: "___.___.___-__" });
   $("#phone_business").mask("(00) 0000-0000", {
      placeholder: "(__) ____-____",
   });
   $("#cnpj").mask("00.000.000/0000-00", { placeholder: "__.___.___/____-__" });
   $("#cep").mask("00.000-000", { placeholder: "__.___-___" });

   // Validação do formulário
   $("#updateForm").on("submit", function (e) {
      let isValid = true;

      // Validar campos obrigatórios
      $(this)
         .find("[required]")
         .each(function () {
            if ($(this).val().trim() === "") {
               isValid = false;
               $(this).addClass("is-invalid");
            } else {
               $(this).removeClass("is-invalid");
            }
         });

      // Validar email
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test($("#email").val())) {
         isValid = false;
         $("#email").addClass("is-invalid");
      }

      if (!isValid) {
         e.preventDefault();
         alert(
            "Por favor, preencha todos os campos obrigatórios corretamente."
         );
      } else {
         if (!confirm("Tem certeza que deseja atualizar seu cadastro?")) {
            e.preventDefault();
         }
      }
   });

   // Feedback visual para campos inválidos
   $("input, select").on("input change", function () {
      $(this).removeClass("is-invalid");
   });

   $("#cep").on("blur", function () {
      var cep = $(this).val().replace(/\D/g, "");
      if (cep.length === 8) {
         $.ajax({
            url: "/cep",
            type: "POST",
            data: {
               cep: cep,
            },
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
});
