<?php

use core\library\Session;

if ( !file_exists( $appFunctions = APP_PATH . '/twig/functions/twig.php' ) ) {
    throw new Exception( "Please create functions inside app/twig/functions/twig.php file. It should return an array of Twig functions." );
}

$coreFunctions = [ 
    'flash'                => function (string $index, string $cssClass = 'error') {
        $flash = Session::get( '__flash' );
        if ( isset( $flash[ $index ] ) ) {
            return "<span class='{$cssClass}'>{$flash[ $index ][ 'message' ]}</span>";
        }
    },
    'url'                  => function ($url) {
        $app_url = env( 'APP_URL' );

        return "$app_url$url";
    },
    'sessionHas'           => fn( string $index ) => Session::has( $index ),
    'sessionGet'           => fn( string $index ) => Session::get( $index ),
    'session'              => fn() => Session::getFlashes(),
    'admin'                => fn() => Session::get( 'admin' ),
    'auth'                 => fn() => Session::get( 'auth' ),
    'userRoles'            => fn() => Session::get( 'userRoles' ),
    'userPermissions'      => fn() => Session::get( 'userPermissions' ),
    'checkPlan'            => fn() => Session::get( 'checkPlan' ),
    'checkPlanPending'     => fn() => Session::get( 'checkPlanPending' ),
    'dump'                 => function ($data) {
        ob_start();
        var_dump( $data );

        return ob_get_clean();
    },
    'getFilters'           => fn( $string ) => json_decode( $string, true ),
    'pathInfo'             => fn() => $_SERVER[ 'REQUEST_URI' ] ?? '/',
    'csrf_token'           => fn() => $_SESSION[ 'csrf_token' ] ?? generateCSRFToken(),
    'calculate_percentage' =>
        function ($value, $total) {
            if ( $total <= 0 ) {
                return 0;
            }

            return round( ( $value / $total ) * 100 );
        },

];

$includeAppFunctions = require $appFunctions;

if ( !is_array( $includeAppFunctions ) ) {
    throw new Exception( "Twig file must return an array" );
}

return [ ...$includeAppFunctions, ...$coreFunctions ];