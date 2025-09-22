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
        Schema::table( 'units', function (Blueprint $table) {
            // Drop unique index on slug if it exists
            $table->dropUnique( [ 'slug' ] );

            // Drop slug and description columns
            $table->dropColumn( [ 'slug', 'description' ] );

            // Add new columns
            $table->string( 'symbol', 16 )->default( '' )->after( 'name' );
            $table->string( 'type', 32 )->nullable()->after( 'symbol' );

            // Add unique composite index on (name, symbol)
            $table->unique( [ 'name', 'symbol' ], 'units_name_symbol_unique' );
        } );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table( 'units', function (Blueprint $table) {
            // Drop the new unique index
            $table->dropUnique( 'units_name_symbol_unique' );

            // Drop new columns
            $table->dropColumn( [ 'symbol', 'type' ] );

            // Re-add dropped columns
            $table->string( 'slug' )->unique()->after( 'name' );
            $table->text( 'description' )->nullable()->after( 'slug' );
        } );
    }

};
