/**
 * TablePaginator - Uma biblioteca simples para paginação de tabelas
 * 
 * @author Easy Budget
 * @version 1.0.0
 */
class TablePaginator {
    /**
     * Inicializa o paginador de tabela
     * 
     * @param {Object} config - Configuração do paginador
     * @param {string} config.tableId - ID da tabela a ser paginada
     * @param {string} config.paginationId - ID do container de paginação
     * @param {string} config.infoId - ID do elemento de informação da paginação
     * @param {number} config.itemsPerPage - Número de itens por página (padrão: 10)
     * @param {Function} config.formatRow - Função para formatar cada linha da tabela
     * @param {number} config.colSpan - Número de colunas para mensagem de "nenhum resultado"
     */
    constructor(config) {
        this.tableId = config.tableId;
        this.paginationId = config.paginationId;
        this.infoId = config.infoId;
        this.itemsPerPage = config.itemsPerPage || 10;
        this.formatRow = config.formatRow;
        this.colSpan = config.colSpan || 1;
        
        this.currentPage = 1;
        this.allItems = [];
        
        this.tableBody = document.querySelector(`#${this.tableId} tbody`);
        this.paginationContainer = document.querySelector(`#${this.paginationId}`);
        this.paginationInfo = document.getElementById(this.infoId);
    }
    
    /**
     * Atualiza a tabela com novos dados
     * 
     * @param {Array} items - Array de itens para exibir na tabela
     */
    updateTable(items) {
        if (!this.tableBody) {
            console.error(`Elemento tbody da tabela #${this.tableId} não encontrado!`);
            return;
        }
        
        // Armazena todos os itens
        this.allItems = items;
        this.currentPage = 1;
        
        // Atualiza a paginação
        this.setupPagination(items.length);
        
        // Mostra a página atual
        this.showPage(this.currentPage);
    }
    
    /**
     * Exibe uma página específica da tabela
     * 
     * @param {number} page - Número da página a ser exibida
     */
    showPage(page) {
        // Calcula os índices de início e fim para a página atual
        const startIndex = (page - 1) * this.itemsPerPage;
        const endIndex = Math.min(startIndex + this.itemsPerPage, this.allItems.length);
        
        // Obtém os itens da página atual
        const pageItems = this.allItems.slice(startIndex, endIndex);
        
        // Formata e exibe os resultados
        const html = this.formatItems(pageItems);
        this.tableBody.innerHTML = html;
        
        // Atualiza a informação de paginação
        this.updatePaginationInfo(this.allItems.length);
    }
    
    /**
     * Configura a paginação
     * 
     * @param {number} totalItems - Número total de itens
     */
    setupPagination(totalItems) {
        const totalPages = Math.ceil(totalItems / this.itemsPerPage);
        
        if (!this.paginationContainer) return;
        
        // Atualiza o HTML da paginação
        this.updatePaginationHTML(totalPages);
        
        // Atualiza a informação de paginação
        this.updatePaginationInfo(totalItems);
    }
    
    /**
     * Atualiza o HTML da paginação
     * 
     * @param {number} totalPages - Número total de páginas
     */
    updatePaginationHTML(totalPages) {
        if (!this.paginationContainer) return;
        
        // Se não houver páginas suficientes, não mostra a paginação
        if (totalPages <= 1) {
            this.paginationContainer.innerHTML = "";
            return;
        }
        
        let paginationHTML = "";
        
        // Botão Primeira página
        paginationHTML += `
        <li class="page-item ${this.currentPage === 1 ? "disabled" : ""}">
            <a class="page-link" href="#" data-page="1" aria-label="Primeira">
                <i class="bi bi-chevron-double-left"></i>
            </a>
        </li>`;
        
        // Botão Anterior
        paginationHTML += `
        <li class="page-item ${this.currentPage === 1 ? "disabled" : ""}">
            <a class="page-link" href="#" data-page="${this.currentPage - 1}" aria-label="Anterior">
                <i class="bi bi-chevron-left"></i>
            </a>
        </li>`;
        
        // Determina quais páginas mostrar (máximo 5)
        let startPage = Math.max(1, this.currentPage - 2);
        let endPage = Math.min(totalPages, startPage + 4);
        
        // Ajusta se estiver perto do final
        if (endPage - startPage < 4) {
            startPage = Math.max(1, endPage - 4);
        }
        
        // Adiciona "..." se necessário no início
        if (startPage > 1) {
            paginationHTML += `
            <li class="page-item disabled">
                <span class="page-link">...</span>
            </li>`;
        }
        
        // Páginas numeradas
        for (let i = startPage; i <= endPage; i++) {
            paginationHTML += `
            <li class="page-item ${i === this.currentPage ? "active" : ""}">
                <a class="page-link" href="#" data-page="${i}">${i}</a>
            </li>`;
        }
        
        // Adiciona "..." se necessário no final
        if (endPage < totalPages) {
            paginationHTML += `
            <li class="page-item disabled">
                <span class="page-link">...</span>
            </li>`;
        }
        
        // Botão Próximo
        paginationHTML += `
        <li class="page-item ${this.currentPage === totalPages ? "disabled" : ""}">
            <a class="page-link" href="#" data-page="${this.currentPage + 1}" aria-label="Próximo">
                <i class="bi bi-chevron-right"></i>
            </a>
        </li>`;
        
        // Botão Última página
        paginationHTML += `
        <li class="page-item ${this.currentPage === totalPages ? "disabled" : ""}">
            <a class="page-link" href="#" data-page="${totalPages}" aria-label="Última">
                <i class="bi bi-chevron-double-right"></i>
            </a>
        </li>`;
        
        this.paginationContainer.innerHTML = paginationHTML;
        
        // Adiciona event listeners aos links de paginação
        this.addPaginationEventListeners(totalPages);
    }
    
    /**
     * Adiciona event listeners aos links de paginação
     * 
     * @param {number} totalPages - Número total de páginas
     */
    addPaginationEventListeners(totalPages) {
        const self = this;
        document.querySelectorAll(`#${this.paginationId} .page-link`).forEach((link) => {
            link.addEventListener("click", function(e) {
                e.preventDefault();
                
                // Se o botão estiver em um item desabilitado, não faz nada
                if (this.parentElement.classList.contains("disabled")) {
                    return;
                }
                
                const page = parseInt(this.getAttribute("data-page"));
                
                // Verifica se é uma página válida
                if (page >= 1 && page <= totalPages && page !== self.currentPage) {
                    self.currentPage = page;
                    self.showPage(self.currentPage);
                    
                    // Atualiza a paginação após mudar de página
                    self.updatePaginationHTML(totalPages);
                    
                    // Rola a página para o topo da tabela
                    document.querySelector(`#${self.tableId}`).scrollIntoView({ behavior: "smooth", block: "start" });
                }
            });
        });
    }
    
    /**
     * Atualiza a informação de paginação
     * 
     * @param {number} totalItems - Número total de itens
     */
    updatePaginationInfo(totalItems) {
        if (this.paginationInfo && totalItems > 0) {
            const startItem = Math.min((this.currentPage - 1) * this.itemsPerPage + 1, totalItems);
            const endItem = Math.min(this.currentPage * this.itemsPerPage, totalItems);
            this.paginationInfo.textContent = `Mostrando ${startItem} a ${endItem} de ${totalItems} registros`;
        }
    }
    
    /**
     * Formata os itens para exibição na tabela
     * 
     * @param {Array} items - Array de itens para formatar
     * @returns {string} HTML formatado para as linhas da tabela
     */
    formatItems(items) {
        if (!items || !items.length) {
            return `
            <tr>
                <td colspan="${this.colSpan}" class="text-center py-4">
                    <p class="text-muted mb-0">Nenhum registro encontrado</p>
                </td>
            </tr>`;
        }
        
        return items.map(item => this.formatRow(item)).join("");
    }
}

// Export para compatibilidade com Vite
export { TablePaginator };