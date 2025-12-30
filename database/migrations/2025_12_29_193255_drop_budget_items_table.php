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
        Schema::dropIfExists('budget_items');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('budget_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('budget_id')->constrained()->onDelete('cascade');
            $table->foreignId('budget_item_category_id')->nullable()->constrained('budget_item_categories')->onDelete('set null');
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('quantity', 15, 2)->default(1);
            $table->string('unit')->nullable();
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('tax_percentage', 5, 2)->default(0);
            $table->decimal('total_price', 15, 2)->default(0);
            $table->decimal('net_total', 15, 2)->default(0);
            $table->integer('order_index')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('budget_item_category_id');
        });
    }
};
