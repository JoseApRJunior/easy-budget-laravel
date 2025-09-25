<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration para criação da tabela global area_of_activities.
 * Tabela de lookup global, sem tenant_id, para armazenar áreas de atividade.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create( 'areas_of_activity', function (Blueprint $table) {
            $table->id();
            $table->string( 'slug', 50 )->unique();
            $table->string( 'name', 100 );
            $table->boolean( 'is_active' )->default( true );
            $table->timestamps();

            // Índices para performance
            $table->index( 'slug', 'uk_slug' );
        } );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists( 'areas_of_activity' );
    }

};
