<?php

use Closure;
use core\library\Session;
use http\Redirect;
use Illuminate\Session\Store;

class SessionTimeout
{
    protected $session;
    protected $timeout = 10; // em segundos

    public function __construct( Store $session )
    {
        $this->session = $session;
    }

    public function handle( $request, Closure $next )
    {
        if ( !Session::has( 'last_activity' ) ) {
            Session::set( 'last_activity', time() );
        }

        if ( time() - $this->session->get( 'last_activity' ) > $this->timeout ) {
            Session::removeSessionAll();
            return Redirect::redirect( '/login' )->withMessage( 'message', 'Sua sessão expirou!' );
        }

        Session::set( 'last_activity', time() );
        return $next( $request );
    }

}