<?php

use App\Http\Controllers\PlanController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BudgetController;
use Illuminate\Support\Facades\Route;

// Rotas públicas
Route::get( '/', function () {
    return view( 'welcome' );
} );

// Rotas autenticadas
Route::middleware( 'auth' )->group( function () {
    // Dashboard
    Route::get( '/dashboard', function () {
        return view( 'dashboard' );
    } )->name( 'dashboard' );

    // Recursos principais
    Route::resource( 'plans', PlanController::class);
    Route::resource( 'users', UserController::class);
    Route::resource( 'budgets', BudgetController::class);

    // Rotas específicas para Plans
    Route::patch( 'plans/{plan}/activate', [ PlanController::class, 'activate' ] )->name( 'plans.activate' );
    Route::patch( 'plans/{plan}/deactivate', [ PlanController::class, 'deactivate' ] )->name( 'plans.deactivate' );

    // Rotas específicas para Users
    Route::patch( 'users/{user}/activate', [ UserController::class, 'activate' ] )->name( 'users.activate' );
    Route::get( 'users/{user}/confirm', [ UserController::class, 'confirmAccount' ] )->name( 'users.confirm' );

    // Rotas específicas para Budgets
    Route::patch( 'budgets/{budget}/status', [ BudgetController::class, 'updateStatus' ] )->name( 'budgets.update-status' );
    Route::post( 'budgets/{budget}/duplicate', [ BudgetController::class, 'duplicate' ] )->name( 'budgets.duplicate' );
    Route::get( 'budgets/{budget}/print', [ BudgetController::class, 'print' ] )->name( 'budgets.print' );
} );

// Rotas de autenticação
require __DIR__ . '/auth.php';
