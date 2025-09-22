<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration para criação da tabela global professions.
 * Tabela de lookup global, sem tenant_id, para armazenar profissões.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create( 'professions', function (Blueprint $table) {
            // Chave primária auto-incrementada de 64 bits
            $table->bigIncrements( 'id' );

            // Nome da profissão, limitado a 100 caracteres
            $table->string( 'name', 100 );

            // Slug único para a profissão, para URLs amigáveis, limitado a 100 caracteres
            $table->string( 'slug', 100 )->unique();

            // Descrição detalhada da profissão, pode ser nula
            $table->text( 'description' )->nullable();

            // Flag indicando se a profissão está ativa, padrão true
            $table->boolean( 'is_active' )->default( true );

            // Timestamps para criação e atualização
            $table->timestamps();

            // Índice no nome para buscas eficientes
            $table->index( 'name' );
        } );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists( 'professions' );
    }

};
