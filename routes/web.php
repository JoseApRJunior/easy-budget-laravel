<?php

use App\Http\Controllers\Admin\ActivityManagementController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdvancedMetricsController;
use App\Http\Controllers\Admin\AIMetricsController;
use App\Http\Controllers\Admin\CustomerManagementController;
use App\Http\Controllers\Admin\EnterpriseController;
use App\Http\Controllers\Admin\FinancialControlController;
use App\Http\Controllers\Admin\GlobalSettingsController;
use App\Http\Controllers\Admin\PlanManagementController;
use App\Http\Controllers\Admin\ProfessionManagementController;
use App\Http\Controllers\Admin\ProviderManagementController;
use App\Http\Controllers\Admin\SystemReportsController;
use App\Http\Controllers\Admin\TenantManagementController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\AIAnalyticsController;
use App\Http\Controllers\Auth\CustomVerifyEmailController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\BudgetShareController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentVerificationController;
use App\Http\Controllers\EmailPreviewController;
use App\Http\Controllers\ErrorController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Integrations\MercadoPagoController as IntegrationsMercadoPagoController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MailtrapController;
use App\Http\Controllers\MercadoPagoWebhookController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProviderBusinessController;
use App\Http\Controllers\ProviderController;
use App\Http\Controllers\PublicInvoiceController;
use App\Http\Controllers\QrCodeController;
use App\Http\Controllers\QueueManagementController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Public routes group
// Routes accessible without authentication for public pages and token-based access
Route::group([], function () {
    // Public pages - HomeController
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('/home', [HomeController::class, 'index'])->name('home.index');
    Route::get('/features', [HomeController::class, 'features'])->name('home.features');
    Route::get('/pricing', [HomeController::class, 'pricing'])->name('home.pricing');
    Route::get('/about', [HomeController::class, 'about'])->name('home.about');
    Route::get('/contact', [HomeController::class, 'contact'])->name('home.contact');
    Route::post('/contact', [HomeController::class, 'contactSubmit'])->name('home.contact.submit');

    // Temporary CSRF token route
    Route::get('/csrf-token', function () {
        return response()->json(['csrf_token' => csrf_token()]);
    })->name('csrf-token');


    Route::get('/support', [SupportController::class, 'index'])->name('support');
    Route::post('/support', [SupportController::class, 'store'])->name('support.store');
    Route::get('/terms-of-service', [HomeController::class, 'terms'])->name('terms');
    Route::get('/privacy-policy', [HomeController::class, 'privacy'])->name('privacy');

    // Public token-based routes for budgets, services, invoices
    Route::prefix('budgets')->name('budgets.public.')->group(function () {
        Route::get('/choose-budget-status/code/{code}/token/{token}', [BudgetController::class, 'chooseBudgetStatus'])->name('choose-status');
        Route::post('/choose-budget-status', [BudgetController::class, 'chooseBudgetStatusStore'])->name('choose-status.store');
        Route::get('/print/code/{code}/token/{token}', [BudgetController::class, 'print'])->name('print');

        // Budget sharing public routes
        Route::get('/shared/{token}', [BudgetShareController::class, 'access'])->name('shared.view');
        Route::post('/shared/{token}/accept', [BudgetShareController::class, 'approve'])->name('shared.accept');
        Route::post('/shared/{token}/reject', [BudgetShareController::class, 'rejectShare'])->name('shared.reject');
    });

    /**
     * Customer Dashboard (Área autenticada / provider)
     * Mantém compatibilidade com arquitetura atual e segue padrão de rotas provider.*
     */
    Route::middleware(['auth'])->group(function () {
        Route::get('/provider/customers/dashboard', [CustomerController::class, 'dashboard'])
            ->name('provider.customers.dashboard');
    });

    Route::prefix('services')->name('services.public.')->group(function () {
        Route::get('/view-service-status/code/{code}/token/{token}', [ServiceController::class, 'viewServiceStatus'])->name('view-status');
        Route::post('/choose-service-status', [ServiceController::class, 'chooseServiceStatus'])->name('choose-status');
        Route::get('/print/code/{code}/token/{token}', [ServiceController::class, 'print'])->name('print');
    });

    Route::prefix('invoices')->name('invoices.public.')->group(function () {
        Route::get('/view/{hash}', [PublicInvoiceController::class, 'show'])->name('show');
        Route::get('/pay/{hash}', [PublicInvoiceController::class, 'redirectToPayment'])->name('pay');
        Route::get('/status', [PublicInvoiceController::class, 'paymentStatus'])->name('status');
        Route::get('/error', [PublicInvoiceController::class, 'error'])->name('error');
    });

    // Document verification
    Route::prefix('documents')->name('documents.')->group(function () {
        Route::get('/verify/{hash}', [DocumentVerificationController::class, 'verify'])->name('verify');
    });
});

// Auth routes
// Routes for authentication processes like Google OAuth
Route::prefix('auth')->name('auth.')->group(function () {
    Route::get('/google', [GoogleController::class, 'redirect'])->name('google');
    Route::get('/google/callback', [GoogleController::class, 'callback'])->name('google.callback');
});

Route::middleware('auth')->group(function () {
    Route::post('/auth/google/unlink', [GoogleController::class, 'unlink'])->name('auth.google.unlink');
});

// Email verification routes
// Routes for email verification process
Route::prefix('email')->name('verification.')->group(function () {
    Route::get('/verify', [CustomVerifyEmailController::class, 'show'])->name('notice');
    Route::get('/verify/{id}/{hash}', [CustomVerifyEmailController::class, 'confirmAccount'])->middleware(['signed:relative'])->name('verify');
});

Route::get('/confirm-account', [CustomVerifyEmailController::class, 'confirmAccount'])->name('confirm-account');

// Provider routes group
// Routes for provider users with auth, verified, and provider middlewares
Route::prefix('provider')->name('provider.')->middleware(['auth', 'verified', 'provider', 'monitoring'])->group(function () {
    // Debug Tenant Access
    Route::get('/debug-tenant', [\App\Http\Controllers\DebugTenantController::class, 'index'])->name('debug-tenant');

    // Dashboard
    Route::get('/dashboard', [ProviderController::class, 'index'])->name('dashboard');

    // AI Analytics
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/', [AIAnalyticsController::class, 'index'])->name('index');
        Route::get('/overview', [AIAnalyticsController::class, 'overview'])->name('overview');
        Route::get('/trends', [AIAnalyticsController::class, 'trends'])->name('trends');
        Route::get('/predictions', [AIAnalyticsController::class, 'predictions'])->name('predictions');
        Route::get('/suggestions', [AIAnalyticsController::class, 'suggestions'])->name('suggestions');
        Route::get('/performance', [AIAnalyticsController::class, 'performance'])->name('performance');
        Route::get('/customers', [AIAnalyticsController::class, 'customers'])->name('customers');
        Route::get('/financial', [AIAnalyticsController::class, 'financial'])->name('financial');
        Route::get('/efficiency', [AIAnalyticsController::class, 'efficiency'])->name('efficiency');
    });

    // Plans
    Route::prefix('plans')->name('plans.')->group(function () {
        Route::get('/', [PlanController::class, 'index'])->name('index');
        Route::get('/create', [PlanController::class, 'create'])->name('create');
        Route::post('/', [PlanController::class, 'store'])->name('store');
        Route::get('/{plan}', [PlanController::class, 'show'])->name('show');
        Route::get('/{plan}/edit', [PlanController::class, 'edit'])->name('edit');
        Route::post('/{plan}', [PlanController::class, 'update'])->name('update');
        Route::delete('/{plan}', [PlanController::class, 'destroy'])->name('destroy');
        Route::post('/{plan}/activate', [PlanController::class, 'activate'])->name('activate');
        Route::post('/{plan}/deactivate', [PlanController::class, 'deactivate'])->name('deactivate');

        // Métodos específicos para gestão de planos e pagamentos
        Route::post('/{plan}/redirect-to-payment', [PlanController::class, 'redirectToPayment'])->name('redirect-to-payment');
        Route::post('/{plan}/cancel-pending-subscription', [PlanController::class, 'cancelPendingSubscription'])->name('cancel-pending-subscription');
        Route::get('/{plan}/status', [PlanController::class, 'status'])->name('status');
        Route::get('/payment-status', [PlanController::class, 'paymentStatus'])->name('payment-status');
    });

    // Customers
    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('/', [CustomerController::class, 'index'])->name('index');
        Route::get('/create', [CustomerController::class, 'create'])->name('create');

        // Método unificado para criação (Pessoa Física e Jurídica)
        Route::post('/', [CustomerController::class, 'store'])->name('store');

        Route::get('/find-nearby', [CustomerController::class, 'findNearby'])->name('find-nearby');
        Route::get('/search', [CustomerController::class, 'search'])->name('search');
        Route::get('/autocomplete', [CustomerController::class, 'autocomplete'])->name('autocomplete');
        Route::get('/export', [CustomerController::class, 'export'])->name('export');
        Route::get('/dashboard', [CustomerController::class, 'dashboard'])->name('dashboard');

        // Rotas genéricas de cliente (depois das rotas específicas)
        Route::get('/{customer}', [CustomerController::class, 'show'])->name('show');
        Route::get('/{customer}/edit', [CustomerController::class, 'edit'])->name('edit');

        // Métodos específicos de atualização com Form Requests
        // Formulários específicos para edição
        Route::get('/{customer}/pessoa-fisica/edit', [CustomerController::class, 'editPessoaFisica'])->name('edit-pessoa-fisica');
        Route::get('/{customer}/pessoa-juridica/edit', [CustomerController::class, 'editPessoaJuridica'])->name('edit-pessoa-juridica');
        Route::put('/{customer}/pessoa-fisica', [CustomerController::class, 'updatePessoaFisica'])->name('update-pessoa-fisica');
        Route::put('/{customer}/pessoa-juridica', [CustomerController::class, 'updatePessoaJuridica'])->name('update-pessoa-juridica');

        // Alterar status
        Route::post('/{customer}/toggle-status', [CustomerController::class, 'toggleStatus'])->name('toggle-status');
        // Método legado para compatibilidade
        Route::put('/{customer}', [CustomerController::class, 'update'])->name('update');

        Route::delete('/{customer}', [CustomerController::class, 'destroy'])->name('destroy');
        Route::post('/{customer}/restore', [CustomerController::class, 'restore'])->name('restore');
        Route::post('/{customer}/duplicate', [CustomerController::class, 'duplicate'])->name('duplicate');
    });

    // Products (novo módulo baseado em SKU + Service Layer)
    Route::prefix('products')->name('products.')->group(function () {
        // Dashboard de Produtos
        Route::get('/dashboard', [ProductController::class, 'dashboard'])->name('dashboard');

        // CRUD principal
        Route::get('/', [ProductController::class, 'index'])->name('index');
        Route::get('/create', [ProductController::class, 'create'])->name('create');
        Route::post('/', [ProductController::class, 'store'])->name('store');
        Route::get('/{sku}', [ProductController::class, 'show'])->name('show');
        Route::get('/{sku}/edit', [ProductController::class, 'edit'])->name('edit');
        Route::put('/{sku}', [ProductController::class, 'update'])->name('update');

        // Status e exclusão via SKU
        Route::patch('/{sku}/toggle-status', [ProductController::class, 'toggle_status'])->name('toggle-status');
        Route::delete('/{sku}', [ProductController::class, 'delete_store'])->name('destroy');

        // Inventory Management
        Route::post('/{id}/inventory/add', [InventoryController::class, 'add'])->name('inventory.add');
        Route::post('/{id}/inventory/remove', [InventoryController::class, 'remove'])->name('inventory.remove');
    });

    // Services
    Route::prefix('services')->name('services.')->group(function () {
        // Dashboard de Serviços
        Route::get('/dashboard', [ServiceController::class, 'dashboard'])->name('dashboard');

        Route::get('/', [ServiceController::class, 'index'])->name('index');
        Route::get('/create', [ServiceController::class, 'create'])->name('create');
        Route::post('/', [ServiceController::class, 'store'])->name('store');
        Route::get('/{service}', [ServiceController::class, 'show'])->name('show');
        Route::get('/{service}/edit', [ServiceController::class, 'edit'])->name('edit');
        Route::put('/{service}', [ServiceController::class, 'update'])->name('update');
        Route::post('/{service}/change-status', [ServiceController::class, 'change_status'])->name('change-status');
        Route::post('/{service}/cancel', [ServiceController::class, 'cancel'])->name('cancel');
        Route::delete('/{service}', [ServiceController::class, 'destroy'])->name('destroy');
        Route::get('/search/ajax', [ServiceController::class, 'search'])->name('search');
        Route::get('/{service}/print', [ServiceController::class, 'print'])->name('print');
    });

    // Schedules
    Route::prefix('schedules')->name('schedules.')->group(function () {
        Route::get('/dashboard', [ScheduleController::class, 'dashboard'])->name('dashboard');
        Route::get('/', [ScheduleController::class, 'index'])->name('index');
        Route::get('/calendar', [ScheduleController::class, 'calendar'])->name('calendar');
        Route::get('/create/{service}', [ScheduleController::class, 'create'])->name('create');
        Route::post('/{service}', [ScheduleController::class, 'store'])->name('store');
        Route::get('/{schedule}', [ScheduleController::class, 'show'])->name('show');
        Route::get('/{schedule}/edit', [ScheduleController::class, 'edit'])->name('edit');
        Route::put('/{schedule}', [ScheduleController::class, 'update'])->name('update');
        Route::delete('/{schedule}', [ScheduleController::class, 'destroy'])->name('destroy');
        Route::get('/calendar/data', [ScheduleController::class, 'getCalendarData'])->name('calendar.data');
        Route::get('/check-conflicts', [ScheduleController::class, 'checkConflicts'])->name('check-conflicts');
    });

    // Budgets
    Route::prefix('budgets')->name('budgets.')->group(function () {
        // Dashboard de Orçamentos
        Route::get('/dashboard', [BudgetController::class, 'dashboard'])->name('dashboard');

        Route::get('/', [BudgetController::class, 'index'])->name('index');
        Route::get('/create', [BudgetController::class, 'create'])->name('create');
        Route::post('/', [BudgetController::class, 'store'])->name('store');

        // Budget Sharing Routes - MOVIDAS PARA ANTES das rotas com parâmetros
        Route::prefix('shares')->name('shares.')->group(function () {
            Route::get('/dashboard', [BudgetShareController::class, 'dashboard'])->name('dashboard');
            Route::get('/', [BudgetShareController::class, 'index'])->name('index');
            Route::get('/create', [BudgetShareController::class, 'create'])->name('create');
            Route::post('/', [BudgetShareController::class, 'store'])->name('store');
            Route::get('/{share}', [BudgetShareController::class, 'show'])->name('show');
            Route::get('/{share}/edit', [BudgetShareController::class, 'edit'])->name('edit');
            Route::put('/{share}', [BudgetShareController::class, 'update'])->name('update');
            Route::delete('/{share}', [BudgetShareController::class, 'destroy'])->name('destroy');
            Route::post('/{share}/regenerate', [BudgetShareController::class, 'regenerateToken'])->name('regenerate');
            Route::post('/{share}/revoke', [BudgetShareController::class, 'revoke'])->name('revoke');
        });

        // Rotas com parâmetros devem vir DEPOIS das rotas específicas
        Route::get('/{code}', [BudgetController::class, 'show'])->name('show');
        Route::get('/{code}/edit', [BudgetController::class, 'edit'])->name('edit');
        Route::post('/{budget}', [BudgetController::class, 'update'])->name('update');
        Route::post('/{budget}/change-status', [BudgetController::class, 'changeStatus'])->name('change-status');
        Route::delete('/{budget}', [BudgetController::class, 'destroy'])->name('destroy');
        Route::get('/{budget}/print', [BudgetController::class, 'print'])->name('print');
        Route::get('/{budget}/services/create', [ServiceController::class, 'create'])->name('services.create');
    });

    // Invoices
    Route::prefix('invoices')->name('invoices.')->group(function () {
        Route::get('/dashboard', [InvoiceController::class, 'dashboard'])->name('dashboard');
        Route::get('/', [InvoiceController::class, 'index'])->name('index');
        Route::get('/create', [InvoiceController::class, 'create'])->name('create');
        Route::get('/budgets/{budget}/create', [InvoiceController::class, 'createFromBudget'])->name('create.from-budget');
        Route::get('/services/{serviceCode}/create', [InvoiceController::class, 'createFromService'])->name('create.from-service');
        Route::get('/services/{serviceCode}/create-partial', [InvoiceController::class, 'createPartialFromService'])->name('create.partial-from-service');
        Route::post('/services/{serviceCode}/manual', [InvoiceController::class, 'storeManualFromService'])->name('store.manual-from-service');
        Route::post('/', [InvoiceController::class, 'store'])->name('store');
        Route::post('/store-from-service', [InvoiceController::class, 'storeFromService'])->name('store.from-service');
        Route::get('/{code}', [InvoiceController::class, 'show'])->name('show');
        Route::get('/{code}/edit', [InvoiceController::class, 'edit'])->name('edit');
        Route::put('/{code}', [InvoiceController::class, 'update'])->name('update');
        Route::delete('/{code}', [InvoiceController::class, 'destroy'])->name('destroy');
        Route::get('/search/ajax', [InvoiceController::class, 'search'])->name('search');
        Route::get('/{code}/print', [InvoiceController::class, 'print'])->name('print');
        Route::get('/export', [InvoiceController::class, 'export'])->name('export');

        Route::get('/budgets/{budget}/create', [InvoiceController::class, 'createFromBudget'])->name('create.from-budget');
        Route::post('/budgets/{budget}', [InvoiceController::class, 'storeFromBudget'])->name('store.from-budget');
    });

    // QR Code routes - MOVIDO PARA DENTRO DO GRUPO PROVIDER
    Route::prefix('qrcode')->name('qrcode.')->group(function () {
        Route::get('/', [QrCodeController::class, 'index'])->name('index');
        Route::post('/generate', [QrCodeController::class, 'generate'])->name('generate');
        Route::post('/handle', [QrCodeController::class, 'handle'])->name('handle');
        Route::post('/budget', [QrCodeController::class, 'generateForBudget'])->name('budget');
        Route::post('/invoice', [QrCodeController::class, 'generateForInvoice'])->name('invoice');
    });

    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/dashboard', [ReportController::class, 'index'])->name('dashboard');
        Route::get('/download/{hash}', [ReportController::class, 'download'])->name('download');
        Route::get('/financial', [ReportController::class, 'financial'])->name('financial');
        Route::get('/budgets', [ReportController::class, 'budgets'])->name('budgets');
        Route::get('/budgets/excel', [ReportController::class, 'budgets_excel'])->name('budgets.excel');
        Route::get('/budgets/pdf', [ReportController::class, 'budgets_pdf'])->name('budgets.pdf');
        Route::get('/services', [ReportController::class, 'services'])->name('services');
        Route::get('/customers', [ReportController::class, 'customers'])->name('customers');
        Route::post('/customers/search', [ReportController::class, 'customersSearch'])->name('customers.search');
        Route::get('/customers/pdf', [ReportController::class, 'customersPdf'])->name('customers.pdf');
        Route::get('/customers/excel', [ReportController::class, 'customersExcel'])->name('customers.excel');
        Route::get('/products', [ReportController::class, 'products'])->name('products');
    });

    // Business
    Route::prefix('business')->name('business.')->group(function () {
        Route::get('/edit', [ProviderBusinessController::class, 'edit'])->name('edit');
        Route::put('/', [ProviderBusinessController::class, 'update'])->name('update')->withoutMiddleware('provider');
    });

    // Legacy routes (for backward compatibility)
    Route::get('/update', [ProviderController::class, 'update'])->name('update');
    Route::match(['post', 'put'], '/update', [ProviderController::class, 'update_store'])->name('update_store');
    Route::get('/change-password', [ProviderController::class, 'change_password'])->name('change_password');
    Route::post('/change-password', [ProviderController::class, 'change_password_store'])->name('change_password_store');

    // Inventory Management (Provider Access)
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/dashboard', [InventoryController::class, 'dashboard'])->name('dashboard');
        Route::get('/', [InventoryController::class, 'index'])->name('index');
        Route::get('/movements', [InventoryController::class, 'movements'])->name('movements');
        Route::get('/stock-turnover', [InventoryController::class, 'stockTurnover'])->name('stock-turnover');
        Route::get('/most-used', [InventoryController::class, 'mostUsedProducts'])->name('most-used');
        Route::get('/alerts', [InventoryController::class, 'alerts'])->name('alerts');
        Route::get('/report', [InventoryController::class, 'report'])->name('report');
        Route::get('/export', [InventoryController::class, 'export'])->name('export');
        Route::get('/export-movements', [InventoryController::class, 'exportMovements'])->name('export-movements');
        Route::get('/export-stock-turnover', [InventoryController::class, 'exportStockTurnover'])->name('export-stock-turnover');
        Route::get('/export-most-used', [InventoryController::class, 'exportMostUsed'])->name('export-most-used');
        Route::get('/{sku}', [InventoryController::class, 'show'])->name('show');

        // Entrada e saída de estoque
        Route::get('/{sku}/entry', [InventoryController::class, 'entryForm'])->name('entry');
        Route::post('/{sku}/entry', [InventoryController::class, 'entry'])->name('entry.store');
        Route::get('/{sku}/exit', [InventoryController::class, 'exitForm'])->name('exit');
        Route::post('/{sku}/exit', [InventoryController::class, 'exit'])->name('exit.store');

        Route::get('/{sku}/adjust', [InventoryController::class, 'adjustStockForm'])->name('adjust');
        Route::post('/{sku}/adjust', [InventoryController::class, 'adjustStock'])->name('adjust.store');

        // API routes
        Route::prefix('api')->name('api.')->group(function () {
            Route::post('/check-availability', [InventoryController::class, 'checkAvailability'])->name('check-availability');
        });
    });
});

// Admin routes group
// Routes for admin users with auth and admin middlewares
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin', 'monitoring'])->group(function () {
    // Dashboard (rota única)
    Route::get('/', [AdminDashboardController::class, 'index'])->name('index');

    // Users
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/create', [UserController::class, 'create'])->name('create');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('/{user}', [UserController::class, 'show'])->name('show');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
        Route::post('/{user}', [UserController::class, 'update'])->name('update');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
    });

    // Queues
    Route::prefix('queues')->name('queues.')->group(function () {
        Route::post('/work', [QueueManagementController::class, 'work'])->name('work');
        Route::post('/stop', [QueueManagementController::class, 'stop'])->name('stop');
    });

    // Settings
    Route::get('/settings', [SettingsController::class, 'admin'])->name('settings');

    // Enterprise Management
    Route::prefix('enterprises')->name('enterprises.')->group(function () {
        Route::get('/', [EnterpriseController::class, 'index'])->name('index');
        Route::get('/data', [EnterpriseController::class, 'data'])->name('data');
        Route::get('/{tenant}', [EnterpriseController::class, 'show'])->name('show');
        Route::get('/{tenant}/edit', [EnterpriseController::class, 'edit'])->name('edit');
        Route::get('/{tenant}/financial-data', [EnterpriseController::class, 'financialData'])->name('financial-data');
        Route::post('/{tenant}', [EnterpriseController::class, 'update'])->name('update');
        Route::delete('/{tenant}', [EnterpriseController::class, 'destroy'])->name('destroy');
    });

    // Financial Control
    Route::prefix('financial')->name('financial.')->group(function () {
        Route::get('/', [FinancialControlController::class, 'index'])->name('index');
        Route::get('/reports', [FinancialControlController::class, 'reports'])->name('reports');
        Route::get('/reports/export', [FinancialControlController::class, 'exportReports'])->name('reports.export');
        Route::get('/providers/{tenant}/details', [FinancialControlController::class, 'providerDetails'])->name('providers.details');
        Route::get('/budget-alerts', [FinancialControlController::class, 'budgetAlerts'])->name('budget-alerts');
    });

    // Monitoring (Admin)
    Route::prefix('monitoring')->name('monitoring.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\MonitoringController::class, 'dashboard'])->name('dashboard');
        Route::get('/metrics', [\App\Http\Controllers\Admin\MonitoringController::class, 'metrics'])->name('metrics');
        Route::get('/middleware', [\App\Http\Controllers\Admin\MonitoringController::class, 'middlewareMetrics'])->name('middleware');
        Route::get('/api/metrics', [\App\Http\Controllers\Admin\MonitoringController::class, 'apiMetrics'])->name('api.metrics');
    });

    // Global Settings Management
    Route::prefix('global-settings')->name('global-settings.')->group(function () {
        Route::get('/', [GlobalSettingsController::class, 'index'])->name('index');
        Route::post('/general/update', [GlobalSettingsController::class, 'updateGeneral'])->name('general.update');
        Route::post('/configuration/update', [GlobalSettingsController::class, 'updateConfiguration'])->name('configuration.update');
        Route::post('/email/update', [GlobalSettingsController::class, 'updateEmail'])->name('email.update');
        Route::post('/payment/update', [GlobalSettingsController::class, 'updatePayment'])->name('payment.update');
        Route::post('/notifications/update', [GlobalSettingsController::class, 'updateNotifications'])->name('notifications.update');
        Route::post('/ai/update', [GlobalSettingsController::class, 'updateAIAnalytics'])->name('ai.update');
        Route::post('/backup/update', [GlobalSettingsController::class, 'updateBackup'])->name('backup.update');
        Route::post('/email/test', [GlobalSettingsController::class, 'testEmail'])->name('email.test');
        Route::post('/payment/test', [GlobalSettingsController::class, 'testPayment'])->name('payment.test');
        Route::post('/clear-cache', [GlobalSettingsController::class, 'clearCache'])->name('clear-cache');
        Route::get('/export', [GlobalSettingsController::class, 'export'])->name('export');
        Route::post('/import', [GlobalSettingsController::class, 'import'])->name('import');
    });

    // Alerts Management
    Route::prefix('alerts')->name('alerts.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\AlertsController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\AlertsController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\AlertsController::class, 'store'])->name('store');
        Route::get('/{alert}', [\App\Http\Controllers\Admin\AlertsController::class, 'show'])->name('show');
        Route::get('/{alert}/edit', [\App\Http\Controllers\Admin\AlertsController::class, 'edit'])->name('edit');
        Route::put('/{alert}', [\App\Http\Controllers\Admin\AlertsController::class, 'update'])->name('update');
        Route::delete('/{alert}', [\App\Http\Controllers\Admin\AlertsController::class, 'destroy'])->name('destroy');
        Route::post('/{alert}/toggle-status', [\App\Http\Controllers\Admin\AlertsController::class, 'toggleStatus'])->name('toggle-status');
        Route::get('/export/{format}', [\App\Http\Controllers\Admin\AlertsController::class, 'export'])->name('export');
    });

    // Plan Management
    Route::prefix('plans')->name('plans.')->group(function () {
        Route::get('/', [PlanManagementController::class, 'index'])->name('index');
        Route::get('/create', [PlanManagementController::class, 'create'])->name('create');
        Route::post('/', [PlanManagementController::class, 'store'])->name('store');
        Route::get('/{plan}', [PlanManagementController::class, 'show'])->name('show');
        Route::get('/{plan}/edit', [PlanManagementController::class, 'edit'])->name('edit');
        Route::put('/{plan}', [PlanManagementController::class, 'update'])->name('update');
        Route::delete('/{plan}', [PlanManagementController::class, 'destroy'])->name('destroy');
        Route::post('/{plan}/toggle-status', [PlanManagementController::class, 'toggleStatus'])->name('toggle-status');
        Route::post('/{plan}/duplicate', [PlanManagementController::class, 'duplicate'])->name('duplicate');
        Route::get('/{plan}/subscribers', [PlanManagementController::class, 'subscribers'])->name('subscribers');
        Route::get('/{plan}/history', [PlanManagementController::class, 'history'])->name('history');
        Route::get('/{plan}/analytics', [PlanManagementController::class, 'analytics'])->name('analytics');
        Route::get('/export/{format}', [PlanManagementController::class, 'export'])->name('export');
    });

    // Tenant Management
    Route::prefix('tenants')->name('tenants.')->group(function () {
        Route::get('/', [TenantManagementController::class, 'index'])->name('index');
        Route::get('/create', [TenantManagementController::class, 'create'])->name('create');
        Route::post('/', [TenantManagementController::class, 'store'])->name('store');
        Route::get('/{tenant}', [TenantManagementController::class, 'show'])->name('show');
        Route::get('/{tenant}/edit', [TenantManagementController::class, 'edit'])->name('edit');
        Route::put('/{tenant}', [TenantManagementController::class, 'update'])->name('update');
        Route::delete('/{tenant}', [TenantManagementController::class, 'destroy'])->name('destroy');
        Route::post('/{tenant}/suspend', [TenantManagementController::class, 'suspend'])->name('suspend');
        Route::post('/{tenant}/activate', [TenantManagementController::class, 'activate'])->name('activate');
        Route::post('/{tenant}/impersonate', [TenantManagementController::class, 'impersonate'])->name('impersonate');
        Route::get('/{tenant}/analytics', [TenantManagementController::class, 'analytics'])->name('analytics');
        Route::get('/{tenant}/billing', [TenantManagementController::class, 'billing'])->name('billing');
    });

    // User Management
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserManagementController::class, 'index'])->name('index');
        Route::get('/create', [UserManagementController::class, 'create'])->name('create');
        Route::post('/', [UserManagementController::class, 'store'])->name('store');
        Route::get('/{user}', [UserManagementController::class, 'show'])->name('show');
        Route::get('/{user}/edit', [UserManagementController::class, 'edit'])->name('edit');
        Route::put('/{user}', [UserManagementController::class, 'update'])->name('update');
        Route::delete('/{user}', [UserManagementController::class, 'destroy'])->name('destroy');
        Route::post('/{user}/block', [UserManagementController::class, 'block'])->name('block');
        Route::post('/{user}/unblock', [UserManagementController::class, 'unblock'])->name('unblock');
        Route::post('/{user}/impersonate', [UserManagementController::class, 'impersonate'])->name('impersonate');
        Route::get('/{user}/activity', [UserManagementController::class, 'activity'])->name('activity');
    });

// Category Management (unificado)
Route::prefix('categories')->name('categories.')->group(function () {
    Route::get('/', [\App\Http\Controllers\CategoryController::class, 'index'])->name('index');
    Route::get('/create', [\App\Http\Controllers\CategoryController::class, 'create'])->name('create');
    Route::get('/export', [\App\Http\Controllers\CategoryController::class, 'export'])->name('export');
    Route::post('/', [\App\Http\Controllers\CategoryController::class, 'store'])->name('store');
    Route::get('/{slug}', [\App\Http\Controllers\CategoryController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [\App\Http\Controllers\CategoryController::class, 'edit'])->name('edit');
    Route::put('/{id}', [\App\Http\Controllers\CategoryController::class, 'update'])->name('update');
    Route::delete('/{id}', [\App\Http\Controllers\CategoryController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/set-default', [\App\Http\Controllers\CategoryController::class, 'setDefault'])->name('set-default');
});

    // Activity Management
    Route::prefix('activities')->name('activities.')->group(function () {
        Route::get('/', [ActivityManagementController::class, 'index'])->name('index');
        Route::get('/create', [ActivityManagementController::class, 'create'])->name('create');
        Route::post('/', [ActivityManagementController::class, 'store'])->name('store');
        Route::get('/{activity}', [ActivityManagementController::class, 'show'])->name('show');
        Route::get('/{activity}/edit', [ActivityManagementController::class, 'edit'])->name('edit');
        Route::put('/{activity}', [ActivityManagementController::class, 'update'])->name('update');
        Route::delete('/{activity}', [ActivityManagementController::class, 'destroy'])->name('destroy');
        Route::post('/{activity}/toggle-status', [ActivityManagementController::class, 'toggleStatus'])->name('toggle-status');
        Route::post('/{activity}/duplicate', [ActivityManagementController::class, 'duplicate'])->name('duplicate');
        Route::get('/export/{format}', [ActivityManagementController::class, 'export'])->name('export');
        Route::get('/ajax/by-category', [ActivityManagementController::class, 'getActivitiesByCategory'])->name('ajax.by-category');
        Route::get('/ajax/price', [ActivityManagementController::class, 'getActivityPrice'])->name('ajax.price');
    });

    // Profession Management
    Route::prefix('professions')->name('professions.')->group(function () {
        Route::get('/', [ProfessionManagementController::class, 'index'])->name('index');
        Route::get('/create', [ProfessionManagementController::class, 'create'])->name('create');
        Route::post('/', [ProfessionManagementController::class, 'store'])->name('store');
        Route::get('/{profession}', [ProfessionManagementController::class, 'show'])->name('show');
        Route::get('/{profession}/edit', [ProfessionManagementController::class, 'edit'])->name('edit');
        Route::put('/{profession}', [ProfessionManagementController::class, 'update'])->name('update');
        Route::delete('/{profession}', [ProfessionManagementController::class, 'destroy'])->name('destroy');
        Route::post('/{profession}/toggle-status', [ProfessionManagementController::class, 'toggleStatus'])->name('toggle-status');
        Route::post('/{profession}/duplicate', [ProfessionManagementController::class, 'duplicate'])->name('duplicate');
        Route::get('/export/{format}', [ProfessionManagementController::class, 'export'])->name('export');
        Route::get('/ajax/by-type', [ProfessionManagementController::class, 'getProfessionsByType'])->name('ajax.by-type');
    });

    // Customer Management
    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('/', [CustomerManagementController::class, 'index'])->name('index');
        Route::get('/create', [CustomerManagementController::class, 'create'])->name('create');
        Route::post('/', [CustomerManagementController::class, 'store'])->name('store');
        Route::get('/{customer}', [CustomerManagementController::class, 'show'])->name('show');
        Route::get('/{customer}/edit', [CustomerManagementController::class, 'edit'])->name('edit');
        Route::put('/{customer}', [CustomerManagementController::class, 'update'])->name('update');
        Route::delete('/{customer}', [CustomerManagementController::class, 'destroy'])->name('destroy');
        Route::post('/{customer}/toggle-status', [CustomerManagementController::class, 'toggleStatus'])->name('toggle-status');
        Route::get('/export/{format}', [CustomerManagementController::class, 'export'])->name('export');
        Route::get('/ajax/by-tenant', [CustomerManagementController::class, 'getCustomersByTenant'])->name('ajax.by-tenant');
    });

    // Provider Management
    Route::prefix('providers')->name('providers.')->group(function () {
        Route::get('/', [ProviderManagementController::class, 'index'])->name('index');
        Route::get('/create', [ProviderManagementController::class, 'create'])->name('create');
        Route::post('/', [ProviderManagementController::class, 'store'])->name('store');
        Route::get('/{provider}', [ProviderManagementController::class, 'show'])->name('show');
        Route::get('/{provider}/edit', [ProviderManagementController::class, 'edit'])->name('edit');
        Route::put('/{provider}', [ProviderManagementController::class, 'update'])->name('update');
        Route::delete('/{provider}', [ProviderManagementController::class, 'destroy'])->name('destroy');
        Route::post('/{provider}/toggle-status', [ProviderManagementController::class, 'toggleStatus'])->name('toggle-status');
        Route::get('/export/{format}', [ProviderManagementController::class, 'export'])->name('export');
        Route::get('/ajax/by-tenant', [ProviderManagementController::class, 'getProvidersByTenant'])->name('ajax.by-tenant');
    });

    // System Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [SystemReportsController::class, 'index'])->name('index');
        Route::get('/financial', [SystemReportsController::class, 'financial'])->name('financial');
        Route::get('/users', [SystemReportsController::class, 'users'])->name('users');
        Route::get('/tenants', [SystemReportsController::class, 'tenants'])->name('tenants');
        Route::get('/plans', [SystemReportsController::class, 'plans'])->name('plans');
        Route::get('/system', [SystemReportsController::class, 'system'])->name('system');
        Route::get('/export/{type}/{format}', [SystemReportsController::class, 'export'])->name('export');
        Route::post('/generate', [SystemReportsController::class, 'generate'])->name('generate');
    });

    // Advanced Metrics Dashboard
    Route::prefix('metrics')->name('metrics.')->group(function () {
        Route::get('/', [AdvancedMetricsController::class, 'index'])->name('index');
        Route::get('/realtime', [AdvancedMetricsController::class, 'realtime'])->name('realtime');
        Route::get('/export', [AdvancedMetricsController::class, 'export'])->name('export');
    });

    // AI Metrics and Analytics
    Route::prefix('ai')->name('ai.')->group(function () {
        Route::get('/metrics', [AIMetricsController::class, 'index'])->name('metrics');
        Route::get('/analytics', [AIMetricsController::class, 'analytics'])->name('analytics');
        Route::get('/predictions', [AIMetricsController::class, 'predictions'])->name('predictions');
        Route::get('/anomalies', [AIMetricsController::class, 'anomalies'])->name('anomalies');
        Route::get('/insights', [AIMetricsController::class, 'insights'])->name('insights');
        Route::post('/retrain', [AIMetricsController::class, 'retrain'])->name('retrain');
        Route::get('/export/{type}', [AIMetricsController::class, 'export'])->name('export');
    });

    // Audit Logs
    Route::prefix('audit')->name('audit.')->group(function () {
        Route::get('/logs', [\App\Http\Controllers\Admin\AuditController::class, 'index'])->name('logs');
        Route::get('/logs/{log}', [\App\Http\Controllers\Admin\AuditController::class, 'show'])->name('logs.show');
        Route::get('/export', [\App\Http\Controllers\Admin\AuditController::class, 'export'])->name('logs.export');
        Route::delete('/logs/{log}', [\App\Http\Controllers\Admin\AuditController::class, 'destroy'])->name('logs.destroy');
    });
});

// Settings routes group
// Routes for user settings with auth, verified and provider middlewares
Route::prefix('settings')->name('settings.')->middleware(['auth', 'verified', 'provider'])->group(function () {
    Route::get('/', [SettingsController::class, 'index'])->name('index');
    Route::post('/general', [SettingsController::class, 'updateGeneral'])->name('general.update');
    Route::post('/profile', [SettingsController::class, 'updateProfile'])->name('profile.update');
    Route::post('/security', [SettingsController::class, 'updateSecurity'])->name('security.update');
    Route::post('/notifications', [SettingsController::class, 'updateNotifications'])->name('notifications.update');
    Route::post('/integrations', [SettingsController::class, 'updateIntegrations'])->name('integrations.update');
    Route::post('/customization', [SettingsController::class, 'updateCustomization'])->name('customization.update');
    Route::post('/avatar', [SettingsController::class, 'updateAvatar'])->name('avatar.update');
    Route::delete('/avatar', [SettingsController::class, 'removeAvatar'])->name('avatar.remove');
    Route::post('/company-logo', [SettingsController::class, 'updateCompanyLogo'])->name('company-logo.update');
    Route::post('/backup', [SettingsController::class, 'createBackup'])->name('backup.create');
    Route::get('/backups', [SettingsController::class, 'listBackups'])->name('backups');
    Route::post('/backup/restore', [SettingsController::class, 'restoreBackup'])->name('backup.restore');
    Route::delete('/backup', [SettingsController::class, 'deleteBackup'])->name('backup.delete');
    Route::post('/restore-defaults', [SettingsController::class, 'restoreDefaults'])->name('restore-defaults');
    Route::get('/audit', [SettingsController::class, 'audit'])->name('audit');

    // Profile routes moved here for consistency
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Reports routes group
// Routes for general reports with auth and verified middlewares
Route::prefix('reports')->name('reports.')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/', [ReportController::class, 'index'])->name('index');
    Route::get('/builder', [ReportController::class, 'builder'])->name('builder');
    Route::post('/generate', [ReportController::class, 'generate'])->name('generate');
    Route::get('/{report}/show', [ReportController::class, 'show'])->name('show');
    Route::get('/{report}/export/{format}', [ReportController::class, 'export'])->name('export');
    Route::post('/{report}/schedule', [ReportController::class, 'schedule'])->name('schedule');
    Route::delete('/{report}/schedule', [ReportController::class, 'unschedule'])->name('unschedule');
});

// Public plan status routes
Route::prefix('plans')->name('plans.public.')->group(function () {
    Route::get('/status', [\App\Http\Controllers\PlanController::class, 'paymentStatus'])->name('status');
});

Route::middleware(['auth'])->prefix('categories')->name('categories.')->group(function () {
    Route::get('/', [\App\Http\Controllers\CategoryController::class, 'index'])->name('index');
    Route::get('/create', [\App\Http\Controllers\CategoryController::class, 'create'])->name('create');
    Route::get('/export', [\App\Http\Controllers\CategoryController::class, 'export'])->name('export');
    Route::get('/ajax/check-slug', [\App\Http\Controllers\CategoryController::class, 'checkSlug'])->name('ajax.check-slug');
    Route::post('/', [\App\Http\Controllers\CategoryController::class, 'store'])->name('store');
    Route::get('/{slug}', [\App\Http\Controllers\CategoryController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [\App\Http\Controllers\CategoryController::class, 'edit'])->name('edit');
    Route::put('/{id}', [\App\Http\Controllers\CategoryController::class, 'update'])->name('update');
    Route::delete('/{id}', [\App\Http\Controllers\CategoryController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/set-default', [\App\Http\Controllers\CategoryController::class, 'setDefault'])->name('set-default');
});

// Admin Category Management (espelhado)
Route::middleware(['auth'])->prefix('admin/categories')->name('admin.categories.')->group(function () {
    Route::get('/', [\App\Http\Controllers\CategoryController::class, 'index'])->name('index');
    Route::get('/create', [\App\Http\Controllers\CategoryController::class, 'create'])->name('create');
    Route::get('/export', [\App\Http\Controllers\CategoryController::class, 'export'])->name('export');
    Route::post('/', [\App\Http\Controllers\CategoryController::class, 'store'])->name('store');
    Route::get('/{slug}', [\App\Http\Controllers\CategoryController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [\App\Http\Controllers\CategoryController::class, 'edit'])->name('edit');
    Route::put('/{id}', [\App\Http\Controllers\CategoryController::class, 'update'])->name('update');
    Route::delete('/{id}', [\App\Http\Controllers\CategoryController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/set-default', [\App\Http\Controllers\CategoryController::class, 'setDefault'])->name('set-default');
});

// Queues routes group
// Routes for queue management with auth and verified middlewares
Route::prefix('queues')->name('queues.')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/', [QueueManagementController::class, 'index'])->name('index');
    Route::get('/stats', [QueueManagementController::class, 'stats'])->name('stats');
    Route::get('/health', [QueueManagementController::class, 'health'])->name('health');
    Route::post('/cleanup', [QueueManagementController::class, 'cleanup'])->name('cleanup');
    Route::post('/retry', [QueueManagementController::class, 'retry'])->name('retry');
    Route::post('/test-email', [QueueManagementController::class, 'testEmail'])->name('test-email');
});

// Email preview routes group
// Routes for email preview with auth and verified middlewares
Route::prefix('email-preview')->name('email-preview.')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/', [EmailPreviewController::class, 'index'])->name('index');
    Route::get('/{emailType}', [EmailPreviewController::class, 'show'])->name('show');
    Route::get('/config/data', [EmailPreviewController::class, 'config'])->name('config');
});

// Mailtrap routes group
// Routes for mailtrap integration with auth and verified middlewares
Route::prefix('mailtrap')->name('mailtrap.')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/', [MailtrapController::class, 'index'])->name('index');
    Route::get('/providers', [MailtrapController::class, 'providers'])->name('providers');
    Route::get('/tests', [MailtrapController::class, 'tests'])->name('tests');
    Route::get('/logs', [MailtrapController::class, 'logs'])->name('logs');
    Route::get('/report', [MailtrapController::class, 'generateReport'])->name('report');

    // AJAX routes for dynamic functionalities
    Route::post('/test-provider', [MailtrapController::class, 'testProvider'])->name('test-provider');
    Route::post('/run-test', [MailtrapController::class, 'runTest'])->name('run-test');
    Route::post('/generate-report', [MailtrapController::class, 'generateReport'])->name('generate-report');
    Route::post('/clear-cache', [MailtrapController::class, 'clearCache'])->name('clear-cache');
    Route::get('/provider/{provider}/config', [MailtrapController::class, 'providerConfig'])->name('provider-config');
});

// Webhooks routes group
// Routes for webhooks with necessary security (no auth middleware for external access)
Route::prefix('webhooks')->name('webhooks.')->group(function () {
    Route::post('/mercadopago/invoices', [MercadoPagoWebhookController::class, 'handleInvoiceWebhook'])->name('mercadopago.invoices');
    Route::post('/mercadopago/plans', [MercadoPagoWebhookController::class, 'handlePlanWebhook'])->name('mercadopago.plans');
});

// Error routes group
// Routes for error pages accessible without authentication
Route::group([], function () {
    Route::get('/not-allowed', [ErrorController::class, 'notAllowed'])->name('error.not-allowed');
    Route::get('/not-found', [ErrorController::class, 'notFound'])->name('error.not-found');
    Route::get('/internal', [ErrorController::class, 'internal'])->name('error.internal');
    Route::get('/internal-error', [ErrorController::class, 'internal'])->name('error.internal-alt');
});

// Redirects for backward compatibility
// Redirects for legacy routes to maintain compatibility
Route::middleware(['auth', 'verified', 'provider'])->group(function () {
    Route::redirect('/provider/update', '/provider/business/edit')->name('provider.update.redirect');
    Route::put('/provider/business', [ProviderBusinessController::class, 'update'])->name('provider.business.update');
});

require __DIR__ . '/auth.php';

// Upload routes (require provider authentication)
Route::middleware(['auth', 'verified', 'provider'])->group(function () {
    Route::prefix('upload')->name('upload.')->group(function () {
        Route::post('/image', [UploadController::class, 'uploadImage'])->name('image');
        Route::delete('/image', [UploadController::class, 'deleteImage'])->name('image.delete');
        Route::post('/image/optimize', [UploadController::class, 'optimizeImage'])->name('image.optimize');
    });
});

Route::prefix('integrations')->name('integrations.')->group(function () {
    Route::get('/mercadopago', [IntegrationsMercadoPagoController::class, 'index'])->name('mercadopago.index');
    Route::get('/mercadopago/callback', [IntegrationsMercadoPagoController::class, 'callback'])->name('mercadopago.callback');
    Route::post('/mercadopago/disconnect', [IntegrationsMercadoPagoController::class, 'disconnect'])->name('mercadopago.disconnect');
    Route::post('/mercadopago/refresh', [IntegrationsMercadoPagoController::class, 'refresh'])->name('mercadopago.refresh');
    Route::get('/mercadopago/test', [IntegrationsMercadoPagoController::class, 'testConnection'])->name('mercadopago.test');
});
