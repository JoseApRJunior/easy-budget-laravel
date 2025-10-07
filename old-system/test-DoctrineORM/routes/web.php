<?php

use core\support\ErrorHandler;
use http\Router;

try {

    /** @var Router $this */
    /** @var Router $router */
    /** @var \DI\Container $container  */
    $router = $container->get( Router::class);

    /**
     * Rotas públicas
     *
     * Estas rotas são acessíveis a todos os usuários, incluindo visitantes não autenticados.
     * Incluem páginas como home, login, registro, recuperação de senha e informações gerais.
     */
    $router->add( '/', 'GET', 'HomeController:index' );
    $router->add( '/login', 'GET', 'LoginController:index' );
    $router->add( '/login', 'POST', 'LoginController:login' );
    $router->add( '/logout', 'POST', 'LoginController:logout' );
    $router->add( '/register', 'POST', 'UserController:register' );
    $router->add( '/confirm-account', 'GET', 'UserController:confirmAccount' );
    $router->add( '/block-account', 'GET', 'UserController:blockAccount' );
    $router->add( '/resend-confirmation', 'GET', 'UserController:resendConfirmation' );
    $router->add( '/resend-confirmation', 'POST', 'UserController:resendConfirmationLink' );
    $router->add( '/forgot-password', 'GET', 'LoginController:forgotPassword' );
    $router->add( '/forgot-password', 'POST', 'LoginController:sendResetLink' );
    $router->add( '/terms-of-service', 'GET', 'LegalController:termsOfService' );
    $router->add( '/privacy-policy', 'GET', 'LegalController:privacyPolicy' );
    $router->add( '/about', 'GET', 'InfoController:about' );
    $router->add( '/support', 'GET', 'SupportController:support' );
    $router->add( '/support', 'POST', 'SupportController:store' );
    $router->add( '/cep', 'POST', 'AjaxController:buscarCep' );

    /**
     * Rotas de planos e pagamentos
     * Estas rotas são acessíveis apenas para usuários autenticados.
     * Fornece informações sobre os planos disponíveis para assinatura.
     * Lidam com o processamento de pagamentos e callbacks relacionados.
     */
    $router->group( [ 'prefix' => 'plans', 'middlewares' => [ 'auth' ] ], function () {
        $this->add( '/', 'GET', 'PlanController:index' );
        // Rotas de pagamento
        $this->add( '/status', 'GET', 'PlanController:status' );
        $this->add( '/pay', 'POST', 'PlanController:redirectToPayment' );
        $this->add( '/cancel-pending', 'POST', 'PlanController:cancelPendingSubscription' );
        // Rotas de pagamento planos
        $this->add( '/error', 'GET', 'PaymentController:error' );
    } );

    $router->add( '/plans/payment-status', 'GET', 'PlanController:paymentStatus' );

    /**
     * Rotas de orçamento publicas com token de confirmação
     *
     * Estas rotas são usadas para gerenciar a mudança de status de orçamentos pelo cliente.
     * Algumas rotas exigem autenticação e autorização de provedor.
     */
    $router->add( '/budgets/choose-budget-status/code/(:any)/token/(:any)', 'GET', 'BudgetController:choose_budget_status', [ 'code', 'token' ] );
    $router->add( '/budgets/choose-budget-status', 'POST', 'BudgetController:choose_budget_status_store' );
    $router->add( '/budgets/print/code/(:any)/token/(:any)', 'GET', 'BudgetController:print', [ 'code', 'token' ] );

    /**
     * Rotas de serviços públicos com token de confirmação
     *
     * Estas rotas são usadas para gerenciar o status de serviços pelo cliente.
     * Algumas rotas exigem autenticação e autorização de provedor.
     */
    $router->add( '/services/view-service-status/code/(:any)/token/(:any)', 'GET', 'ServiceController:view_service_status', [ 'code', 'token' ] );
    $router->add( '/services/choose-service-status', 'POST', 'ServiceController:choose_service_status_store' );
    $router->add( '/services/print/code/(:any)/token/(:any)', 'GET', 'ServiceController:print', [ 'code', 'token' ] );

    /**
     * Rotas de verificação de documentos
     *
     * Estas rotas são usadas para verificar a autenticidade de documentos.
     * A rota de verificação exige um hash único para identificar o documento.
     */
    $router->add( '/documents/verify/(:any)', 'GET', 'DocumentVerificationController:verify', [ 'hash' ] );

    /**
     * Rotas de Webhook
     *
     * Estas rotas são para receber notificações de serviços externos como o Mercado Pago.
     * Não devem ser acessadas diretamente por usuários.
     */
    $router->add( '/webhooks/mercadopago/invoices', 'POST', 'WebhookController:handleMercadoPagoInvoice' );
    $router->add( '/webhooks/mercadopago/plans', 'POST', 'WebhookController:handleMercadoPagoPlan' );
    $router->add( '/webhooks', 'POST', 'WebhookController:handleWebhookMercadoPago' );

    /**
     * Rotas de erro
     *
     * Estas rotas são usadas para exibir páginas de erro personalizadas.
     */
    $router->add( '/not-allowed', 'GET', 'ErrorController:notAllowed' );
    $router->add( '/not-found', 'GET', 'ErrorController:notFound' );
    $router->add( '/internal-error', 'GET', 'ErrorController:internal' );

    /**
     * Rotas para área administrativa
     *
     * Estas rotas são protegidas por middlewares de autenticação e autorização de admin.
     * Permitem o acesso a funcionalidades de gerenciamento do sistema.
     */
    $router->group( [ 'prefix' => 'admin', 'controller' => 'admin', 'middlewares' => [ 'auth', 'admin' ] ], function () {
        $this->add( '/', 'GET', 'HomeController:index' );
        $this->add( '/dashboard', 'GET', 'DashboardController:index' );
        $this->add( '/user', 'GET', 'UserController:index' );
        $this->add( '/logs', 'GET', 'LogController:index' );

        // Rotas de admin para gerenciamento de planos
        $this->add( '/plans/subscriptions', 'GET', 'PlanController:adminIndex' );
        $this->add( '/plans/subscription/show/(:numeric)', 'GET', 'PlanController:adminShow', [ 'subscriptionId' ] );

        // Rotas de admin para gerenciamento de assinaturas
        $this->add( '/plans/subscription/(:numeric)/cancel', 'POST', 'PlanController:adminCancelSubscription', [ 'subscriptionId' ] );
        $this->add( '/plans/subscription/(:numeric)/refund', 'POST', 'PlanController:adminRefundSubscription', [ 'subscriptionId' ] );
        $this->add( '/plans/provider-history/(:numeric)', 'GET', 'PlanController:adminProviderHistory', [ 'providerId' ] );

        $this->add( '/users/create', 'GET', 'UserController:create' );
        $this->add( '/users/store', 'POST', 'UserController:store' );
        $this->add( '/users/(:alpha)', 'GET', 'UserController:alpha' );
        $this->add( '/users/(:numeric)/name/(:alpha)', 'GET', 'UserController:index' );
        $this->add( '/users/(:numeric)/name/(:alpha)/tipo/(:any)', 'GET', 'UserController:show' );

        // Rotas de admin backup
        $this->add( '/backups', 'GET', 'BackupController:index' );
        $this->add( '/backups/create', 'POST', 'BackupController:create' );
        $this->add( '/backups/restore', 'POST', 'BackupController:restore' );
        $this->add( '/backups/delete', 'POST', 'BackupController:delete' );
        $this->add( '/backups/cleanup', 'POST', 'BackupController:cleanup' );

        // Rotas de admin categories
        $this->add( '/categories', 'GET', 'CategoryController:index' );
        $this->add( '/categories/create', 'GET', 'CategoryController:create' );
        $this->add( '/categories/store', 'POST', 'CategoryController:store' );
        $this->add( '/categories/show/(:numeric)', 'GET', 'CategoryController:show', [ 'id' ] );
        $this->add( '/categories/edit/(:numeric)', 'GET', 'CategoryController:edit', [ 'id' ] );
        $this->add( '/categories/update', 'POST', 'CategoryController:update' );
        $this->add( '/categories/delete/(:numeric)', 'POST', 'CategoryController:delete', [ 'id' ] );

        // Rotas de admin activities (logs de atividades)
        $this->add( '/activities', 'GET', 'ActivityController:index' );
        $this->add( '/activities/show/(:numeric)', 'GET', 'ActivityController:show', [ 'id' ] );

        // Sistema de Monitoramento
        $this->add( '/monitoring', 'GET', 'MonitoringController:index' );
        $this->add( '/monitoring/metrics', 'GET', 'MonitoringController:metrics' );
        $this->add( '/monitoring/api/metrics', 'GET', 'MonitoringController:apiMetrics' );
        $this->add( '/monitoring/api/reports', 'GET', 'MonitoringController:apiReports' );
        $this->add( '/monitoring/realtime', 'GET', 'MonitoringController:realTimeMetrics' );
        $this->add( '/monitoring/record', 'POST', 'MonitoringController:recordMetrics' );
        $this->add( '/monitoring/cleanup', 'POST', 'MonitoringController:cleanup' );
        $this->add( '/monitoring/middleware/(:any)', 'GET', 'MonitoringController:middleware', [ 'middlewareName' ] );

        // Sistema de Alertas
        $this->add( '/alerts', 'GET', 'AlertController:index' );
        $this->add( '/alerts/api', 'GET', 'AlertController:getAlertsApi' );
        $this->add( '/alerts/resolve/(:numeric)', 'POST', 'AlertController:resolveAlert', [ 'id' ] );
        $this->add( '/alerts/check-now', 'POST', 'AlertController:checkNow' );
        $this->add( '/alerts/settings', 'GET', 'AlertController:settings' );
        $this->add( '/alerts/settings', 'POST', 'AlertController:settings' );
        $this->add( '/alerts/history', 'GET', 'AlertController:history' );

        // Rotas admin adicionais referenciadas no menu
        $this->add( '/users', 'GET', 'UserController:index' );
        $this->add( '/roles', 'GET', 'RoleController:index' );
        $this->add( '/tenants', 'GET', 'TenantController:index' );
        $this->add( '/settings', 'GET', 'SettingsController:index' );

        // API para coleta de métricas movida para MonitoringController
        $this->add( '/api/metrics/collect', 'POST', 'MonitoringController:recordMetrics' );

        // Rotas de Inteligência Artificial
        $this->add( '/ai', 'GET', 'AIController:dashboard' );
        $this->add( '/ai/dataset', 'GET', 'AIController:dataset' );
        $this->add( '/ai/analyze/budget/(:numeric)', 'GET', 'AIController:analyzeBudget', [ 'budgetId' ] );
        $this->add( '/ai/insights/user/(:numeric)', 'GET', 'AIController:userInsights', [ 'userId' ] );
        $this->add( '/ai/alerts', 'GET', 'AIController:getAlerts' );
        $this->add( '/ai/metrics/roi', 'GET', 'AIController:roiMetrics' );

        // Adicionando rotas com prefixo /admin/ai para compatibilidade com frontend
        $this->add( '/ai/alerts', 'GET', 'AIController:getAlerts' );

        // Rota de teste para verificar funcionamento
        $this->add( '/ai/test', 'GET', 'AIController:test' );

        // Adicionando rota de teste no grupo admin também
        $this->add( '/ai/test-admin', 'GET', 'AIController:test' );

        // Adicionando mais uma rota de teste
        $this->add( '/ai/health', 'GET', 'AIController:test' );

    } );

    /**
     * Rotas para painel do provedor
     *
     * Permitem o acesso a funcionalidades específicas para pretadores.
     */
    $router->group( [ 'prefix' => 'provider', 'middlewares' => [ 'auth', 'provider' ] ], function () {
        $this->add( '/', 'GET', 'ProviderController:index' );
        $this->add( '/profile', 'GET', 'ProviderController:profile' );
        $this->add( '/update', 'GET', 'ProviderController:update' );
        $this->add( '/update', 'POST', 'ProviderController:update_store' );
        $this->add( '/change-password', 'GET', 'ProviderController:change_password' );
        $this->add( '/change-password', 'POST', 'ProviderController:change_password_store' );

        // Rotas de customers exemplo
        $this->add( '/customers', 'GET', 'CustomerController:index' );
        $this->add( '/customers/services-and-quotes', 'GET', 'CustomerController:servicesAndQuotes' );
        $this->add( '/customers/create', 'GET', 'CustomerController:create' );
        $this->add( '/customers/create', 'POST', 'CustomerController:store' );
        $this->add( '/customers/search', 'POST', 'AjaxController:customerSearch' );
        $this->add( '/customers/show/(:numeric)', 'GET', 'CustomerController:show', [ 'id' ] );
        $this->add( '/customers/update/(:numeric)', 'GET', 'CustomerController:update', [ 'id' ] );
        $this->add( '/customers/update', 'POST', 'CustomerController:update_store' );
        $this->add( '/customers/delete/(:numeric)', 'POST', 'CustomerController:delete_store', [ 'id' ] );

        // Rotas de customers exemplo
        $this->add( '/products', 'GET', 'ProductController:index' );
        $this->add( '/products/create', 'GET', 'ProductController:create' );
        $this->add( '/products/create', 'POST', 'ProductController:store' );
        $this->add( '/products/search', 'POST', 'AjaxController:productSearch' );
        $this->add( '/products/show/(:any)', 'GET', 'ProductController:show', [ 'code' ] );
        $this->add( '/products/update/(:any)', 'GET', 'ProductController:update', [ 'code' ] );
        $this->add( '/products/update', 'POST', 'ProductController:update_store' );
        $this->add( '/products/deactivate/(:any)', 'POST', 'ProductController:deactivate', [ 'code' ] );
        $this->add( '/products/activate/(:any)', 'POST', 'ProductController:activate', [ 'code' ] );
        $this->add( '/products/delete/(:any)', 'POST', 'ProductController:delete_store', [ 'code' ] );

        // Rotas de services exemplo
        $this->add( '/services', 'GET', 'ServiceController:index' );
        $this->add( '/services/create', 'GET', 'ServiceController:create' );
        $this->add( '/services/create', 'POST', 'ServiceController:store' );
        $this->add( '/services/services_filter', 'POST', 'AjaxController:services_filter' );
        $this->add( '/services/show/(:any)', 'GET', 'ServiceController:show', [ 'code' ] );
        $this->add( '/services/change-status', 'POST', 'ServiceController:change_status' );
        $this->add( '/services/cancel/(:any)', 'POST', 'ServiceController:cancel', [ 'code' ] );
        $this->add( '/budgets/(:numeric)/services/create', 'GET', 'ServiceController:create', [ 'code' ] );
        $this->add( '/services/(:numeric)', 'GET', 'ServiceController:show' );
        $this->add( '/services/edit/(:any)', 'GET', 'ServiceController:edit', [ 'code' ] );
        $this->add( '/services/update/(:any)', 'GET', 'ServiceController:update', [ 'code' ] );
        $this->add( '/services/update', 'POST', 'ServiceController:update_store', [ 'code' ] );
        $this->add( '/services/delete/(:any)', 'POST', 'ServiceController:delete_store', [ 'code' ] );
        $this->add( '/services/print/(:any)', 'GET', 'ServiceController:print', [ 'code' ] );

        $this->add( '/budgets', 'GET', 'BudgetController:index' );
        $this->add( '/budgets/create', 'GET', 'BudgetController:create', [ 'code' ] );
        $this->add( '/budgets/create', 'POST', 'BudgetController:store' );
        $this->add( '/budgets/budgets_filter', 'POST', 'AjaxController:budgets_filter' );
        $this->add( '/budgets/show/(:any)', 'GET', 'BudgetController:show', [ 'code' ] );
        $this->add( '/budgets/change-status', 'POST', 'BudgetController:change_status' );
        $this->add( '/budgets/update/(:any)', 'GET', 'BudgetController:update', [ 'code' ] );
        $this->add( '/budgets/update/(:any)', 'POST', 'BudgetController:update_store', [ 'code' ] );
        $this->add( '/budgets/delete/(:any)', 'POST', 'BudgetController:delete_store', [ 'code' ] );
        $this->add( '/budgets/print/(:any)', 'GET', 'BudgetController:print', [ 'code' ] );

        // Rotas de reports exemplo
        $this->add( '/reports', 'GET', 'ReportController:index' );
        $this->add( '/reports/customers', 'GET', 'ReportController:customers' );
        $this->add( '/reports/products', 'GET', 'ReportController:products' );
        $this->add( '/reports/services', 'GET', 'ReportController:services' );
        $this->add( '/reports/budgets', 'GET', 'ReportController:budgets' );
        $this->add( '/reports/budgets/pdf', 'GET', 'ReportController:budgets_pdf' );
        $this->add( '/reports/budgets/excel', 'GET', 'ReportController:budgets_excel' );
        $this->add( '/reports/budgets_filter', 'POST', 'AjaxController:budgets_filter' );
        $this->add( '/reports/services_filter', 'POST', 'AjaxController:services_filter' );

        // Rotas de invoices exemplo
        $this->add( '/invoices', 'GET', 'InvoiceController:index' );
        $this->add( '/invoices/create/(:any)', 'GET', 'InvoiceController:create', [ 'code' ] );
        $this->add( '/invoices/show/(:any)', 'GET', 'InvoiceController:show', [ 'code' ] );
        $this->add( '/invoices/store', 'POST', 'InvoiceController:store' );
        $this->add( '/invoices/print/(:any)', 'GET', 'InvoiceController:print', [ 'code' ] );
        $this->add( '/invoices/filter', 'POST', 'AjaxController:invoices_filter' );

        // Integração Mercado Pago
        $this->add( '/integrations/mercadopago', 'GET', 'MercadoPagoController:index' );
        $this->add( '/integrations/mercadopago/callback', 'GET', 'MercadoPagoController:callback' );
        $this->add( '/integrations/mercadopago/disconnect', 'POST', 'MercadoPagoController:disconnect' );

    } );

    /**
     * Rotas de fatura públicas
     *
     * Estas rotas são para o cliente final visualizar e pagar a fatura.
     * O acesso é feito através de um hash seguro.
     */
    $router->group( [ 'prefix' => 'invoices', 'middlewares' => [] ], function () {
        // Rota para visualizar a fatura pública
        $this->add( '/view/(:any)', 'GET', 'PublicInvoiceController:show', [ 'hash' ] );
        $this->add( '/pay/(:any)', 'GET', 'PublicInvoiceController:redirectToPayment', [ 'hash' ] );
        // Rota para verificar o status do pagamento
        $this->add( '/status', 'GET', 'PublicInvoiceController:paymentStatus' );
        $this->add( '/error', 'GET', 'PublicInvoiceController:error' );

    } );

    /**
     * Rotas de configurações
     *
     * Estas rotas são protegidas pelo middleware de autenticação.
     * Permitem aos usuários acessar e atualizar suas configurações.
     */
    $router->group( [ 'prefix' => 'settings', 'middlewares' => [ 'auth', 'provider' ] ], function () {
        $this->add( '/', 'GET', 'SettingsController:index' );
        $this->add( '/update', 'GET', 'SettingsController:update' );
    } );

    // Inicializa o roteamento
    $router->init();

} catch ( \Throwable $e ) {
    // Trata qualquer exceção não capturada
    $errorHandler = new ErrorHandler;
    $errorHandler->handle( $e );
}
