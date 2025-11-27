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
        // Índices para tabela users - JÁ CRIADOS
        /*
        Schema::table('users', function (Blueprint $table) {
            // Índice composto para buscas por tenant e email
            $table->index(['tenant_id', 'email'], 'users_tenant_email_index');

            // Índice composto para buscas por tenant e status ativo
            $table->index(['tenant_id', 'is_active'], 'users_tenant_active_index');

            // Índice para soft deletes
            $table->index('deleted_at', 'users_deleted_at_index');
        });

        // Índices para tabela products
        Schema::table('products', function (Blueprint $table) {
            // Índice composto para busca por tenant e SKU (único por tenant)
            $table->index(['tenant_id', 'sku'], 'products_tenant_sku_index');

            // Índice composto para busca por tenant e status ativo
            $table->index(['tenant_id', 'active'], 'products_tenant_active_index');

            // Índice para busca por categoria
            $table->index('category_id', 'products_category_index');
        });

        // Índices para tabela product_inventory
        Schema::table('product_inventory', function (Blueprint $table) {
            // Índice composto para busca por produto e tenant
            $table->index(['product_id', 'tenant_id'], 'inventory_product_tenant_index');

            // Índice para busca por tenant
            $table->index('tenant_id', 'inventory_tenant_index');

            // Índice para verificação de estoque baixo
            $table->index(['quantity', 'min_quantity'], 'inventory_low_stock_index');
        });

        // Índices para tabela inventory_movements
        Schema::table('inventory_movements', function (Blueprint $table) {
            // Índice composto para histórico de movimentações
            $table->index(['product_id', 'created_at'], 'movements_product_date_index');

            // Índice composto para filtrar por tipo e data
            $table->index(['type', 'created_at'], 'movements_type_date_index');

            // Índice para busca por tenant
            $table->index('tenant_id', 'movements_tenant_index');

            // Índice para busca por usuário
            if (Schema::hasColumn('inventory_movements', 'user_id')) {
                $table->index('user_id', 'movements_user_index');
            }
        });

        // Índices para tabela user_roles
        Schema::table('user_roles', function (Blueprint $table) {
            // Índice composto para verificação de roles por tenant
            $table->index(['user_id', 'tenant_id', 'role_id'], 'user_roles_composite_index');

            // Índice para busca por tenant
            $table->index('tenant_id', 'user_roles_tenant_index');
        });
        */

        // Índices para tabela sessions - JÁ CRIADOS
        /*
        Schema::table('sessions', function (Blueprint $table) {
            if (Schema::hasColumn('sessions', 'user_id')) {
                $table->index('user_id', 'sessions_user_index');
            }
            $table->index('last_activity', 'sessions_last_activity_index');
        });
        */

        // Índices para tabela categories
        Schema::table('categories', function (Blueprint $table) {
            // Índice composto para busca por tenant e status ativo
            // Corrigido: coluna é is_active, não active
            $table->index(['tenant_id', 'is_active'], 'categories_tenant_active_index');

            // Índice para soft deletes
            if (Schema::hasColumn('categories', 'deleted_at')) {
                $table->index('deleted_at', 'categories_deleted_at_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remover índices da tabela users
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_tenant_email_index');
            $table->dropIndex('users_tenant_active_index');
            $table->dropIndex('users_deleted_at_index');
        });

        // Remover índices da tabela products
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_tenant_sku_index');
            $table->dropIndex('products_tenant_active_index');
            $table->dropIndex('products_category_index');
        });

        // Remover índices da tabela product_inventory
        Schema::table('product_inventory', function (Blueprint $table) {
            $table->dropIndex('inventory_product_tenant_index');
            $table->dropIndex('inventory_tenant_index');
            $table->dropIndex('inventory_low_stock_index');
        });

        // Remover índices da tabela inventory_movements
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropIndex('movements_product_date_index');
            $table->dropIndex('movements_type_date_index');
            $table->dropIndex('movements_tenant_index');

            if (Schema::hasColumn('inventory_movements', 'user_id')) {
                $table->dropIndex('movements_user_index');
            }
        });

        // Remover índices da tabela user_roles
        Schema::table('user_roles', function (Blueprint $table) {
            $table->dropIndex('user_roles_composite_index');
            $table->dropIndex('user_roles_tenant_index');
        });

        // Remover índices da tabela sessions
        Schema::table('sessions', function (Blueprint $table) {
            if (Schema::hasColumn('sessions', 'user_id')) {
                $table->dropIndex('sessions_user_index');
            }
            $table->dropIndex('sessions_last_activity_index');
        });

        // Remover índices da tabela categories
        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex('categories_tenant_active_index');

            if (Schema::hasColumn('categories', 'deleted_at')) {
                $table->dropIndex('categories_deleted_at_index');
            }
        });
    }
};
