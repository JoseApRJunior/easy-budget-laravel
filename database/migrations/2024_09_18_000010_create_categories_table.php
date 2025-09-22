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
        Schema::create( 'categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger( 'tenant_id' );
            $table->string( 'name', 255 );
            $table->text( 'description' )->nullable();
            $table->string( 'slug', 255 )->unique();
            $table->boolean( 'is_active' )->default( true );
            $table->timestamps();

            $table->foreign( 'tenant_id' )->references( 'id' )->on( 'tenants' )->onDelete( 'cascade' );
            $table->unique( [ 'tenant_id', 'slug' ] );
            $table->index( 'tenant_id' );
        } );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists( 'categories' );
    }

};
