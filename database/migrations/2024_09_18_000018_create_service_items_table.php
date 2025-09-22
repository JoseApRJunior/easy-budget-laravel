<?php

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
        Schema::create( 'service_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger( 'tenant_id' );
            $table->unsignedBigInteger( 'service_id' );
            $table->unsignedBigInteger( 'product_id' );
            $table->decimal( 'unit_value', 10, 2 );
            $table->integer( 'quantity' )->default( 1 );
            $table->decimal( 'total', 10, 2 )->default( 0.00 );
            $table->timestamps();

            // Chaves estrangeiras
            $table->foreign( 'tenant_id' )->references( 'id' )->on( 'tenants' )->onDelete( 'cascade' );
            $table->foreign( 'service_id' )->references( 'id' )->on( 'services' )->onDelete( 'cascade' );
            $table->foreign( 'product_id' )->references( 'id' )->on( 'products' )->onDelete( 'restrict' );

            // Índices
            $table->index( [ 'tenant_id', 'service_id' ] );
            $table->index( 'product_id' );
        } );
    }

    /**
     * Reverte as migrações.
     */
    public function down(): void
    {
        Schema::dropIfExists( 'service_items' );
    }

};
