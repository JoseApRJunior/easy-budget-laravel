<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class LogSizeMonitor extends Command
{
    protected $signature   = 'logs:monitor-size';
    protected $description = 'Monitora o tamanho do arquivo de log e alerta se estiver muito grande';

    public function handle()
    {
        $logPath = storage_path( 'logs/laravel.log' );

        if ( !File::exists( $logPath ) ) {
            $this->warn( 'Arquivo de log não encontrado: ' . $logPath );
            return;
        }

        $sizeInBytes = File::size( $logPath );
        $sizeInMB    = round( $sizeInBytes / 1024 / 1024, 2 );

        // Log da informação atual
        Log::info( 'Monitoramento de tamanho do arquivo de log', [
            'size_bytes'   => $sizeInBytes,
            'size_mb'      => $sizeInMB,
            'threshold_mb' => 100
        ] );

        // Verificar se o arquivo está muito grande
        if ( $sizeInMB > 100 ) {
            $this->warn( "Arquivo de log muito grande: {$sizeInMB}MB" );
            Log::warning( 'Arquivo de log excedeu 100MB', [
                'size_mb'         => $sizeInMB,
                'action_required' => 'Executar limpeza manual recomendada'
            ] );

            // Sugerir limpeza
            $this->info( 'Considere executar: php artisan logs:clear' );
        } elseif ( $sizeInMB > 50 ) {
            $this->info( "Arquivo de log: {$sizeInMB}MB (tamanho moderado)" );
        } else {
            $this->info( "Arquivo de log: {$sizeInMB}MB (tamanho normal)" );
        }

        return 0;
    }

}
