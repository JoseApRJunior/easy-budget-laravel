<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Variáveis disponíveis para uso nos templates
        Schema::create('email_variables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name', 255);
            $table->string('slug', 100)->unique();
            $table->string('description', 500);
            $table->string('category', 50); // system, user, customer, budget, invoice, company
            $table->enum('data_type', ['string', 'number', 'date', 'boolean', 'array'])->default('string');
            $table->string('default_value', 1000)->nullable();
            $table->json('validation_rules')->nullable(); // Regras de validação Laravel
            $table->boolean('is_system')->default(false); // Se é variável do sistema (não pode ser editada)
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->json('metadata')->nullable(); // Dados adicionais
            $table->timestamps();

            $table->unique(['tenant_id', 'slug'], 'uq_email_variables_tenant_slug');
            $table->index(['tenant_id', 'category'], 'idx_email_variables_tenant_category');
            $table->index(['tenant_id', 'is_active'], 'idx_email_variables_tenant_active');
            $table->index(['category', 'sort_order'], 'idx_email_variables_category_order');
        });

        // 2) Templates de email
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name', 255);
            $table->string('slug', 100)->unique();
            $table->enum('category', ['transactional', 'promotional', 'notification', 'system'])->default('transactional');
            $table->string('subject', 500);
            $table->longText('html_content'); // Conteúdo HTML do template
            $table->text('text_content')->nullable(); // Versão texto puro
            $table->json('variables')->nullable(); // Variáveis utilizadas no template
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false); // Templates do sistema (não podem ser excluídos)
            $table->integer('sort_order')->default(0);
            $table->json('metadata')->nullable(); // Configurações adicionais
            $table->timestamps();

            $table->unique(['tenant_id', 'slug'], 'uq_email_templates_tenant_slug');
            $table->index(['tenant_id', 'category'], 'idx_email_templates_tenant_category');
            $table->index(['tenant_id', 'is_active'], 'idx_email_templates_tenant_active');
            $table->index(['category', 'sort_order'], 'idx_email_templates_category_order');
        });

        // 3) Logs de emails enviados
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('email_template_id')->constrained('email_templates')->cascadeOnDelete();
            $table->string('recipient_email', 255);
            $table->string('recipient_name', 255)->nullable();
            $table->string('subject', 500);
            $table->string('sender_email', 255);
            $table->string('sender_name', 255)->nullable();
            $table->enum('status', ['pending', 'sent', 'delivered', 'opened', 'clicked', 'bounced', 'failed'])->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->timestamp('bounced_at')->nullable();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable(); // Dados adicionais do envio
            $table->string('tracking_id', 100)->unique()->nullable(); // ID para rastreamento
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'email_template_id'], 'idx_email_logs_tenant_template');
            $table->index(['email_template_id', 'created_at'], 'idx_email_logs_template_date');
            $table->index(['recipient_email'], 'idx_email_logs_recipient');
            $table->index(['status'], 'idx_email_logs_status');
            $table->index(['tracking_id'], 'idx_email_logs_tracking');
            $table->index(['sent_at'], 'idx_email_logs_sent_at');
        });

        // 4) Configurações de envio de emails por tenant
        Schema::create('email_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('provider', 50)->default('smtp'); // smtp, mailgun, ses, etc.
            $table->json('smtp_settings')->nullable(); // Configurações SMTP
            $table->json('provider_settings')->nullable(); // Configurações específicas do provider
            $table->string('default_sender_email', 255)->nullable();
            $table->string('default_sender_name', 255)->nullable();
            $table->boolean('track_opens')->default(true);
            $table->boolean('track_clicks')->default(true);
            $table->boolean('enable_queue')->default(true);
            $table->integer('queue_priority')->default(5); // 1-10, sendo 10 mais alta
            $table->integer('retry_attempts')->default(3);
            $table->integer('retry_delay')->default(60); // segundos
            $table->json('rate_limiting')->nullable(); // Configurações de limite de envio
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id'], 'uq_email_settings_tenant');
            $table->index(['tenant_id', 'is_active'], 'idx_email_settings_tenant_active');
        });

        // 5) Filas de emails para processamento assíncrono
        Schema::create('email_queue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('email_template_id')->constrained('email_templates')->cascadeOnDelete();
            $table->string('recipient_email', 255);
            $table->string('recipient_name', 255)->nullable();
            $table->string('subject', 500);
            $table->longText('html_content');
            $table->text('text_content')->nullable();
            $table->json('variables')->nullable(); // Dados das variáveis para processamento
            $table->json('attachments')->nullable(); // Anexos do email
            $table->string('priority', 20)->default('normal'); // low, normal, high, urgent
            $table->integer('attempts')->default(0);
            $table->integer('max_attempts')->default(3);
            $table->timestamp('scheduled_at')->nullable(); // Para agendamento
            $table->timestamp('processed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'priority'], 'idx_email_queue_tenant_priority');
            $table->index(['scheduled_at'], 'idx_email_queue_scheduled');
            $table->index(['processed_at'], 'idx_email_queue_processed');
            $table->index(['attempts', 'max_attempts'], 'idx_email_queue_attempts');
        });

        // 6) Templates de resposta automática
        Schema::create('email_autoresponders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name', 255);
            $table->string('trigger_event', 100); // budget_created, budget_approved, etc.
            $table->integer('delay_minutes')->default(0); // Atraso antes do envio
            $table->json('conditions')->nullable(); // Condições para envio
            $table->foreignId('email_template_id')->constrained('email_templates')->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->integer('usage_count')->default(0);
            $table->timestamp('last_triggered_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'trigger_event'], 'idx_email_autoresponders_tenant_event');
            $table->index(['tenant_id', 'is_active'], 'idx_email_autoresponders_tenant_active');
            $table->index(['trigger_event'], 'idx_email_autoresponders_event');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_autoresponders');
        Schema::dropIfExists('email_queue');
        Schema::dropIfExists('email_settings');
        Schema::dropIfExists('email_logs');
        Schema::dropIfExists('email_templates');
        Schema::dropIfExists('email_variables');
    }
};
