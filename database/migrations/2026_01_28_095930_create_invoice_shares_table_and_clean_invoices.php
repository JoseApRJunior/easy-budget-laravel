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
        // 1. Create invoice_shares table
        Schema::create('invoice_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->string('share_token', 43)->unique(); // base64url format: 32 bytes = 43 caracteres
            $table->string('recipient_email', 255)->nullable();
            $table->string('recipient_name', 255)->nullable();
            $table->text('message')->nullable();
            $table->json('permissions')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->enum('status', ['active', 'rejected', 'expired'])->default('active');
            $table->integer('access_count')->default(0);
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamp('rejected_at')->nullable(); // Can be used if client "rejects" the invoice? Maybe "disputed"? Keeping generic for now.
            $table->timestamps();
        });

        // 2. Remove columns from invoices table
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['public_token', 'public_expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Add columns back to invoices table
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('public_token', 43)->nullable()->unique()->comment('Token para acesso público');
            $table->timestamp('public_expires_at')->nullable()->comment('Expiração do token público');
            
            // Re-add indexes if necessary
            $table->index('public_token');
            $table->index(['public_token', 'public_expires_at']);
        });

        // 2. Drop invoice_shares table
        Schema::dropIfExists('invoice_shares');
    }
};
