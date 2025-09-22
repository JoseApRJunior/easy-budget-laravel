<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AICoontroller;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InfoController;
use App\Http\Controllers\IntegrationController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProviderController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get( '/', [ HomeController::class, 'index' ] )->name( 'home' );

Route::get( '/about', [ InfoController::class, 'about' ] )->name( 'about' );
Route::get( '/terms-of-service', [ LegalController::class, 'termsOfService' ] )->name( 'legal.terms' );
Route::get( '/privacy-policy', [ LegalController::class, 'privacyPolicy' ] )->name( 'legal.privacy' );

Route::get( '/register', [ LoginController::class, 'showRegisterForm' ] )->name( 'register' );
Route::post( '/register', [ LoginController::class, 'register' ] )->name( 'register.store' );

Route::get( '/login', [ LoginController::class, 'showLoginForm' ] )->name( 'login' );
Route::post( '/login', [ LoginController::class, 'login' ] )->name( 'login.store' );
Route::post( '/logout', [ LoginController::class, 'logout' ] )->name( 'logout' );

Route::get( '/forgot-password', [ LoginController::class, 'showForgotPassword' ] )->name( 'password.request' );
Route::post( '/forgot-password', [ LoginController::class, 'forgotPassword' ] )->name( 'password.email' );
Route::get( '/reset-password/{token}', [ LoginController::class, 'showResetPassword' ] )->name( 'password.reset' );
Route::post( '/reset-password', [ LoginController::class, 'resetPassword' ] )->name( 'password.update' );

Route::get( '/confirm-account/{token}', [ UserController::class, 'showConfirmAccountForm' ] )->name( 'confirm.account' );
Route::post( '/confirm-account', [ UserController::class, 'confirmAccount' ] )->name( 'confirm.account.store' );
Route::post( '/resend-confirmation', [ UserController::class, 'resendConfirmation' ] )->name( 'resend.confirmation' );

Route::get( '/block-account/{token}', [ UserController::class, 'showBlockAccountForm' ] )->name( 'block.account' );
Route::post( '/block-account', [ UserController::class, 'blockAccount' ] )->name( 'block.account.store' );

Route::get( '/support', [ SupportController::class, 'index' ] )->name( 'support' );
Route::post( '/support', [ SupportController::class, 'store' ] )->name( 'support.store' );

Route::post( '/webhooks/mercado-pago', [ WebhookController::class, 'handleMercadoPago' ] )->name( 'webhook.mercadopago' );

// Public invoice and plan routes
Route::get( '/invoices/{id}', [ InvoiceController::class, 'publicShow' ] )->name( 'public.invoice.show' );
Route::get( '/plans/{slug}', [ PlanController::class, 'show' ] )->name( 'public.plan.show' );

Route::middleware( 'auth' )->group( function () {
    Route::get( '/profile', [ UserController::class, 'showProfile' ] )->name( 'profile.show' );

    Route::get( '/provider', [ ProviderController::class, 'index' ] )->name( 'provider.dashboard' );
    Route::get( '/provider/update', [ ProviderController::class, 'edit' ] )->name( 'provider.edit' );
    Route::post( '/provider/update', [ ProviderController::class, 'update' ] )->name( 'provider.update' );
    Route::get( '/provider/change-password', [ ProviderController::class, 'showChangePassword' ] )->name( 'provider.change.password' );
    Route::post( '/provider/change-password', [ ProviderController::class, 'changePassword' ] )->name( 'provider.change.password.store' );

    Route::get( '/provider/budgets', [ BudgetController::class, 'index' ] )->name( 'budgets.index' );
    Route::get( '/provider/budgets/create', [ BudgetController::class, 'create' ] )->name( 'budgets.create' );
    Route::post( '/provider/budgets', [ BudgetController::class, 'store' ] )->name( 'budgets.store' );
    Route::get( '/provider/budgets/{code}', [ BudgetController::class, 'show' ] )->name( 'budgets.show' );
    Route::get( '/provider/budgets/{code}/edit', [ BudgetController::class, 'edit' ] )->name( 'budgets.edit' );
    Route::post( '/provider/budgets/{code}', [ BudgetController::class, 'update' ] )->name( 'budgets.update' );
    Route::post( '/provider/budgets/change-status', [ BudgetController::class, 'changeStatus' ] )->name( 'budgets.change.status' );
    Route::get( '/budgets/choose-status/{code}/{token}', [ BudgetController::class, 'chooseBudgetStatus' ] )->name( 'budgets.choose.status' );
    Route::post( '/budgets/choose-status-store', [ BudgetController::class, 'chooseBudgetStatusStore' ] )->name( 'budgets.choose.status.store' );
    Route::get( '/provider/budgets/print/{code}', [ BudgetController::class, 'print' ] )->name( 'budgets.print' );
    Route::delete( '/provider/budgets/{code}', [ BudgetController::class, 'destroy' ] )->name( 'budgets.destroy' );

    Route::get( '/provider/customers', [ CustomerController::class, 'index' ] )->name( 'customers.index' );
    Route::get( '/provider/customers/create', [ CustomerController::class, 'create' ] )->name( 'customers.create' );
    Route::post( '/provider/customers', [ CustomerController::class, 'store' ] )->name( 'customers.store' );
    Route::get( '/provider/customers/{id}', [ CustomerController::class, 'show' ] )->name( 'customers.show' );
    Route::get( '/provider/customers/{id}/edit', [ CustomerController::class, 'edit' ] )->name( 'customers.edit' );
    Route::post( '/provider/customers/{id}', [ CustomerController::class, 'update' ] )->name( 'customers.update' );
    Route::get( '/provider/customers/{id}/services-quotes', [ CustomerController::class, 'servicesAndQuotes' ] )->name( 'customers.services.quotes' );
    Route::delete( '/provider/customers/{id}', [ CustomerController::class, 'destroy' ] )->name( 'customers.destroy' );

    Route::get( '/provider/products', [ ProductController::class, 'index' ] )->name( 'products.index' );
    Route::get( '/provider/products/create', [ ProductController::class, 'create' ] )->name( 'products.create' );
    Route::post( '/provider/products', [ ProductController::class, 'store' ] )->name( 'products.store' );
    Route::get( '/provider/products/{code}', [ ProductController::class, 'show' ] )->name( 'products.show' );
    Route::get( '/provider/products/{code}/edit', [ ProductController::class, 'edit' ] )->name( 'products.edit' );
    Route::post( '/provider/products/{code}', [ ProductController::class, 'update' ] )->name( 'products.update' );
    Route::get( '/provider/products/{code}/deactivate', [ ProductController::class, 'deactivate' ] )->name( 'products.deactivate' );
    Route::get( '/provider/products/{code}/activate', [ ProductController::class, 'activate' ] )->name( 'products.activate' );
    Route::delete( '/provider/products/{code}', [ ProductController::class, 'destroy' ] )->name( 'products.destroy' );

    Route::get( '/provider/services', [ ServiceController::class, 'index' ] )->name( 'services.index' );
    Route::get( '/provider/services/create', [ ServiceController::class, 'create' ] )->name( 'services.create' );
    Route::post( '/provider/services', [ ServiceController::class, 'store' ] )->name( 'services.store' );
    Route::get( '/provider/services/{code}', [ ServiceController::class, 'show' ] )->name( 'services.show' );
    Route::get( '/provider/services/{code}/edit', [ ServiceController::class, 'edit' ] )->name( 'services.edit' );
    Route::post( '/provider/services/{code}', [ ServiceController::class, 'update' ] )->name( 'services.update' );
    Route::post( '/provider/services/change-status', [ ServiceController::class, 'changeStatus' ] )->name( 'services.change.status' );
    Route::get( '/services/view-service-status/{code}/{token}', [ ServiceController::class, 'viewServiceStatus' ] )->name( 'services.view.status' );
    Route::post( '/services/choose-service-status-store', [ ServiceController::class, 'chooseServiceStatusStore' ] )->name( 'services.choose.status.store' );
    Route::delete( '/provider/services/{code}', [ ServiceController::class, 'destroy' ] )->name( 'services.destroy' );
    Route::get( '/provider/services/{code}/cancel', [ ServiceController::class, 'cancel' ] )->name( 'services.cancel' );
    Route::get( '/provider/services/{code}/print', [ ServiceController::class, 'print' ] )->name( 'services.print' );

    Route::get( '/provider/invoices', [ InvoiceController::class, 'index' ] )->name( 'invoices.index' );
    Route::get( '/provider/invoices/create', [ InvoiceController::class, 'create' ] )->name( 'invoices.create' );
    Route::post( '/provider/invoices', [ InvoiceController::class, 'store' ] )->name( 'invoices.store' );
    Route::get( '/provider/invoices/{id}', [ InvoiceController::class, 'show' ] )->name( 'invoices.show' );
    Route::get( '/provider/invoices/{id}/edit', [ InvoiceController::class, 'edit' ] )->name( 'invoices.edit' );
    Route::post( '/provider/invoices/{id}', [ InvoiceController::class, 'update' ] )->name( 'invoices.update' );
    Route::post( '/provider/invoices/change-status', [ InvoiceController::class, 'changeStatus' ] )->name( 'invoices.change.status' );
    Route::delete( '/provider/invoices/{id}', [ InvoiceController::class, 'destroy' ] )->name( 'invoices.destroy' );
    Route::get( '/provider/invoices/{id}/print', [ InvoiceController::class, 'print' ] )->name( 'invoices.print' );

    Route::get( '/provider/reports', [ ReportController::class, 'index' ] )->name( 'provider.reports.index' );
    Route::get( '/provider/reports/budgets', [ ReportController::class, 'budgets' ] )->name( 'reports.budgets' );
    Route::get( '/provider/reports/customers', [ ReportController::class, 'customers' ] )->name( 'reports.customers' );
    Route::get( '/provider/reports/services', [ ReportController::class, 'services' ] )->name( 'reports.services' );
    Route::get( '/provider/reports/invoices', [ ReportController::class, 'invoices' ] )->name( 'reports.invoices' );
    Route::get( '/provider/reports/export/{type}', [ ReportController::class, 'export' ] )->name( 'reports.export' );

    Route::get( '/provider/integrations', [ IntegrationController::class, 'index' ] )->name( 'integrations.index' );
    Route::get( '/provider/integrations/mercado-pago', [ IntegrationController::class, 'mercadoPago' ] )->name( 'integrations.mercadopago' );
    Route::post( '/provider/integrations/mercado-pago/setup', [ IntegrationController::class, 'setupMercadoPago' ] )->name( 'integrations.mercadopago.setup' );

    Route::get( '/provider/categories', [ CategoryController::class, 'index' ] )->name( 'categories.index' );
    Route::get( '/provider/categories/create', [ CategoryController::class, 'create' ] )->name( 'categories.create' );
    Route::post( '/provider/categories', [ CategoryController::class, 'store' ] )->name( 'categories.store' );
    Route::get( '/provider/categories/{id}/edit', [ CategoryController::class, 'edit' ] )->name( 'categories.edit' );
    Route::post( '/provider/categories/{id}', [ CategoryController::class, 'update' ] )->name( 'categories.update' );
    Route::delete( '/provider/categories/{id}', [ CategoryController::class, 'destroy' ] )->name( 'categories.destroy' );

    Route::get( '/provider/activities', [ ActivityController::class, 'index' ] )->name( 'activities.index' );
    Route::get( '/provider/activities/{id}', [ ActivityController::class, 'show' ] )->name( 'activities.show' );

    Route::get( '/provider/backups', [ BackupController::class, 'index' ] )->name( 'backups.index' );
    Route::post( '/provider/backups/create', [ BackupController::class, 'create' ] )->name( 'backups.create' );

    Route::get( '/provider/monitoring', [ MonitoringController::class, 'index' ] )->name( 'monitoring.index' );
    Route::get( '/provider/alerts', [ AlertController::class, 'index' ] )->name( 'alerts.index' );
    Route::get( '/provider/ai', [ AICoontroller::class, 'index' ] )->name( 'ai.index' );
} );

// Admin routes
Route::prefix( 'admin' )->middleware( [ 'auth', 'admin' ] )->name( 'admin.' )->group( function () {
    Route::get( '/dashboard', [ DashboardController::class, 'index' ] )->name( 'dashboard' );

    Route::get( '/plans/subscriptions', [ PlanController::class, 'subscriptions' ] )->name( 'plans.subscriptions' );

    Route::get( '/users', [ UserController::class, 'index' ] )->name( 'users.index' );
    Route::get( '/users/create', [ UserController::class, 'create' ] )->name( 'users.create' );
    Route::post( '/users', [ UserController::class, 'store' ] )->name( 'users.store' );
    Route::get( '/users/{id}', [ UserController::class, 'show' ] )->name( 'users.show' );
    Route::get( '/users/{id}/edit', [ UserController::class, 'edit' ] )->name( 'users.edit' );
    Route::post( '/users/{id}', [ UserController::class, 'update' ] )->name( 'users.update' );
    Route::delete( '/users/{id}', [ UserController::class, 'destroy' ] )->name( 'users.destroy' );

    Route::get( '/roles', [ RoleController::class, 'index' ] )->name( 'roles.index' );
    Route::get( '/roles/create', [ RoleController::class, 'create' ] )->name( 'roles.create' );
    Route::post( '/roles', [ RoleController::class, 'store' ] )->name( 'roles.store' );
    Route::get( '/roles/{id}', [ RoleController::class, 'show' ] )->name( 'roles.show' );
    Route::get( '/roles/{id}/edit', [ RoleController::class, 'edit' ] )->name( 'roles.edit' );
    Route::post( '/roles/{id}', [ RoleController::class, 'update' ] )->name( 'roles.update' );
    Route::delete( '/roles/{id}', [ RoleController::class, 'destroy' ] )->name( 'roles.destroy' );

    Route::get( '/tenants', [ TenantController::class, 'index' ] )->name( 'tenants.index' );
    Route::get( '/tenants/create', [ TenantController::class, 'create' ] )->name( 'tenants.create' );
    Route::post( '/tenants', [ TenantController::class, 'store' ] )->name( 'tenants.store' );
    Route::get( '/tenants/{id}', [ TenantController::class, 'show' ] )->name( 'tenants.show' );
    Route::get( '/tenants/{id}/edit', [ TenantController::class, 'edit' ] )->name( 'tenants.edit' );
    Route::post( '/tenants/{id}', [ TenantController::class, 'update' ] )->name( 'tenants.update' );
    Route::delete( '/tenants/{id}', [ TenantController::class, 'destroy' ] )->name( 'tenants.destroy' );

    Route::get( '/settings', [ SettingsController::class, 'index' ] )->name( 'settings.index' );
    Route::post( '/settings', [ SettingsController::class, 'store' ] )->name( 'settings.store' );

    Route::get( '/logs', [ LogController::class, 'index' ] )->name( 'logs.index' );

    Route::get( '/monitoring', [ MonitoringController::class, 'index' ] )->name( 'monitoring.index' );
    Route::get( '/alerts', [ AlertController::class, 'index' ] )->name( 'alerts.index' );
    Route::get( '/ai', [ AICoontroller::class, 'index' ] )->name( 'ai.index' );
    Route::get( '/backups', [ BackupController::class, 'index' ] )->name( 'backups.index' );
    Route::post( '/backups', [ BackupController::class, 'store' ] )->name( 'backups.store' );
    Route::get( '/categories', [ CategoryController::class, 'index' ] )->name( 'categories.index' );
    Route::get( '/activities', [ ActivityController::class, 'index' ] )->name( 'activities.index' );
} );

// Provider middleware group
Route::middleware( 'provider' )->group( function () {
    Route::get( '/provider/integrations', [ IntegrationController::class, 'index' ] )->name( 'provider.integrations' );
} );

// API routes for AJAX
Route::middleware( 'auth' )->group( function () {
    Route::get( '/api/customers/search', [ CustomerController::class, 'search' ] )->name( 'api.customers.search' );
    Route::get( '/api/products/search', [ ProductController::class, 'search' ] )->name( 'api.products.search' );
    Route::get( '/api/services/filter', [ ServiceController::class, 'filter' ] )->name( 'api.services.filter' );
    Route::get( '/api/budgets/filter', [ BudgetController::class, 'filter' ] )->name( 'api.budgets.filter' );
    Route::get( '/api/invoices/filter', [ InvoiceController::class, 'filter' ] )->name( 'api.invoices.filter' );
    Route::get( '/api/cep/{cep}', [ IntegrationController::class, 'getCep' ] )->name( 'api.cep.lookup' );
    Route::get( '/api/admin/metrics', [ MonitoringController::class, 'getMetrics' ] )->name( 'api.admin.metrics' );
} );

// Webhook routes
Route::post( '/webhooks/mercado-pago', [ WebhookController::class, 'handleMercadoPago' ] )->name( 'webhooks.mercadopago' );
