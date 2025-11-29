<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            // Remover a restrição de unicidade global do slug
            $table->dropUnique('categories_slug_unique');

            // Garantir índice não único para performance nas buscas por slug
            $table->index('slug', 'idx_categories_slug');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            // Remover índice não único
            $table->dropIndex('idx_categories_slug');

            // Restaurar unicidade global do slug
            $table->unique('slug');
        });
    }
};

