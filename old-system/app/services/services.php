<?php

use core\dbal\Connection;
use core\library\Twig;
use core\services\CacheService;

use function DI\autowire;

use Doctrine\DBAL\Connection as DBALConnection;
use http\Router;

return [
    DBALConnection::class => Connection::create(),
    Twig::class => function () {
        $twig = new Twig(Connection::create());
        $twig->addFunctions();
        $twig->addFilters();
        $twig->addGlobals();

        return $twig;
    },
    Router::class => autowire(Router::class),
    CacheService::class => autowire(CacheService::class),
];
