<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration para criação da tabela global plans.
 * Tabela de lookup global, sem tenant_id, para armazenar planos de assinatura.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create( 'plans', function (Blueprint $table) {
            // Chave primária auto-incrementada de 64 bits
            $table->bigIncrements( 'id' );

            // Nome do plano, limitado a 100 caracteres
            $table->string( 'name', 100 );

            // Slug único para o plano, para URLs amigáveis, limitado a 50 caracteres
            $table->string( 'slug', 50 )->unique();

            // Recursos do plano em formato JSON, pode ser nulo
            $table->json( 'features' )->nullable();

            // Preço do plano, decimal com 10 dígitos totais e 2 decimais
            $table->decimal( 'price', 10, 2 );

            // Duração do plano em meses, inteiro com padrão 12
            $table->integer( 'duration' )->default( 12 );

            // Flag indicando se o plano está ativo, padrão true
            $table->boolean( 'is_active' )->default( true );

            // Timestamps para criação e atualização
            $table->timestamps();

            // Índice no slug para buscas únicas eficientes
            $table->index( 'slug' );
        } );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists( 'plans' );
    }

};
