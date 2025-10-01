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
        Schema::create( 'audit_logs', function ( Blueprint $table ) {
            $table->id();
            $table->foreignId( 'tenant_id' )->constrained( 'tenants' )->onDelete( 'cascade' );
            $table->foreignId( 'user_id' )->constrained( 'users' )->onDelete( 'cascade' );

            // Informações da ação
            $table->string( 'action', 100 );
            $table->string( 'model_type', 255 )->nullable();
            $table->unsignedBigInteger( 'model_id' )->nullable();

            // Valores antes e depois da mudança
            $table->json( 'old_values' )->nullable();
            $table->json( 'new_values' )->nullable();

            // Informações de contexto
            $table->string( 'ip_address', 45 )->nullable();
            $table->text( 'user_agent' )->nullable();
            $table->json( 'metadata' )->nullable();

            // Descrição adicional
            $table->text( 'description' )->nullable();

            // Classificação da ação
            $table->enum( 'severity', [ 'low', 'info', 'warning', 'high', 'critical' ] )->default( 'info' );
            $table->string( 'category', 50 )->nullable();
            $table->boolean( 'is_system_action' )->default( false );

            $table->timestamps();

            // Índices para performance
            $table->index( [ 'tenant_id', 'created_at' ] );
            $table->index( [ 'user_id', 'created_at' ] );
            $table->index( [ 'tenant_id', 'severity' ] );
            $table->index( [ 'tenant_id', 'category' ] );
            $table->index( [ 'tenant_id', 'action' ] );
            $table->index( [ 'model_type', 'model_id' ] );

            // Índice composto para consultas comuns
            $table->index( [ 'tenant_id', 'user_id', 'created_at' ] );
        } );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists( 'audit_logs' );
    }

};
