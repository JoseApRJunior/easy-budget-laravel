<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('supports', function (Blueprint $table) {
            $table->string('code', 20)->nullable()->after('status');
            $table->unique(['tenant_id', 'code'], 'uq_supports_tenant_code');
        });
    }

    public function down(): void
    {
        Schema::table('supports', function (Blueprint $table) {
            $table->dropUnique('uq_supports_tenant_code');
            $table->dropColumn('code');
        });
    }
};