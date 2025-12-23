/**
 * Product Index JavaScript - External File
 * Following established patterns from customer.js and category.js
 *
 * Provides:
 * - Filter confirmation modal handling
 * - BRL currency formatting and normalization
 * - Auto-submit functionality
 * - AJAX status toggle operations
 */

(function () {
   "use strict";

   // State management
   let productState = {
      filterTimeout: null,
      modalInstance: null,
   };

   /**
    * Initialize delete modal functionality
    */
   function initializeDeleteModal() {
      const deleteModal = document.getElementById("deleteModal");
      if (!deleteModal) return;

      // Move modal to body if not already there
      if (deleteModal.parentElement !== document.body) {
         document.body.appendChild(deleteModal);
      }

      deleteModal.addEventListener("show.bs.modal", function (event) {
         const button = event.relatedTarget;
         if (!button) return;

         const deleteUrl = button.getAttribute("data-delete-url");
         const productName = button.getAttribute("data-product-name");
         const form = document.getElementById("deleteForm");
         const nameEl = document.getElementById("deleteProductName");

         if (form && deleteUrl) {
            form.setAttribute("action", deleteUrl);
         }
         if (nameEl) {
            nameEl.textContent = '"' + (productName || "") + '"';
         }
      });
   }

   /**
    * Initialize restore modal functionality
    */
   function initializeRestoreModal() {
      const restoreModal = document.getElementById("restoreModal");
      if (!restoreModal) return;

      // Move modal to body if not already there
      if (restoreModal.parentElement !== document.body) {
         document.body.appendChild(restoreModal);
      }

      restoreModal.addEventListener("show.bs.modal", function (event) {
         const button = event.relatedTarget;
         if (!button) return;

         const restoreUrl = button.getAttribute("data-restore-url");
         const productName = button.getAttribute("data-product-name");
         const form = document.getElementById("restoreForm");
         const nameEl = document.getElementById("restoreProductName");

         if (form && restoreUrl) {
            form.setAttribute("action", restoreUrl);
         }
         if (nameEl) {
            nameEl.textContent = '"' + (productName || "") + '"';
         }
      });
   }

   /**
    * Initialize all product index functionality
    */
   function initializeProductIndex() {
      initializeFilterConfirmation();
      initializeAutoSubmit();
      initializeCurrencyFormatting();
      initializeStatusToggle();
      initializeDeleteModal();
      initializeRestoreModal();
   }

   /**
    * Filter confirmation modal - prevents bulk loads without filters
    */
   function initializeFilterConfirmation() {
      const form = document.getElementById("filtersFormProducts");
      if (!form) return;

      form.addEventListener("submit", function (e) {
         if (!e.submitter || e.submitter.id !== "btnFilterProducts") return;

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
    * Show filter confirmation modal
    */
   function showFilterConfirmationModal(form) {
      const modalEl = document.getElementById("confirmAllProductsModal");
      const confirmBtn = modalEl.querySelector(".btn-confirm-all-products");

      // Remove any existing listeners
      const newConfirmBtn = confirmBtn.cloneNode(true);
      confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);

      // Add confirmation handler
      newConfirmBtn.addEventListener("click", function () {
         const hiddenInput = document.createElement("input");
         hiddenInput.type = "hidden";
         hiddenInput.name = "all";
         hiddenInput.value = "1";
         form.appendChild(hiddenInput);

         productState.modalInstance.hide();
         form.submit();
      });

      if (typeof window.bootstrap !== "undefined") {
         productState.modalInstance = new window.bootstrap.Modal(modalEl);
      } else {
         console.error("Bootstrap 5 not found in window scope");
         // Fallback manual or retry logic could go here
         return;
      }
      productState.modalInstance.show();
   }

   /**
    * Auto-submit functionality for search fields
    */
   function initializeAutoSubmit() {
      const autoSubmitFields = [
         "search",
         "category_id",
         "active",
         "min_price",
         "max_price",
      ];

      autoSubmitFields.forEach(function (fieldId) {
         const element = document.getElementById(fieldId);
         if (!element) return;

         element.addEventListener("change", function () {
            clearTimeout(productState.filterTimeout);
            productState.filterTimeout = setTimeout(function () {
               normalizeCurrencyInputs(element.closest("form"));
               element.closest("form").submit();
            }, 500);
         });
      });
   }

   /**
    * BRL Currency formatting and normalization
    */
   function initializeCurrencyFormatting() {
      const currencyInputs = document.querySelectorAll(".currency-brl");

      currencyInputs.forEach(function (input) {
         // Format existing value
         if (input.value) {
            input.value = formatBRL(input.value);
         }

         // Focus: show raw value
         input.addEventListener("focus", function () {
            input.value = normalizeCurrency(input.value);
         });

         // Blur: format as BRL
         input.addEventListener("blur", function () {
            input.value = formatBRL(input.value);
         });
      });
   }

   /**
    * Format value as BRL currency
    */
   function formatBRL(value) {
      if (value === null || value === undefined) return "";

      let onlyDigits = String(value).replace(/[^0-9,\.]/g, "");

      // Handle decimal separator
      if (onlyDigits.indexOf(".") !== -1 && onlyDigits.indexOf(",") === -1) {
         onlyDigits = onlyDigits.replace(/\./g, ",");
      }

      let digits = onlyDigits.replace(/[^0-9]/g, "");
      if (digits.length === 0) return "";

      // Ensure at least 3 digits for proper decimal formatting
      while (digits.length < 3) {
         digits = "0" + digits;
      }

      const intPart = digits.slice(0, -2);
      const decPart = digits.slice(-2);

      // Add thousand separators
      intPart.replace(/\B(?=(\d{3})+(?!\d))/g, ".");

      return intPart + "," + decPart;
   }

   /**
    * Normalize currency to decimal format
    */
   function normalizeCurrency(val) {
      if (!val) return "";

      const digits = String(val).replace(/[^0-9]/g, "");
      if (digits.length === 0) return "";

      if (digits.length === 1) digits = "0" + digits;

      const intPart = digits.slice(0, -2);
      const decPart = digits.slice(-2);

      return (intPart.length ? intPart : "0") + "." + decPart;
   }

   /**
    * Normalize all currency inputs in form
    */
   function normalizeCurrencyInputs(form) {
      const currencyInputs = form.querySelectorAll(".currency-brl");
      currencyInputs.forEach(function (input) {
         input.value = normalizeCurrency(input.value);
      });
   }

   /**
    * Initialize AJAX status toggle functionality
    */
   function initializeStatusToggle() {
      const toggleForms = document.querySelectorAll(".toggle-status-form");

      toggleForms.forEach(function (form) {
         form.addEventListener("submit", function (e) {
            e.preventDefault();

            const url = form.getAttribute("action");
            const btn = form.querySelector("button");

            // Disable button during request
            btn.disabled = true;

            const headers = {
               Accept: "application/json",
               "X-Requested-With": "XMLHttpRequest",
            };

            fetch(url, {
               method: "PATCH",
               headers: headers,
            })
               .then(function (response) {
                  return response.json();
               })
               .then(function (data) {
                  if (data && data.success) {
                     handleStatusToggleSuccess(form, btn, data.message);
                  } else {
                     const message =
                        data && data.message
                           ? data.message
                           : "Erro ao atualizar status";
                     window.easyAlert.error(message);
                  }
               })
               .catch(function () {
                  window.easyAlert.error("Erro de comunicação");
               })
               .finally(function () {
                  btn.disabled = false;
               });
         });
      });
   }

   /**
    * Handle successful status toggle
    */
   function handleStatusToggleSuccess(form, btn, message) {
      window.easyAlert.success(message || "Status atualizado");

      const statusCell = form
         .closest("tr")
         .querySelector("td:nth-child(6) .badge");
      if (!statusCell) return;

      const isActive = statusCell.classList.contains("badge-success");

      if (isActive) {
         // Change to inactive
         statusCell.classList.remove("badge-success");
         statusCell.classList.add("badge-danger");
         statusCell.textContent = "Inativo";

         btn.classList.remove("btn-warning");
         btn.classList.add("btn-success");
         btn.querySelector("i").className = "bi bi-check-lg";
         btn.setAttribute("aria-label", "Ativar produto");
         form.setAttribute(
            "onsubmit",
            "return confirm('Ativar este produto?')"
         );
      } else {
         // Change to active
         statusCell.classList.remove("badge-danger");
         statusCell.classList.add("badge-success");
         statusCell.textContent = "Ativo";

         btn.classList.remove("btn-success");
         btn.classList.add("btn-warning");
         btn.querySelector("i").className = "bi bi-slash-circle";
         btn.setAttribute("aria-label", "Desativar produto");
         form.setAttribute(
            "onsubmit",
            "return confirm('Desativar este produto?')"
         );
      }
   }

   // Initialize when DOM is ready
   if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", initializeProductIndex);
   } else {
      initializeProductIndex();
   }

   // Export for external access if needed
   window.ProductIndexJS = {
      initialize: initializeProductIndex,
      normalizeCurrencyInputs: normalizeCurrencyInputs,
      formatBRL: formatBRL,
      normalizeCurrency: normalizeCurrency,
      initializeRestoreModal: initializeRestoreModal,
   };
})();
