<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alert_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('alert_type', 20); // performance, security, availability, resource, business, system
            $table->string('metric_name', 100);
            $table->string('severity', 10); // info, warning, error, critical
            $table->decimal('threshold_value', 10, 3);
            $table->integer('evaluation_window_minutes')->default(5);
            $table->integer('cooldown_minutes')->default(15);
            $table->boolean('is_active')->default(true);
            $table->json('notification_channels'); // ['email', 'sms', 'slack']
            $table->json('notification_emails')->nullable();
            $table->string('slack_webhook_url', 500)->nullable();
            $table->text('custom_message')->nullable();
            $table->timestamps();

            // Índices para performance
            $table->index(['tenant_id', 'alert_type']);
            $table->index(['tenant_id', 'is_active']);
            $table->index(['alert_type', 'severity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_settings');
    }
};