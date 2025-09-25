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
        Schema::create( 'schedules', function ( Blueprint $table ) {
            $table->id();
            $table->unsignedBigInteger( 'tenant_id' );
            $table->unsignedBigInteger( 'service_id' );
            $table->unsignedBigInteger( 'user_confirmation_token_id' )->nullable();
            $table->dateTime( 'start_date_time' );
            $table->dateTime( 'end_date_time' )->nullable();
            $table->string( 'location', 255 )->nullable();
            $table->timestamps();

            // Chaves estrangeiras
            $table->foreign( 'tenant_id' )->references( 'id' )->on( 'tenants' )->onDelete( 'cascade' );
            $table->foreign( 'service_id' )->references( 'id' )->on( 'services' )->onDelete( 'cascade' );
            $table->foreign( 'user_confirmation_token_id' )->references( 'id' )->on( 'user_confirmation_tokens' )->onDelete( 'set null' );

            // Índices
            $table->index( 'tenant_id' );
            $table->index( 'service_id' );
            $table->index( 'user_confirmation_token_id' );
            $table->index( 'start_date_time' );
        } );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists( 'schedules' );
    }

};