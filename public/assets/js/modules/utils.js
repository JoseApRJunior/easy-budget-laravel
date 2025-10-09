// Função para scroll suave
export function scrollToElement(elementId) {
   const element = document.getElementById(elementId);
   if (element) {
      const offset = 100;
      const elementPosition = element.getBoundingClientRect().top;
      const offsetPosition = elementPosition + window.scrollY - offset;
      window.scrollTo({ top: offsetPosition, behavior: "smooth" });
   }
}

// Funções de tema
export function toggleTheme() {
   const body = document.body;
   const themeButton = document.querySelector(".theme-toggle");
   const sunIcon = themeButton?.querySelector(".theme-light-icon");
   const moonIcon = themeButton?.querySelector(".theme-dark-icon");

   if (body.classList.contains("theme-dark")) {
      body.classList.replace("theme-dark", "theme-light");
      localStorage.setItem("theme", "light");
      // Mostrar ícone do sol e ocultar ícone da lua
      if (sunIcon) sunIcon.setAttribute("aria-hidden", "false");
      if (moonIcon) moonIcon.setAttribute("aria-hidden", "true");
   } else {
      body.classList.replace("theme-light", "theme-dark");
      localStorage.setItem("theme", "dark");
      // Mostrar ícone da lua e ocultar ícone do sol
      if (sunIcon) sunIcon.setAttribute("aria-hidden", "true");
      if (moonIcon) moonIcon.setAttribute("aria-hidden", "false");
   }
}

export function initTheme() {
   const savedTheme = localStorage.getItem("theme") || "dark";
   const body = document.body;
   body.classList.add(`theme-${savedTheme}`);

   // Aguardar o DOM estar pronto e definir aria-hidden baseado no tema inicial
   const setInitialIcons = () => {
      const themeButton = document.querySelector(".theme-toggle");
      const sunIcon = themeButton?.querySelector(".theme-light-icon");
      const moonIcon = themeButton?.querySelector(".theme-dark-icon");

      if (sunIcon && moonIcon) {
         if (savedTheme === "light") {
            sunIcon.setAttribute("aria-hidden", "false");
            moonIcon.setAttribute("aria-hidden", "true");
         } else {
            sunIcon.setAttribute("aria-hidden", "true");
            moonIcon.setAttribute("aria-hidden", "false");
         }
      }
   };

   // Tentar definir imediatamente
   setInitialIcons();

   // Tentar novamente após um pequeno delay para garantir que o DOM esteja pronto
   setTimeout(setInitialIcons, 100);
}

// Validação de formulário
export function validatePhoneNumber(phoneValue) {
   const cleanPhone = phoneValue.replace(/\D/g, "");
   return cleanPhone.length >= 10 && cleanPhone.length <= 11;
}

// Em utils.js
export function togglePassword(inputId) {
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

export function handleFormSubmit(event) {
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

export function initializePasswordValidation() {
   const passwordInput = document.getElementById("password");
   const confirmPasswordInput = document.getElementById("confirm_password");
   const passwordRules = document.querySelector(".password-rules ul");
   const passwordRulesStyle = document.querySelector(".password-rules");

   // Só inicializa se os elementos essenciais existirem na página
   if (
      passwordInput &&
      confirmPasswordInput &&
      passwordRules &&
      passwordRulesStyle
   ) {
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

export function updatePlanSelection(selectedPlan, buttons, select) {
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
