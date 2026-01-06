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
        Schema::table('budgets', function (Blueprint $table) {
            $table->dropUnique('budgets_code_unique');
            $table->unique(['tenant_id', 'code'], 'budgets_tenant_code_unique');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropUnique('services_code_unique');
            $table->unique(['tenant_id', 'code'], 'services_tenant_code_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropUnique('services_tenant_code_unique');
            $table->unique('code', 'services_code_unique');
        });

        Schema::table('budgets', function (Blueprint $table) {
            $table->dropUnique('budgets_tenant_code_unique');
            $table->unique('code', 'budgets_code_unique');
        });
    }
};
