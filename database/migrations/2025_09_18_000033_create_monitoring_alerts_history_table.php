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
        Schema::create( 'monitoring_alerts_history', function (Blueprint $table) {
            $table->id(); // Chave primária auto-incrementada (bigint unsigned)

            // ID do tenant associado (pode ser null para alertas globais), indexado para buscas por tenant
            $table->unsignedBigInteger( 'tenant_id' )->nullable()->index();

            // Tipo do alerta (máximo 100 caracteres, ex: 'performance', 'security')
            $table->string( 'alert_type', 100 );

            // Mensagem detalhada do alerta
            $table->text( 'message' );

            // Nível de severidade do alerta: low, medium, high, critical (padrão: low)
            $table->enum( 'severity', [ 'low', 'medium', 'high', 'critical' ] )->default( 'low' );

            // Flag indicando se o alerta foi resolvido (padrão: false)
            $table->boolean( 'resolved' )->default( false );

            // Timestamp de criação do registro
            $table->timestamps(); // created_at e updated_at com current timestamp

            // Chave estrangeira para tenant: se deletado, define como null
            $table->foreign( 'tenant_id' )->references( 'id' )->on( 'tenants' )->onDelete( 'set null' );

            // Índice no tipo de alerta para otimizar consultas por tipo
            $table->index( 'alert_type' );

            // Índice na severidade para filtrar alertas por nível de prioridade
            $table->index( 'severity' );

            // Índice no status resolvido para listar alertas pendentes/resolvidos
            $table->index( 'resolved' );
        } );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists( 'monitoring_alerts_history' );
    }

};
