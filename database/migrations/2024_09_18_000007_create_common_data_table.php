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
        Schema::create( 'common_data', function (Blueprint $table) {
            $table->bigIncrements( 'id' ); // ID primário auto-incrementado

            $table->unsignedBigInteger( 'tenant_id' ); // ID do tenant, para multi-tenancy
            $table->index( 'tenant_id' ); // Índice para consultas rápidas por tenant
            $table->foreign( 'tenant_id' ) // Chave estrangeira para tenants.id
                ->references( 'id' )
                ->on( 'tenants' )
                ->onDelete( 'cascade' ); // Deleta dados comuns se tenant for deletado

            $table->string( 'key', 100 ); // Chave identificadora, limite de 100 caracteres
            $table->index( 'key' ); // Índice para buscas por chave

            $table->text( 'value' )->nullable(); // Valor associado à chave, opcional

            $table->text( 'description' )->nullable(); // Descrição adicional, opcional (extra do schema legacy)

            // Constraint único composto: key único por tenant
            $table->unique( [ 'tenant_id', 'key' ] );

            $table->timestamps(); // created_at e updated_at automáticos
        } );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists( 'common_data' );
    }

};
