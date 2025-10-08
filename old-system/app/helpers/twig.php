<?php

use app\database\models\Resource;
use core\library\Session;
use core\library\Twig;

/** @var Twig $this */
return [ 
    'getResource' => function (string $slug) {
        if ( $slug !== null )
            if ( !Session::get( 'resource' ) ) {
                Session::set( 'resource', ( new Resource(
                    $this->getConnection(),
                ) )->findBySlug( $slug ) );
            }
        return Session::get( 'resource' );
    },
    'title'       => function () {
        return Session::get( 'title' );
    },
    'script'      => function () {
        return Session::get( 'script' );
    }

];
