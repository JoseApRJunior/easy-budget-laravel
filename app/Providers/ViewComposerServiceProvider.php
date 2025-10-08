<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class ViewComposerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        View::composer( '*', function ( $view ) {
            $view->with( [
                'auth'  => auth()->check(),
                'user'  => auth()->user(),
                'flash' => session( 'flash' ),
                // Outras variáveis globais
            ] );
        } );
    }

}
