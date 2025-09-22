// Função para scroll suave
function scrollToElement(elementId) {
   const element = document.getElementById(elementId);
   if (element) {
      const offset = 100;
      const elementPosition = element.getBoundingClientRect().top;
      const offsetPosition = elementPosition + window.scrollY - offset;
      window.scrollTo({ top: offsetPosition, behavior: "smooth" });
   }
}

// Funções de tema
function toggleTheme() {
   const body = document.body;
   if (body.classList.contains("theme-dark")) {
      body.classList.replace("theme-dark", "theme-light");
      localStorage.setItem("theme", "light");
   } else {
      body.classList.replace("theme-light", "theme-dark");
      localStorage.setItem("theme", "dark");
   }
}

function initTheme() {
   const savedTheme = localStorage.getItem("theme") || "dark";
   document.body.classList.add(`theme-${savedTheme}`);
}

// Validação de formulário
function validatePhoneNumber(phoneValue) {
   const cleanPhone = phoneValue.replace(/\D/g, "");
   return cleanPhone.length >= 10 && cleanPhone.length <= 11;
}

// Toggle password visibility
function togglePassword(inputId) {
   const input = document.getElementById(inputId);
   const button = input.parentNode.querySelector(".password-toggle");
   const eyeIcon = button.querySelector(".bi-eye, .bi-eye-slash");

   if (input.type === "password") {
      input.type = "text";
      eyeIcon.classList.replace("bi-eye", "bi-eye-slash");
   } else {
      input.type = "password";
      eyeIcon.classList.replace("bi-eye-slash", "bi-eye");
   }
}

function handleFormSubmit(event) {
   event.preventDefault();

   const isValid = validateFormPassword();

   if (isValid) {
      event.target.submit();
   }
}

const rules = [
   { regex: /.{6,}/, text: "Pelo menos 6 caracteres" },
   { regex: /[a-z]/, text: "Letras minúsculas (a-z)" },
   { regex: /[A-Z]/, text: "Letras maiúsculas (A-Z)" },
   { regex: /[0-9]/, text: "Números (0-9)" },
   { regex: /[@#$!%*?&]/, text: "Caracteres especiais (@#$!%*?&)" },
];

function validateFormPassword() {
   var isValid = true;
   const passwordInput = document.getElementById("password");
   const confirmPasswordInput = document.getElementById("confirm_password");

   const passwordValue = passwordInput.value;

   for (const rule of rules) {
      if (!rule.regex.test(passwordValue)) {
         // Adicionar mensagem de erro
         setInvalid(passwordInput, "Senha inválida: " + rule.text);
         isValid = false;
         break;
      }
   }

   if (passwordInput.value !== confirmPasswordInput.value) {
      setInvalid(confirmPasswordInput, "As senhas não coincidem");
      isValid = false;
   }

   return isValid;
}

function initializePasswordValidation() {
   const passwordInput = document.getElementById("password");
   const confirmPasswordInput = document.getElementById("confirm_password");
   const passwordRules = document.querySelector(".password-rules ul");
   const passwordRulesStyle = document.querySelector(".password-rules");

   if (passwordInput && confirmPasswordInput) {
      passwordInput.addEventListener("input", () => {
         if (passwordInput.value.length > 0) {
            passwordRulesStyle.style.display = "block";
         } else {
            passwordRulesStyle.style.display = "none";
         }

         const password = passwordInput.value;

         const lis = passwordRules.children;
         let allRulesOk = true;
         Array.from(lis).forEach((li, index) => {
            const rule = rules[index];
            if (rule.regex.test(password)) {
               li.style.color = "#2ecc71";
            } else {
               li.style.color = "#ccc";
               allRulesOk = false;
            }

            if (allRulesOk) {
               setValid(passwordInput);
            } else {
               setInvalid(passwordInput);
            }
         });
      });

      confirmPasswordInput.addEventListener("keyup", () =>
         validateConfirmPassword(passwordInput, confirmPasswordInput)
      );
      passwordInput.addEventListener("keyup", () =>
         validateConfirmPassword(passwordInput, confirmPasswordInput)
      );
   }
}

function validateConfirmPassword(passwordInput, confirmPasswordInput) {
   const password = passwordInput.value;
   const confirmPassword = confirmPasswordInput.value;

   if (confirmPassword.length > 0) {
      if (password !== confirmPassword) {
         setInvalid(confirmPasswordInput, "As senhas não coincidem");
      } else {
         setValid(confirmPasswordInput);
      }
   }
}

function setValid(input) {
   const feedback = input.nextElementSibling;
   if (feedback && feedback.classList.contains("invalid-feedback")) {
      feedback.remove();
   }
}

function setInvalid(input, message) {
   let feedback = input.nextElementSibling;
   if (!feedback || !feedback.classList.contains("invalid-feedback")) {
      feedback = document.createElement("div");
      feedback.className = "invalid-feedback";
      input.parentNode.insertBefore(feedback, input.nextSibling);
   }
   feedback.textContent = message;
   feedback.style.display = "block";
}

function updatePlanSelection(selectedPlan, buttons, select) {
   // Atualizar select
   for (let i = 0; i < select.options.length; i++) {
      if (select.options[i].text.includes(selectedPlan)) {
         select.selectedIndex = i;
         break;
      }
   }

   // Atualizar botões
   buttons.forEach((btn) => {
      if (btn.getAttribute("data-plan") === selectedPlan) {
         btn.classList.add("active");
      } else {
         btn.classList.remove("active");
      }
   });
}

// Inicialização principal
document.addEventListener("DOMContentLoaded", function () {
   console.log("App.js carregado - Easy Budget Laravel");

   // Inicializar tema
   initTheme();

   // Event listener para tema no header
   const themeButton = document.querySelector('[onclick="toggleTheme()"]');
   if (themeButton) {
      themeButton.removeAttribute("onclick");
      themeButton.addEventListener("click", toggleTheme);
   }

   // Submenu dropdown
   document
      .querySelectorAll(".dropdown-submenu .dropdown-toggle")
      .forEach(function (element) {
         element.addEventListener("click", function (e) {
            e.preventDefault();
            e.stopPropagation();

            const submenu = this.nextElementSibling;
            if (submenu) {
               submenu.classList.toggle("show");
            }
         });
      });

   // Inicializar elementos específicos da página
   setupElements();
   setupFormMasks();
   initializePasswordValidation();
   setupPlanSelection();
   setupTermsValidation();
});

function setupElements() {
   const planButtons = document.querySelectorAll(".select-plan");
   const planSelect = document.getElementById("planSelect");
   const conhecaPlanos = document.getElementById("conhecaPlanos");

   if (conhecaPlanos) {
      conhecaPlanos.addEventListener("click", (e) => {
         e.preventDefault();
         scrollToElement("plans");
      });
   }

   planButtons.forEach((button) => {
      button.addEventListener("click", () => {
         const selectedPlan = button.getAttribute("data-plan");
         updatePlanSelection(selectedPlan, planButtons, planSelect);
         scrollToElement("preCadastroForm");
      });
   });

   document.querySelectorAll(".password-toggle").forEach((button) => {
      button.addEventListener("click", () => {
         togglePassword(button.getAttribute("data-input"));
      });
   });
}

function setupFormMasks() {
   console.log("Configurando máscaras nativas - SEM jQuery");

   const phoneInput = document.getElementById("phone");
   if (phoneInput) {
      console.log("Aplicando máscara nativa ao telefone");
      phoneInput.addEventListener("input", function (e) {
         // Remove tudo que não é dígito
         let digits = e.target.value.replace(/\D/g, "");

         // Limita rigorosamente a 11 dígitos
         digits = digits.substring(0, 11);

         // Aplica formatação baseada no número de dígitos
         let formatted = "";
         if (digits.length >= 11) {
            formatted = digits.replace(/(\d{2})(\d{5})(\d{4})/, "($1) $2-$3");
         } else if (digits.length >= 10) {
            formatted = digits.replace(/(\d{2})(\d{4})(\d{4})/, "($1) $2-$3");
         } else if (digits.length > 6) {
            formatted = digits.replace(/(\d{2})(\d{4})(\d+)/, "($1) $2-$3");
         } else if (digits.length > 2) {
            formatted = digits.replace(/(\d{2})(\d+)/, "($1) $2");
         } else {
            formatted = digits;
         }

         e.target.value = formatted;
      });
   }

   const form = document.querySelector("#preRegisterForm");
   if (form) {
      form.addEventListener("submit", (e) => {
         if (!checkTerms()) {
            e.preventDefault();
            return;
         }
         handleFormSubmit(e);
      });
   }
}

function setupTermsValidation() {
   const checkbox = document.getElementById("terms_accepted");
   if (checkbox) {
      checkbox.addEventListener("change", function () {
         if (this.checked) {
            clearTermsError();
         }
      });
   }
}

function checkTerms() {
   const checkbox = document.getElementById("terms_accepted");
   if (!checkbox.checked) {
      displayTermsError();
      return false;
   }
   return true;
}

function displayTermsError() {
   const checkbox = document.getElementById("terms_accepted");
   clearTermsError();

   const errorDiv = document.createElement("div");
   errorDiv.className = "alert alert-danger mt-2";
   errorDiv.id = "terms-error";
   errorDiv.setAttribute("role", "alert");
   errorDiv.innerHTML =
      '<i class="bi bi-exclamation-triangle-fill me-2"></i>Você precisa aceitar os termos e a política de privacidade.';

   checkbox.closest(".form-check").appendChild(errorDiv);
   checkbox.classList.add("is-invalid");
   checkbox.focus();
}

function clearTermsError() {
   const errorDiv = document.getElementById("terms-error");
   const checkbox = document.getElementById("terms_accepted");

   if (errorDiv) {
      errorDiv.remove();
   }
   if (checkbox) {
      checkbox.classList.remove("is-invalid");
   }
}

function setupPlanSelection() {
   const planButtons = document.querySelectorAll(".select-plan");
   const planSelect = document.getElementById("planSelect");

   planButtons.forEach((button) => {
      button.addEventListener("click", () => {
         const selectedPlan = button.getAttribute("data-plan");
         updatePlanSelection(selectedPlan, planButtons, planSelect);

         const preCadastroForm = document.getElementById("preCadastroForm");
         if (preCadastroForm) {
            preCadastroForm.scrollIntoView({ behavior: "smooth" });
         }
      });
   });
}

// Export functions for potential use in other modules
window.EasyBudget = {
   scrollToElement,
   toggleTheme,
   initTheme,
   validatePhoneNumber,
   togglePassword,
   handleFormSubmit,
   initializePasswordValidation,
   updatePlanSelection,
};
