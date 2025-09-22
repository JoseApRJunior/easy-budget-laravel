<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migração atualizada para criação da tabela 'permissions' como global (sem tenant_id).
 *
 * Alterações implementadas conforme diretrizes PHP e tarefa:
 * - Removido completamente o campo 'tenant_id', sua foreign key associada e o index relacionado ['tenant_id', 'slug'].
 * - Removidos os campos 'slug' e 'description' para simplificação, conforme especificações.
 * - Adicionado o campo 'guard_name' como string com valor default 'web'.
 * - O campo 'name' agora é unique() para garantir unicidade global.
 * - Adicionado index simples em 'name' para otimização de consultas.
 * - Mantida a estrutura básica: id, name, guard_name, timestamps.
 * - Mantido declare(strict_types=1) para tipagem rigorosa em conformidade com PSR-12.
 * - down() permanece como dropIfExists para reversibilidade simples.
 *
 * Esta migração segue Clean Architecture ao isolar a definição de schema, MVC ao preparar dados para controllers/models,
 * e utiliza Doctrine ORM compatível via Laravel Schema (não altera models ainda, conforme instrução).
 */
return new class extends Migration
{
    /**
     * Executa a migração para criar a tabela permissions.
     */
    public function up(): void
    {
        Schema::create( 'permissions', function (Blueprint $table) {
            $table->id();
            $table->string( 'name' )->unique();
            $table->string( 'guard_name' )->default( 'web' );
            $table->timestamps();

            $table->index( 'name' );
        } );
    }

    /**
     * Reverte a migração removendo a tabela permissions.
     */
    public function down(): void
    {
        Schema::dropIfExists( 'permissions' );
    }

};
