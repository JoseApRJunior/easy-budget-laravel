<?php

namespace App\Helpers;

class FlashHelper
{
    /**
     * Flash success message
     */
    public static function success( $message )
    {
        session()->flash( 'flash.success', $message );
    }

    /**
     * Flash error message
     */
    public static function error( $message )
    {
        session()->flash( 'flash.error', $message );
    }

    /**
     * Flash warning message
     */
    public static function warning( $message )
    {
        session()->flash( 'flash.warning', $message );
    }

    /**
     * Flash info message
     */
    public static function info( $message )
    {
        session()->flash( 'flash.info', $message );
    }

    /**
     * Flash multiple messages
     */
    public static function messages( $messages )
    {
        foreach ( $messages as $type => $message ) {
            self::{$type}( $message );
        }
    }

}
