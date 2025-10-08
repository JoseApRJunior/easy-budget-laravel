<?php

namespace app\support;

use core\library\Session;

class Language // TODO TERMINAR DE IMPLEMENTAR ESTA CLASE Language
{
    public static function get( $key, $default = '' )
    {
        $lang = Session::get( 'lang' );

        if ( !isset( $lang ) ) {
            $lang = 'en';
        }

        $langFile = 'lang/' . $lang . '.php';

        if ( file_exists( $langFile ) ) {
            $lang = require $langFile;

            if ( isset( $lang[ $key ] ) ) {
                return $lang[ $key ];
            }
        }

        return $default;
    }

}
