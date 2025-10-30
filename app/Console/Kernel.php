<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\ClearLogs::class,
        \App\Console\Commands\LogSizeMonitor::class,
        \App\Console\Commands\LimparCachesEFecharServicos::class,
    ];

    protected function schedule( Schedule $schedule )
    {
        // Limpeza automática de logs antigos (diariamente às 2:00)
        $schedule->command( 'clean:directories' )
            ->dailyAt( '02:00' )
            ->withoutOverlapping()
            ->runInBackground();

        // Monitoramento do tamanho do arquivo de log (a cada 6 horas)
        $schedule->command( 'logs:monitor-size' )
            ->everySixHours()
            ->withoutOverlapping()
            ->runInBackground();
    }

    protected function commands()
    {
        $this->load( __DIR__ . '/Commands' );

        require base_path( 'routes/console.php' );
    }

}
