<?php

declare(strict_types=1);

namespace Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migration para criar a tabela de atividades de auditoria, registrando ações de usuários em recursos do tenant.
 */
return new class extends Migration
{
    /**
     * Executa as migrações.
     */
    public function up(): void
    {
        Schema::create( 'activities', function (Blueprint $table) {
            // Chave primária auto-incrementada de 64 bits para identificação única da atividade.
            $table->bigIncrements( 'id' );

            // ID do tenant (escopo multi-tenant), referenciando a tabela tenants para isolamento de dados.
            $table->unsignedBigInteger( 'tenant_id' );
            $table->index( 'tenant_id' ); // Índice para consultas rápidas por tenant.
            $table->foreign( 'tenant_id' )
                ->references( 'id' )
                ->on( 'tenants' )
                ->onDelete( 'cascade' ); // Cascade delete para remover atividades se o tenant for excluído.

            // ID do usuário que executou a ação, referenciando a tabela users.
            $table->unsignedBigInteger( 'user_id' );
            $table->index( 'user_id' ); // Índice para buscas por usuário.
            $table->foreign( 'user_id' )
                ->references( 'id' )
                ->on( 'users' );

            // Tipo de ação realizada (ex: 'create', 'update', 'delete'), limitada a 100 caracteres.
            $table->string( 'action', 100 );
            $table->index( 'action' ); // Índice para filtrar por tipo de ação.

            // Tipo de recurso afetado (ex: 'budget', 'user'), limitado a 50 caracteres.
            $table->string( 'resource_type', 50 );
            $table->index( 'resource_type' ); // Índice para consultas por tipo de recurso.

            // ID do recurso específico afetado (nullable se ação não vinculada a um recurso específico).
            $table->unsignedBigInteger( 'resource_id' )->nullable();

            // Timestamp de criação da atividade, com valor padrão CURRENT_TIMESTAMP.
            $table->timestamp( 'created_at' )->useCurrent();

            // Sem campo updated_at, pois atividades são imutáveis (apenas inseridas).
        } );
    }

    /**
     * Reverte as migrações.
     */
    public function down(): void
    {
        Schema::dropIfExists( 'activities' );
    }

};
