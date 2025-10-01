<?php

use App\Http\Controllers\BudgetController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get( '/', [ HomeController::class, 'index' ] )->name( 'home' );

Route::get( '/dashboard', [ DashboardController::class, 'index' ] )
    ->middleware( [ 'auth', 'verified' ] )
    ->name( 'dashboard' );

// Páginas públicas
Route::get( '/about', function () {
    return view( 'home.about' );
} )->name( 'about' );

Route::get( '/support', function () {
    return view( 'home.support' );
} )->name( 'support' );

Route::get( '/terms-of-service', function () {
    return view( 'home.terms' );
} )->name( 'terms-of-service' );

Route::get( '/privacy-policy', function () {
    return view( 'home.privacy' );
} )->name( 'privacy-policy' );

// Rotas do Provider (área logada)
Route::middleware( 'auth' )->group( function () {
    // Dashboard principal do provider
    Route::get( '/provider', function () {
        return view( 'layouts.app' );
    } )->name( 'provider.dashboard' );

    // Módulos do provider
    Route::get( '/provider/budgets', function () {
        return view( 'layouts.app' );
    } )->name( 'provider.budgets' );

    Route::get( '/provider/services', function () {
        return view( 'layouts.app' );
    } )->name( 'provider.services' );

    Route::get( '/provider/invoices', function () {
        return view( 'layouts.app' );
    } )->name( 'provider.invoices' );

    // Customer Module - Rotas Web
    Route::prefix( 'customers' )->name( 'customers.' )->group( function () {
        Route::get( '/', [ CustomerController::class, 'index' ] )->name( 'index' );
        Route::get( '/dashboard', [ CustomerController::class, 'dashboard' ] )->name( 'dashboard' );
        Route::get( '/create/pessoa-fisica', [ CustomerController::class, 'createPessoaFisica' ] )->name( 'create.pessoa-fisica' );
        Route::get( '/create/pessoa-juridica', [ CustomerController::class, 'createPessoaJuridica' ] )->name( 'create.pessoa-juridica' );
        Route::post( '/pessoa-fisica', [ CustomerController::class, 'storePessoaFisica' ] )->name( 'store.pessoa-fisica' );
        Route::post( '/pessoa-juridica', [ CustomerController::class, 'storePessoaJuridica' ] )->name( 'store.pessoa-juridica' );
        Route::get( '/{customer}', [ CustomerController::class, 'show' ] )->name( 'show' );
        Route::get( '/{customer}/edit', [ CustomerController::class, 'edit' ] )->name( 'edit' );
        Route::put( '/{customer}', [ CustomerController::class, 'update' ] )->name( 'update' );
        Route::delete( '/{customer}', [ CustomerController::class, 'destroy' ] )->name( 'destroy' );
        Route::post( '/{customer}/restore', [ CustomerController::class, 'restore' ] )->name( 'restore' );
        Route::post( '/{customer}/duplicate', [ CustomerController::class, 'duplicate' ] )->name( 'duplicate' );

        // AJAX routes
        Route::get( '/find-nearby', [ CustomerController::class, 'findNearby' ] )->name( 'find-nearby' );
        Route::get( '/autocomplete', [ CustomerController::class, 'autocomplete' ] )->name( 'autocomplete' );
        Route::get( '/export', [ CustomerController::class, 'export' ] )->name( 'export' );
    } );

    // Budget Module - Rotas Web
    Route::prefix( 'budgets' )->name( 'budgets.' )->group( function () {
        Route::get( '/', [ BudgetController::class, 'index' ] )->name( 'index' );
        Route::get( '/create', [ BudgetController::class, 'create' ] )->name( 'create' );
        Route::post( '/', [ BudgetController::class, 'store' ] )->name( 'store' );
        Route::get( '/{budget}', [ BudgetController::class, 'show' ] )->name( 'show' );
        Route::get( '/{budget}/edit', [ BudgetController::class, 'edit' ] )->name( 'edit' );
        Route::put( '/{budget}', [ BudgetController::class, 'update' ] )->name( 'update' );
        Route::post( '/{budget}/send', [ BudgetController::class, 'send' ] )->name( 'send' );
        Route::get( '/{budget}/pdf', [ BudgetController::class, 'generatePdf' ] )->name( 'generate-pdf' );
        Route::post( '/{budget}/duplicate', [ BudgetController::class, 'duplicate' ] )->name( 'duplicate' );
        Route::get( '/{budget}/versions', [ BudgetController::class, 'versions' ] )->name( 'versions' );
        Route::post( '/{budget}/restore-version/{version}', [ BudgetController::class, 'restoreVersion' ] )->name( 'restore-version' );
    } );

    // Email Templates Module - Rotas Web
    Route::prefix( 'email-templates' )->name( 'email-templates.' )->group( function () {
        Route::get( '/', [ App\Http\Controllers\EmailTemplateController::class, 'index' ] )->name( 'index' );
        Route::get( '/create', [ App\Http\Controllers\EmailTemplateController::class, 'create' ] )->name( 'create' );
        Route::post( '/', [ App\Http\Controllers\EmailTemplateController::class, 'store' ] )->name( 'store' );
        Route::get( '/{template}', [ App\Http\Controllers\EmailTemplateController::class, 'show' ] )->name( 'show' );
        Route::get( '/{template}/edit', [ App\Http\Controllers\EmailTemplateController::class, 'edit' ] )->name( 'edit' );
        Route::put( '/{template}', [ App\Http\Controllers\EmailTemplateController::class, 'update' ] )->name( 'update' );
        Route::delete( '/{template}', [ App\Http\Controllers\EmailTemplateController::class, 'destroy' ] )->name( 'destroy' );
        Route::post( '/{template}/duplicate', [ App\Http\Controllers\EmailTemplateController::class, 'duplicate' ] )->name( 'duplicate' );
        Route::post( '/{template}/send-test', [ App\Http\Controllers\EmailTemplateController::class, 'sendTest' ] )->name( 'send-test' );
        Route::patch( '/{template}/toggle-status', [ App\Http\Controllers\EmailTemplateController::class, 'toggleStatus' ] )->name( 'toggle-status' );

        // Preview e funcionalidades AJAX
        Route::get( '/{template}/preview', [ App\Http\Controllers\EmailTemplateController::class, 'preview' ] )->name( 'preview' );
        Route::get( '/stats', [ App\Http\Controllers\EmailTemplateController::class, 'stats' ] )->name( 'stats' );
    } );

    Route::get( '/provider/products', function () {
        return view( 'layouts.app' );
    } )->name( 'provider.products' );

    Route::get( '/provider/reports', function () {
        return view( 'layouts.app' );
    } )->name( 'provider.reports' );

    // Configurações e planos
    Route::get( '/settings', [ App\Http\Controllers\SettingsController::class, 'index' ] )->name( 'settings' );
    Route::put( '/settings/general', [ App\Http\Controllers\SettingsController::class, 'updateGeneral' ] )->name( 'settings.general.update' );
    Route::put( '/settings/profile', [ App\Http\Controllers\SettingsController::class, 'updateProfile' ] )->name( 'settings.profile.update' );
    Route::put( '/settings/security', [ App\Http\Controllers\SettingsController::class, 'updateSecurity' ] )->name( 'settings.security.update' );
    Route::put( '/settings/notifications', [ App\Http\Controllers\SettingsController::class, 'updateNotifications' ] )->name( 'settings.notifications.update' );
    Route::put( '/settings/integrations', [ App\Http\Controllers\SettingsController::class, 'updateIntegrations' ] )->name( 'settings.integrations.update' );
    Route::put( '/settings/customization', [ App\Http\Controllers\SettingsController::class, 'updateCustomization' ] )->name( 'settings.customization.update' );
    Route::post( '/settings/avatar', [ App\Http\Controllers\SettingsController::class, 'updateAvatar' ] )->name( 'settings.avatar.update' );
    Route::delete( '/settings/avatar', [ App\Http\Controllers\SettingsController::class, 'removeAvatar' ] )->name( 'settings.avatar.remove' );
    Route::post( '/settings/company-logo', [ App\Http\Controllers\SettingsController::class, 'updateCompanyLogo' ] )->name( 'settings.company-logo.update' );
    Route::post( '/settings/backup', [ App\Http\Controllers\SettingsController::class, 'createBackup' ] )->name( 'settings.backup.create' );
    Route::get( '/settings/backups', [ App\Http\Controllers\SettingsController::class, 'listBackups' ] )->name( 'settings.backups' );
    Route::post( '/settings/restore', [ App\Http\Controllers\SettingsController::class, 'restoreBackup' ] )->name( 'settings.backup.restore' );
    Route::delete( '/settings/backup', [ App\Http\Controllers\SettingsController::class, 'deleteBackup' ] )->name( 'settings.backup.delete' );
    Route::post( '/settings/restore-defaults', [ App\Http\Controllers\SettingsController::class, 'restoreDefaults' ] )->name( 'settings.defaults.restore' );
    Route::get( '/settings/audit', [ App\Http\Controllers\SettingsController::class, 'audit' ] )->name( 'settings.audit' );

    Route::get( '/plans', function () {
        return view( 'layouts.app' );
    } )->name( 'plans' );

    // Integrações
    Route::get( '/provider/integrations/mercadopago', function () {
        return view( 'layouts.app' );
    } )->name( 'provider.integrations.mercadopago' );
} );

// Rotas administrativas
Route::middleware( [ 'auth' ] )->prefix( 'admin' )->name( 'admin.' )->group( function () {
    Route::get( '/', function () {
        return view( 'layouts.app' );
    } )->name( 'index' );

    Route::get( '/dashboard', function () {
        return view( 'layouts.app' );
    } )->name( 'dashboard' );

    Route::get( '/monitoring', function () {
        return view( 'layouts.app' );
    } )->name( 'monitoring' );

    Route::get( '/alerts', function () {
        return view( 'layouts.app' );
    } )->name( 'alerts' );

    Route::get( '/plans/subscriptions', function () {
        return view( 'layouts.app' );
    } )->name( 'plans.subscriptions' );

    Route::get( '/backups', function () {
        return view( 'layouts.app' );
    } )->name( 'backups' );

    Route::get( '/logs', function () {
        return view( 'layouts.app' );
    } )->name( 'logs' );

    Route::get( '/activities', function () {
        return view( 'layouts.app' );
    } )->name( 'activities' );

    Route::get( '/ai', function () {
        return view( 'layouts.app' );
    } )->name( 'ai' );

    Route::get( '/categories', function () {
        return view( 'layouts.app' );
    } )->name( 'categories' );

    Route::get( '/users', function () {
        return view( 'layouts.app' );
    } )->name( 'users' );

    Route::get( '/roles', function () {
        return view( 'layouts.app' );
    } )->name( 'roles' );

    Route::get( '/tenants', function () {
        return view( 'layouts.app' );
    } )->name( 'tenants' );

    Route::get( '/settings', function () {
        return view( 'layouts.app' );
    } )->name( 'settings' );
} );

// Rota de teste para o layout convertido
Route::get( '/test-layout', function () {
    return view( 'test-layout' );
} );

// Rota de teste simples
Route::get( '/simple-test', function () {
    return '<html><body><h1>Teste Simples</h1><p>Esta página funciona!</p></body></html>';
} );

// Rota de teste sem assets
Route::get( '/no-assets-test', function () {
    return response()->view( 'no-assets-test' );
} );

// Rota de teste para o botão de tema
Route::get( '/test-tema', function () {
    return view( 'test-tema' );
} );

// Rota de debug para Cloudflare Tunnel
Route::get( '/debug-tunnel', function () {
    return view( 'debug-tunnel' );
} );

// Rota de debug para navbar
Route::get( '/debug-navbar', function () {
    return view( 'debug-navbar' );
} );

// Rotas de teste para páginas de erro
Route::get( '/test-403', function () {
    abort( 403 );
} );

Route::get( '/test-500', function () {
    abort( 500 );
} );

// Rotas de perfil
Route::middleware( 'auth' )->group( function () {
    Route::get( '/profile', [ ProfileController::class, 'edit' ] )->name( 'profile.edit' );
    Route::patch( '/profile', [ ProfileController::class, 'update' ] )->name( 'profile.update' );
    Route::delete( '/profile', [ ProfileController::class, 'destroy' ] )->name( 'profile.destroy' );
} );

require __DIR__ . '/auth.php';
