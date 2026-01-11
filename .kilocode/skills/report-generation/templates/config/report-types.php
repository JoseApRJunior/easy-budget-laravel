<?php

// Configuração de tipos de relatórios disponíveis no sistema
return [
    // Relatórios Financeiros
    'financial_summary' => [
        'name' => 'Resumo Financeiro',
        'description' => 'Visão geral das finanças do período',
        'required_filters' => ['start_date', 'end_date'],
        'optional_filters' => ['customer_id', 'category_id', 'status'],
        'data_source' => 'FinancialReportService',
        'cache_ttl' => 300, // 5 minutos
        'max_results' => 10000,
        'export_formats' => ['pdf', 'excel', 'csv'],
    ],

    'cash_flow' => [
        'name' => 'Fluxo de Caixa',
        'description' => 'Entradas e saídas de recursos financeiros',
        'required_filters' => ['start_date', 'end_date'],
        'optional_filters' => ['account_type', 'payment_method'],
        'data_source' => 'CashFlowReportService',
        'cache_ttl' => 600, // 10 minutos
        'max_results' => 5000,
        'export_formats' => ['pdf', 'excel'],
    ],

    'accounts_receivable' => [
        'name' => 'Contas a Receber',
        'description' => 'Faturas pendentes e inadimplência',
        'required_filters' => ['start_date', 'end_date'],
        'optional_filters' => ['customer_id', 'overdue_days', 'status'],
        'data_source' => 'AccountsReceivableReportService',
        'cache_ttl' => 900, // 15 minutos
        'max_results' => 2000,
        'export_formats' => ['pdf', 'excel', 'csv'],
    ],

    'accounts_payable' => [
        'name' => 'Contas a Pagar',
        'description' => 'Despesas programadas e pagas',
        'required_filters' => ['start_date', 'end_date'],
        'optional_filters' => ['supplier_id', 'category_id', 'status'],
        'data_source' => 'AccountsPayableReportService',
        'cache_ttl' => 900, // 15 minutos
        'max_results' => 2000,
        'export_formats' => ['pdf', 'excel', 'csv'],
    ],

    // Relatórios Operacionais
    'inventory_movements' => [
        'name' => 'Movimentação de Estoque',
        'description' => 'Entradas e saídas de produtos',
        'required_filters' => ['start_date', 'end_date'],
        'optional_filters' => ['product_id', 'movement_type', 'warehouse_id'],
        'data_source' => 'InventoryReportService',
        'cache_ttl' => 600, // 10 minutos
        'max_results' => 5000,
        'export_formats' => ['pdf', 'excel', 'csv'],
    ],

    'sales_performance' => [
        'name' => 'Performance de Vendas',
        'description' => 'Vendas por período, produtos e serviços',
        'required_filters' => ['start_date', 'end_date'],
        'optional_filters' => ['product_id', 'service_id', 'salesperson', 'region'],
        'data_source' => 'SalesReportService',
        'cache_ttl' => 1800, // 30 minutos
        'max_results' => 10000,
        'export_formats' => ['pdf', 'excel'],
    ],

    'budget_conversion' => [
        'name' => 'Conversão de Orçamentos',
        'description' => 'Taxa de conversão de propostas em vendas',
        'required_filters' => ['start_date', 'end_date'],
        'optional_filters' => ['salesperson', 'customer_type', 'category_id'],
        'data_source' => 'BudgetConversionReportService',
        'cache_ttl' => 1800, // 30 minutos
        'max_results' => 5000,
        'export_formats' => ['pdf', 'excel'],
    ],

    // Relatórios Analíticos
    'customer_analytics' => [
        'name' => 'Análise de Clientes',
        'description' => 'Comportamento e performance de clientes',
        'required_filters' => ['start_date', 'end_date'],
        'optional_filters' => ['customer_type', 'status', 'region', 'segment'],
        'data_source' => 'CustomerReportService',
        'cache_ttl' => 1800, // 30 minutos
        'max_results' => 10000,
        'export_formats' => ['pdf', 'excel'],
    ],

    'product_analytics' => [
        'name' => 'Análise de Produtos',
        'description' => 'Performance e rentabilidade de produtos',
        'required_filters' => ['start_date', 'end_date'],
        'optional_filters' => ['category_id', 'product_type', 'status'],
        'data_source' => 'ProductReportService',
        'cache_ttl' => 1800, // 30 minutos
        'max_results' => 5000,
        'export_formats' => ['pdf', 'excel'],
    ],

    'market_trends' => [
        'name' => 'Tendências de Mercado',
        'description' => 'Análise de tendências e oportunidades',
        'required_filters' => ['start_date', 'end_date'],
        'optional_filters' => ['region', 'customer_segment', 'product_category'],
        'data_source' => 'MarketTrendsReportService',
        'cache_ttl' => 3600, // 1 hora
        'max_results' => 10000,
        'export_formats' => ['pdf', 'excel'],
    ],

    // Relatórios Personalizados
    'custom_report' => [
        'name' => 'Relatório Personalizado',
        'description' => 'Relatório sob medida para necessidades específicas',
        'required_filters' => ['start_date', 'end_date'],
        'optional_filters' => ['custom_fields'],
        'data_source' => 'CustomReportService',
        'cache_ttl' => 900, // 15 minutos
        'max_results' => 5000,
        'export_formats' => ['pdf', 'excel', 'csv'],
    ],

    // Relatórios Executivos
    'executive_summary' => [
        'name' => 'Resumo Executivo',
        'description' => 'Visão geral estratégica do negócio',
        'required_filters' => ['start_date', 'end_date'],
        'optional_filters' => ['department', 'region'],
        'data_source' => 'ExecutiveReportService',
        'cache_ttl' => 3600, // 1 hora
        'max_results' => 1000,
        'export_formats' => ['pdf'],
    ],

    'kpi_dashboard' => [
        'name' => 'Dashboard de KPIs',
        'description' => 'Indicadores-chave de performance',
        'required_filters' => ['start_date', 'end_date'],
        'optional_filters' => ['kpi_type', 'department'],
        'data_source' => 'KPIReportService',
        'cache_ttl' => 600, // 10 minutos
        'max_results' => 500,
        'export_formats' => ['pdf', 'excel'],
    ],
];
