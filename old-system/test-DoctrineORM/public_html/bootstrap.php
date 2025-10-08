<?php

declare(strict_types=1);

require BASE_PATH . '/vendor/autoload.php';

// Iniciar a sessão
session_start();

$container = new \http\Container;
$container = $container->build( [ 'services' ] );

// 1. Carregar .env base
$dotenv = \Dotenv\Dotenv::createImmutable( BASE_PATH );
$dotenv->load();

// 2. Determinar qual arquivo de ambiente carregar
$appEnv = $_ENV[ 'APP_ENV' ] ?? 'development';

$envFiles = [ ".env.production", '.env.local', '.env.staging' ];

foreach ( $envFiles as $envFile ) {
    if ( file_exists( BASE_PATH . '/' . $envFile ) ) {
        $envDotenv = \Dotenv\Dotenv::createMutable( BASE_PATH, $envFile );
        $envDotenv->load();
        break;
    }
}

\http\Application::resolve( $container );
