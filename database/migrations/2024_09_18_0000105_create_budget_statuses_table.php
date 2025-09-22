<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Executa as migrações.
     */
    public function up(): void
    {
        Schema::create( 'budget_statuses', function (Blueprint $table) {
            // ID primário auto-incrementado (bigIncrements) para identificação única de cada status de orçamento global no sistema
            $table->bigIncrements( 'id' );

            // Slug único para identificação rápida e amigável do status de orçamento, indexado para buscas eficientes
            $table->string( 'slug', 50 )->unique()->index();

            // Nome descritivo do status de orçamento, limitado a 100 caracteres para exibição clara na interface do usuário
            $table->string( 'name', 100 );
            $table->string( 'code', 10 )->unique()->index();

            // Descrição detalhada do status de orçamento, permitindo valores nulos quando a descrição não é necessária
            $table->text( 'description' )->nullable();

            // Cor em formato hexadecimal ou nome para representação visual do status na interface gráfica do sistema
            $table->string( 'color', 20 );

            // Nome ou código do ícone utilizado na UI para representar visualmente o status de orçamento
            $table->string( 'icon', 50 );

            // Índice numérico para ordenar a exibição dos status de orçamento nas listagens do sistema
            $table->integer( 'order_index' )->default( 0 );

            // Flag booleana que indica se o status de orçamento está ativo e disponível para uso no sistema
            $table->boolean( 'is_active' )->default( true );

            // Campos de timestamp para registro de criação e última atualização do status de orçamento
            $table->timestamps();
        } );
    }

    /**
     * Reverte as migrações.
     */
    public function down(): void
    {
        Schema::dropIfExists( 'budget_statuses' );
    }

};
