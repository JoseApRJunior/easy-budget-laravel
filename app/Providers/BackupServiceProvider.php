<?php

namespace App\Providers;

use App\Helpers\BackupHelper;
use Illuminate\Support\ServiceProvider;

class BackupServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton( BackupHelper::class, function ( $app ) {
            return new BackupHelper();
        } );
    }

    public function boot(): void
    {
        //
    }

}
