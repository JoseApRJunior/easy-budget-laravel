<?php

/**
 * Helper functions for Easy Budget Laravel
 */

/**
 * Check if user is authenticated
 */
if ( !function_exists( 'user_auth' ) ) {
    function user_auth(): ?array
    {
        // Check if user is authenticated via session
        if ( session()->has( 'auth' ) ) {
            return session( 'auth' );
        }

        // Check if user is authenticated via Laravel's auth system
        $authManager = app( 'auth' );
        if ( $authManager->check() ) {
            $user = $authManager->user();
            return [
                'id'       => $user->id,
                'name'     => $user->name,
                'email'    => $user->email,
                'role'     => $user->role ?? 'user',
                'is_admin' => $user->role === 'admin' || $user->role === 'super_admin'
            ];
        }

        return null;
    }
}

/**
 * Check if current user is admin
 */
if ( !function_exists( 'admin' ) ) {
    function admin(): bool
    {
        $user = user_auth();
        return $user && isset( $user[ 'is_admin' ] ) && $user[ 'is_admin' ] === true;
    }
}

/**
 * Check if user is authenticated
 */
if ( !function_exists( 'is_authenticated' ) ) {
    function is_authenticated(): bool
    {
        return user_auth() !== null;
    }
}

/**
 * Get current user
 */
if ( !function_exists( 'current_user' ) ) {
    function current_user(): ?array
    {
        return user_auth();
    }
}

if ( !function_exists( 'money' ) ) {
    function money( $value, $decimals = 2 )
    {
        return app( App\Helpers\CurrencyHelper::class)->format( $value, $decimals );
    }
}

if ( !function_exists( 'format_date' ) ) {
    function format_date( $date, $format = 'd/m/Y' )
    {
        return app( App\Helpers\DateHelper::class)->format( $date, $format );
    }
}
