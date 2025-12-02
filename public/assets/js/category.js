document.addEventListener("DOMContentLoaded", function () {
   // Elementos do DOM
   const filtersForm = document.getElementById("filtersFormCategories");
   const searchInput = document.getElementById("search");
   const statusSelect = document.getElementById("active");
   const perPageSelect = document.getElementById("per_page");
   const deletedSelect = document.getElementById("deleted");
   const deleteModal = document.getElementById("deleteModal");

   // Configurar modal de confirmação
   function setupDeleteModal() {
      if (deleteModal && deleteModal.parentElement !== document.body) {
         document.body.appendChild(deleteModal);
      }

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

   // Configurar formulário de filtros
   function setupFilters() {
      if (filtersForm) {
         filtersForm.addEventListener("submit", function (e) {
            if (!e.submitter || e.submitter.id !== "btnFilterCategories")
               return;

            var search = (searchInput?.value || "").trim();
            var status = (statusSelect?.value || "").trim();
            var hasFilters = !!(search || status);

            if (!hasFilters) {
               e.preventDefault();
               showConfirmModal();
            }
         });
      }
   }

   // Auto-submeter ao alterar filtros
   function setupAutoFilter() {
      [searchInput, statusSelect, perPageSelect, deletedSelect].forEach(
         function (element) {
            if (!element) return;

            element.addEventListener("change", function () {
               clearTimeout(window.filterTimeout);
               window.filterTimeout = setTimeout(function () {
                  element.closest("form").submit();
               }, 500);
            });
         }
      );
   }

   // Modal de confirmação para mostrar todos
   function showConfirmModal() {
      var modalEl = document.getElementById("confirmAllCategoriesModal");
      if (!modalEl) {
         filtersForm.submit();
         return;
      }

      var confirmBtn = modalEl.querySelector(".btn-confirm-all-categories");
      var modal = new bootstrap.Modal(modalEl);

      var handler = function () {
         confirmBtn.removeEventListener("click", handler);

         var hidden = document.createElement("input");
         hidden.type = "hidden";
         hidden.name = "all";
         hidden.value = "1";
         filtersForm.appendChild(hidden);

         modal.hide();
         filtersForm.submit();
      };

      confirmBtn.addEventListener("click", handler);
      modal.show();
   }

   // Inicialização
   setupDeleteModal();
   setupFilters();
   setupAutoFilter();
});
