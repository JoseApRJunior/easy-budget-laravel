<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get( '/', function () {
    return view( 'layouts.app' );
} );

Route::get( '/dashboard', function () {
    return view( 'dashboard' );
} )->middleware( [ 'auth', 'verified' ] )->name( 'dashboard' );

// Páginas públicas
Route::get( '/about', function () {
    return view( 'layouts.app' );
} )->name( 'about' );

Route::get( '/support', function () {
    return view( 'layouts.app' );
} )->name( 'support' );

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

    Route::get( '/provider/customers', function () {
        return view( 'layouts.app' );
    } )->name( 'provider.customers' );

    Route::get( '/provider/products', function () {
        return view( 'layouts.app' );
    } )->name( 'provider.products' );

    Route::get( '/provider/reports', function () {
        return view( 'layouts.app' );
    } )->name( 'provider.reports' );

    // Configurações e planos
    Route::get( '/settings', function () {
        return view( 'layouts.app' );
    } )->name( 'settings' );

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

// Rotas de perfil
Route::middleware( 'auth' )->group( function () {
    Route::get( '/profile', [ ProfileController::class, 'edit' ] )->name( 'profile.edit' );
    Route::patch( '/profile', [ ProfileController::class, 'update' ] )->name( 'profile.update' );
    Route::delete( '/profile', [ ProfileController::class, 'destroy' ] )->name( 'profile.destroy' );
} );

require __DIR__ . '/auth.php';
