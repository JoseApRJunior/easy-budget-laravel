<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Compartilhar flash messages com todas as views
        View::composer( '*', function ( $view ) {
            $flash = session()->get( 'flash', [] );

            // Converter para formato padronizado
            $messages = $this->formatFlashMessages( $flash );

            $view->with( 'flashMessages', $messages );
        } );
    }

    /**
     * Formatar mensagens flash para uso no componente
     */
    private function formatFlashMessages( $flash )
    {
        $messages = [
            'success' => [],
            'error'   => [],
            'warning' => [],
            'info'    => [],
        ];

        foreach ( $flash as $type => $content ) {
            if ( isset( $messages[ $type ] ) ) {
                $message           = is_array( $content ) ? $content[ 'message' ] : $content;
                $messages[ $type ][] = [
                    'id'   => uniqid(),
                    'text' => $message,
                    'type' => $type,
                ];
            }
        }

        return $messages;
    }

}
