<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Public pages
Route::get( '/', [ HomeController::class, 'index' ] )->name( 'home' );
Route::get( '/about', [ HomeController::class, 'about' ] )->name( 'about' );
Route::get( '/support', [ HomeController::class, 'support' ] )->name( 'support' );
Route::get( '/terms-of-service', [ HomeController::class, 'terms' ] )->name( 'terms' );
Route::get( '/privacy-policy', [ HomeController::class, 'privacy' ] )->name( 'privacy' );

Route::get( '/dashboard', function () {
    return view( 'dashboard' );
} )->middleware( [ 'auth', 'verified' ] )->name( 'dashboard' );

Route::middleware( 'auth' )->group( function () {
    Route::get( '/profile', [ ProfileController::class, 'edit' ] )->name( 'profile.edit' );
    Route::patch( '/profile', [ ProfileController::class, 'update' ] )->name( 'profile.update' );
    Route::delete( '/profile', [ ProfileController::class, 'destroy' ] )->name( 'profile.destroy' );
} );

require __DIR__ . '/auth.php';
