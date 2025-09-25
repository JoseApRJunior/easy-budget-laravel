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
        Schema::table( 'products', function ( Blueprint $table ) {
            $table->dropForeign( 'products_category_id_foreign' );
            $table->dropForeign( 'products_unit_id_foreign' );
            $table->dropColumn( [ 'category_id', 'unit_id', 'total' ] );
        } );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table( 'products', function ( Blueprint $table ) {
            $table->unsignedBigInteger( 'category_id' )->nullable();
            $table->unsignedBigInteger( 'unit_id' )->nullable();
            $table->decimal( 'total', 10, 2 )->nullable();

            $table->foreign( 'category_id', 'products_category_id_foreign' )->references( 'id' )->on( 'categories' );
            $table->foreign( 'unit_id', 'products_unit_id_foreign' )->references( 'id' )->on( 'units' );
        } );
    }

};