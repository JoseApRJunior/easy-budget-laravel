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
    protected $description = 'Limpa todos os caches do Laravel e encerra processos PHP, Node.js e Python';

    public function handle()
    {
        $this->info( '🧹 Limpando caches do Laravel...' );

        // Limpar caches do Laravel
        $this->callSilent( 'cache:clear' );
        $this->callSilent( 'config:clear' );
        $this->callSilent( 'route:clear' );
        $this->callSilent( 'view:clear' );
        $this->callSilent( 'event:clear' );

        $this->info( '✅ Caches do Laravel limpos.' );

        // Encerrar processos
        $this->info( '🛑 Encerrando processos PHP, Node.js e Python...' );

        $comandos = [
            'taskkill /F /IM php.exe',
            'taskkill /F /IM node.exe',
            'taskkill /F /IM python.exe'
        ];

        foreach ( $comandos as $cmd ) {
            try {
                exec( $cmd );
            } catch ( \Exception $e ) {
                $this->error( "Erro ao executar: $cmd" );
            }
        }

        $this->info( '✅ Processos encerrados.' );
    }

}
