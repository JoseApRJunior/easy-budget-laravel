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
        Schema::table('budgets', function (Blueprint $table) {
            $table->dropColumn(['public_token', 'public_expires_at']);
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['public_token', 'public_expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            $table->string('public_token', 64)->nullable()->after('pdf_verification_hash')->index();
            $table->timestamp('public_expires_at')->nullable()->after('public_token');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->string('public_token', 64)->nullable()->after('pdf_verification_hash')->index();
            $table->timestamp('public_expires_at')->nullable()->after('public_token');
        });
    }
};
