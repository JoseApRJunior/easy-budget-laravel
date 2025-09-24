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
            $table->index( 'tenant_id', 'idx_user_confirmation_tokens_tenant' );
            $table->index( 'user_id', 'idx_user_confirmation_tokens_user' );
            $table->index( 'expires_at', 'idx_user_confirmation_tokens_expires' );

            // Chaves estrangeiras
            $table->foreign( 'tenant_id', 'fk_user_confirmation_tokens_tenant' )
                ->references( 'id' )
                ->on( 'tenants' )
                ->onDelete( 'cascade' );

            $table->foreign( 'user_id', 'fk_user_confirmation_tokens_user' )
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
        Schema::dropIfExists( 'user_confirmation_tokens' );
    }

};