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
        Schema::table( 'providers', function (Blueprint $table) {
            // Adiciona índice único composto para garantir idempotência na criação de providers
            // (tenant_id, user_id) - um usuário só pode ter um provider por tenant
            $table->unique( [ 'tenant_id', 'user_id' ], 'providers_tenant_user_unique' );
        } );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table( 'providers', function (Blueprint $table) {
            // Remove o índice único composto
            $table->dropUnique( 'providers_tenant_user_unique' );
        } );
    }

};
