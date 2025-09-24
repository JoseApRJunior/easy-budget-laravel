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
        Schema::table( 'customers', function ( Blueprint $table ) {
            // Remover campos antigos
            $table->dropColumn( [ 'email', 'phone', 'is_active' ] );

            // Adicionar novos campos
            $table->unsignedBigInteger( 'contact_id' )->nullable();
            $table->unsignedBigInteger( 'address_id' )->nullable();
            $table->enum( 'status', [ 'active', 'inactive', 'suspended' ] )->default( 'active' );

            // Adicionar foreign keys
            $table->foreign( 'contact_id' )->references( 'id' )->on( 'contacts' )->onDelete( 'set null' );
            $table->foreign( 'address_id' )->references( 'id' )->on( 'addresses' )->onDelete( 'set null' );

            // Adicionar índices
            $table->index( 'contact_id' );
            $table->index( 'address_id' );
            $table->index( 'status' );
        } );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table( 'customers', function ( Blueprint $table ) {
            // Remover foreign keys
            $table->dropForeign( [ 'contact_id' ] );
            $table->dropForeign( [ 'address_id' ] );

            // Remover índices
            $table->dropIndex( [ 'contact_id' ] );
            $table->dropIndex( [ 'address_id' ] );
            $table->dropIndex( [ 'status' ] );

            // Remover novos campos
            $table->dropColumn( [ 'contact_id', 'address_id', 'status' ] );

            // Recriar campos antigos
            $table->string( 'email' )->unique();
            $table->string( 'phone' )->nullable();
            $table->boolean( 'is_active' )->default( true );
        } );
    }

};