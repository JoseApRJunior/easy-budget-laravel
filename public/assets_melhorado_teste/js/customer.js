document.addEventListener("DOMContentLoaded", function () {
   // Elementos do DOM
   const searchInput = document.getElementById("search");
   const initialMessage = document.getElementById("initial-message");
   const loadingSpinner = document.getElementById("loading-spinner");
   const resultsContainer = document.getElementById("results-container");
   const statusSelect = document.getElementById("status");
   const typeSelect = document.getElementById("type");
   const areaSelect = document.getElementById("area_of_activity_id");
   const deletedSelect = document.getElementById("deleted");

   // Verificação de elementos essenciais
   if (!searchInput) {
      console.error("Elemento #search não encontrado!");
      return;
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
   }

   // Função para mostrar mensagens de erro
   function showError(message) {
      const existingAlerts = document.querySelectorAll(".alert");
      existingAlerts.forEach((alert) => alert.remove());

      const alertContainer = document.createElement("div");
      alertContainer.innerHTML = `
         <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
         </div>
      `;

      const container = document.querySelector(".container-fluid");
      if (container) {
         container.insertBefore(
            alertContainer.firstElementChild,
            container.firstElementChild
         );
      }

      setTimeout(() => {
         const alert = document.querySelector(".alert-danger");
         if (alert) {
            alert.classList.remove("show");
            setTimeout(() => alert.remove(), 150);
         }
      }, 5000);
   }

   // Event Listeners
   if (searchInput) {
      searchInput.addEventListener("keypress", (e) => {
         if (e.key === "Enter") {
            e.preventDefault();
            handleFilterSubmit();
         }
      });
   }

   // Auto-submeter ao alterar filtros
   [statusSelect, typeSelect, areaSelect, deletedSelect].forEach((el) => {
      if (!el) return;
      el.addEventListener("change", () => {
         clearTimeout(window.filterTimeout);
         window.filterTimeout = setTimeout(() => handleFilterSubmit(), 400);
      });
   });

   function handleFilterSubmit() {
      document.getElementById("filtersFormCustomers").submit();
   }
});

// Função para confirmar exclusão
function confirmDelete(customerId) {
   const modal = new bootstrap.Modal(document.getElementById("deleteModal"));
   const confirmBtn = document.getElementById("confirmDeleteBtn");
   const csrfToken = document
      .querySelector('meta[name="csrf-token"]')
      .getAttribute("content");

   // Criar formulário DELETE dinamicamente
   const form = document.createElement("form");
   form.method = "POST";
   form.action = `/provider/customers/${customerId}`;
   form.style.display = "none";

   // Token CSRF
   const csrfInput = document.createElement("input");
   csrfInput.type = "hidden";
   csrfInput.name = "_token";
   csrfInput.value = csrfToken;
   form.appendChild(csrfInput);

   // Método DELETE
   const methodInput = document.createElement("input");
   methodInput.type = "hidden";
   methodInput.name = "_method";
   methodInput.value = "DELETE";
   form.appendChild(methodInput);

   document.body.appendChild(form);

   // Configurar botão de confirmação
   confirmBtn.onclick = function () {
      form.submit();
      modal.hide();
   };

   // Cleanup ao fechar modal
   document
      .getElementById("deleteModal")
      .addEventListener("hidden.bs.modal", function () {
         document.body.removeChild(form);
         confirmBtn.onclick = null;
      });

   modal.show();
}

// Restaurar cliente via POST com CSRF
function restoreCustomer(customerId) {
   const csrfToken = document
      .querySelector('meta[name="csrf-token"]')
      ?.getAttribute("content");
   if (!csrfToken) return;

   const form = document.createElement("form");
   form.method = "POST";
   form.action = `/provider/customers/${customerId}/restore`;
   const csrfInput = document.createElement("input");
   csrfInput.type = "hidden";
   csrfInput.name = "_token";
   csrfInput.value = csrfToken;
   form.appendChild(csrfInput);
   document.body.appendChild(form);
   form.submit();
}
