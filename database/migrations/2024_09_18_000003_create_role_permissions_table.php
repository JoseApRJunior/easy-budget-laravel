<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migração atualizada para criação da tabela 'role_permissions' como global (sem tenant_id).
 *
 * Alterações implementadas conforme diretrizes PHP e tarefa:
 * - Removido completamente o campo 'tenant_id', sua foreign key associada e o unique constraint relacionado ['role_id', 'permission_id', 'tenant_id'].
 * - Removidos os indexes relacionados a tenant_id: ['tenant_id', 'role_id'] e ['tenant_id', 'permission_id'].
 * - Mantidos os campos 'role_id' e 'permission_id' como unsignedBigInteger.
 * - Adicionado unique(['role_id', 'permission_id']) para garantir unicidade global de associações.
 * - Mantidas as foreign keys para 'role_id' referenciando 'roles.id' e 'permission_id' referenciando 'permissions.id', ambas com onDelete('cascade').
 * - Adicionados indexes simples em 'role_id' e 'permission_id' para otimização de consultas de join.
 * - Mantida a estrutura básica: id, role_id, permission_id, timestamps.
 * - Mantido declare(strict_types=1) para tipagem rigorosa em conformidade com PSR-12.
 * - down() permanece como dropIfExists para reversibilidade simples.
 *
 * Esta migração segue Clean Architecture ao isolar a definição de schema, MVC ao preparar dados para controllers/models,
 * e utiliza Doctrine ORM compatível via Laravel Schema (não altera models ainda, conforme instrução).
 */
return new class extends Migration
{
    /**
     * Executa a migração para criar a tabela role_permissions.
     */
    public function up(): void
    {
        Schema::create( 'role_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger( 'role_id' );
            $table->unsignedBigInteger( 'permission_id' );
            $table->timestamps();

            $table->foreign( 'role_id' )->references( 'id' )->on( 'roles' )->onDelete( 'cascade' );
            $table->foreign( 'permission_id' )->references( 'id' )->on( 'permissions' )->onDelete( 'cascade' );

            $table->unique( [ 'role_id', 'permission_id' ] );
            $table->index( 'role_id' );
            $table->index( 'permission_id' );
        } );
    }

    /**
     * Reverte a migração removendo a tabela role_permissions.
     */
    public function down(): void
    {
        Schema::dropIfExists( 'role_permissions' );
    }

};
