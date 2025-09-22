/**
 * service.js
 * Script para gerenciar a listagem e filtragem de serviços
 */

import { MoneyFormatter } from "./modules/moneyFormatter.js";

document.addEventListener("DOMContentLoaded", function () {
   // Elementos DOM
   const startDate = document.getElementById("start_date");
   const endDate = document.getElementById("end_date");
   const clearButton = document.getElementById("clear-filters");
   const filterForm = document.getElementById("filter-form");
   const resultsCount = document.getElementById("results-count");
   const loadingSpinner = document.getElementById("loading-spinner");
   const initialMessage = document.getElementById("initial-message");
   const resultsContainer = document.getElementById("results-container");
   const moneyInputs = document.querySelectorAll(".money-input");

   // Inicializa o paginador de tabela
   const servicePaginator = new TablePaginator({
      tableId: "results-table",
      paginationId: "pagination",
      infoId: "pagination-info",
      itemsPerPage: 10,
      colSpan: 7,
      formatRow: formatServiceRow,
   });

   // Inicialização
   initValidation();
   initMoneyFields();
   initEventListeners();

   /**
    * Inicializa a validação de formulário
    */
   function initValidation() {
      // Validação inicial de datas
      validateDates();
   }

   /**
    * Inicializa os campos monetários
    */
   function initMoneyFields() {
      // Aplicar formatação monetária
      moneyInputs.forEach((input) => {
         MoneyFormatter.setupInput(input);
      });
   }

   /**
    * Função para validar as datas
    */
   function validateDates() {
      const startFilled = startDate.value !== "";
      const endFilled = endDate.value !== "";

      // Limpa validações anteriores
      startDate.setCustomValidity("");
      endDate.setCustomValidity("");

      // Remove todas as mensagens de erro existentes
      removeValidationUI(startDate);
      removeValidationUI(endDate);

      // Se uma data está preenchida, a outra é obrigatória
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

      // Se ambas estão preenchidas, verifica se a data inicial é menor que a final
      if (startFilled && endFilled && startDate.value > endDate.value) {
         startDate.setCustomValidity(
            "* Data inicial deve ser menor que a final"
         );
         showValidationError(startDate);
         return;
      }

      // Se chegou aqui, as datas são válidas
      if (startFilled && endFilled) {
         showValidationSuccess(startDate);
         showValidationSuccess(endDate);
      }
   }

   /**
    * Função para remover UI de validação
    */
   function removeValidationUI(input) {
      input.classList.remove("is-invalid", "is-valid");
      const errorSpan = input.parentNode.querySelector(".required-asterisk");
      if (errorSpan) {
         errorSpan.remove();
      }
   }

   /**
    * Função para mostrar erro
    */
   function showValidationError(input) {
      removeValidationUI(input);
      input.classList.add("is-invalid");

      const asterisk = document.createElement("span");
      asterisk.className = "required-asterisk";
      asterisk.textContent = "* Obrigatório";
      input.parentNode.appendChild(asterisk);
   }

   /**
    * Função para mostrar sucesso
    */
   function showValidationSuccess(input) {
      removeValidationUI(input);
      input.classList.add("is-valid");
   }

   /**
    * Inicializa os event listeners
    */
   function initEventListeners() {
      // Event listeners para validação de datas com debounce
      ["change", "input"].forEach((eventType) => {
         startDate.addEventListener(eventType, debouncedValidation);
         endDate.addEventListener(eventType, debouncedValidation);
      });

      // Botão de limpar filtros
      if (clearButton) {
         clearButton.addEventListener("click", (e) => {
            e.preventDefault();
            clearFields();
         });
      }

      // Formulário de filtro
      if (filterForm) {
         filterForm.addEventListener("submit", fetchResults);
      }
   }

   /**
    * Validação de datas com debounce
    */
   let timeoutId = null;
   function debouncedValidation() {
      clearTimeout(timeoutId);
      timeoutId = setTimeout(validateDates, 100);
   }

   /**
    * Limpa os campos do formulário
    */
   function clearFields() {
      // Limpa todos os campos do formulário
      filterForm.reset();

      // Remove validações
      removeValidationUI(startDate);
      removeValidationUI(endDate);

      // Limpa o campo de valor monetário
      moneyInputs.forEach((input) => {
         input.value = "";
      });

      // Mostra mensagem inicial e esconde resultados
      initialMessage.classList.remove("d-none");
      resultsContainer.classList.add("d-none");

      // Limpa a tabela usando o paginador
      servicePaginator.updateTable([]);

      if (resultsCount) {
         resultsCount.textContent = "";
      }
   }

   /**
    * Busca resultados via AJAX
    */
   async function fetchResults(event) {
      try {
         event.preventDefault();

         // Esconde mensagem inicial e mostra loading
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

         const response = await fetch("/provider/services/services_filter", {
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

         const services = await response.json();

         // Atualiza a tabela usando o paginador
         servicePaginator.updateTable(services);

         // Atualiza o contador de resultados
         if (resultsCount) {
            resultsCount.textContent = `${services.length} resultados encontrados`;
         }

         // Mostra os resultados
         resultsContainer.classList.remove("d-none");

         // Inicializa tooltips
         const tooltips = document.querySelectorAll(
            '[data-bs-toggle="tooltip"]'
         );
         tooltips.forEach((el) => new bootstrap.Tooltip(el));
      } catch (error) {
         console.error("Erro detalhado:", error);
         showError("Ocorreu um erro ao buscar os resultados. Tente novamente.");
         initialMessage.classList.remove("d-none");
      } finally {
         loadingSpinner.classList.add("d-none");
      }
   }

   /**
    * Formata cada linha da tabela de serviços
    */
   function formatServiceRow(service) {
      return `
      <tr>
         <td class="px-4 align-middle">
            <span class="fw-semibold">${service.code || ""}</span>
         </td>
         <td class="align-middle">
            ${service.customer_name || ""}
         </td>
         <td class="align-middle">
            <div class="text-truncate" style="max-width: 250px;">
               ${service.description || ""}
            </div>
         </td>
         <td class="align-middle">
            <div class="d-flex align-items-center">
               <i class="bi bi-calendar3 me-2 text-muted"></i>
               <span ${
                  isDateOverdue(service.due_date)
                     ? 'class="text-danger fw-medium"'
                     : ""
               }>
                  ${formatDate(service.due_date)}
               </span>
            </div>
         </td>
         <td class="align-middle">
            <span class="fw-semibold text-success">
               ${MoneyFormatter.format(service.total)}
            </span>
         </td>
         <td class="align-middle">
            <span class="badge" style="background-color: ${
               service.status_color || "#6c757d"
            }">
               ${service.status_name || ""}
            </span>
         </td>
         <td class="text-end px-4 align-middle">
            <div class="btn-group gap-1">
               <a href="/provider/services/show/${
                  service.code
               }" class="btn btn-sm btn-outline-warning"
                  data-bs-toggle="tooltip" title="Visualizar">
                  <i class="bi bi-eye"></i>
               </a>
               <a href="/provider/services/update/${
                  service.code
               }" class="btn btn-sm btn-outline-primary"
                  data-bs-toggle="tooltip" title="Editar">
                  <i class="bi bi-pencil"></i>
               </a>
               <button type="button" class="btn btn-sm btn-outline-danger"
                  onclick="confirmDelete('${service.code}')"
                  data-bs-toggle="tooltip" title="Excluir">
                  <i class="bi bi-trash"></i>
               </button>
            </div>
         </td>
      </tr>`;
   }

   /**
    * Verifica se uma data está vencida
    */
   function isDateOverdue(dateString) {
      if (!dateString) return false;
      const today = new Date();
      today.setHours(0, 0, 0, 0);
      const dueDate = new Date(dateString);
      return dueDate < today;
   }

   /**
    * Formata uma data para o formato brasileiro
    */
   function formatDate(dateString) {
      if (!dateString) return "";
      return new Date(dateString).toLocaleDateString("pt-BR");
   }

   /**
    * Mostra uma mensagem de erro
    */
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
});
