<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make( Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    echo "Verificando tabelas existentes...\n";

    $tables = DB::select( 'SHOW TABLES' );
    foreach ( $tables as $table ) {
        $tableName = $table->Tables_in_easybudget;
        echo $tableName . "\n";

        // Verificar se password_reset_tokens existe
        if ( $tableName === 'password_reset_tokens' ) {
            echo "✅ Tabela password_reset_tokens encontrada!\n";

            // Verificar estrutura da tabela
            $columns = DB::select( "DESCRIBE password_reset_tokens" );
            echo "Estrutura da tabela:\n";
            foreach ( $columns as $column ) {
                $nullInfo = ( $column->Null === 'NO' ) ? 'NOT NULL' : 'NULL';
                echo "  - {$column->Field}: {$column->Type} {$nullInfo} {$column->Key}\n";
            }
        }
    }

    if ( empty( $tables ) ) {
        echo "❌ Nenhuma tabela encontrada no banco de dados!\n";
    }

    // Verificar se há tabelas específicas que sabemos que deveriam existir
    $expectedTables = [ 'users', 'password_reset_tokens', 'cache', 'sessions' ];
    echo "\nVerificando tabelas esperadas:\n";

    foreach ( $expectedTables as $expectedTable ) {
        $exists = false;
        foreach ( $tables as $table ) {
            if ( $table->Tables_in_easybudget === $expectedTable ) {
                $exists = true;
                break;
            }
        }

        if ( $exists ) {
            echo "✅ {$expectedTable}: Existe\n";
        } else {
            echo "❌ {$expectedTable}: Não existe\n";
        }
    }

} catch ( Exception $e ) {
    echo "Erro: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
}
