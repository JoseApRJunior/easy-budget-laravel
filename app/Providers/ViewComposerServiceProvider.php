<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewComposerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        View::composer('*', function ($view) {
            $view->with([
                'auth' => auth()->check(),
                'user' => auth()->user(),
                'flash' => session('flash'),
                // Outras variáveis globais
            ]);
        });
    }
}
