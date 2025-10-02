export const initializeFormValidation = () => {
   const validateEmail = (email) => {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      return emailRegex.test(email);
   };

   const validateRequiredFields = (form) => {
      let isValid = true;

      form.find("[required]").each(function () {
         if ($(this).val().trim() === "") {
            isValid = false;
            $(this).addClass("is-invalid");
         } else {
            $(this).removeClass("is-invalid");
         }
      });

      return isValid;
   };

   $("#updateForm").on("submit", function (e) {
      let isValid = validateRequiredFields($(this));

      if (!validateEmail($("#email").val())) {
         isValid = false;
         $("#email").addClass("is-invalid");
      }

      if (!isValid) {
         console.log("Formulário inválido");
         e.preventDefault();
         alert(
            "Por favor, preencha todos os campos obrigatórios corretamente."
         );
      } else if (!confirm("Tem certeza que deseja atualizar seu cadastro?")) {
         e.preventDefault();
      }
   });

   // Feedback visual para campos inválidos
   $("input, select").on("input change", function () {
      $(this).removeClass("is-invalid");
   });
};
