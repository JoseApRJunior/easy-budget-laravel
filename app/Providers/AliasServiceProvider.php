<?php

namespace App\Providers;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;

class AliasServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register()
    {
        $loader = AliasLoader::getInstance();
        $loader->alias( 'Currency', \App\Helpers\CurrencyHelper::class);
        $loader->alias( 'DateHelper', \App\Helpers\DateHelper::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }

}
