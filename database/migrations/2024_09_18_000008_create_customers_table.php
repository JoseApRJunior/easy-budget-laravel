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
        Schema::create( 'customers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger( 'tenant_id' );
            $table->unsignedBigInteger( 'common_data_id' );
            $table->string( 'email' )->unique();
            $table->string( 'phone' )->nullable();
            $table->boolean( 'is_active' )->default( true );
            $table->timestamps();

            $table->foreign( 'tenant_id' )->references( 'id' )->on( 'tenants' )->onDelete( 'cascade' );
            $table->foreign( 'common_data_id' )->references( 'id' )->on( 'common_data' )->onDelete( 'restrict' );
            $table->index( 'tenant_id' );
        } );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists( 'customers' );
    }

};
