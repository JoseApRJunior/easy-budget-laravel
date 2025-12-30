/**
 * Product Index JavaScript - Optimized Version
 * - Sem auto-submit (melhor performance)
 * - Normalização de moeda no momento do envio
 * - Tratamento de modais de exclusão e restauração
 */

(function () {
   "use strict";

   // Gerenciamento de estado interno
   let productState = {
      modalInstance: null,
   };

   /**
    * Inicialização principal
    */
   function initializeProductIndex() {
      // 1. Modais de Ação
      initializeDeleteModal();
      initializeRestoreModal();

      // 2. Filtros e Formulário
      initializeFilterConfirmation();
      initializeFormSubmission();

      // 3. UI e Máscaras
      initializeFormatting();
      initializeStatusToggle();
   }

   /**
    * Inicializa as máscaras de moeda e data usando VanillaMask
    */
   function initializeFormatting() {
      const applyMask = () => {
         if (typeof window.VanillaMask !== "undefined") {
            const minPrice = document.getElementById("min_price");
            const maxPrice = document.getElementById("max_price");
            const startDate = document.getElementById("start_date");
            const endDate = document.getElementById("end_date");

            if (minPrice && !minPrice.dataset.maskApplied) {
               new window.VanillaMask(minPrice, "currency");
               minPrice.dataset.maskApplied = "true";
            }
            if (maxPrice && !maxPrice.dataset.maskApplied) {
               new window.VanillaMask(maxPrice, "currency");
               maxPrice.dataset.maskApplied = "true";
            }
            if (startDate && !startDate.dataset.maskApplied) {
               new window.VanillaMask(startDate, "date");
               startDate.dataset.maskApplied = "true";
            }
            if (endDate && !endDate.dataset.maskApplied) {
               new window.VanillaMask(endDate, "date");
               endDate.dataset.maskApplied = "true";
            }
         } else {
            console.warn(
               "Product JS: VanillaMask ainda não disponível, tentando novamente em 100ms..."
            );
            setTimeout(applyMask, 100);
         }
      };

      applyMask();
   }

   /**
    * Intercepta o envio para validar se há filtros aplicados
    */
   function initializeFilterConfirmation() {
      const form = document.getElementById("filtersFormProducts");
      if (!form) return;

      form.addEventListener("submit", function (e) {
         // Se o campo oculto 'all' existir (vindo do modal), ignora a validação
         if (form.querySelector('input[name="all"]')) return;

         const search = (form.querySelector("#search")?.value || "").trim();
         const category = (
            form.querySelector("#category_id")?.value || ""
         ).trim();
         const status = (form.querySelector("#active")?.value || "").trim();
         const minPrice = (
            form.querySelector("#min_price")?.value || ""
         ).trim();
         const maxPrice = (
            form.querySelector("#max_price")?.value || ""
         ).trim();

         const hasFilters = !!(
            search ||
            category ||
            status ||
            minPrice ||
            maxPrice
         );

         if (!hasFilters) {
            e.preventDefault();
            showFilterConfirmationModal(form);
         }
      });
   }

   /**
    * Normaliza os valores de moeda antes de enviar ao Laravel
    * Removido: O backend agora utiliza CurrencyHelper::unformat para lidar com o formato BRL
    */
   function initializeFormSubmission() {
      // Não é mais necessário normalizar no front-end para os filtros,
      // pois o ProductService agora usa o CurrencyHelper para desformatar.
   }

   /**
    * Exibe o modal avisando que carregar tudo pode ser lento
    */
   function showFilterConfirmationModal(form) {
      const modalEl = document.getElementById("confirmAllProductsModal");
      if (!modalEl) return;

      const confirmBtn = modalEl.querySelector(".btn-confirm-all-products");

      // Limpa listeners antigos para evitar múltiplas submissões
      const newConfirmBtn = confirmBtn.cloneNode(true);
      confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);

      newConfirmBtn.addEventListener("click", function () {
         const hiddenInput = document.createElement("input");
         hiddenInput.type = "hidden";
         hiddenInput.name = "all";
         hiddenInput.value = "1";
         form.appendChild(hiddenInput);

         if (productState.modalInstance) productState.modalInstance.hide();

         // normalizeCurrencyInputs(form); // Removido: O backend agora trata
         form.submit();
      });

      if (typeof window.bootstrap !== "undefined") {
         productState.modalInstance = new window.bootstrap.Modal(modalEl);
         productState.modalInstance.show();
      } else {
         console.error("Bootstrap 5 não encontrado.");
      }
   }

   /**
    * Funções de normalização removidas em favor do VanillaMask e CurrencyHelper (Backend)
    */
   function normalizeCurrencyInputs(form) {
      // Mantido apenas como stub se necessário, mas a lógica foi movida para o backend
   }

   /**
    * Modais de Deleção e Restauração
    */
   function initializeDeleteModal() {
      const modal = document.getElementById("deleteModal");
      if (!modal) return;
      if (modal.parentElement !== document.body)
         document.body.appendChild(modal);

      modal.addEventListener("show.bs.modal", function (event) {
         const button = event.relatedTarget;
         const url = button.getAttribute("data-delete-url");
         const name = button.getAttribute("data-product-name");

         document.getElementById("deleteForm")?.setAttribute("action", url);
         const nameEl = document.getElementById("deleteProductName");
         if (nameEl) nameEl.textContent = `"${name}"`;
      });
   }

   function initializeRestoreModal() {
      const modal = document.getElementById("restoreModal");
      if (!modal) return;
      if (modal.parentElement !== document.body)
         document.body.appendChild(modal);

      modal.addEventListener("show.bs.modal", function (event) {
         const button = event.relatedTarget;
         const url = button.getAttribute("data-restore-url");
         const name = button.getAttribute("data-product-name");

         document.getElementById("restoreForm")?.setAttribute("action", url);
         const nameEl = document.getElementById("restoreProductName");
         if (nameEl) nameEl.textContent = `"${name}"`;
      });
   }

   /**
    * Toggle de Status via AJAX
    */
   function initializeStatusToggle() {
      document.querySelectorAll(".toggle-status-form").forEach((form) => {
         form.addEventListener("submit", function (e) {
            e.preventDefault();
            const btn = this.querySelector("button");
            btn.disabled = true;

            fetch(this.getAttribute("action"), {
               method: "PATCH",
               headers: {
                  Accept: "application/json",
                  "X-Requested-With": "XMLHttpRequest",
                  "X-CSRF-TOKEN": document.querySelector(
                     'meta[name="csrf-token"]'
                  )?.content,
               },
            })
               .then((res) => res.json())
               .then((data) => {
                  if (data.success) {
                     window.location.reload(); // Recarregar é mais seguro para manter a consistência da lista
                  } else {
                     window.easyAlert.error(
                        data.message || "Erro ao atualizar"
                     );
                  }
               })
               .catch(() => window.easyAlert.error("Erro de conexão"))
               .finally(() => (btn.disabled = false));
         });
      });
   }

   // Execução
   if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", initializeProductIndex);
   } else {
      initializeProductIndex();
   }
})();
