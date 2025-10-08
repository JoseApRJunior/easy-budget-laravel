<?php

namespace core\dbal;

use Doctrine\DBAL\DriverManager;

class Connection
{
    public static function create()
    {
        return DriverManager::getConnection([
            'host' => env('DB_HOST'),
            'dbname' => env('DB_NAME'),
            'user' => env('DB_USER'),
            'password' => env('DB_PASSWORD'),
            'charset' => 'utf8mb4',
            'driver' => env('DB_DRIVER'),
        ]);
    }

}
