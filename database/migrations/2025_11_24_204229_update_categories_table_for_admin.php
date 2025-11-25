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
        Schema::table('categories', function (Blueprint $table) {
            // Adicionar campos necessários para o CategoryManagementController
            $table->foreignId('parent_id')->nullable()->after('id')->constrained('categories')->onDelete('cascade');
            $table->boolean('is_active')->default(true)->after('name');
            $table->string('type', 50)->default('general')->after('is_active');
            $table->string('description')->nullable()->after('type');
            $table->string('color', 7)->nullable()->after('description');
            $table->string('icon', 50)->nullable()->after('color');
            $table->unsignedInteger('order')->default(0)->after('icon');
            $table->string('meta_title')->nullable()->after('order');
            $table->text('meta_description')->nullable()->after('meta_title');
            $table->json('config')->nullable()->after('meta_description');
            $table->boolean('show_in_menu')->default(true)->after('config');
            
            // Índices para performance
            $table->index(['parent_id', 'is_active']);
            $table->index(['type', 'is_active']);
            $table->index('order');
            $table->index('show_in_menu');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex(['parent_id', 'is_active']);
            $table->dropIndex(['type', 'is_active']);
            $table->dropIndex('order');
            $table->dropIndex('show_in_menu');
            
            $table->dropColumn([
                'parent_id', 'is_active', 'type', 'description', 
                'color', 'icon', 'order', 'meta_title', 
                'meta_description', 'config', 'show_in_menu'
            ]);
        });
    }
};
