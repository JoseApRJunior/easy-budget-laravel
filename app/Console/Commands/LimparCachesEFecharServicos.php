<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class LimparCachesEFecharServicos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'limpar:geral';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpa todos os caches do Laravel, logs e encerra processos PHP, Node.js e Python';

    public function handle()
    {
        $this->info( '🧹 Limpando caches do Laravel...' );

        // Limpar caches do Laravel
        $this->callSilent( 'cache:clear' );
        $this->callSilent( 'config:clear' );
        $this->callSilent( 'route:clear' );
        $this->callSilent( 'view:clear' );
        $this->callSilent( 'event:clear' );
        $this->callSilent( 'logs:clear' );

        $this->info( '✅ Caches do Laravel limpos.' );

        // Encerrar processos
        $this->info( '🛑 Encerrando processos PHP, Node.js e Python...' );

        $comandos = [
            'taskkill /F /IM php.exe',
            'taskkill /F /IM node.exe',
            'taskkill /F /IM python.exe',
            'taskkill /F /IM cmd.exe',
            'taskkill /F /IM powershell.exe',
            'taskkill /F /IM pwsh.exe',
            'taskkill /F /IM bash.exe',
            'taskkill /F /IM git.exe',
            'taskkill /F /IM notepad++.exe',
            'taskkill /F /IM msedge.exe',
        ];

        if ( strtoupper( substr( PHP_OS, 0, 3 ) ) !== 'WIN' ) {
            $this->warn( '⚠️ Este comando só é compatível com Windows.' );

            return;
        }

        foreach ( $comandos as $cmd ) {
            $output = null;
            $result = null;
            exec( $cmd, $output, $result );

            if ( $result === 0 ) {
                $this->line( "✅ Encerrado: $cmd" );
            } else {
                $this->warn( "⚠️ Falha ao encerrar: $cmd" );
            }
        }

        $this->info( '✅ Processos encerrados.' );
    }

}
