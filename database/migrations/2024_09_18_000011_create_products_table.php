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
        Schema::create( 'products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger( 'tenant_id' );
            $table->string( 'name', 255 );
            $table->string( 'code', 50 )->unique();
            $table->text( 'description' )->nullable();
            $table->unsignedBigInteger( 'category_id' );
            $table->unsignedBigInteger( 'unit_id' )->nullable();
            $table->string( 'image', 255 )->nullable();
            $table->decimal( 'price', 10, 2 );
            $table->decimal( 'total', 12, 2 )->default( 0.00 );
            $table->boolean( 'active' )->default( true );
            $table->timestamps();

            // Chaves estrangeiras
            $table->foreign( 'tenant_id' )->references( 'id' )->on( 'tenants' )->onDelete( 'cascade' );
            $table->foreign( 'category_id' )->references( 'id' )->on( 'categories' )->onDelete( 'restrict' );
            $table->foreign( 'unit_id' )->references( 'id' )->on( 'units' )->onDelete( 'set null' );

            // Unicidade e índices
            $table->unique( [ 'tenant_id', 'code' ] );
            $table->index( 'code' );
            $table->index( 'category_id' );
            $table->index( [ 'tenant_id', 'category_id' ] );
            $table->index( 'unit_id' );
        } );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists( 'products' );
    }

};
