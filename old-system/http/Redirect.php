<?php

namespace http;

use core\library\Response;
use core\library\Session;

class Redirect extends Response
{
    public function __construct(string $uri)
    {
        parent::__construct(
            '',
            302,
            [ 'Location' => $uri ],
        );
    }

    public static function back()
    {
        if (Session::has('redirect')) {
            return new Redirect(Session::get('redirect')[ 'previous' ]);
        }

        return new Redirect('/');

    }

    private static function registerFirstRedirect(Route $route)
    {
        Session::set('redirect', [
            'actual' => $route->getRouteUriInstance()->getUri(),
            'previous' => '',
            'request' => $route->request,
        ]);
    }

    private static function canChangeRedirect(Route $route)
    {
        $redirect = Session::get('redirect');
        if (!$redirect) {
            return false;
        }

        return ($route->getRouteUriInstance()->getUri() !== $redirect[ 'actual' ])
            && ($route->request === $redirect[ 'request' ])
            || ($route->getRouteUriInstance()->getUri() === $redirect[ 'actual' ])
            && ($route->request !== $redirect[ 'request' ]);
    }

    private static function registerRedirect(Route $route)
    {
        if (self::canChangeRedirect($route)) {
            Session::set('redirect', [
                'actual' => $route->getRouteUriInstance()->getUri(),
                'previous' => Session::get('redirect')[ 'actual' ],
                'request' => $route->request,
            ]);
        }
    }

    public static function refresh(): void
    {
        if (Session::has('redirect')) {
            Session::remove('redirect');
        }
    }

    public static function register(Route $route)
    {
        (!isset($_SESSION[ 'redirect' ])) ?
            self::registerFirstRedirect($route) :
            self::registerRedirect($route);

        if ($route->request !== 'GET') {
            self::refresh();
        }
    }

    public function send()
    {
        // /** @phpstan-ignore-next-line */
        return header(
            'Location: ' . $this->headers[ 'Location' ],
            true,
            $this->statusCode,
        );
    }

    public static function redirect(string $to): Redirect
    {
        return new Redirect($to);
    }

    public function withMessage(string $index, string $message)
    {
        Session::flash($index, $message);

        return $this;
    }

}
