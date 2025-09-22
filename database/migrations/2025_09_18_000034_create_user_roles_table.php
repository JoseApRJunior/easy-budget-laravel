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
        Schema::create( 'user_roles', function (Blueprint $table) {
            $table->unsignedBigInteger( 'user_id' );
            $table->unsignedBigInteger( 'role_id' );
            $table->unsignedBigInteger( 'tenant_id' );
            $table->timestamps();

            $table->unique( [ 'user_id', 'role_id', 'tenant_id' ] );

            $table->index( 'user_id' );
            $table->index( 'role_id' );
            $table->index( 'tenant_id' );
        } );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists( 'user_roles' );
    }

};
