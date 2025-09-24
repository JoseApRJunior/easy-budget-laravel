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
        Schema::table( 'providers', function ( Blueprint $table ) {
            // Remover campos antigos
            $table->dropColumn( [ 'name', 'email', 'phone', 'address', 'cnpj', 'is_active' ] );

            // Remover constraint único antigo
            $table->dropUnique( [ 'tenant_id', 'email' ] );

            // Adicionar novos campos
            $table->unsignedBigInteger( 'user_id' )->nullable();
            $table->unsignedBigInteger( 'common_data_id' )->nullable();
            $table->unsignedBigInteger( 'contact_id' )->nullable();
            $table->unsignedBigInteger( 'address_id' )->nullable();
            $table->boolean( 'terms_accepted' )->default( false );

            // Adicionar foreign keys
            $table->foreign( 'user_id' )->references( 'id' )->on( 'users' )->onDelete( 'set null' );
            $table->foreign( 'common_data_id' )->references( 'id' )->on( 'common_data' )->onDelete( 'restrict' );
            $table->foreign( 'contact_id' )->references( 'id' )->on( 'contacts' )->onDelete( 'set null' );
            $table->foreign( 'address_id' )->references( 'id' )->on( 'addresses' )->onDelete( 'set null' );

            // Adicionar constraint único composto
            $table->unique( [ 'tenant_id', 'user_id' ] );

            // Adicionar índices
            $table->index( 'user_id' );
            $table->index( 'common_data_id' );
            $table->index( 'contact_id' );
            $table->index( 'address_id' );
            $table->index( 'terms_accepted' );
        } );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table( 'providers', function ( Blueprint $table ) {
            // Remover constraint único novo
            $table->dropUnique( [ 'tenant_id', 'user_id' ] );

            // Remover foreign keys
            $table->dropForeign( [ 'user_id' ] );
            $table->dropForeign( [ 'common_data_id' ] );
            $table->dropForeign( [ 'contact_id' ] );
            $table->dropForeign( [ 'address_id' ] );

            // Remover índices
            $table->dropIndex( [ 'user_id' ] );
            $table->dropIndex( [ 'common_data_id' ] );
            $table->dropIndex( [ 'contact_id' ] );
            $table->dropIndex( [ 'address_id' ] );
            $table->dropIndex( [ 'terms_accepted' ] );

            // Remover novos campos
            $table->dropColumn( [ 'user_id', 'common_data_id', 'contact_id', 'address_id', 'terms_accepted' ] );

            // Recriar campos antigos
            $table->string( 'name', 100 );
            $table->string( 'email', 100 );
            $table->string( 'phone', 20 )->nullable();
            $table->text( 'address' )->nullable();
            $table->string( 'cnpj', 20 )->nullable();
            $table->boolean( 'is_active' )->default( true );

            // Recriar constraint único antigo
            $table->unique( [ 'tenant_id', 'email' ] );

            // Recriar índice para name
            $table->index( 'name' );
        } );
    }

};