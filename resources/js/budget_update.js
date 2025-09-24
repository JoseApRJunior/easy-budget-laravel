/**
 * budget_update.js
 * Script para gerenciar a atualização de orçamentos
 */

// Inicialização
document.addEventListener("DOMContentLoaded", function () {
   // Inicializar componentes
   initComponents();
});

/**
 * Inicializa todos os componentes
 */
function initComponents() {
   // Elementos DOM
   const elements = {
      description: document.getElementById("description"),
      charCount: document.getElementById("char-count"),
      paymentTerms: document.getElementById("payment_terms"),
      budgetStatus: document.getElementById("budget_status"),
      form: document.getElementById("update-budget-form"),
   };

   // Inicializar contador de caracteres
   if (elements.description && elements.charCount) {
      initCharCounter(elements);
   }

   // Inicializar confirmação de mudança de status
   if (elements.budgetStatus && elements.form) {
      initStatusChangeConfirmation(elements);
   }
}

/**
 * Inicializa o contador de caracteres para o campo de descrição
 */
function initCharCounter(elements) {
   // Atualiza o contador inicialmente
   updateCharCounter(elements);

   // Adiciona o evento de input para atualizar o contador
   elements.description.addEventListener("input", function () {
      updateCharCounter(elements);
   });
}

/**
 * Atualiza o contador de caracteres
 */
function updateCharCounter(elements) {
   const maxLength = elements.description.maxLength || 255;
   const currentLength = elements.description.value.length;
   const remaining = maxLength - currentLength;

   if (elements.charCount.querySelector("span")) {
      elements.charCount.querySelector("span").textContent = remaining;

      // Adiciona classe de alerta quando estiver próximo do limite
      if (remaining < 50) {
         elements.charCount.classList.add("text-warning");
      } else {
         elements.charCount.classList.remove("text-warning");
      }
   }
}

// Validação do campo de data
document.addEventListener("DOMContentLoaded", function () {
   const dueDateInput = document.getElementById("due_date");
   const dueDateFeedback = document.getElementById("due_date_feedback");
   const form = document.getElementById("update-budget-form");

   if (dueDateInput && form) {
      // Limpa a notificação quando a data é alterada
      dueDateInput.addEventListener("change", function () {
         dueDateInput.classList.remove("is-invalid");
         if (dueDateFeedback) {
            dueDateFeedback.textContent = "";
         }
      });

      dueDateInput.addEventListener("input", function () {
         dueDateInput.classList.remove("is-invalid");
         if (dueDateFeedback) {
            dueDateFeedback.textContent = "";
         }
      });

      // Validação da data antes de enviar o formulário
      form.addEventListener("submit", function (e) {
         const dateValue = dueDateInput.value;
         if (dateValue) {
            const selectedDate = new Date(dateValue);
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            // Se a data de serviço for anterior à data atual
            if (selectedDate < today) {
               // Verifica se é uma edição de um serviço existente com data passada
               const originalDate = new Date(budget.due_date);

               // Se a data selecionada for diferente da original e for passada
               if (
                  selectedDate.getTime() !== originalDate.getTime() &&
                  selectedDate < today
               ) {
                  e.preventDefault();
                  dueDateInput.classList.add("is-invalid");

                  // Exibe mensagem de erro
                  if (dueDateFeedback) {
                     dueDateFeedback.textContent =
                        "A data não pode ser anterior à data atual.";
                  }

                  // Exibe alerta
                  alert(
                     "A data de vencimento não pode ser anterior à data atual."
                  );
               }
            }
         }
      });
   }
});
/**
 * Inicializa a confirmação de mudança de status
 */
function initStatusChangeConfirmation(elements) {
   // Guarda o valor original do status
   const originalStatus = elements.budgetStatus.value;

   // Adiciona confirmação ao mudar para status críticos
   elements.form.addEventListener("submit", function (e) {
      const newStatus = elements.budgetStatus.value;

      // Se mudou para cancelado ou finalizado
      if (
         originalStatus != newStatus &&
         (newStatus == "3" || newStatus == "4")
      ) {
         const statusName =
            elements.budgetStatus.options[elements.budgetStatus.selectedIndex]
               .text;

         if (
            !confirm(
               `Tem certeza que deseja alterar o status para "${statusName}"? Esta ação pode ter consequências irreversíveis.`
            )
         ) {
            e.preventDefault();
            elements.budgetStatus.value = originalStatus;
         }
      }
   });
}

function showFieldError(field, message) {
   field.classList.add("is-invalid");

   // Determina onde colocar a mensagem de erro
   let errorContainer = field.nextElementSibling;
   if (
      errorContainer &&
      errorContainer.classList.contains("invalid-feedback")
   ) {
      errorContainer.remove();
   }
   errorContainer = null;

   // Se não encontrou um contêiner específico, usa o elemento após o campo
   if (!errorContainer) {
      errorContainer = document.createElement("div");
      field.insertAdjacentElement("afterend", errorContainer);
   }

   // Limpa o contêiner
   errorContainer.innerHTML = "";

   // Adiciona a mensagem de erro
   const feedback = document.createElement("div");
   feedback.className = "invalid-feedback d-block";
   feedback.textContent = message;
   errorContainer.appendChild(feedback);

   // Remove o erro quando o campo for alterado
   field.addEventListener(
      "input",
      function () {
         this.classList.remove("is-invalid");
         feedback.remove();
      },
      { once: true }
   );
}

/**
 * Mostra um alerta na página
 * @param {string} message - A mensagem a ser exibida
 * @param {string} type - O tipo de alerta (success, danger, warning, info)
 * @param {HTMLElement} container - O elemento onde o alerta será adicionado
 */
function showAlert(message, type = "info", container) {
   const alertDiv = document.createElement("div");
   alertDiv.className = `alert alert-${type} alert-dismissible fade show mt-3`;
   alertDiv.innerHTML = `
      ${message}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
   `;

   // Adiciona o alerta no topo do formulário
   container.prepend(alertDiv);

   // Remove o alerta após 5 segundos
   setTimeout(() => {
      alertDiv.classList.remove("show");
      setTimeout(() => alertDiv.remove(), 150);
   }, 5000);
}
