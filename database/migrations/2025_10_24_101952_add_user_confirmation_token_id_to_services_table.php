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
        Schema::table( 'services', function ( Blueprint $table ) {
            $table->foreignId( 'user_confirmation_token_id' )
                ->nullable()
                ->constrained( 'user_confirmation_tokens' )
                ->nullOnDelete();
        } );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table( 'services', function ( Blueprint $table ) {
            $table->dropForeign( [ 'user_confirmation_token_id' ] );
            $table->dropColumn( 'user_confirmation_token_id' );
        } );
    }

};
