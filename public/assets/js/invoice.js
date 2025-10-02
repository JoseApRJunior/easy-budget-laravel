document.addEventListener("DOMContentLoaded", function () {
   const startDate = document.getElementById("start_date");
   const endDate = document.getElementById("end_date");
   const clearButton = document.getElementById("clear-filters");
   const filterForm = document.getElementById("filter-form");
   const resultsCount = document.getElementById("results-count");
   const loadingSpinner = document.getElementById("loading-spinner");
   const initialMessage = document.getElementById("initial-message");
   const resultsContainer = document.getElementById("results-container");

   const invoicePaginator = new TablePaginator({
      tableId: "results-table",
      paginationId: "pagination",
      infoId: "pagination-info",
      itemsPerPage: 10,
      colSpan: 6,
      formatRow: formatInvoiceRow,
   });

   function validateDates() {
      const startFilled = startDate.value !== "";
      const endFilled = endDate.value !== "";

      startDate.setCustomValidity("");
      endDate.setCustomValidity("");
      removeValidationUI(startDate);
      removeValidationUI(endDate);

      if (startFilled !== endFilled) {
         if (startFilled) {
            endDate.setCustomValidity("* Data final obrigatória");
            showValidationError(endDate);
         } else {
            startDate.setCustomValidity("* Data inicial obrigatória");
            showValidationError(startDate);
         }
         return;
      }

      if (startFilled && endFilled && startDate.value > endDate.value) {
         startDate.setCustomValidity(
            "* Data inicial deve ser menor que a final"
         );
         showValidationError(startDate);
         return;
      }

      if (startFilled && endFilled) {
         showValidationSuccess(startDate);
         showValidationSuccess(endDate);
      }
   }

   function removeValidationUI(input) {
      input.classList.remove("is-invalid", "is-valid");
      const errorSpan = input.parentNode.querySelector(".required-asterisk");
      if (errorSpan) {
         errorSpan.remove();
      }
   }

   function showValidationError(input) {
      removeValidationUI(input);
      input.classList.add("is-invalid");
      const asterisk = document.createElement("span");
      asterisk.className = "required-asterisk";
      asterisk.textContent = "* Obrigatório";
      input.parentNode.appendChild(asterisk);
   }

   function showValidationSuccess(input) {
      removeValidationUI(input);
      input.classList.add("is-valid");
   }

   function clearFields() {
      filterForm.reset();
      removeValidationUI(startDate);
      removeValidationUI(endDate);
      // Limpa o campo de valor monetário
      const moneyInput = document.querySelector(".money-input");
      if (moneyInput) {
         moneyInput.value = "";
      }
      initialMessage.classList.remove("d-none");
      resultsContainer.classList.add("d-none");
      if (resultsCount) {
         resultsCount.textContent = "";
      }
   }

   async function fetchResults(event) {
      try {
         event.preventDefault();

         initialMessage.classList.add("d-none");
         loadingSpinner.classList.remove("d-none");
         resultsContainer.classList.add("d-none");

         const formData = new FormData(filterForm);
         const data = {};

         formData.forEach((value, key) => {
            if (value) {
               data[key] = value;
            }
         });

         const response = await fetch("/provider/invoices/filter", {
            method: "POST",
            headers: {
               "Content-Type": "application/json",
               "X-Requested-With": "XMLHttpRequest",
               "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')
                  .content,
            },
            body: JSON.stringify(data),
         });

         if (!response.ok) {
            throw new Error(`Erro na requisição: ${response.status}`);
         }

         const invoices = await response.json();
         invoicePaginator.updateTable(invoices);

         if (resultsCount) {
            resultsCount.textContent = `${invoices.length} resultados encontrados`;
         }

         resultsContainer.classList.remove("d-none");
      } catch (error) {
         console.error("Erro detalhado:", error);
         showError("Ocorreu um erro ao buscar os resultados. Tente novamente.");
         initialMessage.classList.remove("d-none");
      } finally {
         loadingSpinner.classList.add("d-none");
      }
   }

   function getStatusBadge(status) {
      const statuses = {
         pending: { class: "bg-warning", text: "Pendente" },
         paid: { class: "bg-success", text: "Paga" },
         cancelled: { class: "bg-danger", text: "Cancelada" },
      };
      const statusInfo = statuses[status] || {
         class: "bg-secondary",
         text: status,
      };
      return `<span class="badge ${statusInfo.class}">${statusInfo.text}</span>`;
   }

   function formatInvoiceRow(invoice) {
      return `
      <tr>
         <td><strong>${invoice.code || ""}</strong></td>
         <td>${invoice.customer_name || ""}</td>
         <td><span class="badge" style="background-color: ${
            invoice.status_color || "#6c757d"
         }"><i class="bi ${invoice.status_icon || "bi-circle"}"></i> ${invoice.status_name}</span></td>
         <td>${formatMoney(invoice.total)}</td>
         <td>${formatDate(invoice.created_at)}</td>
         <td>${formatDate(invoice.due_date)}</td>
         <td class="text-end">
            <div class="btn-group gap-1">
               <a href="/provider/invoices/show/${
                  invoice.code
               }" class="btn btn-sm btn-outline-primary"
                  data-bs-toggle="tooltip" title="Ver Detalhes">
                  <i class="bi bi-eye"></i>
               </a>
            </div>
         </td>
      </tr>`;
   }

   function formatDate(dateString) {
      if (!dateString) return "";
      const date = new Date(dateString);
      // Adicionando timeZone: 'UTC' para evitar problemas de fuso horário na formatação
      return date.toLocaleDateString("pt-BR", { timeZone: "UTC" });
   }
   function formatMoney(value) {
      if (!value) return "R$ 0,00";
      return `R$ ${parseFloat(value).toLocaleString("pt-BR", {
         minimumFractionDigits: 2,
         maximumFractionDigits: 2,
      })}`;
   }
   function showError(message) {
      const alertDiv = document.createElement("div");
      alertDiv.className =
         "alert alert-danger alert-dismissible fade show mt-3";
      alertDiv.innerHTML = `
         ${message}
         <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
      `;
      filterForm.insertAdjacentElement("afterend", alertDiv);
      setTimeout(() => alertDiv.remove(), 5000);
   }

   // Event Listeners
   let timeoutId = null;
   function debouncedValidation() {
      clearTimeout(timeoutId);
      timeoutId = setTimeout(validateDates, 100);
   }

   ["change", "input"].forEach((eventType) => {
      startDate.addEventListener(eventType, debouncedValidation);
      endDate.addEventListener(eventType, debouncedValidation);
   });

   if (clearButton) {
      clearButton.addEventListener("click", (e) => {
         e.preventDefault();
         clearFields();
      });
   }

   if (filterForm) {
      filterForm.addEventListener("submit", fetchResults);
   }

   validateDates();
});

// Função para formatar moeda
function formatMoney(input) {
   // Remove tudo que não é número
   let value = input.value.replace(/\D/g, "");

   // Converte para número e divide por 100 para considerar os centavos
   value = (parseInt(value) / 100).toFixed(2);

   // Formata o número para o padrão brasileiro
   value = value.replace(".", ",");
   value = value.replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1.");

   // Adiciona R$ no início
   input.value = value ? `R$ ${value}` : "";
}

// Configuração do campo de valor mínimo
document.addEventListener("DOMContentLoaded", function () {
   const moneyInputs = document.querySelectorAll(".money-input");

   moneyInputs.forEach(function (input) {
      function formatMoney(value) {
         if (!value) return "";

         // Remove tudo exceto números
         value = value.replace(/\D/g, "");

         // Converte para centavos
         value = (parseInt(value) / 100).toFixed(2);

         // Formata com separadores
         value = value.replace(".", ",");
         value = value.replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1.");

         return `R$ ${value}`;
      }

      // Formata valor inicial
      if (input.value) {
         input.value = formatMoney(input.value.replace(/[^\d]/g, ""));
      }

      input.addEventListener("input", function (e) {
         let value = e.target.value.replace(/\D/g, "");
         const position = e.target.selectionStart;
         const oldLength = e.target.value.length;

         e.target.value = formatMoney(value);

         // Ajusta posição do cursor
         const newLength = e.target.value.length;
         const newPosition = position + (newLength - oldLength);
         e.target.setSelectionRange(newPosition, newPosition);
      });

      // Limpa formatação ao focar
      input.addEventListener("focus", function (e) {
         const value = e.target.value.replace(/\D/g, "");
         if (value === "0") {
            e.target.value = "";
         }
      });

      // Reaplica formatação ao perder foco
      input.addEventListener("blur", function (e) {
         const value = e.target.value.replace(/\D/g, "");
         if (value) {
            e.target.value = formatMoney(value);
         }
      });
   });
});
