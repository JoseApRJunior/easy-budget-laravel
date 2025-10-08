// Importa a biblioteca de paginação
document.addEventListener("DOMContentLoaded", function () {
    const startDate = document.getElementById("start_date");
    const endDate = document.getElementById("end_date");
    const clearButton = document.getElementById("clear-filters");
    const filterForm = document.getElementById("filter-form");
    const resultsCount = document.getElementById("results-count");
    const loadingSpinner = document.getElementById("loading-spinner");
    const initialMessage = document.getElementById("initial-message");
    const resultsContainer = document.getElementById("results-container");

    // Inicializa o paginador de tabela
    const budgetPaginator = new TablePaginator({
        tableId: "results-table",
        paginationId: "pagination",
        infoId: "pagination-info",
        itemsPerPage: 10,
        colSpan: 8,
        formatRow: formatBudgetRow
    });

    // Função para validar as datas
    function validateDates() {
        const startFilled = startDate.value !== "";
        const endFilled = endDate.value !== "";

        // Limpa validações anteriores
        startDate.setCustomValidity("");
        endDate.setCustomValidity("");

        // Remove todas as mensagens de erro existentes
        removeValidationUI(startDate);
        removeValidationUI(endDate);

        // Se uma data está preenchida, a outra é obrigatória
        if (startFilled !== endFilled) {
            if (startFilled) {
                endDate.setCustomValidity("* Data final obrigatória");
                showValidationError(endDate);
            } else {
                startDate.setCustomValidity("* Data inicial obrigatória");
                showValidationError(startDate);
            }
            return;
        }

        // Se ambas estão preenchidas, verifica se a data inicial é menor que a final
        if (startFilled && endFilled && startDate.value > endDate.value) {
            startDate.setCustomValidity(
                "* Data inicial deve ser menor que a final"
            );
            showValidationError(startDate);
            return;
        }

        // Se chegou aqui, as datas são válidas
        if (startFilled && endFilled) {
            showValidationSuccess(startDate);
            showValidationSuccess(endDate);
        }
    }

    // Função para remover UI de validação
    function removeValidationUI(input) {
        input.classList.remove("is-invalid", "is-valid");
        const errorSpan = input.parentNode.querySelector(".required-asterisk");
        if (errorSpan) {
            errorSpan.remove();
        }
    }

    // Função para mostrar erro
    function showValidationError(input) {
        removeValidationUI(input);
        input.classList.add("is-invalid");

        const asterisk = document.createElement("span");
        asterisk.className = "required-asterisk";
        asterisk.textContent = "* Obrigatório";
        input.parentNode.appendChild(asterisk);
    }

    // Função para mostrar sucesso
    function showValidationSuccess(input) {
        removeValidationUI(input);
        input.classList.add("is-valid");
    }

    // Função para limpar campos
    function clearFields() {
        // Limpa todos os campos do formulário
        filterForm.reset();

        // Remove validações
        removeValidationUI(startDate);
        removeValidationUI(endDate);

        // Limpa o campo de valor monetário
        const moneyInput = document.querySelector(".money-input");
        if (moneyInput) {
            moneyInput.value = "";
        }

        // Mostra mensagem inicial e esconde resultados
        initialMessage.classList.remove("d-none");
        resultsContainer.classList.add("d-none");

        // Limpa a contagem
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

            const response = await fetch("/provider/budgets/budgets_filter", {
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

            const budgets = await response.json();

            // Atualiza a tabela usando o paginador
            budgetPaginator.updateTable(budgets);

            // Atualiza o contador de resultados
            if (resultsCount) {
                resultsCount.textContent = `${budgets.length} resultados encontrados`;
            }

            // Mostra os resultados
            resultsContainer.classList.remove("d-none");
        } catch (error) {
            console.error("Erro detalhado:", error);
            showError("Ocorreu um erro ao buscar os resultados. Tente novamente.");
            initialMessage.classList.remove("d-none");
        } finally {
            loadingSpinner.classList.add("d-none");
        }
    }

    // Função para formatar cada linha da tabela de orçamentos
    function formatBudgetRow(budget) {
        return `
        <tr>
            <td style="width: 10%; text-align: left;">${budget.code || ""}</td>
            <td style="width: 20%; text-align: left;">${budget.customer_name || ""}</td>
            <td style="width: 30%; text-align: left;">${budget.description || ""}</td>
            <td style="width: 10%; text-align: left;">${formatDate(budget.created_at)}</td>
            <td style="width: 10%; text-align: left;">${formatDate(budget.due_date)}</td>
            <td style="width: 10%; text-align: right;">${formatMoney(budget.total)}</td>
            <td style="width: 10%; text-align: right;">
                <span class="badge" style="background-color: ${budget.color}">
                    <i class="bi ${budget.icon}"></i>
                    ${budget.name}
                </span>
            </td>
            <td class="text-end px-4 align-middle">
                <div class="btn-group gap-1">
                    <a href="/provider/budgets/${budget.code}/services/create" class="btn btn-sm btn-outline-success"
                        data-bs-toggle="tooltip" title="Novo Serviço">
                        <i class="bi bi-plus-lg"></i>
                    </a>
                    <a href="/provider/budgets/show/${budget.code}" class="btn btn-sm btn-outline-warning"
                        data-bs-toggle="tooltip" title="Visualizar">
                        <i class="bi bi-eye"></i>
                    </a>
                    <a href="/provider/budgets/update/${budget.code}" class="btn btn-sm btn-outline-primary"
                        data-bs-toggle="tooltip" title="Editar">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmDelete('${budget.code}')"
                        data-bs-toggle="tooltip" title="Excluir">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </td>
        </tr>`;
    }

    function formatDate(dateString) {
        if (!dateString) return "";
        return new Date(dateString).toLocaleDateString("pt-BR");
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
        alertDiv.className = "alert alert-danger alert-dismissible fade show mt-3";
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        `;
        filterForm.insertAdjacentElement("afterend", alertDiv);
        setTimeout(() => alertDiv.remove(), 5000);
    }

    // Event Listeners com debounce
    let timeoutId = null;

    function debouncedValidation() {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(validateDates, 100);
    }

    // Adiciona os event listeners
    ["change", "input"].forEach((eventType) => {
        startDate.addEventListener(eventType, debouncedValidation);
        endDate.addEventListener(eventType, debouncedValidation);
    });

    if (clearButton) {
        clearButton.addEventListener("click", (e) => {
            e.preventDefault();
            clearFields();
        });
    }

    if (filterForm) {
        filterForm.addEventListener("submit", fetchResults);
    }

    // Validação inicial
    validateDates();
});