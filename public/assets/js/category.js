document.addEventListener("DOMContentLoaded", function () {
   // Elementos do DOM
   const filtersForm = document.getElementById("filtersFormCategories");
   const searchInput = document.getElementById("search");
   const statusSelect = document.getElementById("active");
   const perPageSelect = document.getElementById("per_page");
   const deletedSelect = document.getElementById("deleted");
   const startDateInput = document.getElementById("start_date");
   const endDateInput = document.getElementById("end_date");
   const deleteModal = document.getElementById("deleteModal");
   const confirmAllModal = document.getElementById("confirmAllCategoriesModal");

   // Flag para detectar carregamento inicial
   let isInitialLoad = true;

   // Mover modais para o final do body para evitar problemas de posicionamento
   function moveModalsToBody() {
      [deleteModal, confirmAllModal].forEach(modal => {
         if (modal && modal.parentElement !== document.body) {
            document.body.appendChild(modal);
         }
      });
   }

   // Configurar modal de confirmação de exclusão
   function setupDeleteModal() {
      if (!deleteModal) return;

      deleteModal.addEventListener("show.bs.modal", function (event) {
         var button = event.relatedTarget;
         if (!button) return;

         var deleteUrl = button.getAttribute("data-delete-url");
         var categoryName = button.getAttribute("data-category-name");
         var form = document.getElementById("deleteForm");
         var nameEl = document.getElementById("deleteCategoryName");

         if (form && deleteUrl) {
            form.setAttribute("action", deleteUrl);
         }
         if (nameEl) {
            nameEl.textContent = '"' + (categoryName || "") + '"';
         }
      });
   }

   // Auxiliar para parsing de datas (formato DD/MM/AAAA)
   function parseDate(str) {
      if (!str) return null;
      const parts = str.split('/');
      if (parts.length === 3) {
         const d = new Date(parts[2], parts[1] - 1, parts[0]);
         return isNaN(d.getTime()) ? null : d;
      }
      return null;
   }

   // Validar intervalo de datas
   function validateDates() {
      if (!startDateInput?.value || !endDateInput?.value) return true;

      const start = parseDate(startDateInput.value);
      const end = parseDate(endDateInput.value);

      if (start && end && start > end) {
         const message = 'A data inicial não pode ser maior que a data final.';
         if (window.easyAlert) {
            window.easyAlert.warning(message);
         } else {
            alert(message);
         }
         return false;
      }
      return true;
   }

   // Configurar formulário de filtros
   function setupFilters() {
      if (filtersForm) {
         filtersForm.addEventListener("submit", function (e) {
            // 1. Validação de intervalo de datas
            if (!validateDates()) {
               e.preventDefault();
               return;
            }

            // 2. Validação de preenchimento de ambas as datas para busca por período
            const startVal = (startDateInput?.value || "").trim();
            const endVal = (endDateInput?.value || "").trim();

            if ((startVal && !endVal) || (!startVal && endVal)) {
               e.preventDefault();
               const message = 'Para filtrar por período, informe as datas inicial e final.';
               if (window.easyAlert) {
                  window.easyAlert.error(message);
               } else {
                  alert(message);
               }
               if (!startVal) startDateInput.focus();
               else endDateInput.focus();
               return;
            }

            // 3. Lógica do Modal "Listar Todos"
            if (filtersForm.querySelector('input[name="all"]')) return;

            var search = (searchInput?.value || "").trim();
            var status = (statusSelect?.value || "").trim();
            var deleted = (deletedSelect?.value || "").trim();
            
            var hasFilters = !!(
               search || 
               (status !== "all" && status !== "") || 
               (deleted !== "all") || 
               startVal || 
               endVal
            );

            if (!hasFilters) {
               e.preventDefault();
               showConfirmModal();
            }
         });
      }
   }

   // Modal de confirmação para mostrar todos
   function showConfirmModal() {
      var modalEl = document.getElementById("confirmAllCategoriesModal");
      if (!modalEl) {
         filtersForm.submit();
         return;
      }

      var modal = new bootstrap.Modal(modalEl);
      var confirmBtn = modalEl.querySelector(".btn-confirm-all-categories");
      
      if (confirmBtn) {
         const handleClick = function () {
            var hidden = document.createElement("input");
            hidden.type = "hidden";
            hidden.name = "all";
            hidden.value = "1";
            filtersForm.appendChild(hidden);

            modal.hide();
            filtersForm.submit();
            
            confirmBtn.removeEventListener("click", handleClick);
         };

         confirmBtn.addEventListener("click", handleClick);
      }

      modal.show();
   }

   // Inicialização
   moveModalsToBody();
   setupDeleteModal();
   setupFilters();

   setTimeout(() => {
      isInitialLoad = false;
   }, 100);
});
