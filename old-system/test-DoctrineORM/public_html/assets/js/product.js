document.addEventListener("DOMContentLoaded", function () {
   // Elementos do DOM
   const filterForm = document.getElementById("filter-form");
   const clearButton = document.getElementById("clear-filters");
   const resultsCount = document.getElementById("results-count");
   const loadingSpinner = document.getElementById("loading-spinner");
   const initialMessage = document.getElementById("initial-message");
   const resultsContainer = document.getElementById("results-container");

   // Inicializa o paginador de tabela
   const productPaginator = new TablePaginator({
      tableId: "results-table",
      paginationId: "pagination",
      infoId: "pagination-info",
      itemsPerPage: 5,
      colSpan: 6,
      formatRow: formatProductRow,
   });

   // Função para limpar campos
   function clearFields() {
      // Limpa todos os campos do formulário
      filterForm.reset();

      // Limpa o campo de valor monetário
      const moneyInput = document.querySelector(".money-input");
      if (moneyInput) {
         moneyInput.value = "";
      }

      // Mostra mensagem inicial e esconde resultados
      initialMessage.classList.remove("d-none");
      resultsContainer.classList.add("d-none");

      // Limpa a tabela e a contagem
      productPaginator.updateTable([]);

      if (resultsCount) {
         resultsCount.textContent = "";
      }
   }

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

         const response = await fetch("/provider/products/search", {
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

         const products = await response.json();

         // Atualiza a tabela usando o paginador
         productPaginator.updateTable(products);

         // Atualiza o contador de resultados
         if (resultsCount) {
            resultsCount.textContent = `${products.length} resultados encontrados`;
         }

         // Mostra os resultados
         resultsContainer.classList.remove("d-none");

         // Reinicializa tooltips
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

   // Função para formatar cada linha da tabela de produtos
   function formatProductRow(product) {
      return `
      <tr>
         <td class="px-4 align-middle">
            <span class="fw-semibold">${product.code}</span>
         </td>
         <td class="align-middle">
            <div class="d-flex align-items-center">
               ${
                  product.image
                     ? `<img src="${product.image}" class="rounded me-2" width="40" height="40" >`
                     : `<div class="bg-opacity-10 rounded me-2 p-2"><i class="bi bi-box"></i></div>`
               }
               <div class="text-truncate" style="max-width: 200px;">
                  ${product.name}
               </div>
            </div>
         </td>
         <td class="align-middle">
            <span class="fw-semibold">
               ${formatMoney(product.price)}
            </span>
         </td>
         <td class="align-middle">
            <span class="badge bg-${
               product.stock_quantity > 0 ? "success" : "danger"
            }">
               ${product.stock_quantity} unidades
            </span>
         </td>
         <td class="align-middle">
            <span class="badge bg-${product.active ? "success" : "secondary"}">
               ${product.active ? "Ativo" : "Inativo"}
            </span>
         </td>
         <td class="text-end px-4 align-middle">
            <div class="btn-group gap-1">
               <a href="/provider/products/show/${product.code}"
                  class="btn btn-sm btn-outline-warning"
                  data-bs-toggle="tooltip"
                  title="Visualizar">
                  <i class="bi bi-eye"></i>
               </a>
               <a href="/provider/products/update/${product.code}"
                  class="btn btn-sm btn-outline-primary"
                  data-bs-toggle="tooltip"
                  title="Editar">
                  <i class="bi bi-pencil"></i>
               </a>
               <button type="button"
                     class="btn btn-sm btn-outline-danger"
                     onclick="confirmDelete('${product.code}')"
                     data-bs-toggle="tooltip"
                     title="Excluir">
                  <i class="bi bi-trash"></i>
               </button>
            </div>
         </td>
      </tr>`;
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
   if (clearButton) {
      clearButton.addEventListener("click", (e) => {
         e.preventDefault();
         clearFields();
      });
   }

   if (filterForm) {
      filterForm.addEventListener("submit", fetchResults);
   }

   // Configuração dos campos monetários
   const moneyInputs = document.querySelectorAll(".money-input");
   moneyInputs.forEach(function (input) {
      function formatMoneyInput(value) {
         if (!value) return "";
         value = value.replace(/\D/g, "");
         value = (parseInt(value) / 100).toFixed(2);
         value = value.replace(".", ",");
         value = value.replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1.");
         return `R$ ${value}`;
      }

      if (input.value) {
         input.value = formatMoneyInput(input.value.replace(/[^\d]/g, ""));
      }

      input.addEventListener("input", function (e) {
         let value = e.target.value.replace(/\D/g, "");
         const position = e.target.selectionStart;
         const oldLength = e.target.value.length;
         e.target.value = formatMoneyInput(value);
         const newLength = e.target.value.length;
         const newPosition = position + (newLength - oldLength);
         e.target.setSelectionRange(newPosition, newPosition);
      });

      input.addEventListener("focus", function (e) {
         const value = e.target.value.replace(/\D/g, "");
         if (value === "0") {
            e.target.value = "";
         }
      });

      input.addEventListener("blur", function (e) {
         const value = e.target.value.replace(/\D/g, "");
         if (value) {
            e.target.value = formatMoneyInput(value);
         }
      });
   });
});

// Função para confirmar exclusão
function confirmDelete(productId) {
   // Cria o modal dinamicamente
   const modalHtml = `
      <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
         <div class="modal-dialog">
            <div class="modal-content">
               <div class="modal-header">
                  <h5 class="modal-title" id="deleteConfirmModalLabel">Confirmar Exclusão</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
               </div>
               <div class="modal-body">
                  <p>Tem certeza que deseja excluir este produto? Esta ação não pode ser desfeita.</p>
               </div>
               <div class="modal-footer">
                  <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                  <a href="/provider/products/delete/${productId}" class="btn btn-danger">Excluir</a>
               </div>
            </div>
         </div>
      </div>
   `;

   // Adiciona o modal ao corpo do documento
   document.body.insertAdjacentHTML("beforeend", modalHtml);

   // Inicializa e mostra o modal
   const modal = new bootstrap.Modal(
      document.getElementById("deleteConfirmModal")
   );
   modal.show();

   // Remove o modal do DOM quando for fechado
   document
      .getElementById("deleteConfirmModal")
      .addEventListener("hidden.bs.modal", function () {
         this.remove();
      });
}
