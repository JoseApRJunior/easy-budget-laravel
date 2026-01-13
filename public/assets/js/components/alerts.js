/**
 * EasyAlert - Sistema de alertas para o EasyBudget
 *
 * Um sistema de alertas leve e reutilizável para notificações e validações
 *
 * @author Amazon Q
 * @version 1.0.0
 */

class EasyAlert {
   constructor(options = {}) {
      // Configurações padrão
      this.defaults = {
         position: "top-right", // Posição do alerta: top-right, top-left, bottom-right, bottom-left, top-center, bottom-center
         duration: 5000, // Duração em ms (0 para não fechar automaticamente)
         closeButton: true, // Mostrar botão de fechar
         animation: true, // Usar animações
         maxAlerts: 5, // Número máximo de alertas visíveis ao mesmo tempo
         container: document.body, // Container onde os alertas serão adicionados
         zIndex: 9999, // z-index dos alertas
         icons: {
            success: '<i class="bi bi-check-circle-fill"></i>',
            error: '<i class="bi bi-x-circle-fill"></i>',
            warning: '<i class="bi bi-exclamation-triangle-fill"></i>',
            info: '<i class="bi bi-info-circle-fill"></i>',
            question: '<i class="bi bi-question-circle-fill"></i>',
         },
      };

      // Mescla as opções fornecidas com as padrões
      this.options = { ...this.defaults, ...options };

      // Inicializa o container de alertas
      this.initContainer();

      // Contador para IDs únicos
      this.counter = 0;

      // Armazena os alertas ativos
      this.activeAlerts = [];
   }

   /**
    * Inicializa o container de alertas
    */
   initContainer() {
      // Verifica se já existe um container
      let container = document.querySelector(".easy-alert-container");

      if (!container) {
         // Cria um novo container
         container = document.createElement("div");
         container.className = `easy-alert-container easy-alert-${this.options.position}`;
         container.style.zIndex = this.options.zIndex;
         this.options.container.appendChild(container);
      }

      this.container = container;
   }

   /**
    * Cria e exibe um alerta
    *
    * @param {string} type - Tipo de alerta: success, error, warning, info, question
    * @param {string} message - Mensagem do alerta
    * @param {object} options - Opções específicas para este alerta
    * @returns {string} ID do alerta criado
    */
   show(type, message, options = {}) {
      // Mescla as opções específicas com as padrões
      const alertOptions = { ...this.options, ...options };

      // Gera um ID único para o alerta
      const id = `easy-alert-${Date.now()}-${this.counter++}`;

      // Cria o elemento do alerta
      const alert = document.createElement("div");
      alert.id = id;
      alert.className = `easy-alert easy-alert-${type} ${
         alertOptions.animation ? "easy-alert-animated" : ""
      }`;
      alert.setAttribute("role", "alert");

      // Adiciona o ícone se disponível
      const icon = alertOptions.icons[type] || "";

      // Constrói o HTML do alerta
      alert.innerHTML = `
            <div class="easy-alert-content">
                ${icon ? `<div class="easy-alert-icon">${icon}</div>` : ""}
                <div class="easy-alert-message">${message}</div>
                ${
                   alertOptions.closeButton
                      ? '<button type="button" class="easy-alert-close" aria-label="Fechar">&times;</button>'
                      : ""
                }
            </div>
            ${
               alertOptions.duration > 0
                  ? '<div class="easy-alert-progress"></div>'
                  : ""
            }
        `;

      // Adiciona evento de clique no botão de fechar
      if (alertOptions.closeButton) {
         const closeButton = alert.querySelector(".easy-alert-close");
         closeButton.addEventListener("click", () => this.close(id));
      }

      // Adiciona o alerta ao container
      this.container.appendChild(alert);

      // Gerencia o número máximo de alertas
      this.manageAlerts();

      // Adiciona à lista de alertas ativos
      this.activeAlerts.push({
         id,
         element: alert,
         timeout:
            alertOptions.duration > 0
               ? setTimeout(() => this.close(id), alertOptions.duration)
               : null,
      });

      // Inicia a animação da barra de progresso
      if (alertOptions.duration > 0) {
         const progressBar = alert.querySelector(".easy-alert-progress");
         progressBar.style.animationDuration = `${alertOptions.duration}ms`;
         progressBar.classList.add("easy-alert-progress-active");
      }

      // Retorna o ID para referência futura
      return id;
   }

   /**
    * Fecha um alerta específico
    *
    * @param {string} id - ID do alerta a ser fechado
    */
   close(id) {
      const alertIndex = this.activeAlerts.findIndex(
         (alert) => alert.id === id
      );

      if (alertIndex !== -1) {
         const alert = this.activeAlerts[alertIndex];

         // Limpa o timeout se existir
         if (alert.timeout) {
            clearTimeout(alert.timeout);
         }

         // Adiciona classe de saída para animação
         alert.element.classList.add("easy-alert-exit");

         // Remove o alerta após a animação
         setTimeout(() => {
            if (alert.element.parentNode) {
               alert.element.parentNode.removeChild(alert.element);
            }
            this.activeAlerts.splice(alertIndex, 1);
         }, 300);
      }
   }

   /**
    * Fecha todos os alertas ativos
    */
   closeAll() {
      this.activeAlerts.forEach((alert) => this.close(alert.id));
   }

   /**
    * Gerencia o número máximo de alertas visíveis
    */
   manageAlerts() {
      if (this.activeAlerts.length >= this.options.maxAlerts) {
         this.close(this.activeAlerts[0].id);
      }
   }

   /**
    * Exibe um alerta de sucesso
    *
    * @param {string} message - Mensagem do alerta
    * @param {object} options - Opções específicas para este alerta
    * @returns {string} ID do alerta criado
    */
   success(message, options = {}) {
      return this.show("success", message, options);
   }

   /**
    * Exibe um alerta de erro
    *
    * @param {string} message - Mensagem do alerta
    * @param {object} options - Opções específicas para este alerta
    * @returns {string} ID do alerta criado
    */
   error(message, options = {}) {
      return this.show("error", message, options);
   }

   /**
    * Exibe um alerta de aviso
    *
    * @param {string} message - Mensagem do alerta
    * @param {object} options - Opções específicas para este alerta
    * @returns {string} ID do alerta criado
    */
   warning(message, options = {}) {
      return this.show("warning", message, options);
   }

   /**
    * Exibe um alerta informativo
    *
    * @param {string} message - Mensagem do alerta
    * @param {object} options - Opções específicas para este alerta
    * @returns {string} ID do alerta criado
    */
   info(message, options = {}) {
      return this.show("info", message, options);
   }

   /**
    * Exibe um alerta de pergunta
    *
    * @param {string} message - Mensagem do alerta
    * @param {object} options - Opções específicas para este alerta
    * @returns {string} ID do alerta criado
    */
   question(message, options = {}) {
      return this.show("question", message, options);
   }

   /**
    * Exibe um alerta de validação de formulário
    *
    * @param {HTMLElement} element - Elemento do formulário a ser validado
    * @param {string} message - Mensagem de erro
    * @param {object} options - Opções específicas para este alerta
    */
   validateField(element, message, options = {}) {
      // Adiciona classe de erro ao elemento
      element.classList.add("is-invalid");

      // Cria ou atualiza a mensagem de feedback
      let feedback = element.nextElementSibling;
      if (!feedback || !feedback.classList.contains("invalid-alert-feedback")) {
         feedback = document.createElement("div");
         feedback.className = "invalid-alert-feedback";
         element.parentNode.insertBefore(feedback, element.nextSibling);
      }

      feedback.textContent = message;

      // Exibe um alerta se especificado
      if (options.showAlert !== false) {
         this.error(message, {
            ...options,
            duration: options.duration || 3000,
         });
      }

      // Adiciona evento para remover o erro quando o campo for alterado
      const clearError = () => {
         element.classList.remove("is-invalid");
         if (feedback) {
            feedback.textContent = "";
         }
         element.removeEventListener("input", clearError);
         element.removeEventListener("change", clearError);
      };

      element.addEventListener("input", clearError);
      element.addEventListener("change", clearError);

      return false;
   }

   /**
    * Valida um formulário inteiro
    *
    * @param {HTMLFormElement} form - Formulário a ser validado
    * @param {object} rules - Regras de validação
    * @returns {boolean} Verdadeiro se o formulário for válido
    */
   validateForm(form, rules) {
      let isValid = true;

      for (const field in rules) {
         const element = form.elements[field];
         if (!element) continue;

         const rule = rules[field];
         const value = element.value.trim();

         // Verifica se o campo é obrigatório
         if (rule.required && value === "") {
            this.validateField(
               element,
               rule.message || "Este campo é obrigatório"
            );
            isValid = false;
            continue;
         }

         // Verifica o comprimento mínimo
         if (rule.minLength && value.length < rule.minLength) {
            this.validateField(
               element,
               rule.message ||
                  `Este campo deve ter pelo menos ${rule.minLength} caracteres`
            );
            isValid = false;
            continue;
         }

         // Verifica o comprimento máximo
         if (rule.maxLength && value.length > rule.maxLength) {
            this.validateField(
               element,
               rule.message ||
                  `Este campo deve ter no máximo ${rule.maxLength} caracteres`
            );
            isValid = false;
            continue;
         }

         // Verifica o padrão
         if (rule.pattern && !new RegExp(rule.pattern).test(value)) {
            this.validateField(
               element,
               rule.message || "Este campo não está no formato correto"
            );
            isValid = false;
            continue;
         }

         // Verifica a função de validação personalizada
         if (rule.validate && typeof rule.validate === "function") {
            const result = rule.validate(value, form);
            if (result !== true) {
               this.validateField(
                  element,
                  result || rule.message || "Este campo é inválido"
               );
               isValid = false;
               continue;
            }
         }
      }

      return isValid;
   }
}

// Cria uma instância global
window.easyAlert = new EasyAlert();

// Exporta a classe para uso com módulos
if (typeof module !== "undefined" && module.exports) {
   module.exports = EasyAlert;
}
