<?php

use App\Http\Controllers\AdminMetricsController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\CepController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\Api\BudgetController as ApiBudgetController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware( 'auth' )->group( function () {
    // Customer search API
    Route::get( '/customers/search', [ CustomerController::class, 'search' ] )->name( 'api.customers.search' );

    // Product search API
    Route::get( '/products/search', [ ProductController::class, 'search' ] )->name( 'api.products.search' );

    // Service filter API
    Route::get( '/services/filter', [ ServiceController::class, 'filter' ] )->name( 'api.services.filter' );

    // Budget filter API (legacy)
    Route::get( '/budgets/filter', [ BudgetController::class, 'filter' ] )->name( 'api.budgets.filter' );

    // Invoice filter API
    Route::get( '/invoices/filter', [ InvoiceController::class, 'filter' ] )->name( 'api.invoices.filter' );

    // CEP lookup API
    Route::get( '/cep/{cep}', [ CepController::class, 'lookup' ] )->name( 'api.cep.lookup' );

    // Admin metrics API
    Route::middleware( 'admin' )->get( '/admin/metrics', [ AdminMetricsController::class, 'getMetrics' ] )->name( 'api.admin.metrics' );

    // Budget API Routes (Nova API RESTful)
    Route::prefix('v1/budgets')->name('api.v1.budgets.')->group(function () {
        // CRUD básico
        Route::get('/', [ApiBudgetController::class, 'index'])->name('index');
        Route::post('/', [ApiBudgetController::class, 'store'])->name('store');
        Route::get('/{code}', [ApiBudgetController::class, 'show'])->name('show');
        Route::put('/{code}', [ApiBudgetController::class, 'update'])->name('update');
        Route::delete('/{code}', [ApiBudgetController::class, 'destroy'])->name('destroy');
        
        // Ações específicas
        Route::patch('/{code}/status', [ApiBudgetController::class, 'changeStatus'])->name('change-status');
        Route::post('/{code}/duplicate', [ApiBudgetController::class, 'duplicate'])->name('duplicate');
        Route::get('/{code}/pdf', [ApiBudgetController::class, 'generatePdf'])->name('generate-pdf');
        
        // Operações em lote
        Route::patch('/bulk/status', [ApiBudgetController::class, 'bulkUpdateStatus'])->name('bulk-update-status');
        
        // Relatórios e estatísticas
        Route::get('/reports/generate', [ApiBudgetController::class, 'report'])->name('report');
        Route::get('/statistics/conversion', [ApiBudgetController::class, 'stats'])->name('stats');
        Route::get('/alerts/near-expiration', [ApiBudgetController::class, 'nearExpiration'])->name('near-expiration');
    });
} );

// Webhook routes (no auth for external webhooks)
Route::post( '/webhooks/mercado-pago', [ WebhookController::class, 'handleMercadoPago' ] )->name( 'api.webhook.mercadopago' );