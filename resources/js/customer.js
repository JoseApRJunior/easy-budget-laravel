document.addEventListener("DOMContentLoaded", function () {
   // Inicializa tooltips
   const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
   tooltips.forEach((el) => new bootstrap.Tooltip(el));

   // Elementos do DOM
   const searchInput = document.getElementById("search");
   const clearMainSearch = document.getElementById("clearMainSearch");
   const mainSearchBtn = document.getElementById("mainSearch");
   const initialMessage = document.getElementById("initial-message");
   const loadingSpinner = document.getElementById("loading-spinner");
   const resultsContainer = document.getElementById("results-container");
   const tableBody = document.querySelector("tbody");
   const resultsCount = document.getElementById("results-count");

   // Inicializa o paginador de tabela
   const customerPaginator = new TablePaginator({
      tableId: "results-table",
      paginationId: "pagination",
      infoId: "pagination-info",
      itemsPerPage: 10,
      colSpan: 6,
      formatRow: formatCustomerRow,
   });

   // Verificação de debug
   console.log("Elemento de paginação:", document.getElementById("pagination"));

   async function performSearch() {
      const searchTerm = searchInput.value.trim();

      // Controle de visibilidade dos elementos
      initialMessage.classList.add("d-none");
      loadingSpinner.classList.remove("d-none");
      resultsContainer.classList.add("d-none");

      try {
         // Configuração da requisição AJAX
         const response = await fetch("/provider/customers/search", {
            method: "POST",
            headers: {
               "Content-Type": "application/json",
               "X-Requested-With": "XMLHttpRequest",
            },
            body: JSON.stringify({
               search: searchTerm,
               csrf_token: document.querySelector('[name="csrf_token"]').value,
            }),
         });

         if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
         }

         const data = await response.json();

         // Atualiza a tabela com os resultados
         customerPaginator.updateTable(data);

         // Atualiza o contador de resultados
         if (resultsCount) {
            resultsCount.textContent = `${data.length} resultados encontrados`;
         }

         // Mostra os resultados
         resultsContainer.classList.remove("d-none");
      } catch (error) {
         console.error("Erro na busca:", error);
         showError("Ocorreu um erro ao realizar a busca. Tente novamente.");
         initialMessage.classList.remove("d-none");
      } finally {
         loadingSpinner.classList.add("d-none");
      }
   }

   // Função para limpar campos e resetar estado
   function clearFields() {
      searchInput.value = "";
      searchInput.focus();

      // Reset visual state
      initialMessage.classList.remove("d-none");
      resultsContainer.classList.add("d-none");
      loadingSpinner.classList.add("d-none");

      if (resultsCount) {
         resultsCount.textContent = "";
      }

      // Limpa a tabela
      customerPaginator.updateTable([]);
   }

   // Função para formatar cada linha da tabela de clientes
   function formatCustomerRow(customer) {
      return `
   <tr>
      <td class="px-4 align-middle">
         <span class="fw-semibold">${customer.customer_name}</span>
      </td>
      <td class="align-middle">
         ${customer.cpf || ""}
         ${customer.cnpj ? "/ " + customer.cnpj : ""}
      </td>
      <td class="align-middle">
         ${customer.email || ""}
         ${customer.email_business ? "/ " + customer.email_business : ""}
      </td>
      <td class="align-middle">
         ${customer.phone || ""}
         ${customer.phone_business ? "/ " + customer.phone_business : ""}
      </td>
      <td class="align-middle">
         ${formatDate(customer.created_at)}
      </td>
      <td class="text-end px-4 align-middle">
         <div class="btn-group gap-1">
            <a href="/provider/customers/show/${customer.id}"
               class="btn btn-sm btn-outline-warning"
               data-bs-toggle="tooltip"
               title="Visualizar">
               <i class="bi bi-eye"></i>
            </a>
            <a href="/provider/customers/update/${customer.id}"
               class="btn btn-sm btn-outline-primary"
               data-bs-toggle="tooltip"
               title="Editar">
               <i class="bi bi-pencil"></i>
            </a>
            <button type="button"
                  class="btn btn-sm btn-outline-danger"
                  data-bs-toggle="tooltip"
                  onclick="confirmDelete('${customer.id}')"
                  title="Excluir">
               <i class="bi bi-trash"></i>
            </button>
         </div>
      </td>
   </tr>`;
   }

   // Função para mostrar mensagens de erro
   function showError(message) {
      const errorAlert = document.createElement("div");
      errorAlert.className = "alert alert-danger alert-dismissible fade show";
      errorAlert.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

      document
         .querySelector(".card-body")
         .insertAdjacentElement("afterbegin", errorAlert);

      setTimeout(() => {
         errorAlert.remove();
      }, 5000);
   }

   // Formata data para o padrão brasileiro
   function formatDate(dateString) {
      const date = new Date(dateString);
      return date.toLocaleDateString("pt-BR");
   }

   // Event Listeners
   mainSearchBtn.addEventListener("click", (e) => {
      e.preventDefault();
      performSearch();
   });

   searchInput.addEventListener("keypress", (e) => {
      if (e.key === "Enter") {
         e.preventDefault();
         performSearch();
      }
   });

   clearMainSearch.addEventListener("click", clearFields);

   // Animações e efeitos visuais
   searchInput.addEventListener("focus", () => {
      searchInput.parentElement.classList.add("search-focused");
   });

   searchInput.addEventListener("blur", () => {
      searchInput.parentElement.classList.remove("search-focused");
   });
});

// Função para confirmar exclusão
function confirmDelete(customerId) {
   const modal = new bootstrap.Modal(document.getElementById("deleteModal"));
   const confirmBtn = document.getElementById("confirmDeleteBtn");
   confirmBtn.href = `/provider/customers/delete/${customerId}`;
   modal.show();
}
