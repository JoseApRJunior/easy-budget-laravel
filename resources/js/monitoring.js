/**
 * JavaScript para Dashboard de Monitoramento de Middlewares
 * Easy Budget - Sistema de Monitoramento
 */

// ===== CONFIGURAÇÕES GLOBAIS =====
const MonitoringConfig = {
    refreshInterval: 30000, // 30 segundos
    apiEndpoints: {
        metrics: '/admin/monitoring/api/metrics',
        reports: '/admin/monitoring/api/reports'
    },
    charts: {
        colors: {
            primary: '#0d6efd',
            success: '#198754',
            warning: '#ffc107',
            danger: '#dc3545',
            info: '#0dcaf0'
        }
    }
};

// ===== CLASSE PRINCIPAL DO DASHBOARD =====
class MonitoringDashboard {
    constructor() {
        this.refreshTimer = null;
        this.isLoading = false;
        this.charts = {};
        this.init();
    }

    /**
     * Inicializa o dashboard
     */
    init() {
        this.bindEvents();
        this.startAutoRefresh();
        this.initializeCharts();
        this.loadInitialData();
    }

    /**
     * Vincula eventos aos elementos da interface
     */
    bindEvents() {
        // Botão de refresh manual
        const refreshBtn = document.getElementById('refreshMetrics');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => this.refreshMetrics());
        }

        // Botão de exportar dados
        const exportBtn = document.getElementById('exportData');
        if (exportBtn) {
            exportBtn.addEventListener('click', () => this.exportData());
        }

        // Filtros de middleware
        const middlewareFilter = document.getElementById('middlewareFilter');
        if (middlewareFilter) {
            middlewareFilter.addEventListener('change', () => this.applyFilters());
        }

        // Filtro de período
        const periodFilter = document.getElementById('periodFilter');
        if (periodFilter) {
            periodFilter.addEventListener('change', () => this.applyFilters());
        }

        // Campo de busca
        const searchInput = document.getElementById('searchMiddleware');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => this.searchMiddlewares(e.target.value));
        }

        // Ordenação da tabela
        const sortableHeaders = document.querySelectorAll('.sortable');
        sortableHeaders.forEach(header => {
            header.addEventListener('click', () => this.sortTable(header));
        });
    }

    /**
     * Inicia o refresh automático
     */
    startAutoRefresh() {
        this.refreshTimer = setInterval(() => {
            if (!this.isLoading) {
                this.refreshMetrics();
            }
        }, MonitoringConfig.refreshInterval);
    }

    /**
     * Para o refresh automático
     */
    stopAutoRefresh() {
        if (this.refreshTimer) {
            clearInterval(this.refreshTimer);
            this.refreshTimer = null;
        }
    }

    /**
     * Carrega dados iniciais
     */
    async loadInitialData() {
        await this.refreshMetrics();
    }

    /**
     * Atualiza as métricas do dashboard
     */
    async refreshMetrics() {
        if (this.isLoading) return;

        this.isLoading = true;
        this.showLoading();

        try {
            const response = await fetch(MonitoringConfig.apiEndpoints.metrics);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();
            this.updateDashboard(data);
            this.showAlert('Métricas atualizadas com sucesso!', 'success');
        } catch (error) {
            console.error('Erro ao carregar métricas:', error);
            this.showAlert('Erro ao carregar métricas: ' + error.message, 'danger');
        } finally {
            this.isLoading = false;
            this.hideLoading();
        }
    }

    /**
     * Atualiza os elementos do dashboard com novos dados
     */
    updateDashboard(data) {
        // Atualiza cards de métricas
        this.updateMetricCards(data.summary);
        
        // Atualiza tabela de middlewares
        this.updateMiddlewareTable(data.middlewares);
        
        // Atualiza alertas
        this.updateAlerts(data.alerts);
        
        // Atualiza gráficos
        this.updateCharts(data.trends);
        
        // Atualiza timestamp da última atualização
        this.updateLastRefresh();
    }

    /**
     * Atualiza os cards de métricas
     */
    updateMetricCards(summary) {
        const metrics = {
            'avgResponseTime': summary.averageResponseTime || 0,
            'successRate': summary.successRate || 0,
            'memoryUsage': summary.averageMemoryUsage || 0,
            'totalExecutions': summary.totalExecutions || 0
        };

        Object.entries(metrics).forEach(([key, value]) => {
            const element = document.getElementById(key);
            if (element) {
                this.animateValue(element, parseFloat(element.textContent) || 0, value);
            }
        });
    }

    /**
     * Atualiza a tabela de middlewares
     */
    updateMiddlewareTable(middlewares) {
        const tbody = document.querySelector('#middlewareTable tbody');
        if (!tbody || !middlewares) return;

        tbody.innerHTML = '';

        middlewares.forEach(middleware => {
            const row = this.createMiddlewareRow(middleware);
            tbody.appendChild(row);
        });
    }

    /**
     * Cria uma linha da tabela de middleware
     */
    createMiddlewareRow(middleware) {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>
                <strong>${middleware.name}</strong>
                <br>
                <small class="text-muted">${middleware.class}</small>
            </td>
            <td class="text-center">${middleware.executions}</td>
            <td class="text-center text-success">${middleware.successes}</td>
            <td class="text-center text-danger">${middleware.failures}</td>
            <td class="text-center">
                <div class="progress progress-custom">
                    <div class="progress-bar bg-success" style="width: ${middleware.successRate}%"></div>
                </div>
                <small>${middleware.successRate}%</small>
            </td>
            <td class="text-center">${middleware.averageTime}ms</td>
            <td class="text-center">${middleware.totalTime}ms</td>
            <td class="text-center">${this.formatBytes(middleware.averageMemory)}</td>
            <td class="text-center">
                <small class="text-muted">${this.formatDate(middleware.lastExecution)}</small>
            </td>
            <td class="text-center">
                <span class="status-badge ${this.getStatusClass(middleware.status)}">
                    ${middleware.status}
                </span>
            </td>
        `;
        return row;
    }

    /**
     * Atualiza os alertas
     */
    updateAlerts(alerts) {
        const alertsContainer = document.getElementById('alertsContainer');
        if (!alertsContainer || !alerts) return;

        alertsContainer.innerHTML = '';

        if (alerts.length === 0) {
            alertsContainer.innerHTML = '<p class="text-muted text-center">Nenhum alerta ativo</p>';
            return;
        }

        alerts.forEach(alert => {
            const alertElement = this.createAlertElement(alert);
            alertsContainer.appendChild(alertElement);
        });
    }

    /**
     * Cria um elemento de alerta
     */
    createAlertElement(alert) {
        const div = document.createElement('div');
        div.className = `alert alert-${alert.type} alert-card`;
        div.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="fas fa-${this.getAlertIcon(alert.type)} me-2"></i>
                <div>
                    <strong>${alert.title}</strong>
                    <p class="mb-0">${alert.message}</p>
                    <small class="text-muted">${this.formatDate(alert.timestamp)}</small>
                </div>
            </div>
        `;
        return div;
    }

    /**
     * Inicializa os gráficos (placeholder para futuras implementações)
     */
    initializeCharts() {
        // Placeholder para inicialização de gráficos com Chart.js ou similar
        console.log('Gráficos serão implementados em versão futura');
    }

    /**
     * Atualiza os gráficos
     */
    updateCharts(trends) {
        // Placeholder para atualização de gráficos
        console.log('Dados de tendências recebidos:', trends);
    }

    /**
     * Aplica filtros à tabela
     */
    applyFilters() {
        const middlewareFilter = document.getElementById('middlewareFilter')?.value;
        const periodFilter = document.getElementById('periodFilter')?.value;
        
        // Implementar lógica de filtros
        console.log('Aplicando filtros:', { middlewareFilter, periodFilter });
        
        // Recarregar dados com filtros
        this.refreshMetrics();
    }

    /**
     * Busca middlewares na tabela
     */
    searchMiddlewares(query) {
        const rows = document.querySelectorAll('#middlewareTable tbody tr');
        const searchTerm = query.toLowerCase();

        rows.forEach(row => {
            const middlewareName = row.querySelector('td:first-child strong')?.textContent.toLowerCase();
            const middlewareClass = row.querySelector('td:first-child small')?.textContent.toLowerCase();
            
            const matches = middlewareName?.includes(searchTerm) || middlewareClass?.includes(searchTerm);
            row.style.display = matches ? '' : 'none';
        });
    }

    /**
     * Ordena a tabela por coluna
     */
    sortTable(header) {
        const table = document.getElementById('middlewareTable');
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        const columnIndex = Array.from(header.parentNode.children).indexOf(header);
        const isAscending = header.classList.contains('sort-asc');

        // Remove classes de ordenação de todos os headers
        document.querySelectorAll('.sortable').forEach(h => {
            h.classList.remove('sort-asc', 'sort-desc');
        });

        // Adiciona classe de ordenação ao header atual
        header.classList.add(isAscending ? 'sort-desc' : 'sort-asc');

        // Ordena as linhas
        rows.sort((a, b) => {
            const aValue = a.children[columnIndex].textContent.trim();
            const bValue = b.children[columnIndex].textContent.trim();
            
            const aNum = parseFloat(aValue);
            const bNum = parseFloat(bValue);
            
            if (!isNaN(aNum) && !isNaN(bNum)) {
                return isAscending ? bNum - aNum : aNum - bNum;
            }
            
            return isAscending ? bValue.localeCompare(aValue) : aValue.localeCompare(bValue);
        });

        // Reinsere as linhas ordenadas
        rows.forEach(row => tbody.appendChild(row));
    }

    /**
     * Exporta dados para CSV
     */
    exportData() {
        const table = document.getElementById('middlewareTable');
        if (!table) return;

        const rows = table.querySelectorAll('tr');
        const csvContent = [];

        rows.forEach(row => {
            const cols = row.querySelectorAll('th, td');
            const rowData = Array.from(cols).map(col => {
                return '"' + col.textContent.trim().replace(/"/g, '""') + '"';
            });
            csvContent.push(rowData.join(','));
        });

        const csvString = csvContent.join('\n');
        const blob = new Blob([csvString], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        
        if (link.download !== undefined) {
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', `monitoring-metrics-${new Date().toISOString().split('T')[0]}.csv`);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    }

    /**
     * Mostra indicador de carregamento
     */
    showLoading() {
        const loadingElements = document.querySelectorAll('.loading-overlay');
        loadingElements.forEach(el => el.style.display = 'flex');
        
        const refreshBtn = document.getElementById('refreshMetrics');
        if (refreshBtn) {
            refreshBtn.disabled = true;
            refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Carregando...';
        }
    }

    /**
     * Esconde indicador de carregamento
     */
    hideLoading() {
        const loadingElements = document.querySelectorAll('.loading-overlay');
        loadingElements.forEach(el => el.style.display = 'none');
        
        const refreshBtn = document.getElementById('refreshMetrics');
        if (refreshBtn) {
            refreshBtn.disabled = false;
            refreshBtn.innerHTML = '<i class="fas fa-sync-alt"></i> Atualizar';
        }
    }

    /**
     * Mostra alerta temporário
     */
    showAlert(message, type = 'info') {
        const alertContainer = document.getElementById('tempAlerts');
        if (!alertContainer) return;

        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-dismissible fade show`;
        alert.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        alertContainer.appendChild(alert);

        // Remove o alerta após 5 segundos
        setTimeout(() => {
            if (alert.parentNode) {
                alert.remove();
            }
        }, 5000);
    }

    /**
     * Atualiza timestamp da última atualização
     */
    updateLastRefresh() {
        const element = document.getElementById('lastRefresh');
        if (element) {
            element.textContent = new Date().toLocaleString('pt-BR');
        }
    }

    /**
     * Anima mudança de valor numérico
     */
    animateValue(element, start, end, duration = 1000) {
        const range = end - start;
        const increment = range / (duration / 16);
        let current = start;
        
        const timer = setInterval(() => {
            current += increment;
            if ((increment > 0 && current >= end) || (increment < 0 && current <= end)) {
                current = end;
                clearInterval(timer);
            }
            
            if (element.id === 'successRate') {
                element.textContent = current.toFixed(1) + '%';
            } else if (element.id === 'avgResponseTime') {
                element.textContent = current.toFixed(0) + 'ms';
            } else if (element.id === 'memoryUsage') {
                element.textContent = this.formatBytes(current);
            } else {
                element.textContent = Math.round(current).toLocaleString('pt-BR');
            }
        }, 16);
    }

    // ===== MÉTODOS UTILITÁRIOS =====

    /**
     * Formata bytes em unidades legíveis
     */
    formatBytes(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
    }

    /**
     * Formata data para exibição
     */
    formatDate(dateString) {
        if (!dateString) return 'N/A';
        const date = new Date(dateString);
        return date.toLocaleString('pt-BR');
    }

    /**
     * Retorna classe CSS para status
     */
    getStatusClass(status) {
        const statusMap = {
            'healthy': 'healthy',
            'warning': 'warning',
            'critical': 'critical'
        };
        return statusMap[status] || 'healthy';
    }

    /**
     * Retorna ícone para tipo de alerta
     */
    getAlertIcon(type) {
        const iconMap = {
            'danger': 'exclamation-triangle',
            'warning': 'exclamation-circle',
            'info': 'info-circle',
            'success': 'check-circle'
        };
        return iconMap[type] || 'info-circle';
    }
}

// ===== INICIALIZAÇÃO =====
document.addEventListener('DOMContentLoaded', function() {
    // Inicializa o dashboard apenas se estivermos na página de monitoramento
    if (document.getElementById('monitoringDashboard')) {
        window.monitoringDashboard = new MonitoringDashboard();
    }
});

// ===== CLEANUP =====
window.addEventListener('beforeunload', function() {
    if (window.monitoringDashboard) {
        window.monitoringDashboard.stopAutoRefresh();
    }
});