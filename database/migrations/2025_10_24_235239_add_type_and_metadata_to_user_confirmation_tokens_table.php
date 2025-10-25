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
        Schema::table( 'user_confirmation_tokens', function ( Blueprint $table ) {
            $table->string( 'type', 50 )->nullable()->after( 'expires_at' );
            $table->json( 'metadata' )->nullable()->after( 'type' );
        } );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table( 'user_confirmation_tokens', function ( Blueprint $table ) {
            $table->dropColumn( [ 'type', 'metadata' ] );
        } );
    }

};
