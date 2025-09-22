<?php

use App\Http\Controllers\AdminMetricsController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\CepController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ServiceController;
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

    // Budget filter API
    Route::get( '/budgets/filter', [ BudgetController::class, 'filter' ] )->name( 'api.budgets.filter' );

    // Invoice filter API
    Route::get( '/invoices/filter', [ InvoiceController::class, 'filter' ] )->name( 'api.invoices.filter' );

    // CEP lookup API
    Route::get( '/cep/{cep}', [ CepController::class, 'lookup' ] )->name( 'api.cep.lookup' );

    // Admin metrics API
    Route::middleware( 'admin' )->get( '/admin/metrics', [ AdminMetricsController::class, 'getMetrics' ] )->name( 'api.admin.metrics' );
} );

// Webhook routes (no auth for external webhooks)
Route::post( '/webhooks/mercado-pago', [ WebhookController::class, 'handleMercadoPago' ] )->name( 'api.webhook.mercadopago' );