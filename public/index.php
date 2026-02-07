<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Force load env for testing
if (file_exists(__DIR__.'/../.env')) {
    try {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__.'/../');
        $dotenv->load();
    } catch (\Throwable $e) {
        // ignore
    }
}

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

// Ajuste para garantir compatibilidade entre ambientes (Local e Locaweb)
// Isso força o Laravel a usar o diretório atual como path.public, independente se é public/ ou public_html/
if (method_exists($app, 'usePublicPath')) {
    $app->usePublicPath(__DIR__);
}

$app->handleRequest(Request::capture());
