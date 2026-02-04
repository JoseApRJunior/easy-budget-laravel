// Exemplos de uso do EasyAlert

document.addEventListener("DOMContentLoaded", function () {
   // Exemplo básico
   document
      .getElementById("btn-success")
      .addEventListener("click", function () {
         easyAlert.success("Operação realizada com sucesso!");
      });

   document.getElementById("btn-error").addEventListener("click", function () {
      easyAlert.error("Ocorreu um erro ao processar sua solicitação.");
   });

   document
      .getElementById("btn-warning")
      .addEventListener("click", function () {
         easyAlert.warning("Atenção! Esta ação não pode ser desfeita.");
      });

   document.getElementById("btn-info").addEventListener("click", function () {
      easyAlert.info("O sistema será atualizado em breve.");
   });

   // Exemplo com opções personalizadas
   document.getElementById("btn-custom").addEventListener("click", function () {
      easyAlert.show("success", "Configurações salvas!", {
         duration: 10000,
         position: "top-center",
      });
   });

   // Exemplo de validação de campo
   document
      .getElementById("validate-field")
      .addEventListener("click", function () {
         const emailField = document.getElementById("email");
         if (!emailField.value.includes("@")) {
            easyAlert.validateField(
               emailField,
               "Por favor, insira um email válido."
            );
         } else {
            easyAlert.success("Email válido!");
         }
      });

   // Exemplo de validação de formulário
   document
      .getElementById("demo-form")
      .addEventListener("submit", function (e) {
         e.preventDefault();

         const isValid = easyAlert.validateForm(this, {
            name: {
               required: true,
               minLength: 3,
               message: "O nome deve ter pelo menos 3 caracteres",
            },
            email: {
               required: true,
               pattern: "^[^\\s@]+@[^\\s@]+\\.[^\\s@]+$",
               message: "Por favor, insira um email válido",
            },
            password: {
               required: true,
               minLength: 6,
               message: "A senha deve ter pelo menos 6 caracteres",
            },
         });

         if (isValid) {
            easyAlert.success("Formulário enviado com sucesso!");
         }
      });
});
