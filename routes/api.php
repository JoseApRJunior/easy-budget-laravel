<?php

use App\Http\Controllers\Api\BudgetController as ApiBudgetController;
use App\Http\Controllers\BudgetController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware( 'auth' )->group( function () {
    // Budget filter API (legacy)
    Route::get( '/budgets/filter', [ BudgetController::class, 'filter' ] )->name( 'api.budgets.filter' );

} );
