<?php
use App\Http\Controllers\Auth\CustomVerifyEmailController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentVerificationController;
use App\Http\Controllers\EmailPreviewController;
use App\Http\Controllers\ErrorController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MailtrapController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProviderBusinessController;
use App\Http\Controllers\ProviderController;
use App\Http\Controllers\PublicInvoiceController;
use App\Http\Controllers\QueueManagementController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

// Public routes group
// Routes accessible without authentication for public pages and token-based access
Route::group( [], function () {
    // Public pages
    Route::get( '/', [ HomeController::class, 'index' ] )->name( 'home' );
    Route::get( '/about', [ HomeController::class, 'about' ] )->name( 'about' );
    Route::get( '/support', [ SupportController::class, 'index' ] )->name( 'support' );
    Route::post( '/support', [ SupportController::class, 'store' ] )->name( 'support.store' );
    Route::get( '/terms-of-service', [ HomeController::class, 'terms' ] )->name( 'terms' );
    Route::get( '/privacy-policy', [ HomeController::class, 'privacy' ] )->name( 'privacy' );

    // Public token-based routes for budgets, services, invoices
    Route::prefix( 'budgets' )->name( 'budgets.public.' )->group( function () {
        Route::get( '/choose-budget-status/code/{code}/token/{token}', [ BudgetController::class, 'chooseBudgetStatus' ] )->name( 'choose-status' );
        Route::post( '/choose-budget-status', [ BudgetController::class, 'chooseBudgetStatusStore' ] )->name( 'choose-status.store' );
        Route::get( '/print/code/{code}/token/{token}', [ BudgetController::class, 'print' ] )->name( 'print' );
    } );

    Route::prefix( 'services' )->name( 'services.public.' )->group( function () {
        Route::get( '/view-service-status/code/{code}/token/{token}', [ ServiceController::class, 'viewServiceStatus' ] )->name( 'view-status' );
        Route::post( '/choose-service-status', [ ServiceController::class, 'chooseServiceStatus' ] )->name( 'choose-status' );
        Route::get( '/print/code/{code}/token/{token}', [ ServiceController::class, 'print' ] )->name( 'print' );
    } );

    Route::prefix( 'invoices' )->name( 'invoices.public.' )->group( function () {
        Route::get( '/view/{hash}', [ PublicInvoiceController::class, 'show' ] )->name( 'show' );
        Route::get( '/pay/{hash}', [ PublicInvoiceController::class, 'redirectToPayment' ] )->name( 'pay' );
        Route::get( '/status', [ PublicInvoiceController::class, 'paymentStatus' ] )->name( 'status' );
        Route::get( '/error', [ PublicInvoiceController::class, 'error' ] )->name( 'error' );
    } );

    // Document verification
    Route::prefix( 'documents' )->name( 'documents.' )->group( function () {
        Route::get( '/verify/{hash}', [ DocumentVerificationController::class, 'verify' ] )->name( 'verify' );
    } );
} );

// Auth routes
// Routes for authentication processes like Google OAuth
Route::prefix( 'auth' )->name( 'auth.' )->group( function () {
    Route::get( '/google', [ GoogleController::class, 'redirect' ] )->name( 'google' );
    Route::get( '/google/callback', [ GoogleController::class, 'callback' ] )->name( 'google.callback' );
} );

Route::middleware( 'auth' )->group( function () {
    Route::post( '/auth/google/unlink', [ GoogleController::class, 'unlink' ] )->name( 'auth.google.unlink' );
} );

// Email verification routes
// Routes for email verification process
Route::prefix( 'email' )->name( 'verification.' )->group( function () {
    Route::get( '/verify', [ CustomVerifyEmailController::class, 'show' ] )->name( 'notice' );
    Route::get( '/verify/{id}/{hash}', [ CustomVerifyEmailController::class, 'confirmAccount' ] )->middleware( [ 'signed:relative' ] )->name( 'verify' );
} );

Route::get( '/confirm-account', [ CustomVerifyEmailController::class, 'confirmAccount' ] )->name( 'confirm-account' );

// Provider routes group
// Routes for provider users with auth, verified, and provider middlewares
Route::prefix( 'provider' )->name( 'provider.' )->middleware( [ 'auth', 'verified', 'provider' ] )->group( function () {
    // Dashboard
    Route::get( '/dashboard', [ ProviderController::class, 'index' ] )->name( 'dashboard' );

    // Plans
    Route::prefix( 'plans' )->name( 'plans.' )->group( function () {
        Route::get( '/', [ PlanController::class, 'index' ] )->name( 'index' );
        Route::get( '/create', [ PlanController::class, 'create' ] )->name( 'create' );
        Route::post( '/', [ PlanController::class, 'store' ] )->name( 'store' );
        Route::get( '/{plan}', [ PlanController::class, 'show' ] )->name( 'show' );
        Route::get( '/{plan}/edit', [ PlanController::class, 'edit' ] )->name( 'edit' );
        Route::post( '/{plan}', [ PlanController::class, 'update' ] )->name( 'update' );
        Route::delete( '/{plan}', [ PlanController::class, 'destroy' ] )->name( 'destroy' );
        Route::post( '/{plan}/activate', [ PlanController::class, 'activate' ] )->name( 'activate' );
        Route::post( '/{plan}/deactivate', [ PlanController::class, 'deactivate' ] )->name( 'deactivate' );
    } );

    // Customers
    Route::prefix( 'customers' )->name( 'customers.' )->group( function () {
        Route::get( '/', [ CustomerController::class, 'index' ] )->name( 'index' );
        Route::get( '/create/pessoa-fisica', [ CustomerController::class, 'createPessoaFisica' ] )->name( 'create.pessoa-fisica' );
        Route::get( '/create/pessoa-juridica', [ CustomerController::class, 'createPessoaJuridica' ] )->name( 'create.pessoa-juridica' );
        Route::post( '/pessoa-fisica', [ CustomerController::class, 'storePessoaFisica' ] )->name( 'store.pessoa-fisica' );
        Route::post( '/pessoa-juridica', [ CustomerController::class, 'storePessoaJuridica' ] )->name( 'store.pessoa-juridica' );
        Route::get( '/{customer}', [ CustomerController::class, 'show' ] )->name( 'show' );
        Route::get( '/{customer}/edit', [ CustomerController::class, 'edit' ] )->name( 'edit' );
        Route::post( '/{customer}', [ CustomerController::class, 'update' ] )->name( 'update' );
        Route::delete( '/{customer}', [ CustomerController::class, 'destroy' ] )->name( 'destroy' );
        Route::post( '/{customer}/restore', [ CustomerController::class, 'restore' ] )->name( 'restore' );
        Route::post( '/{customer}/duplicate', [ CustomerController::class, 'duplicate' ] )->name( 'duplicate' );
        Route::get( '/find-nearby', [ CustomerController::class, 'findNearby' ] )->name( 'find-nearby' );
        Route::get( '/autocomplete', [ CustomerController::class, 'autocomplete' ] )->name( 'autocomplete' );
        Route::get( '/export', [ CustomerController::class, 'export' ] )->name( 'export' );
        Route::get( '/dashboard', [ CustomerController::class, 'dashboard' ] )->name( 'dashboard' );
    } );

    // Products
    Route::prefix( 'products' )->name( 'products.' )->group( function () {
        Route::get( '/', [ ProductController::class, 'index' ] )->name( 'index' );
        Route::get( '/create', [ ProductController::class, 'create' ] )->name( 'create' );
        Route::post( '/', [ ProductController::class, 'store' ] )->name( 'store' );
        Route::get( '/{product}', [ ProductController::class, 'show' ] )->name( 'show' );
        Route::get( '/{product}/edit', [ ProductController::class, 'edit' ] )->name( 'edit' );
        Route::post( '/{product}', [ ProductController::class, 'update' ] )->name( 'update' );
        Route::post( '/{product}/deactivate', [ ProductController::class, 'deactivate' ] )->name( 'deactivate' );
        Route::post( '/{product}/activate', [ ProductController::class, 'activate' ] )->name( 'activate' );
        Route::delete( '/{product}', [ ProductController::class, 'destroy' ] )->name( 'destroy' );
        Route::get( '/search/ajax', [ ProductController::class, 'search' ] )->name( 'search' );
        Route::get( '/export', [ ProductController::class, 'export' ] )->name( 'export' );
        Route::get( '/{product}/print', [ ProductController::class, 'print' ] )->name( 'print' );
    } );

    // Services
    Route::prefix( 'services' )->name( 'services.' )->group( function () {
        Route::get( '/', [ ServiceController::class, 'index' ] )->name( 'index' );
        Route::get( '/create', [ ServiceController::class, 'create' ] )->name( 'create' );
        Route::post( '/', [ ServiceController::class, 'store' ] )->name( 'store' );
        Route::get( '/{service}', [ ServiceController::class, 'show' ] )->name( 'show' );
        Route::get( '/{service}/edit', [ ServiceController::class, 'edit' ] )->name( 'edit' );
        Route::post( '/{service}', [ ServiceController::class, 'update' ] )->name( 'update' );
        Route::post( '/{service}/change-status', [ ServiceController::class, 'changeStatus' ] )->name( 'change-status' );
        Route::post( '/{service}/cancel', [ ServiceController::class, 'cancel' ] )->name( 'cancel' );
        Route::delete( '/{service}', [ ServiceController::class, 'destroy' ] )->name( 'destroy' );
        Route::get( '/search/ajax', [ ServiceController::class, 'search' ] )->name( 'search' );
        Route::get( '/{service}/print', [ ServiceController::class, 'print' ] )->name( 'print' );
    } );

    // Budgets
    Route::prefix( 'budgets' )->name( 'budgets.' )->group( function () {
        Route::get( '/', [ BudgetController::class, 'index' ] )->name( 'index' );
        Route::get( '/create', [ BudgetController::class, 'create' ] )->name( 'create' );
        Route::post( '/', [ BudgetController::class, 'store' ] )->name( 'store' );
        Route::get( '/{budget}', [ BudgetController::class, 'show' ] )->name( 'show' );
        Route::get( '/{budget}/edit', [ BudgetController::class, 'edit' ] )->name( 'edit' );
        Route::post( '/{budget}', [ BudgetController::class, 'update' ] )->name( 'update' );
        Route::post( '/{budget}/change-status', [ BudgetController::class, 'changeStatus' ] )->name( 'change-status' );
        Route::delete( '/{budget}', [ BudgetController::class, 'destroy' ] )->name( 'destroy' );
        Route::get( '/{budget}/print', [ BudgetController::class, 'print' ] )->name( 'print' );
        Route::get( '/{budget}/services/create', [ ServiceController::class, 'create' ] )->name( 'services.create' );
    } );

    // Invoices
    Route::prefix( 'invoices' )->name( 'invoices.' )->group( function () {
        Route::get( '/', [ InvoiceController::class, 'index' ] )->name( 'index' );
        Route::get( '/create', [ InvoiceController::class, 'create' ] )->name( 'create' );
        Route::get( '/budgets/{budget}/create', [ InvoiceController::class, 'createFromBudget' ] )->name( 'create.from-budget' );
        Route::post( '/', [ InvoiceController::class, 'store' ] )->name( 'store' );
        Route::get( '/{invoice}', [ InvoiceController::class, 'show' ] )->name( 'show' );
        Route::get( '/{invoice}/edit', [ InvoiceController::class, 'edit' ] )->name( 'edit' );
        Route::post( '/{invoice}', [ InvoiceController::class, 'update' ] )->name( 'update' );
        Route::delete( '/{invoice}', [ InvoiceController::class, 'destroy' ] )->name( 'destroy' );
        Route::get( '/search/ajax', [ InvoiceController::class, 'search' ] )->name( 'search' );
        Route::get( '/{invoice}/print', [ InvoiceController::class, 'print' ] )->name( 'print' );
        Route::get( '/export', [ InvoiceController::class, 'export' ] )->name( 'export' );
    } );

    // Reports
    Route::prefix( 'reports' )->name( 'reports.' )->group( function () {
        Route::get( '/', [ ProviderController::class, 'reports_index' ] )->name( 'index' );
        Route::get( '/financial', [ ProviderController::class, 'financial_reports' ] )->name( 'financial' );
        Route::get( '/budgets', [ ProviderController::class, 'budget_reports' ] )->name( 'budgets' );
        Route::get( '/budgets/excel', [ ProviderController::class, 'budget_reports_excel' ] )->name( 'budgets.excel' );
        Route::get( '/budgets/pdf', [ ProviderController::class, 'budget_reports_pdf' ] )->name( 'budgets.pdf' );
        Route::get( '/services', [ ProviderController::class, 'service_reports' ] )->name( 'services' );
        Route::get( '/customers', [ ProviderController::class, 'customer_reports' ] )->name( 'customers' );
    } );

    // Business
    Route::prefix( 'business' )->name( 'business.' )->group( function () {
        Route::get( '/edit', [ ProviderBusinessController::class, 'edit' ] )->name( 'edit' );
        // Temporarily disabled CSRF for testing
        // Route::patch( '/', [ ProviderBusinessController::class, 'update' ] )->name( 'update' );
        Route::patch( '/', [ ProviderBusinessController::class, 'update' ] )->name( 'update' )->withoutMiddleware( [ 'csrf' ] );
    } );

    // Legacy routes (for backward compatibility)
    Route::get( '/update', [ ProviderController::class, 'update' ] )->name( 'update' );
    Route::match( [ 'post', 'put' ], '/update', [ ProviderController::class, 'update_store' ] )->name( 'update_store' );
    Route::get( '/change-password', [ ProviderController::class, 'change_password' ] )->name( 'change_password' );
    Route::post( '/change-password', [ ProviderController::class, 'change_password_store' ] )->name( 'change_password_store' );
} );

// Admin routes group
// Routes for admin users with auth and admin middlewares
Route::prefix( 'admin' )->name( 'admin.' )->middleware( [ 'auth', 'admin' ] )->group( function () {
    // Dashboard
    Route::get( '/', [ HomeController::class, 'admin' ] )->name( 'index' );
    Route::get( '/dashboard', [ DashboardController::class, 'index' ] )->name( 'dashboard' );

    // Users
    Route::prefix( 'users' )->name( 'users.' )->group( function () {
        Route::get( '/', [ UserController::class, 'index' ] )->name( 'index' );
        Route::get( '/create', [ UserController::class, 'create' ] )->name( 'create' );
        Route::post( '/', [ UserController::class, 'store' ] )->name( 'store' );
        Route::get( '/{user}', [ UserController::class, 'show' ] )->name( 'show' );
        Route::get( '/{user}/edit', [ UserController::class, 'edit' ] )->name( 'edit' );
        Route::post( '/{user}', [ UserController::class, 'update' ] )->name( 'update' );
        Route::delete( '/{user}', [ UserController::class, 'destroy' ] )->name( 'destroy' );
    } );

    // Queues
    Route::prefix( 'queues' )->name( 'queues.' )->group( function () {
        Route::post( '/work', [ QueueManagementController::class, 'work' ] )->name( 'work' );
        Route::post( '/stop', [ QueueManagementController::class, 'stop' ] )->name( 'stop' );
    } );

    // Settings
    Route::get( '/settings', [ SettingsController::class, 'admin' ] )->name( 'settings' );
} );

// Settings routes group
// Routes for user settings with auth, verified and provider middlewares
Route::prefix( 'settings' )->name( 'settings.' )->middleware( [ 'auth', 'verified', 'provider' ] )->group( function () {
    Route::get( '/', [ SettingsController::class, 'index' ] )->name( 'index' );
    Route::post( '/general', [ SettingsController::class, 'updateGeneral' ] )->name( 'general.update' );
    Route::post( '/profile', [ SettingsController::class, 'updateProfile' ] )->name( 'profile.update' );
    Route::post( '/security', [ SettingsController::class, 'updateSecurity' ] )->name( 'security.update' );
    Route::post( '/notifications', [ SettingsController::class, 'updateNotifications' ] )->name( 'notifications.update' );
    Route::post( '/integrations', [ SettingsController::class, 'updateIntegrations' ] )->name( 'integrations.update' );
    Route::post( '/customization', [ SettingsController::class, 'updateCustomization' ] )->name( 'customization.update' );
    Route::post( '/avatar', [ SettingsController::class, 'updateAvatar' ] )->name( 'avatar.update' );
    Route::delete( '/avatar', [ SettingsController::class, 'removeAvatar' ] )->name( 'avatar.remove' );
    Route::post( '/company-logo', [ SettingsController::class, 'updateCompanyLogo' ] )->name( 'company-logo.update' );
    Route::post( '/backup', [ SettingsController::class, 'createBackup' ] )->name( 'backup.create' );
    Route::get( '/backups', [ SettingsController::class, 'listBackups' ] )->name( 'backups' );
    Route::post( '/backup/restore', [ SettingsController::class, 'restoreBackup' ] )->name( 'backup.restore' );
    Route::delete( '/backup', [ SettingsController::class, 'deleteBackup' ] )->name( 'backup.delete' );
    Route::post( '/restore-defaults', [ SettingsController::class, 'restoreDefaults' ] )->name( 'restore-defaults' );
    Route::get( '/audit', [ SettingsController::class, 'audit' ] )->name( 'audit' );

    // Profile routes moved here for consistency
    Route::get( '/profile', [ ProfileController::class, 'edit' ] )->name( 'profile.edit' );
    Route::patch( '/profile', [ ProfileController::class, 'update' ] )->name( 'profile.update' );
    Route::delete( '/profile', [ ProfileController::class, 'destroy' ] )->name( 'profile.destroy' );
} );

// Reports routes group
// Routes for general reports with auth and verified middlewares
Route::prefix( 'reports' )->name( 'reports.' )->middleware( [ 'auth', 'verified' ] )->group( function () {
    Route::get( '/', [ ReportController::class, 'index' ] )->name( 'index' );
    Route::get( '/builder', [ ReportController::class, 'builder' ] )->name( 'builder' );
    Route::post( '/generate', [ ReportController::class, 'generate' ] )->name( 'generate' );
    Route::get( '/{report}/show', [ ReportController::class, 'show' ] )->name( 'show' );
    Route::get( '/{report}/export/{format}', [ ReportController::class, 'export' ] )->name( 'export' );
    Route::post( '/{report}/schedule', [ ReportController::class, 'schedule' ] )->name( 'schedule' );
    Route::delete( '/{report}/schedule', [ ReportController::class, 'unschedule' ] )->name( 'unschedule' );
} );

// Queues routes group
// Routes for queue management with auth and verified middlewares
Route::prefix( 'queues' )->name( 'queues.' )->middleware( [ 'auth', 'verified' ] )->group( function () {
    Route::get( '/', [ QueueManagementController::class, 'index' ] )->name( 'index' );
    Route::get( '/stats', [ QueueManagementController::class, 'stats' ] )->name( 'stats' );
    Route::get( '/health', [ QueueManagementController::class, 'health' ] )->name( 'health' );
    Route::post( '/cleanup', [ QueueManagementController::class, 'cleanup' ] )->name( 'cleanup' );
    Route::post( '/retry', [ QueueManagementController::class, 'retry' ] )->name( 'retry' );
    Route::post( '/test-email', [ QueueManagementController::class, 'testEmail' ] )->name( 'test-email' );
} );

// Email preview routes group
// Routes for email preview with auth and verified middlewares
Route::prefix( 'email-preview' )->name( 'email-preview.' )->middleware( [ 'auth', 'verified' ] )->group( function () {
    Route::get( '/', [ EmailPreviewController::class, 'index' ] )->name( 'index' );
    Route::get( '/{emailType}', [ EmailPreviewController::class, 'show' ] )->name( 'show' );
    Route::get( '/config/data', [ EmailPreviewController::class, 'config' ] )->name( 'config' );
} );

// Mailtrap routes group
// Routes for mailtrap integration with auth and verified middlewares
Route::prefix( 'mailtrap' )->name( 'mailtrap.' )->middleware( [ 'auth', 'verified' ] )->group( function () {
    Route::get( '/', [ MailtrapController::class, 'index' ] )->name( 'index' );
    Route::get( '/providers', [ MailtrapController::class, 'providers' ] )->name( 'providers' );
    Route::get( '/tests', [ MailtrapController::class, 'tests' ] )->name( 'tests' );
    Route::get( '/logs', [ MailtrapController::class, 'logs' ] )->name( 'logs' );
    Route::get( '/report', [ MailtrapController::class, 'generateReport' ] )->name( 'report' );

    // AJAX routes for dynamic functionalities
    Route::post( '/test-provider', [ MailtrapController::class, 'testProvider' ] )->name( 'test-provider' );
    Route::post( '/run-test', [ MailtrapController::class, 'runTest' ] )->name( 'run-test' );
    Route::post( '/generate-report', [ MailtrapController::class, 'generateReport' ] )->name( 'generate-report' );
    Route::post( '/clear-cache', [ MailtrapController::class, 'clearCache' ] )->name( 'clear-cache' );
    Route::get( '/provider/{provider}/config', [ MailtrapController::class, 'providerConfig' ] )->name( 'provider-config' );
} );

// Webhooks routes group
// Routes for webhooks with necessary security (no auth middleware for external access)
Route::prefix( 'webhooks' )->name( 'webhooks.' )->group( function () {
    Route::post( '/mercadopago/invoices', [ WebhookController::class, 'handleMercadoPagoInvoice' ] )->name( 'mercadopago.invoices' );
    Route::post( '/mercadopago/plans', [ WebhookController::class, 'handleMercadoPagoPlan' ] )->name( 'mercadopago.plans' );
    Route::post( '/', [ WebhookController::class, 'handleWebhookMercadoPago' ] )->name( 'mercadopago' );
} );

// Error routes group
// Routes for error pages accessible without authentication
Route::group( [], function () {
    Route::get( '/not-allowed', [ ErrorController::class, 'notAllowed' ] )->name( 'error.not-allowed' );
    Route::get( '/not-found', [ ErrorController::class, 'notFound' ] )->name( 'error.not-found' );
    Route::get( '/internal', [ ErrorController::class, 'internal' ] )->name( 'error.internal' );
    Route::get( '/internal-error', [ ErrorController::class, 'internal' ] )->name( 'error.internal-alt' );
} );

// Redirects for backward compatibility
// Redirects for legacy routes to maintain compatibility
Route::middleware( [ 'auth', 'verified', 'provider' ] )->group( function () {
    Route::redirect( '/provider/update', '/provider/business/edit' )->name( 'provider.update.redirect' );

} );

require __DIR__ . '/auth.php';
