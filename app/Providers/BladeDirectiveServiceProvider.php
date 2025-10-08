<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class BladeDirectiveServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Blade::directive( 'money', function ( $expression ) {
            return "<?php echo App\Helpers\CurrencyHelper::format($expression); ?>";
        } );

        Blade::directive( 'date', function ( $expression ) {
            return "<?php echo App\Helpers\DateHelper::format($expression); ?>";
        } );
    }

}
