<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create( 'middleware_metrics_history', function (Blueprint $table) {
            $table->id(); // Chave primária auto-incrementada (bigint unsigned)

            // ID do tenant associado (pode ser null para registros globais), indexado para buscas rápidas por tenant
            $table->unsignedBigInteger( 'tenant_id' )->nullable()->index();

            // Nome do middleware executado (máximo 100 caracteres)
            $table->string( 'middleware', 100 );

            // Tempo de execução do middleware em segundos (precisão de 4 casas decimais, até 8 dígitos totais)
            $table->decimal( 'execution_time', 8, 4 );

            // Uso de memória em bytes durante a execução do middleware
            $table->bigInteger( 'memory_usage' );

            // Dados da requisição em formato JSON (opcional, para logging detalhado)
            $table->json( 'request_data' )->nullable();

            // Timestamp de criação do registro (padrão: current timestamp)
            $table->timestamp( 'created_at' )->useCurrent();

            // Chave estrangeira para tenant: se o tenant for deletado, define como null (cascade set null)
            $table->foreign( 'tenant_id' )->references( 'id' )->on( 'tenants' )->onDelete( 'set null' );

            // Índice no campo middleware para otimizar consultas por tipo de middleware
            $table->index( 'middleware' );
        } );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists( 'middleware_metrics_history' );
    }

};
