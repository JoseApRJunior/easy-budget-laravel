<?php

declare(strict_types=1);

namespace Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create( 'users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger( 'tenant_id' );
            $table->string( 'email', 100 );
            $table->string( 'password' );
            $table->string( 'logo' )->nullable();
            $table->boolean( 'is_active' )->default( false );
            $table->timestamps();

            // Índices para performance
            $table->index( 'tenant_id', 'idx_tenant' );
            
            // Chaves estrangeiras
            $table->foreign( 'tenant_id', 'fk_users_tenant' )->references( 'id' )->on( 'tenants' );
        } );
    }

    public function down(): void
    {
        Schema::dropIfExists( 'users' );
    }

};
