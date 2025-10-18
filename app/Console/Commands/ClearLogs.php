<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ClearLogs extends Command
{
    protected $signature   = 'logs:clear';
    protected $description = 'Limpa os arquivos de log do Laravel';

    public function handle()
    {
        $logPath = storage_path( 'logs/laravel.log' );

        if ( File::exists( $logPath ) ) {
            // Tentar limpar o arquivo atual primeiro
            try {
                File::put( $logPath, '' );
                $this->info( 'Arquivo de log atual limpo com sucesso!' );
            } catch ( \Exception $e ) {
                $this->warn( 'Não foi possível limpar o arquivo atual (pode estar em uso).' );
                $this->warn( 'Erro: ' . $e->getMessage() );
                $this->warn( 'Feche o arquivo no VSCode e tente novamente.' );
            }

            $this->info( 'Processo de limpeza concluído!' );
        } else {
            $this->error( 'Arquivo de log não encontrado.' );
        }
    }

}
