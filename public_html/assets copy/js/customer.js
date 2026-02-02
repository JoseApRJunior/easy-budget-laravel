/**
 * Customer Index JavaScript - Optimized Version
 * - Sem auto-submit (melhor performance, igual ao Product Index)
 * - Tratamento de modais de exclusão e restauração
 * - Validação de filtros
 */

(function () {
    "use strict";

    // Gerenciamento de estado interno
    let customerState = {
        modalInstance: null,
    };

    /**
     * Inicialização principal
     */
    function initializeCustomerIndex() {
        // 1. Modais de Ação
        initializeDeleteModal();
        initializeRestoreModal();

        // 2. Filtros e Formulário
        initializeFilterConfirmation();
        
        // 3. UI e Máscaras (CEP)
        initializeFormatting();
    }

    /**
     * Inicializa as máscaras de CEP e data usando VanillaMask
     */
    function initializeFormatting() {
        const applyMask = () => {
            if (typeof window.VanillaMask !== "undefined") {
                const cepInput = document.getElementById("cep");
                const startDate = document.getElementById("start_date");
                const endDate = document.getElementById("end_date");

                if (cepInput && !cepInput.dataset.maskApplied) {
                    new window.VanillaMask(cepInput, "cep");
                    cepInput.dataset.maskApplied = "true";
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
                setTimeout(applyMask, 100);
            }
        };

        applyMask();
    }

    /**
     * Intercepta o envio para validar se há filtros aplicados
     */
    function initializeFilterConfirmation() {
        const form = document.getElementById("filtersFormCustomers");
        if (!form) return;

        form.addEventListener("submit", function (e) {
            // Se o campo oculto 'all' existir (vindo do modal), ignora a validação
            if (form.querySelector('input[name="all"]')) return;

            const search = (form.querySelector("#search")?.value || "").trim();
            const status = (form.querySelector("#status")?.value || "").trim();
            const type = (form.querySelector("#type")?.value || "").trim();
            const area = (form.querySelector("#area_of_activity_id")?.value || "").trim();

            const hasFilters = !!(search || status || type || area);

            if (!hasFilters) {
                e.preventDefault();
                showFilterConfirmationModal(form);
            }
        });
    }

    /**
     * Exibe o modal avisando que carregar tudo pode ser lento
     */
    function showFilterConfirmationModal(form) {
        const modalEl = document.getElementById("confirmAllCustomersModal");
        if (!modalEl) return;

        const confirmBtn = modalEl.querySelector(".btn-confirm-all-customers");

        // Limpa listeners antigos para evitar múltiplas submissões
        const newConfirmBtn = confirmBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);

        newConfirmBtn.addEventListener("click", function () {
            const hiddenInput = document.createElement("input");
            hiddenInput.type = "hidden";
            hiddenInput.name = "all";
            hiddenInput.value = "1";
            form.appendChild(hiddenInput);

            if (customerState.modalInstance) customerState.modalInstance.hide();
            form.submit();
        });

        if (typeof window.bootstrap !== "undefined") {
            customerState.modalInstance = new window.bootstrap.Modal(modalEl);
            customerState.modalInstance.show();
        }
    }

    /**
     * Modais de Deleção e Restauração
     */
    function initializeDeleteModal() {
        const modal = document.getElementById("deleteModal");
        if (!modal) return;
        
        modal.addEventListener("show.bs.modal", function (event) {
            const button = event.relatedTarget;
            const url = button.getAttribute("data-delete-url") || button.getAttribute("href");
            const name = button.getAttribute("data-name");

            document.getElementById("deleteForm")?.setAttribute("action", url);
            const nameEl = document.getElementById("deleteCustomerName");
            if (nameEl) nameEl.textContent = `"${name}"`;
        });
    }

    function initializeRestoreModal() {
        const modal = document.getElementById("restoreModal");
        if (!modal) return;

        modal.addEventListener("show.bs.modal", function (event) {
            const button = event.relatedTarget;
            const url = button.getAttribute("data-restore-url");
            const name = button.getAttribute("data-name");

            document.getElementById("restoreForm")?.setAttribute("action", url);
            const nameEl = document.getElementById("restoreCustomerName");
            if (nameEl) nameEl.textContent = `"${name}"`;
        });
    }

    // Inicializa ao carregar o DOM
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initializeCustomerIndex);
    } else {
        initializeCustomerIndex();
    }

})();
