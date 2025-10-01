/**
 * Sistema de Dashboard com Auto-refresh
 * Implementa métricas em tempo real e gráficos interativos
 */

class DashboardManager {
   constructor() {
      this.refreshInterval = null;
      this.isActive = false;
      this.refreshTimers = {
         metrics: 30000, // 30 segundos
         charts: 60000, // 1 minuto
         realtime: 15000, // 15 segundos para métricas críticas
      };
      this.lastUpdate = new Date();
      this.charts = {};

      this.init();
   }

   /**
    * Inicializa o dashboard
    */
   init() {
      this.setupEventListeners();
      this.startAutoRefresh();
      this.initializeCharts();
      this.showLoadingState(false);
   }

   /**
    * Configura event listeners
    */
   setupEventListeners() {
      // Botão de refresh manual
      document.addEventListener("click", (e) => {
         if (
            e.target.matches(".btn-refresh") ||
            e.target.closest(".btn-refresh")
         ) {
            e.preventDefault();
            this.manualRefresh();
         }

         // Botão de toggle auto-refresh
         if (
            e.target.matches(".btn-toggle-refresh") ||
            e.target.closest(".btn-toggle-refresh")
         ) {
            e.preventDefault();
            this.toggleAutoRefresh();
         }

         // Filtros de período
         if (e.target.matches(".period-filter")) {
            e.preventDefault();
            const period = e.target.dataset.period;
            this.changePeriod(period);
         }
      });

      // Visibilidade da página (pausar refresh quando não visível)
      document.addEventListener("visibilitychange", () => {
         if (document.hidden) {
            this.pauseAutoRefresh();
         } else {
            this.resumeAutoRefresh();
         }
      });

      // Antes de fechar a página
      window.addEventListener("beforeunload", () => {
         this.stopAutoRefresh();
      });
   }

   /**
    * Inicia o auto-refresh
    */
   startAutoRefresh() {
      if (this.isActive) return;

      this.isActive = true;
      this.refreshInterval = setInterval(() => {
         this.refreshAll();
      }, this.refreshTimers.metrics);

      this.updateStatusIndicator("active");
      console.log("Dashboard: Auto-refresh iniciado");
   }

   /**
    * Pausa o auto-refresh
    */
   pauseAutoRefresh() {
      this.isActive = false;
      if (this.refreshInterval) {
         clearInterval(this.refreshInterval);
         this.refreshInterval = null;
      }
      this.updateStatusIndicator("paused");
      console.log("Dashboard: Auto-refresh pausado");
   }

   /**
    * Retoma o auto-refresh
    */
   resumeAutoRefresh() {
      if (!this.isActive) {
         this.startAutoRefresh();
      }
   }

   /**
    * Para o auto-refresh
    */
   stopAutoRefresh() {
      this.isActive = false;
      if (this.refreshInterval) {
         clearInterval(this.refreshInterval);
         this.refreshInterval = null;
      }
      this.updateStatusIndicator("stopped");
      console.log("Dashboard: Auto-refresh parado");
   }

   /**
    * Toggle auto-refresh
    */
   toggleAutoRefresh() {
      if (this.isActive) {
         this.pauseAutoRefresh();
      } else {
         this.startAutoRefresh();
      }
   }

   /**
    * Refresh manual
    */
   async manualRefresh() {
      this.showLoadingState(true);
      await this.refreshAll();
      this.showLoadingState(false);
      this.showNotification("Dados atualizados com sucesso!", "success");
   }

   /**
    * Refresh de todas as métricas e gráficos
    */
   async refreshAll() {
      try {
         await Promise.all([this.refreshMetrics(), this.refreshCharts()]);

         this.lastUpdate = new Date();
         this.updateLastUpdateTime();
      } catch (error) {
         console.error("Erro ao atualizar dashboard:", error);
         this.showNotification(
            "Erro ao atualizar dados. Tentando novamente...",
            "error"
         );
      }
   }

   /**
    * Atualiza métricas principais
    */
   async refreshMetrics() {
      try {
         const response = await fetch("/api/dashboard/metrics", {
            method: "GET",
            headers: {
               Accept: "application/json",
               "X-Requested-With": "XMLHttpRequest",
            },
         });

         if (!response.ok) throw new Error("Erro na resposta da API");

         const data = await response.json();

         if (data.success && data.data.metrics) {
            this.updateMetricsDisplay(data.data.metrics);
         }
      } catch (error) {
         console.error("Erro ao atualizar métricas:", error);
         throw error;
      }
   }

   /**
    * Atualiza gráficos
    */
   async refreshCharts() {
      try {
         const response = await fetch("/api/dashboard/metrics/charts", {
            method: "GET",
            headers: {
               Accept: "application/json",
               "X-Requested-With": "XMLHttpRequest",
            },
         });

         if (!response.ok) throw new Error("Erro na resposta da API");

         const data = await response.json();

         if (data.success && data.data.charts) {
            this.updateCharts(data.data.charts);
         }
      } catch (error) {
         console.error("Erro ao atualizar gráficos:", error);
         throw error;
      }
   }

   /**
    * Atualiza exibição das métricas
    */
   updateMetricsDisplay(metrics) {
      // Receita Total
      const receitaElement = document.querySelector(
         ".metric-receita-total .metric-value"
      );
      if (receitaElement && metrics.receita_total) {
         receitaElement.textContent = metrics.receita_total.formatado;
         this.updateMetricTrend(
            receitaElement.closest(".metric-card"),
            metrics.receita_total.tendencia
         );
      }

      // Despesas Totais
      const despesasElement = document.querySelector(
         ".metric-despesas-totais .metric-value"
      );
      if (despesasElement && metrics.despesas_totais) {
         despesasElement.textContent = metrics.despesas_totais.formatado;
         this.updateMetricTrend(
            despesasElement.closest(".metric-card"),
            metrics.despesas_totais.tendencia
         );
      }

      // Saldo Atual
      const saldoElement = document.querySelector(
         ".metric-saldo-atual .metric-value"
      );
      if (saldoElement && metrics.saldo_atual) {
         saldoElement.textContent = metrics.saldo_atual.formatado;
         this.updateMetricTrend(
            saldoElement.closest(".metric-card"),
            metrics.saldo_atual.positivo ? "up" : "down"
         );
      }

      // Transações Hoje
      const transacoesElement = document.querySelector(
         ".metric-transacoes-hoje .metric-value"
      );
      if (transacoesElement && metrics.transacoes_hoje) {
         transacoesElement.textContent = metrics.transacoes_hoje.formatado;
      }
   }

   /**
    * Atualiza indicador de tendência das métricas
    */
   updateMetricTrend(metricCard, trend) {
      if (!metricCard) return;

      const trendIndicator = metricCard.querySelector(".metric-trend");
      if (trendIndicator) {
         trendIndicator.className = "metric-trend";

         switch (trend) {
            case "up":
               trendIndicator.classList.add("trend-up");
               trendIndicator.innerHTML = '<i class="bi bi-arrow-up"></i>';
               break;
            case "down":
               trendIndicator.classList.add("trend-down");
               trendIndicator.innerHTML = '<i class="bi bi-arrow-down"></i>';
               break;
            default:
               trendIndicator.classList.add("trend-stable");
               trendIndicator.innerHTML = '<i class="bi bi-dash"></i>';
         }
      }
   }

   /**
    * Atualiza gráficos
    */
   updateCharts(chartsData) {
      // Atualizar gráfico de receitas vs despesas
      if (chartsData.receita_despesa && this.charts.receitaDespesa) {
         this.charts.receitaDespesa.data.labels =
            chartsData.receita_despesa.labels;
         this.charts.receitaDespesa.data.datasets[0].data =
            chartsData.receita_despesa.datasets[0].data;
         this.charts.receitaDespesa.data.datasets[1].data =
            chartsData.receita_despesa.datasets[1].data;
         this.charts.receitaDespesa.update("none");
      }

      // Atualizar gráfico de categorias
      if (chartsData.categorias && this.charts.categorias) {
         this.charts.categorias.data.labels = chartsData.categorias.labels;
         this.charts.categorias.data.datasets[0].data =
            chartsData.categorias.datasets[0].data;
         this.charts.categorias.update("none");
      }

      // Atualizar gráfico mensal
      if (chartsData.mensal && this.charts.mensal) {
         this.charts.mensal.data.labels = chartsData.mensal.labels;
         this.charts.mensal.data.datasets[0].data =
            chartsData.mensal.datasets[0].data;
         this.charts.mensal.data.datasets[1].data =
            chartsData.mensal.datasets[1].data;
         this.charts.mensal.update("none");
      }
   }

   /**
    * Inicializa gráficos com Chart.js
    */
   initializeCharts() {
      if (typeof Chart === "undefined") {
         console.warn("Chart.js não carregado");
         return;
      }

      this.initializeReceitaDespesaChart();
      this.initializeCategoriasChart();
      this.initializeMensalChart();
   }

   /**
    * Inicializa gráfico de receitas vs despesas
    */
   initializeReceitaDespesaChart() {
      const ctx = document.getElementById("receitaDespesaChart");
      if (!ctx) return;

      this.charts.receitaDespesa = new Chart(ctx, {
         type: "line",
         data: window.DASHBOARD_CHARTS?.receita_despesa || {
            labels: [],
            datasets: [],
         },
         options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
               legend: {
                  position: "top",
               },
               tooltip: {
                  mode: "index",
                  intersect: false,
               },
            },
            scales: {
               y: {
                  beginAtZero: true,
                  ticks: {
                     callback: function (value) {
                        return "R$ " + value.toLocaleString("pt-BR");
                     },
                  },
               },
            },
            interaction: {
               mode: "nearest",
               axis: "x",
               intersect: false,
            },
         },
      });
   }

   /**
    * Inicializa gráfico de categorias
    */
   initializeCategoriasChart() {
      const ctx = document.getElementById("categoriasChart");
      if (!ctx) return;

      this.charts.categorias = new Chart(ctx, {
         type: "doughnut",
         data: window.DASHBOARD_CHARTS?.categorias || {
            labels: [],
            datasets: [],
         },
         options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
               legend: {
                  position: "right",
               },
               tooltip: {
                  callbacks: {
                     label: function (context) {
                        return context.label + ": " + context.parsed + "%";
                     },
                  },
               },
            },
         },
      });
   }

   /**
    * Inicializa gráfico mensal
    */
   initializeMensalChart() {
      const ctx = document.getElementById("mensalChart");
      if (!ctx) return;

      this.charts.mensal = new Chart(ctx, {
         type: "bar",
         data: window.DASHBOARD_CHARTS?.mensal || {
            labels: [],
            datasets: [],
         },
         options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
               legend: {
                  position: "top",
               },
               tooltip: {
                  mode: "index",
                  intersect: false,
               },
            },
            scales: {
               y: {
                  beginAtZero: true,
                  ticks: {
                     callback: function (value) {
                        return "R$ " + value.toLocaleString("pt-BR");
                     },
                  },
               },
            },
         },
      });
   }

   /**
    * Muda período do dashboard
    */
   async changePeriod(period) {
      this.showLoadingState(true);

      try {
         // Atualizar filtros ativos
         document.querySelectorAll(".period-filter").forEach((btn) => {
            btn.classList.remove("active");
         });

         const activeButton = document.querySelector(
            `[data-period="${period}"]`
         );
         if (activeButton) {
            activeButton.classList.add("active");
         }

         // Buscar novos dados
         const response = await fetch(
            `/api/dashboard/metrics?period=${period}`,
            {
               method: "GET",
               headers: {
                  Accept: "application/json",
                  "X-Requested-With": "XMLHttpRequest",
               },
            }
         );

         if (!response.ok) throw new Error("Erro na resposta da API");

         const data = await response.json();

         if (data.success) {
            this.updateMetricsDisplay(data.data.metrics);
            this.updateCharts(data.data.charts);
            this.showNotification(`Período alterado para: ${period}`, "info");
         }
      } catch (error) {
         console.error("Erro ao alterar período:", error);
         this.showNotification("Erro ao alterar período", "error");
      } finally {
         this.showLoadingState(false);
      }
   }

   /**
    * Mostra estado de loading
    */
   showLoadingState(show) {
      document.querySelectorAll(".loading-overlay").forEach((overlay) => {
         overlay.style.display = show ? "flex" : "none";
      });

      // Botões de refresh
      document.querySelectorAll(".btn-refresh").forEach((btn) => {
         btn.style.opacity = show ? "0.6" : "1";
         btn.disabled = show;
      });
   }

   /**
    * Atualiza indicador de status
    */
   updateStatusIndicator(status) {
      const indicator = document.querySelector(".status-indicator");
      if (indicator) {
         indicator.className = `status-indicator status-${status}`;
         indicator.title = `Auto-refresh: ${
            status === "active"
               ? "Ativo"
               : status === "paused"
               ? "Pausado"
               : "Parado"
         }`;
      }
   }

   /**
    * Atualiza timestamp do último update
    */
   updateLastUpdateTime() {
      const timestampElement = document.querySelector(".last-update-time");
      if (timestampElement) {
         const now = new Date();
         const timeString = now.toLocaleTimeString("pt-BR", {
            hour: "2-digit",
            minute: "2-digit",
            second: "2-digit",
         });
         timestampElement.textContent = `Última atualização: ${timeString}`;
      }
   }

   /**
    * Mostra notificações
    */
   showNotification(message, type = "info") {
      // Criar elemento de notificação se não existir
      let notification = document.querySelector(".dashboard-notification");
      if (!notification) {
         notification = document.createElement("div");
         notification.className = "dashboard-notification";
         document.body.appendChild(notification);
      }

      // Configurar notificação
      notification.textContent = message;
      notification.className = `dashboard-notification notification-${type}`;

      // Mostrar com animação
      setTimeout(() => notification.classList.add("show"), 100);

      // Esconder após 3 segundos
      setTimeout(() => {
         notification.classList.remove("show");
      }, 3000);
   }
}

// Inicializar quando DOM estiver carregado
document.addEventListener("DOMContentLoaded", function () {
   window.dashboardManager = new DashboardManager();
});

// Exportar para uso global
if (typeof module !== "undefined" && module.exports) {
   module.exports = DashboardManager;
}
