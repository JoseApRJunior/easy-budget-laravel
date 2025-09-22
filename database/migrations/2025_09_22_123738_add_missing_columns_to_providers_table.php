<?php

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
        Schema::table( 'providers', function (Blueprint $table) {
            // Adiciona colunas que estão no modelo Provider mas não na migração original
            $table->unsignedBigInteger( 'user_id' )->nullable()->after( 'tenant_id' );
            $table->unsignedBigInteger( 'common_data_id' )->nullable()->after( 'user_id' );
            $table->unsignedBigInteger( 'contact_id' )->nullable()->after( 'common_data_id' );
            $table->unsignedBigInteger( 'address_id' )->nullable()->after( 'contact_id' );
            $table->boolean( 'terms_accepted' )->default( false )->after( 'address_id' );

            // Adiciona chaves estrangeiras
            $table->foreign( 'user_id' )->references( 'id' )->on( 'users' )->onDelete( 'set null' );
            $table->foreign( 'common_data_id' )->references( 'id' )->on( 'common_data' )->onDelete( 'set null' );
            $table->foreign( 'contact_id' )->references( 'id' )->on( 'contacts' )->onDelete( 'set null' );
            $table->foreign( 'address_id' )->references( 'id' )->on( 'addresses' )->onDelete( 'set null' );
        } );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table( 'providers', function (Blueprint $table) {
            // Remove chaves estrangeiras
            $table->dropForeign( [ 'user_id' ] );
            $table->dropForeign( [ 'common_data_id' ] );
            $table->dropForeign( [ 'contact_id' ] );
            $table->dropForeign( [ 'address_id' ] );

            // Remove colunas
            $table->dropColumn( [ 'user_id', 'common_data_id', 'contact_id', 'address_id', 'terms_accepted' ] );
        } );
    }

};
