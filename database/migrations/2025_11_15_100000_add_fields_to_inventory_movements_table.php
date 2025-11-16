<?php

declare(strict_types=1);

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
        Schema::table('inventory_movements', function (Blueprint $table) {
            // Adiciona campos que estão faltando
            $table->integer('previous_quantity')->nullable()->after('quantity');
            $table->integer('new_quantity')->nullable()->after('previous_quantity');
            $table->integer('reference_id')->nullable()->after('reason');
            $table->string('reference_type', 50)->nullable()->after('reference_id');
            
            // Índices para melhor performance
            $table->index(['reference_id', 'reference_type']);
            $table->index(['product_id', 'type']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropIndex(['reference_id', 'reference_type']);
            $table->dropIndex(['product_id', 'type']);
            $table->dropIndex(['created_at']);
            
            $table->dropColumn([
                'previous_quantity',
                'new_quantity',
                'reference_id',
                'reference_type',
            ]);
        });
    }
};