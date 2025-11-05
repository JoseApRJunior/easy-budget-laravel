document.addEventListener("DOMContentLoaded", function () {
    // Elementos do DOM
    const filterForm = document.getElementById("filter-form");
    const clearFiltersBtn = document.getElementById("clear-filters");
    const initialMessage = document.getElementById("initial-message");
    const loadingSpinner = document.getElementById("loading-spinner");
    const resultsContainer = document.getElementById("results-container");
    const resultsCount = document.getElementById("results-count");
    const exportPdfBtn = document.getElementById("export-pdf");
    const exportExcelBtn = document.getElementById("export-excel");



    // Verificação de elementos essenciais
    if (!filterForm) {
        console.error('Elemento #filter-form não encontrado!');
        return;
    }

    // Inicializa o paginador de tabela
    const reportPaginator = new TablePaginator({
        tableId: "results-table",
        paginationId: "pagination",
        infoId: "pagination-info",
        itemsPerPage: 15,
        colSpan: 5,
        formatRow: formatCustomerReportRow,
    });

    async function performFilter() {
        const formData = new FormData(filterForm);
        const filters = Object.fromEntries(formData);

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
            const response = await fetch("/provider/reports/customers/search", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                    "X-CSRF-TOKEN": csrfToken,
                },
                body: JSON.stringify({
                    name: filters.name || '',
                    document: filters.document || '',
                    start_date: filters.start_date || '',
                    end_date: filters.end_date || '',
                }),
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            // Filtra dados por data se necessário
            let filteredData = data;
            if (filters.start_date || filters.end_date) {
                filteredData = filterByDateRange(data, filters.start_date, filters.end_date);
            }

            // Atualiza a tabela com os resultados
            reportPaginator.updateTable(filteredData);

            // Atualiza o contador de resultados
            if (resultsCount) {
                resultsCount.textContent = `Mostrando ${filteredData.length} resultados`;
            }

            // Mostra os resultados
            resultsContainer.classList.remove("d-none");
        } catch (error) {
            console.error("Erro na busca:", error);
            showError("Ocorreu um erro ao gerar o relatório. Tente novamente.");
            initialMessage.classList.remove("d-none");
        } finally {
            loadingSpinner.classList.add("d-none");
        }
    }

    // Função para filtrar por intervalo de datas
    function filterByDateRange(data, startDate, endDate) {
        if (!startDate && !endDate) return data;

        return data.filter(customer => {
            const customerDate = new Date(customer.created_at);
            const start = startDate ? new Date(startDate) : null;
            const end = endDate ? new Date(endDate) : null;

            if (start && customerDate < start) return false;
            if (end && customerDate > end) return false;
            return true;
        });
    }

    // Função para limpar filtros
    function clearFilters() {
        filterForm.reset();
        
        if (initialMessage) initialMessage.classList.remove("d-none");
        if (resultsContainer) resultsContainer.classList.add("d-none");
        if (loadingSpinner) loadingSpinner.classList.add("d-none");
        if (resultsCount) resultsCount.textContent = "";

        reportPaginator.updateTable([]);
    }

    // Função para formatar cada linha da tabela de relatório
    function formatCustomerReportRow(customer) {
        return `
        <tr>
            <td style="width: 30%; text-align: left; padding: 8px;">
                ${customer.customer_name || 'Nome não informado'}
            </td>
            <td style="width: 20%; text-align: left; padding: 8px;">
                ${customer.email || customer.email_business || 'Não informado'}
            </td>
            <td style="width: 20%; text-align: left; padding: 8px;">
                ${formatPhone(customer.phone || customer.phone_business) || 'Não informado'}
            </td>
            <td style="width: 15%; text-align: left; padding: 8px;">
                ${formatDocument(customer.cpf || customer.cnpj) || 'Não informado'}
            </td>
            <td style="width: 15%; text-align: left; padding: 8px;">
                ${formatDate(customer.created_at)}
            </td>
        </tr>`;
    }

    // Função para exportar PDF
    function exportPDF() {
        const formData = new FormData(filterForm);
        const params = new URLSearchParams(formData);
        window.open(`/provider/reports/customers/pdf?${params.toString()}`, '_blank');
    }

    // Função para exportar Excel
    function exportExcel() {
        const tableData = reportPaginator.getAllData();
        
        if (!tableData || tableData.length === 0) {
            showError("Não há dados para exportar. Execute uma busca primeiro.");
            return;
        }

        // Prepara dados para Excel
        const excelData = tableData.map(customer => ({
            'Nome': customer.customer_name || 'Nome não informado',
            'Email': customer.email || customer.email_business || 'Não informado',
            'Telefone': formatPhone(customer.phone || customer.phone_business) || 'Não informado',
            'CPF/CNPJ': formatDocument(customer.cpf || customer.cnpj) || 'Não informado',
            'Data de Cadastro': formatDate(customer.created_at)
        }));

        // Cria workbook
        const ws = XLSX.utils.json_to_sheet(excelData);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "Clientes");

        // Gera arquivo
        const fileName = `relatorio_clientes_${new Date().toISOString().split('T')[0]}.xlsx`;
        XLSX.writeFile(wb, fileName);
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
    function formatDocument(document) {
        if (!document) return '';
        const clean = document.replace(/\D/g, '');
        if (clean.length === 11) {
            return clean.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
        }
        if (clean.length === 14) {
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

    // Event Listeners
    if (filterForm) {
        filterForm.addEventListener("submit", (e) => {
            e.preventDefault();
            performFilter();
        });
    }

    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener("click", clearFilters);
    }

    if (exportPdfBtn) {
        exportPdfBtn.addEventListener("click", exportPDF);
    }

    if (exportExcelBtn) {
        exportExcelBtn.addEventListener("click", exportExcel);
    }
});