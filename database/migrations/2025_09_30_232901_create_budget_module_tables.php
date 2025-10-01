<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Categorias de itens do orçamento
        Schema::create( 'budget_item_categories', function ( Blueprint $table ) {
            $table->id();
            $table->foreignId( 'tenant_id' )->constrained( 'tenants' )->cascadeOnDelete();
            $table->string( 'name', 100 );
            $table->string( 'slug', 50 )->unique();
            $table->string( 'description', 500 )->nullable();
            $table->string( 'color', 7 )->nullable(); // Para UI
            $table->string( 'icon', 50 )->nullable(); // Ícone para UI
            $table->decimal( 'default_tax_percentage', 5, 2 )->default( 0 ); // Imposto padrão
            $table->boolean( 'is_active' )->default( true );
            $table->integer( 'order_index' )->default( 0 );
            $table->timestamps();

            $table->unique( [ 'tenant_id', 'slug' ], 'uq_budget_item_categories_tenant_slug' );
            $table->index( [ 'tenant_id', 'is_active' ], 'idx_budget_item_categories_tenant_active' );
        } );

        // 2) Itens do orçamento (relacionados diretamente ao orçamento)
        Schema::create( 'budget_items', function ( Blueprint $table ) {
            $table->id();
            $table->foreignId( 'tenant_id' )->constrained( 'tenants' )->cascadeOnDelete();
            $table->foreignId( 'budget_id' )->constrained( 'budgets' )->cascadeOnDelete();
            $table->foreignId( 'budget_item_category_id' )->nullable()->constrained( 'budget_item_categories' )->nullOnDelete();
            $table->string( 'title', 255 );
            $table->text( 'description' )->nullable();
            $table->decimal( 'quantity', 10, 2 )->default( 1 );
            $table->string( 'unit', 20 )->default( 'un' ); // unidade de medida
            $table->decimal( 'unit_price', 10, 2 )->default( 0 );
            $table->decimal( 'discount_percentage', 5, 2 )->default( 0 );
            $table->decimal( 'tax_percentage', 5, 2 )->default( 0 );
            $table->decimal( 'total_price', 10, 2 )->default( 0 );
            $table->decimal( 'net_total', 10, 2 )->default( 0 );
            $table->integer( 'order_index' )->default( 0 );
            $table->json( 'metadata' )->nullable(); // Campos customizáveis
            $table->timestamps();

            $table->index( [ 'tenant_id', 'budget_id' ], 'idx_budget_items_tenant_budget' );
            $table->index( [ 'budget_id', 'order_index' ], 'idx_budget_items_budget_order' );
        } );

        // 3) Controle de versões do orçamento
        Schema::create( 'budget_versions', function ( Blueprint $table ) {
            $table->id();
            $table->foreignId( 'tenant_id' )->constrained( 'tenants' )->cascadeOnDelete();
            $table->foreignId( 'budget_id' )->constrained( 'budgets' )->cascadeOnDelete();
            $table->foreignId( 'user_id' )->constrained( 'users' )->cascadeOnDelete();
            $table->string( 'version_number', 20 ); // Ex: "1.0", "1.1", "2.0"
            $table->text( 'changes_description' )->nullable();
            $table->json( 'budget_data' ); // Snapshot completo do orçamento
            $table->json( 'items_data' ); // Snapshot dos itens
            $table->decimal( 'version_total', 10, 2 )->default( 0 );
            $table->boolean( 'is_current' )->default( false );
            $table->timestamp( 'version_date' );
            $table->timestamps();

            $table->index( [ 'tenant_id', 'budget_id' ], 'idx_budget_versions_tenant_budget' );
            $table->index( [ 'budget_id', 'version_number' ], 'idx_budget_versions_budget_version' );
            $table->index( [ 'budget_id', 'is_current' ], 'idx_budget_versions_budget_current' );
        } );

        // 4) Templates reutilizáveis de orçamento
        Schema::create( 'budget_templates', function ( Blueprint $table ) {
            $table->id();
            $table->foreignId( 'tenant_id' )->constrained( 'tenants' )->cascadeOnDelete();
            $table->foreignId( 'user_id' )->constrained( 'users' )->cascadeOnDelete();
            $table->foreignId( 'parent_template_id' )->nullable()->constrained( 'budget_templates' )->nullOnDelete();
            $table->string( 'name', 255 );
            $table->string( 'slug', 100 )->unique();
            $table->text( 'description' )->nullable();
            $table->string( 'category', 50 )->default( 'geral' ); // produto, servico, projeto, consultoria
            $table->json( 'template_data' ); // Estrutura do template
            $table->json( 'default_items' ); // Itens padrão do template
            $table->json( 'variables' ); // Variáveis customizáveis
            $table->decimal( 'estimated_hours', 8, 2 )->nullable();
            $table->boolean( 'is_public' )->default( false );
            $table->boolean( 'is_active' )->default( true );
            $table->integer( 'usage_count' )->default( 0 );
            $table->timestamp( 'last_used_at' )->nullable();
            $table->timestamps();

            $table->unique( [ 'tenant_id', 'slug' ], 'uq_budget_templates_tenant_slug' );
            $table->index( [ 'tenant_id', 'category' ], 'idx_budget_templates_tenant_category' );
            $table->index( [ 'tenant_id', 'is_public' ], 'idx_budget_templates_tenant_public' );
            $table->index( [ 'parent_template_id' ], 'idx_budget_templates_parent' );
        } );

        // 5) Configurações de cálculo do orçamento
        Schema::create( 'budget_calculation_settings', function ( Blueprint $table ) {
            $table->id();
            $table->foreignId( 'tenant_id' )->constrained( 'tenants' )->cascadeOnDelete();
            $table->boolean( 'auto_calculate' )->default( true );
            $table->boolean( 'apply_global_discount' )->default( true );
            $table->decimal( 'default_global_discount', 5, 2 )->default( 0 );
            $table->boolean( 'round_calculations' )->default( true );
            $table->integer( 'decimal_places' )->default( 2 );
            $table->boolean( 'show_item_discount' )->default( true );
            $table->boolean( 'show_item_tax' )->default( true );
            $table->boolean( 'show_profit_margin' )->default( true );
            $table->json( 'tax_settings' )->nullable(); // Configurações específicas de impostos
            $table->json( 'custom_fields' )->nullable(); // Campos personalizados para itens
            $table->timestamps();

            $table->unique( [ 'tenant_id' ], 'uq_budget_calculation_settings_tenant' );
        } );

        // 6) Histórico de ações do orçamento
        Schema::create( 'budget_action_history', function ( Blueprint $table ) {
            $table->id();
            $table->foreignId( 'tenant_id' )->constrained( 'tenants' )->cascadeOnDelete();
            $table->foreignId( 'budget_id' )->constrained( 'budgets' )->cascadeOnDelete();
            $table->foreignId( 'user_id' )->constrained( 'users' )->cascadeOnDelete();
            $table->string( 'action', 50 ); // created, updated, sent, approved, rejected, expired
            $table->string( 'old_status', 50 )->nullable();
            $table->string( 'new_status', 50 )->nullable();
            $table->text( 'description' )->nullable();
            $table->json( 'changes' )->nullable(); // Detalhes das mudanças
            $table->json( 'metadata' )->nullable(); // Dados adicionais
            $table->string( 'ip_address', 45 )->nullable();
            $table->text( 'user_agent' )->nullable();
            $table->timestamps();

            $table->index( [ 'tenant_id', 'budget_id' ], 'idx_budget_action_history_tenant_budget' );
            $table->index( [ 'budget_id', 'created_at' ], 'idx_budget_action_history_budget_date' );
            $table->index( [ 'tenant_id', 'action' ], 'idx_budget_action_history_tenant_action' );
        } );

        // 7) Anexos do orçamento
        Schema::create( 'budget_attachments', function ( Blueprint $table ) {
            $table->id();
            $table->foreignId( 'tenant_id' )->constrained( 'tenants' )->cascadeOnDelete();
            $table->foreignId( 'budget_id' )->constrained( 'budgets' )->cascadeOnDelete();
            $table->string( 'file_name', 255 );
            $table->string( 'original_name', 255 );
            $table->string( 'file_path', 500 );
            $table->string( 'mime_type', 100 );
            $table->unsignedBigInteger( 'file_size' ); // em bytes
            $table->string( 'file_hash', 64 )->nullable(); // Para verificação de integridade
            $table->text( 'description' )->nullable();
            $table->boolean( 'is_public' )->default( false );
            $table->integer( 'download_count' )->default( 0 );
            $table->timestamp( 'last_downloaded_at' )->nullable();
            $table->timestamps();

            $table->index( [ 'tenant_id', 'budget_id' ], 'idx_budget_attachments_tenant_budget' );
            $table->index( [ 'budget_id', 'created_at' ], 'idx_budget_attachments_budget_date' );
        } );

        // 8) Compartilhamento de orçamentos
        Schema::create( 'budget_shares', function ( Blueprint $table ) {
            $table->id();
            $table->foreignId( 'tenant_id' )->constrained( 'tenants' )->cascadeOnDelete();
            $table->foreignId( 'budget_id' )->constrained( 'budgets' )->cascadeOnDelete();
            $table->string( 'share_token', 64 )->unique();
            $table->string( 'recipient_email', 255 )->nullable();
            $table->string( 'recipient_name', 255 )->nullable();
            $table->text( 'message' )->nullable();
            $table->json( 'permissions' )->nullable(); // ['view', 'download', 'approve', 'reject']
            $table->timestamp( 'expires_at' )->nullable();
            $table->boolean( 'is_active' )->default( true );
            $table->integer( 'access_count' )->default( 0 );
            $table->timestamp( 'last_accessed_at' )->nullable();
            $table->timestamps();

            $table->index( [ 'share_token' ], 'idx_budget_shares_token' );
            $table->index( [ 'budget_id', 'is_active' ], 'idx_budget_shares_budget_active' );
            $table->index( [ 'expires_at' ], 'idx_budget_shares_expires' );
        } );

        // 9) Configurações de expiração automática
        Schema::create( 'budget_expiration_settings', function ( Blueprint $table ) {
            $table->id();
            $table->foreignId( 'tenant_id' )->constrained( 'tenants' )->cascadeOnDelete();
            $table->boolean( 'auto_expire_enabled' )->default( true );
            $table->integer( 'default_validity_days' )->default( 30 );
            $table->integer( 'warning_days_before' )->default( 7 );
            $table->boolean( 'send_reminder_emails' )->default( true );
            $table->boolean( 'auto_reject_expired' )->default( false );
            $table->json( 'custom_rules' )->nullable(); // Regras personalizadas por categoria
            $table->timestamps();

            $table->unique( [ 'tenant_id' ], 'uq_budget_expiration_settings_tenant' );
        } );

        // 10) Notificações relacionadas a orçamentos
        Schema::create( 'budget_notifications', function ( Blueprint $table ) {
            $table->id();
            $table->foreignId( 'tenant_id' )->constrained( 'tenants' )->cascadeOnDelete();
            $table->foreignId( 'budget_id' )->constrained( 'budgets' )->cascadeOnDelete();
            $table->foreignId( 'user_id' )->constrained( 'users' )->cascadeOnDelete();
            $table->string( 'type', 50 ); // created, updated, sent, approved, rejected, expired, reminder
            $table->string( 'channel', 20 )->default( 'email' ); // email, sms, push
            $table->string( 'recipient_email', 255 )->nullable();
            $table->text( 'message' );
            $table->string( 'subject', 255 )->nullable();
            $table->json( 'data' )->nullable(); // Dados específicos da notificação
            $table->boolean( 'sent' )->default( false );
            $table->timestamp( 'sent_at' )->nullable();
            $table->boolean( 'read' )->default( false );
            $table->timestamp( 'read_at' )->nullable();
            $table->timestamps();

            $table->index( [ 'tenant_id', 'budget_id' ], 'idx_budget_notifications_tenant_budget' );
            $table->index( [ 'user_id', 'read' ], 'idx_budget_notifications_user_read' );
            $table->index( [ 'budget_id', 'type' ], 'idx_budget_notifications_budget_type' );
        } );
    }

    public function down(): void
    {
        Schema::dropIfExists( 'budget_notifications' );
        Schema::dropIfExists( 'budget_expiration_settings' );
        Schema::dropIfExists( 'budget_shares' );
        Schema::dropIfExists( 'budget_attachments' );
        Schema::dropIfExists( 'budget_action_history' );
        Schema::dropIfExists( 'budget_calculation_settings' );
        Schema::dropIfExists( 'budget_templates' );
        Schema::dropIfExists( 'budget_versions' );
        Schema::dropIfExists( 'budget_items' );
        Schema::dropIfExists( 'budget_item_categories' );
    }

};
