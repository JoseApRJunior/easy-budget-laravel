<?php

use app\interfaces\BaseRepositoryInterface;
use app\interfaces\BaseServiceInterface;
use core\library\AuthService;
use core\library\Sanitize;
use core\library\Twig;
use core\orm\EntityManagerFactory;
use core\services\CacheService;
use core\support\Logger;
use Doctrine\DBAL\Connection as DBALConnection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use http\Request;
use http\Router;
use Psr\Log\LoggerInterface;

use function DI\autowire;
use function DI\factory;
use function DI\get;

return [ 
        // EntityManagerInterface::class  => get( EntityManager::class),
    EntityManager::class           => factory( [ EntityManagerFactory::class, 'create' ] ),
    DBALConnection::class          => factory( fn( EntityManagerInterface $em ) => $em->getConnection() ),

    LoggerInterface::class         => autowire( Logger::class),

        // EntityManager for Doctrine ORM
    EntityManagerInterface::class  => factory( [ EntityManagerFactory::class, 'create' ] ),

        // Configuração das bibliotecas core
    AuthService::class             => autowire( AuthService::class),
    Sanitize::class                => autowire( Sanitize::class),
    Request::class                 => factory( factory: fn() => Request::create() ),

    Twig::class                    => factory( function (DBALConnection $connection) {
        $twig = new Twig( $connection );
        $twig->addFunctions();
        $twig->addFilters();
        $twig->addGlobals();
        return $twig;
    } ),
    Router::class                  => autowire( Router::class),
    CacheService::class            => autowire( CacheService::class),
    BaseRepositoryInterface::class => autowire( BaseRepositoryInterface::class),
    BaseServiceInterface::class    => autowire( BaseServiceInterface::class),
];