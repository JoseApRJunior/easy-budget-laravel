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
        Schema::create( 'providers', function (Blueprint $table) {
            $table->bigIncrements( 'id' ); // ID primário auto-incrementado

            $table->unsignedBigInteger( 'tenant_id' ); // ID do tenant, para multi-tenancy
            $table->index( 'tenant_id' ); // Índice para consultas rápidas por tenant
            $table->foreign( 'tenant_id' ) // Chave estrangeira para tenants.id
                ->references( 'id' )
                ->on( 'tenants' )
                ->onDelete( 'cascade' ); // Deleta provedores se tenant for deletado

            $table->string( 'name', 100 ); // Nome do provedor, limite de 100 caracteres
            $table->index( 'name' ); // Índice para buscas por nome

            $table->string( 'email', 100 ); // Email do provedor, limite de 100 caracteres
            $table->string( 'phone', 20 )->nullable(); // Telefone, opcional, limite de 20 caracteres
            $table->text( 'address' )->nullable(); // Endereço completo, opcional

            $table->string( 'cnpj', 20 )->nullable(); // CNPJ do provedor, opcional, limite de 20 caracteres (extra do schema legacy)

            $table->boolean( 'is_active' )->default( true ); // Status ativo/inativo, padrão true

            // Constraint único composto: email único por tenant
            $table->unique( [ 'tenant_id', 'email' ] );

            $table->timestamps(); // created_at e updated_at automáticos
        } );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists( 'providers' );
    }

};
