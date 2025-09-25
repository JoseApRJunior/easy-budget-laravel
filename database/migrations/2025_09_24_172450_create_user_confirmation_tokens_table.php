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
        Schema::create( 'user_confirmation_tokens', function ( Blueprint $table ) {
            $table->id();
            $table->unsignedBigInteger( 'tenant_id' );
            $table->unsignedBigInteger( 'user_id' );
            $table->string( 'token', 255 )->unique();
            $table->timestamp( 'expires_at' );
            $table->timestamps();

            // Índices para performance
            $table->index( 'tenant_id' );
            $table->index( 'user_id' );
            $table->index( 'expires_at' );

            // Chaves estrangeiras
            $table->foreign( 'tenant_id' )
                ->references( 'id' )
                ->on( 'tenants' )
                ->onDelete( 'cascade' );

            $table->foreign( 'user_id' )
                ->references( 'id' )
                ->on( 'users' )
                ->onDelete( 'cascade' );
        } );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table( 'user_confirmation_tokens', function ( Blueprint $table ) {
            $table->dropForeign( [ 'tenant_id' ] );
            $table->dropForeign( [ 'user_id' ] );
            $table->dropIndex( [ 'tenant_id' ] );
            $table->dropIndex( [ 'user_id' ] );
            $table->dropIndex( [ 'expires_at' ] );
        } );

        Schema::dropIfExists( 'user_confirmation_tokens' );
    }

};