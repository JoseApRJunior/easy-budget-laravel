document.addEventListener("DOMContentLoaded", function () {
   // Elementos do DOM
   const searchInput = document.getElementById("search");
   const clearMainSearch = document.getElementById("clearMainSearch");
   const mainSearchBtn = document.getElementById("mainSearch");
   const initialMessage = document.getElementById("initial-message");
   const loadingSpinner = document.getElementById("loading-spinner");
   const resultsContainer = document.getElementById("results-container");
   const resultsCount = document.getElementById("results-count");



   // Verificação de elementos essenciais
   if (!searchInput) {
      console.error('Elemento #search não encontrado!');
      return;
   }

   // Inicializa o paginador de tabela
   const customerPaginator = new TablePaginator({
      tableId: "results-table",
      paginationId: "pagination",
      infoId: "pagination-info",
      itemsPerPage: 10,
      colSpan: 6,
      formatRow: formatCustomerRow,
   });

   async function performSearch() {
      const searchTerm = searchInput?.value?.trim() || '';

      if (!initialMessage || !loadingSpinner || !resultsContainer) {
         console.error('Elementos da interface não encontrados');
         return;
      }

      // Controle de visibilidade dos elementos
      initialMessage.classList.add("d-none");
      loadingSpinner.classList.remove("d-none");
      resultsContainer.classList.add("d-none");

      try {
         // Obtém o token CSRF
         const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
         if (!csrfToken) {
            throw new Error('Token CSRF não encontrado');
         }

         // Configuração da requisição AJAX
         const response = await fetch("/provider/customers/search", {
            method: "POST",
            headers: {
               "Content-Type": "application/json",
               "X-Requested-With": "XMLHttpRequest",
               "X-CSRF-TOKEN": csrfToken,
            },
            body: JSON.stringify({
               search: searchTerm,
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
      if (searchInput) {
         searchInput.value = "";
         searchInput.focus();
      }

      if (initialMessage) initialMessage.classList.remove("d-none");
      if (resultsContainer) resultsContainer.classList.add("d-none");
      if (loadingSpinner) loadingSpinner.classList.add("d-none");
      if (resultsCount) resultsCount.textContent = "";

      customerPaginator.updateTable([]);
   }

   // Função para formatar cada linha da tabela de clientes
   function formatCustomerRow(customer) {
      const customerType = customer.cnpj ? 'PJ' : 'PF';
      const customerTypeClass = customer.cnpj ? 'text-info' : 'text-success';
      const customerTypeIcon = customer.cnpj ? 'bi-building' : 'bi-person';
      
      return `
      <tr class="table-row-hover">
         <td class="px-4 py-3 align-middle">
            <div>
               <div class="fw-semibold mb-1">${customer.customer_name || 'Nome não informado'}</div>
               <small class="badge bg-light ${customerTypeClass} border">${customerType}</small>
            </div>
         </td>
         <td class="px-3 py-3 align-middle">
            <div class="document-info">
               ${customer.cpf ? `<div class="text-dark"><i class="bi bi-person-vcard me-1"></i>${formatDocument(customer.cpf, 'cpf')}</div>` : ''}
               ${customer.cnpj ? `<div class="text-dark"><i class="bi bi-building me-1"></i>${formatDocument(customer.cnpj, 'cnpj')}</div>` : ''}
               ${!customer.cpf && !customer.cnpj ? '<span class="text-muted">Não informado</span>' : ''}
            </div>
         </td>
         <td class="px-3 py-3 align-middle">
            <div class="email-info">
               ${customer.email ? `<div class="text-dark mb-1"><i class="bi bi-envelope me-1"></i>${customer.email}</div>` : ''}
               ${customer.email_business ? `<div class="text-muted small"><i class="bi bi-briefcase me-1"></i>${customer.email_business}</div>` : ''}
               ${!customer.email && !customer.email_business ? '<span class="text-muted">Não informado</span>' : ''}
            </div>
         </td>
         <td class="px-3 py-3 align-middle">
            <div class="phone-info">
               ${customer.phone ? `<div class="text-dark mb-1"><i class="bi bi-telephone me-1"></i>${formatPhone(customer.phone)}</div>` : ''}
               ${customer.phone_business ? `<div class="text-muted small"><i class="bi bi-briefcase me-1"></i>${formatPhone(customer.phone_business)}</div>` : ''}
               ${!customer.phone && !customer.phone_business ? '<span class="text-muted">Não informado</span>' : ''}
            </div>
         </td>
         <td class="px-3 py-3 align-middle">
            <div class="date-info">
               <div class="text-dark">${formatDate(customer.created_at)}</div>
               <small class="text-muted">${formatTimeAgo(customer.created_at)}</small>
            </div>
         </td>
         <td class="text-center align-middle">
            <div class="btn-group" role="group">
               <a href="/provider/customers/${customer.id}"
                  class="btn btn-sm btn-outline-primary"
                  data-bs-toggle="tooltip"
                  title="Visualizar">
                  <i class="bi bi-eye"></i>
               </a>
               <a href="/provider/customers/${customer.id}/edit"
                  class="btn btn-sm btn-outline-success"
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
      const existingAlerts = document.querySelectorAll('.alert');
      existingAlerts.forEach(alert => alert.remove());

      const alertContainer = document.createElement('div');
      alertContainer.innerHTML = `
         <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
         </div>
      `;

      const container = document.querySelector('.container-fluid');
      if (container) {
         container.insertBefore(alertContainer.firstElementChild, container.firstElementChild);
      }

      setTimeout(() => {
         const alert = document.querySelector('.alert-danger');
         if (alert) {
            alert.classList.remove('show');
            setTimeout(() => alert.remove(), 150);
         }
      }, 5000);
   }

   // Formata data para o padrão brasileiro
   function formatDate(dateString) {
      const date = new Date(dateString);
      return date.toLocaleDateString("pt-BR");
   }

   // Formata documento (CPF/CNPJ)
   function formatDocument(document, type) {
      if (!document) return '';
      const clean = document.replace(/\D/g, '');
      if (type === 'cpf' && clean.length === 11) {
         return clean.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
      }
      if (type === 'cnpj' && clean.length === 14) {
         return clean.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
      }
      return document;
   }

   // Formata telefone
   function formatPhone(phone) {
      if (!phone) return '';
      const clean = phone.replace(/\D/g, '');
      if (clean.length === 11) {
         return clean.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
      }
      if (clean.length === 10) {
         return clean.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
      }
      return phone;
   }

   // Formata tempo relativo
   function formatTimeAgo(dateString) {
      const date = new Date(dateString);
      const now = new Date();
      const diffTime = Math.abs(now - date);
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
      
      if (diffDays === 1) return 'Ontem';
      if (diffDays < 7) return `${diffDays} dias atrás`;
      if (diffDays < 30) return `${Math.floor(diffDays / 7)} semanas atrás`;
      if (diffDays < 365) return `${Math.floor(diffDays / 30)} meses atrás`;
      return `${Math.floor(diffDays / 365)} anos atrás`;
   }

   // Event Listeners
   if (mainSearchBtn) {
      mainSearchBtn.addEventListener("click", (e) => {
         e.preventDefault();
         performSearch();
      });
   }

   if (searchInput) {
      searchInput.addEventListener("keypress", (e) => {
         if (e.key === "Enter") {
            e.preventDefault();
            performSearch();
         }
      });
   }

   if (clearMainSearch) {
      clearMainSearch.addEventListener("click", clearFields);
   }
});

// Função para confirmar exclusão
function confirmDelete(customerId) {
   const modal = new bootstrap.Modal(document.getElementById("deleteModal"));
   const confirmBtn = document.getElementById("confirmDeleteBtn");
   confirmBtn.href = `/provider/customers/delete/${customerId}`;
   modal.show();
}