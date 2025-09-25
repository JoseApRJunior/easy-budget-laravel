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
        Schema::table( 'budgets', function ( Blueprint $table ) {
            $table->unsignedBigInteger( 'user_confirmation_token_id' )->nullable();

            // Foreign key constraint
            $table->foreign( 'user_confirmation_token_id' )
                ->references( 'id' )
                ->on( 'user_confirmation_tokens' )
                ->onDelete( 'set null' );

            // Index for performance
            $table->index( 'user_confirmation_token_id' );
        } );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table( 'budgets', function ( Blueprint $table ) {
            $table->dropForeign( [ 'user_confirmation_token_id' ] );
            $table->dropIndex( [ 'user_confirmation_token_id' ] );
            $table->dropColumn( 'user_confirmation_token_id' );
        } );
    }

};