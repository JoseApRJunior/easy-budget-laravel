document.addEventListener("DOMContentLoaded", function () {
   // Elementos do DOM
   const filtersForm = document.getElementById("filtersFormCategories");
   const searchInput = document.getElementById("search");
   const statusSelect = document.getElementById("active");
   const perPageSelect = document.getElementById("per_page");
   const deletedSelect = document.getElementById("deleted");
   const deleteModal = document.getElementById("deleteModal");

   // Flag para detectar carregamento inicial
   let isInitialLoad = true;

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
            // Se o campo oculto 'all' existir (vindo do modal), ignora a validação
            if (filtersForm.querySelector('input[name="all"]')) return;

            var search = (searchInput?.value || "").trim();
            var status = (statusSelect?.value || "").trim();
            var deleted = (deletedSelect?.value || "").trim();
            
            // Consideramos filtros ativos se search não estiver vazio,
            // ou se status não for "Todos" (''), ou se deleted não for "Atuais" ('current')
            var hasFilters = !!(search || (status !== "") || (deleted !== "current"));

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

      var confirmBtn = modalEl.querySelector(".btn-confirm-all-categories");
      
      // Limpa listeners antigos para evitar múltiplas submissões
      const newConfirmBtn = confirmBtn.cloneNode(true);
      confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);

      var modal = new bootstrap.Modal(modalEl);

      newConfirmBtn.addEventListener("click", function () {
         var hidden = document.createElement("input");
         hidden.type = "hidden";
         hidden.name = "all";
         hidden.value = "1";
         filtersForm.appendChild(hidden);

         modal.hide();
         filtersForm.submit();
      });

      modal.show();
   }

   // Inicialização
   setupDeleteModal();
   setupFilters();

   // Marcar que o carregamento inicial foi completado
   setTimeout(() => {
      isInitialLoad = false;
   }, 100);
});
