<?php

namespace app\support;

use app\core\Request;
use core\library\Session;
use Exception;

class Csrf
{
    public static function getToken()
    {
        if ( isset( $_SESSION[ 'token' ] ) ) {
            unset( $_SESSION[ 'token' ] );
        }

        $_SESSION[ 'token' ] = md5( uniqid( '', true ) );

        return "<input type='hidden' name='token' value='" . $_SESSION[ 'token' ] . "'>";
    }

    public static function validateToken()
    {
        if ( !isset( $_SESSION[ 'token' ] ) ) {
            throw new Exception( "Token inválido" );
        }

        $token = Session::get( 'token' );

        if ( empty( $token ) || $token[ 'token' ] !== $_SESSION[ 'token' ] ) {
            throw new Exception( "Token inválido" );
        }
        unset( $_SESSION[ 'token' ] );

        return true;
    }

}
