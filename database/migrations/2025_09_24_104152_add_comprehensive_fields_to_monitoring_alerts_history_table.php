<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adiciona todos os campos faltantes na tabela monitoring_alerts_history
     * conforme identificado no relatório de análise do modelo.
     */
    public function up(): void
    {
        Schema::table( 'monitoring_alerts_history', function ( Blueprint $table ) {
            // Verificar e adicionar campos que estão no modelo mas não na tabela

            // Título do alerta (VARCHAR 255)
            if ( !Schema::hasColumn( 'monitoring_alerts_history', 'title' ) ) {
                $table->string( 'title', 255 )->after( 'severity' );
            }

            // Descrição detalhada do alerta (TEXT)
            if ( !Schema::hasColumn( 'monitoring_alerts_history', 'description' ) ) {
                $table->text( 'description' )->after( 'title' );
            }

            // Componente do sistema que gerou o alerta (VARCHAR 100)
            if ( !Schema::hasColumn( 'monitoring_alerts_history', 'component' ) ) {
                $table->string( 'component', 100 )->after( 'description' );
            }

            // Endpoint da requisição que gerou o alerta (VARCHAR 255 nullable)
            if ( !Schema::hasColumn( 'monitoring_alerts_history', 'endpoint' ) ) {
                $table->string( 'endpoint', 255 )->nullable()->after( 'component' );
            }

            // Método HTTP da requisição (VARCHAR 10 nullable)
            if ( !Schema::hasColumn( 'monitoring_alerts_history', 'method' ) ) {
                $table->string( 'method', 10 )->nullable()->after( 'endpoint' );
            }

            // Valor atual da métrica (DECIMAL 15,3 nullable)
            if ( !Schema::hasColumn( 'monitoring_alerts_history', 'current_value' ) ) {
                $table->decimal( 'current_value', 15, 3 )->nullable()->after( 'method' );
            }

            // Valor limite da métrica (DECIMAL 15,3 nullable)
            if ( !Schema::hasColumn( 'monitoring_alerts_history', 'threshold_value' ) ) {
                $table->decimal( 'threshold_value', 15, 3 )->nullable()->after( 'current_value' );
            }

            // Unidade de medida da métrica (VARCHAR 20 nullable)
            if ( !Schema::hasColumn( 'monitoring_alerts_history', 'unit' ) ) {
                $table->string( 'unit', 20 )->nullable()->after( 'threshold_value' );
            }

            // Metadados adicionais em formato JSON (JSON nullable)
            if ( !Schema::hasColumn( 'monitoring_alerts_history', 'metadata' ) ) {
                $table->json( 'metadata' )->nullable()->after( 'unit' );
            }

            // Status do alerta (VARCHAR 20 default 'active')
            if ( !Schema::hasColumn( 'monitoring_alerts_history', 'status' ) ) {
                $table->string( 'status', 20 )->default( 'active' )->after( 'metadata' );
            }

            // Usuário que reconheceu o alerta (BIGINT UNSIGNED nullable FK)
            if ( !Schema::hasColumn( 'monitoring_alerts_history', 'acknowledged_by' ) ) {
                $table->unsignedBigInteger( 'acknowledged_by' )->nullable()->after( 'status' );
            }

            // Data/hora em que o alerta foi reconhecido (TIMESTAMP nullable)
            if ( !Schema::hasColumn( 'monitoring_alerts_history', 'acknowledged_at' ) ) {
                $table->timestamp( 'acknowledged_at' )->nullable()->after( 'acknowledged_by' );
            }

            // Usuário que resolveu o alerta (BIGINT UNSIGNED nullable FK)
            if ( !Schema::hasColumn( 'monitoring_alerts_history', 'resolved_by' ) ) {
                $table->unsignedBigInteger( 'resolved_by' )->nullable()->after( 'acknowledged_at' );
            }

            // Data/hora em que o alerta foi resolvido (TIMESTAMP nullable)
            if ( !Schema::hasColumn( 'monitoring_alerts_history', 'resolved_at' ) ) {
                $table->timestamp( 'resolved_at' )->nullable()->after( 'resolved_by' );
            }

            // Notas de resolução do alerta (TEXT nullable)
            if ( !Schema::hasColumn( 'monitoring_alerts_history', 'resolution_notes' ) ) {
                $table->text( 'resolution_notes' )->nullable()->after( 'resolved_at' );
            }

            // Contador de ocorrências do mesmo tipo de alerta (INTEGER default 1)
            if ( !Schema::hasColumn( 'monitoring_alerts_history', 'occurrence_count' ) ) {
                $table->integer( 'occurrence_count' )->default( 1 )->after( 'resolution_notes' );
            }

            // Data da primeira ocorrência deste tipo de alerta (TIMESTAMP nullable)
            if ( !Schema::hasColumn( 'monitoring_alerts_history', 'first_occurrence' ) ) {
                $table->timestamp( 'first_occurrence' )->nullable()->after( 'occurrence_count' );
            }

            // Data da última ocorrência deste tipo de alerta (TIMESTAMP nullable)
            if ( !Schema::hasColumn( 'monitoring_alerts_history', 'last_occurrence' ) ) {
                $table->timestamp( 'last_occurrence' )->nullable()->after( 'first_occurrence' );
            }

            // Adicionar foreign keys para campos de usuário
            if ( !Schema::hasColumn( 'monitoring_alerts_history', 'acknowledged_by' ) ) {
                $table->foreign( 'acknowledged_by' )
                    ->references( 'id' )
                    ->on( 'users' )
                    ->onDelete( 'set null' );
            }

            if ( !Schema::hasColumn( 'monitoring_alerts_history', 'resolved_by' ) ) {
                $table->foreign( 'resolved_by' )
                    ->references( 'id' )
                    ->on( 'users' )
                    ->onDelete( 'set null' );
            }

            // Adicionar índices otimizados para consultas comuns
            $indexes = [
                'status',
                'component',
                'occurrence_count',
                [ 'tenant_id', 'status' ],
                [ 'alert_type', 'severity' ],
                [ 'created_at', 'status' ],
                [ 'component', 'status' ],
                [ 'acknowledged_by' ],
                [ 'resolved_by' ],
                [ 'first_occurrence' ],
                [ 'last_occurrence' ],
            ];

            foreach ( $indexes as $index ) {
                if ( is_array( $index ) ) {
                    $indexName = implode( '_', $index ) . '_index';
                    // Remover índice existente se houver
                    if ( $this->indexExists( $table, $indexName ) ) {
                        Schema::table( 'monitoring_alerts_history', function ( $t ) use ( $indexName ) {
                            $t->dropIndex( $indexName );
                        } );
                    }
                    $table->index( $index, $indexName );
                } else {
                    // Remover índice existente se houver
                    if ( $this->indexExists( $table, $index ) ) {
                        Schema::table( 'monitoring_alerts_history', function ( $t ) use ( $index ) {
                            $t->dropIndex( $index );
                        } );
                    }
                    $table->index( $index );
                }
            }
        } );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table( 'monitoring_alerts_history', function ( Blueprint $table ) {
            // Remover foreign keys
            $foreignKeys = [ 'acknowledged_by', 'resolved_by' ];
            foreach ( $foreignKeys as $fk ) {
                if ( Schema::hasColumn( 'monitoring_alerts_history', $fk ) ) {
                    $table->dropForeign( [ $fk ] );
                }
            }

            // Remover índices
            $indexes = [
                'status',
                'component',
                'occurrence_count',
                'tenant_id_status_index',
                'alert_type_severity_index',
                'created_at_status_index',
                'component_status_index',
                'acknowledged_by_index',
                'resolved_by_index',
                'first_occurrence_index',
                'last_occurrence_index',
            ];

            foreach ( $indexes as $index ) {
                $table->dropIndexIfExists( $index );
            }

            // Remover campos adicionados
            $columns = [
                'title',
                'description',
                'component',
                'endpoint',
                'method',
                'current_value',
                'threshold_value',
                'unit',
                'metadata',
                'status',
                'acknowledged_by',
                'acknowledged_at',
                'resolved_by',
                'resolved_at',
                'resolution_notes',
                'occurrence_count',
                'first_occurrence',
                'last_occurrence'
            ];

            foreach ( $columns as $column ) {
                if ( Schema::hasColumn( 'monitoring_alerts_history', $column ) ) {
                    $table->dropColumn( $column );
                }
            }
        } );
    }

    /**
     * Verifica se um índice já existe na tabela.
     */
    private function indexExists( Blueprint $table, string $indexName ): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();
        
        try {
            if ($driver === 'sqlite') {
                // Para SQLite, usar pragma index_info
                $result = $connection->select("PRAGMA index_info({$indexName})");
                return !empty($result);
            } else {
                // Para MySQL/MariaDB, usar information_schema
                $databaseName = $connection->getDatabaseName();
                $tableName = 'monitoring_alerts_history';
                
                $indexExists = $connection->select("
                    SELECT COUNT(*) as count
                    FROM information_schema.statistics
                    WHERE table_schema = '{$databaseName}'
                    AND table_name = '{$tableName}'
                    AND index_name = '{$indexName}'
                ");
                
                return $indexExists[0]->count > 0;
            }
        } catch (\Exception $e) {
            // Se houver erro, assumir que o índice não existe
            return false;
        }
    }

};