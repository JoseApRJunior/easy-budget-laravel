<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitoring_alerts_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('alert_setting_id')->nullable()->constrained('alert_settings')->nullOnDelete();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('alert_type', 20); // performance, security, availability, resource, business, system
            $table->string('severity', 10); // info, warning, error, critical
            $table->string('metric_name', 100);
            $table->decimal('metric_value', 10, 3);
            $table->decimal('threshold_value', 10, 3);
            $table->text('message');
            $table->json('additional_data')->nullable();
            $table->boolean('is_resolved')->default(false);
            $table->dateTime('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->boolean('notification_sent')->default(false);
            $table->dateTime('notification_sent_at')->nullable();
            $table->timestamps();

            // Índices para performance
            $table->index(['tenant_id', 'alert_type']);
            $table->index(['tenant_id', 'severity']);
            $table->index(['tenant_id', 'is_resolved']);
            $table->index(['alert_type', 'severity']);
            $table->index('created_at');
            $table->index(['is_resolved', 'resolved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitoring_alerts_history');
    }
};