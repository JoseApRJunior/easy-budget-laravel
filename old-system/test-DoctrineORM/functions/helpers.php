<?php

use core\library\Session;
use http\Redirect;

/**
 * Removes the specified file from the file system.
 *
 * @param string $file The path to the file to be removed.
 */
function removeFile( string $file ): void
{
    @unlink( PUBLIC_PATH . $file );
}

/**
 * Dumps the contents of the current session to the browser.
 * This function is primarily used for debugging purposes to inspect the session data.
 */
function dumpSession(): void
{
    if ( isset( $_SESSION ) ) {
        echo "<pre>";
        var_dump( $_SESSION );
        echo "</pre>";
    }

}

/**
 * @return array<string, mixed>
 */
function getDetailedErrorInfo( Throwable $e ): array
{
    return [ 
        'type'    => get_class( $e ),
        'message' => $e->getMessage(),
        'file'    => $e->getFile(),
        'line'    => $e->getLine(),
        'trace'   => $e->getTraceAsString()
    ];
}

function handleSessionTimeout(): Redirect|null
{
    $timeout = (int) env( "SESSION_TIMEOUT" );

    // Se timeout não estiver configurado ou for inválido, não aplica timeout
    if ( $timeout <= 0 ) {
        return null;
    }

    $currentTime = time();

    if ( !Session::has( 'last_activity' ) ) {
        Session::set( 'last_activity', $currentTime );

        return null;
    }

    $lastActivity = (int) ( Session::get( 'last_activity' ) ?? 0 );

    if ( ( $currentTime - $lastActivity ) > $timeout ) {
        Session::removeAll();

        return Redirect::redirect( '/login' );
    }

    Session::set( 'last_activity', $currentTime );

    return null;
}

/**
 * Handles the last update session for the given key.
 *
 * @param string $key The key to use for the session. Defaults to 'last_updated_session_key'.
 * @return bool True if the session was updated, false otherwise.
 */
function handleLastUpdateSession( string $key ): bool
{
    $key                  = "last_updated_session_$key";
    $last_updated_session = (int) ( Session::get( $key ) ?? 0 );

    $max_time_updated_session = (int) env( 'MAX_TIME_UPDATED_SESSION' );

    if ( !Session::has( $key ) ) {
        Session::set( $key, time() );

        return true;
    }

    if ( ( time() - $last_updated_session ) > $max_time_updated_session ) {
        Session::set( $key, time() );

        return true;
    }

    return false;
}

function validateCSRFToken( string $token ): bool
{
    if ( empty( $_SESSION[ 'csrf_token' ] ) || !hash_equals( $_SESSION[ 'csrf_token' ], $token ) ) {
        return false;
    }

    return true;
}

/**
 * Verifica se o conteúdo tem formato válido de um CSRF token
 */
function isValidTokenFormat( string $token ): bool
{
    return is_string( $token ) &&
        strlen( $token ) === 64 &&
        ctype_xdigit( $token );
}
