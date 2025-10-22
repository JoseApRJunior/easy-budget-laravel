<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Raiz e catálogos globais
        Schema::create( 'tenants', function ( Blueprint $table ) {
            $table->id();
            $table->string( 'name', 255 )->unique();
            $table->boolean( 'is_active' )->default( true );
            $table->timestamps();
        } );

        Schema::create( 'units', function ( Blueprint $table ) {
            $table->id();
            $table->string( 'slug', 50 )->unique();
            $table->string( 'name', 100 )->unique();
            $table->boolean( 'is_active' )->default( true );
            $table->timestamps();
        } );

        Schema::create( 'areas_of_activity', function ( Blueprint $table ) {
            $table->id();
            $table->string( 'slug', 100 )->unique();
            $table->string( 'name', 100 );
            $table->boolean( 'is_active' )->default( true );
            $table->timestamps();
            $table->index( 'is_active' );
        } );

        Schema::create( 'professions', function ( Blueprint $table ) {
            $table->id();
            $table->string( 'slug', 50 )->unique();
            $table->string( 'name', 100 );
            $table->boolean( 'is_active' )->default( true );
            $table->timestamps();
        } );

        Schema::create( 'roles', function ( Blueprint $table ) {
            $table->id();
            $table->string( 'name', 255 )->unique();
            $table->string( 'description', 255 )->nullable();
            $table->timestamps();
        } );

        Schema::create( 'permissions', function ( Blueprint $table ) {
            $table->id();
            $table->string( 'name', 255 )->unique();
            $table->string( 'description', 500 )->nullable();
            $table->timestamps();
        } );

        Schema::create( 'budget_statuses', function ( Blueprint $table ) {
            $table->id();
            $table->string( 'slug', 50 )->unique();
            $table->string( 'name', 100 )->unique();
            $table->string( 'description', 500 )->nullable();
            $table->string( 'color', 7 )->nullable();
            $table->string( 'icon', 50 )->nullable();
            $table->integer( 'order_index' )->nullable();
            $table->boolean( 'is_active' )->default( true );
            $table->timestamps();
        } );

        Schema::create( 'service_statuses', function ( Blueprint $table ) {
            $table->id();
            $table->string( 'slug', 20 )->unique();
            $table->string( 'name', 50 )->unique();
            $table->string( 'description', 500 )->nullable();
            $table->string( 'color', 7 )->nullable();
            $table->string( 'icon', 30 )->nullable();
            $table->integer( 'order_index' )->nullable();
            $table->boolean( 'is_active' )->default( true );
            $table->timestamps();
        } );

        Schema::create( 'invoice_statuses', function ( Blueprint $table ) {
            $table->id();
            $table->string( 'name', 100 )->unique();
            $table->string( 'slug', 50 )->unique();
            $table->string( 'description', 500 )->nullable();
            $table->string( 'color', 7 )->nullable();
            $table->string( 'icon', 50 )->nullable();
            $table->integer( 'order_index' )->nullable();
            $table->boolean( 'is_active' )->default( true );
            $table->timestamps();
        } );

        Schema::create( 'plans', function ( Blueprint $table ) {
            $table->id();
            $table->string( 'name', 50 );
            $table->string( 'slug', 50 )->unique();
            $table->text( 'description' )->nullable();
            $table->decimal( 'price', 10, 2 );
            $table->boolean( 'status' )->default( true );
            $table->integer( 'max_budgets' );
            $table->integer( 'max_clients' );
            $table->json( 'features' )->nullable();
            $table->timestamps();
        } );

        Schema::create( 'categories', function ( Blueprint $table ) {
            $table->id();
            $table->string( 'slug', 255 )->unique();
            $table->string( 'name', 255 );
            $table->timestamps();
        } );

        // 2) Usuários e RBAC
        Schema::create( 'users', function ( Blueprint $table ) {
            $table->id();
            $table->foreignId( 'tenant_id' )->constrained( 'tenants' )->cascadeOnDelete();
            $table->string( 'name', 150 )->nullable(); // novo campo para armazenar nome do Google
            $table->string( 'email', 100 )->unique();
            $table->string( 'password', 255 )->nullable(); // pode ser nulo em login social
            $table->string( 'google_id', 255 )->nullable(); // novo campo para ID do Google
            $table->string( 'avatar', 255 )->nullable(); // novo campo para avatar do Google
            $table->boolean( 'is_active' )->default( true );
            $table->string( 'logo', 255 )->nullable();
            $table->timestamp( 'email_verified_at' )->nullable();
            $table->rememberToken();
            $table->timestamps();
        } );

        Schema::create( 'role_permissions', function ( Blueprint $table ) {
            $table->id();
            $table->foreignId( 'role_id' )->constrained( 'roles' )->cascadeOnDelete();
            $table->foreignId( 'permission_id' )->constrained( 'permissions' )->cascadeOnDelete();
            $table->timestamps();
            $table->unique( [ 'role_id', 'permission_id' ], 'uq_role_permissions' );
        } );

        Schema::create( 'user_roles', function ( Blueprint $table ) {
            $table->id();
            $table->foreignId( 'user_id' )->constrained( 'users' )->cascadeOnDelete();
            $table->foreignId( 'role_id' )->constrained( 'roles' )->cascadeOnDelete();
            $table->foreignId( 'tenant_id' )->constrained( 'tenants' )->cascadeOnDelete();
            $table->timestamps();
            $table->unique( [ 'user_id', 'role_id', 'tenant_id' ], 'uq_user_roles' );
        } );

        // 3) Contatos, endereços, dados comuns, clientes, provedores
        Schema::create( 'addresses', function ( Blueprint $table ) {
            $table->id();
            $table->foreignId( 'tenant_id' )->constrained( 'tenants' )->cascadeOnDelete();
            $table->string( 'address', 255 );
            $table->string( 'address_number', 20 )->nullable();
            $table->string( 'neighborhood', 100 );
            $table->string( 'city', 100 );
            $table->string( 'state', 2 );
            $table->string( 'cep', 9 );
            $table->timestamps();
        } );

        Schema::create( 'contacts', function ( Blueprint $table ) {
            $table->id();
            $table->foreignId( 'tenant_id' )->constrained( 'tenants' )->cascadeOnDelete();
            $table->string( 'email', 255 )->unique();
            $table->string( 'phone', 20 )->nullable();
            $table->string( 'email_business', 255 )->nullable()->unique();
            $table->string( 'phone_business', 20 )->nullable();
            $table->string( 'website', 255 )->nullable();
            $table->timestamps();
        } );

        Schema::create( 'common_datas', function ( Blueprint $table ) {
            $table->id();
            $table->foreignId( 'tenant_id' )->constrained( 'tenants' )->cascadeOnDelete();
            $table->string( 'first_name', 100 );
            $table->string( 'last_name', 100 );
            $table->date( 'birth_date' )->nullable();
            $table->string( 'cnpj', 14 )->nullable()->unique();
            $table->string( 'cpf', 11 )->nullable()->unique();
            $table->string( 'company_name', 255 )->nullable();
            $table->text( 'description' )->nullable();
            $table->foreignId( 'area_of_activity_id' )->nullable()->constrained( 'areas_of_activity' )->restrictOnDelete();
            $table->foreignId( 'profession_id' )->nullable()->constrained( 'professions' )->restrictOnDelete();
            $table->timestamps();
        } );

        Schema::create( 'customers', function ( Blueprint $table ) {
            $table->id();
            $table->foreignId( 'tenant_id' )->constrained( 'tenants' )->cascadeOnDelete();
            $table->foreignId( 'common_data_id' )->nullable()->constrained( 'common_datas' )->nullOnDelete();
            $table->foreignId( 'contact_id' )->nullable()->constrained( 'contacts' )->nullOnDelete();
            $table->foreignId( 'address_id' )->nullable()->constrained( 'addresses' )->nullOnDelete();
            $table->string( 'status', 20 );
            $table->timestamps();
        } );

        Schema::create( 'providers', function ( Blueprint $table ) {
            $table->id();
            $table->foreignId( 'tenant_id' )->constrained( 'tenants' )->cascadeOnDelete();
            $table->foreignId( 'user_id' )->constrained( 'users' )->cascadeOnDelete();
            $table->foreignId( 'common_data_id' )->nullable()->constrained( 'common_datas' )->nullOnDelete();
            $table->foreignId( 'contact_id' )->nullable()->constrained( 'contacts' )->nullOnDelete();
            $table->foreignId( 'address_id' )->nullable()->constrained( 'addresses' )->nullOnDelete();
            $table->boolean( 'terms_accepted' );
            $table->timestamps();
            $table->unique( [ 'tenant_id', 'user_id' ], 'uq_providers_tenant_user' );
        } );

        Schema::create( 'provider_credentials', function ( Blueprint $table ) {
            $table->id();
            $table->string( 'payment_gateway', 50 );
            $table->text( 'access_token_encrypted' );
            $table->text( 'refresh_token_encrypted' );
            $table->string( 'public_key', 50 );
            $table->string( 'user_id_gateway', 50 );
            $table->integer( 'expires_in' )->nullable();
            $table->foreignId( 'provider_id' )->constrained( 'providers' )->cascadeOnDelete();
            $table->foreignId( 'tenant_id' )->constrained( 'tenants' )->cascadeOnDelete();
            $table->timestamps();
        } );

        // 4) Produtos e estoque
        Schema::create( 'products', function ( Blueprint $table ) {
            $table->id();
            $table->foreignId( 'tenant_id' )->constrained( 'tenants' )->cascadeOnDelete();
            $table->string( 'name', 255 );
            $table->string( 'description', 500 )->nullable();
            $table->decimal( 'price', 10, 2 );
            $table->boolean( 'active' )->default( true );
            $table->string( 'code', 50 )->nullable();
            $table->string( 'image', 255 )->nullable();
            $table->timestamps();
            $table->unique( [ 'tenant_id', 'code' ] );
        } );

        Schema::create( 'product_inventory', function ( Blueprint $table ) {
            $table->id();
            $table->foreignId( 'tenant_id' )->constrained( 'tenants' )->cascadeOnDelete();
            $table->foreignId( 'product_id' )->constrained( 'products' )->cascadeOnDelete();
            $table->integer( 'quantity' )->default( 0 );
            $table->integer( 'min_quantity' )->default( 0 );
            $table->integer( 'max_quantity' )->nullable();
            $table->timestamps();
        } );

        Schema::create( 'inventory_movements', function ( Blueprint $table ) {
            $table->id();
            $table->foreignId( 'tenant_id' )->constrained( 'tenants' )->cascadeOnDelete();
            $table->foreignId( 'product_id' )->constrained( 'products' )->cascadeOnDelete();
            $table->string( 'type', 10 ); // 'in' | 'out'
            $table->integer( 'quantity' );
            $table->string( 'reason', 255 )->nullable();
            $table->timestamps();
        } );

        // 5) Tokens e agendamentos
        Schema::create( 'user_confirmation_tokens', function ( Blueprint $table ) {
            $table->id();
            $table->foreignId( 'user_id' )->constrained( 'users' )->cascadeOnDelete();
            $table->foreignId( 'tenant_id' )->constrained( 'tenants' )->cascadeOnDelete();
            $table->string( 'token', 64 )->unique();
            $table->dateTime( 'expires_at' );
            $table->timestamps();
        } );

        Schema::create( 'schedules', function ( Blueprint $table ) {
            $table->id();
            $table->foreignId( 'tenant_id' )->constrained( 'tenants' )->cascadeOnDelete();
            // Definido primeiro como coluna simples; FK adicionada após a criação de services
            $table->unsignedBigInteger( 'service_id' );
            $table->foreignId( 'user_confirmation_token_id' )->constrained( 'user_confirmation_tokens' )->cascadeOnDelete();
            $table->dateTime( 'start_date_time' );
            $table->dateTime( 'end_date_time' );
            $table->string( 'location', 500 )->nullable();
            $table->timestamps();
        } );

        // 6) Budgets, Services e itens (depende de tokens, customers, categories, statuses)
        Schema::create( 'budgets', function ( Blueprint $table ) {
            $table->id();
            $table->foreignId( 'tenant_id' )->constrained( 'tenants' )->cascadeOnDelete();
            $table->foreignId( 'customer_id' )->constrained( 'customers' )->restrictOnDelete();
            $table->foreignId( 'budget_statuses_id' )->constrained( 'budget_statuses' )->restrictOnDelete();
            $table->foreignId( 'user_confirmation_token_id' )->nullable()->constrained( 'user_confirmation_tokens' )->nullOnDelete();
            $table->string( 'code', 50 )->unique();
            $table->date( 'due_date' )->nullable();
            $table->decimal( 'discount', 10, 2 );
            $table->decimal( 'total', 10, 2 );
            $table->text( 'description' )->nullable();
            $table->text( 'payment_terms' )->nullable();
            $table->string( 'attachment', 255 )->nullable();
            $table->longText( 'history' )->nullable();
            $table->string( 'pdf_verification_hash', 64 )->nullable()->unique();
            $table->timestamps();
        } );

        Schema::create( 'services', function ( Blueprint $table ) {
            $table->id();
            $table->foreignId( 'tenant_id' )->constrained( 'tenants' )->cascadeOnDelete();
            $table->foreignId( 'budget_id' )->constrained( 'budgets' )->restrictOnDelete();
            $table->foreignId( 'category_id' )->constrained( 'categories' )->restrictOnDelete();
            $table->foreignId( 'service_statuses_id' )->constrained( 'service_statuses' )->restrictOnDelete();
            $table->string( 'code', 50 )->unique();
            $table->text( 'description' )->nullable();
            $table->decimal( 'discount', 10, 2 )->default( 0 );
            $table->decimal( 'total', 10, 2 )->default( 0 );
            $table->date( 'due_date' )->nullable();
            $table->string( 'pdf_verification_hash', 64 )->nullable();
            $table->timestamps();
        } );

        Schema::table( 'schedules', function ( Blueprint $table ) {
            // adicionar FK de service agora que services existe
            $table->foreign( 'service_id' )->references( 'id' )->on( 'services' )->cascadeOnDelete();
        } );

        Schema::create( 'service_items', function ( Blueprint $table ) {
            $table->id();
            $table->foreignId( 'tenant_id' )->constrained( 'tenants' )->cascadeOnDelete();
            $table->foreignId( 'service_id' )->constrained( 'services' )->cascadeOnDelete();
            $table->foreignId( 'product_id' )->constrained( 'products' )->restrictOnDelete();
            $table->decimal( 'unit_value', 10, 2 );
            $table->integer( 'quantity' );
            $table->decimal( 'total', 10, 2 )->nullable();
            $table->timestamps();
        } );

        // 7) Invoices e itens
        Schema::create( 'invoices', function ( Blueprint $table ) {
            $table->id();
            $table->foreignId( 'tenant_id' )->constrained( 'tenants' )->cascadeOnDelete();
            $table->foreignId( 'service_id' )->constrained( 'services' )->restrictOnDelete();
            $table->foreignId( 'customer_id' )->constrained( 'customers' )->restrictOnDelete();
            $table->foreignId( 'invoice_statuses_id' )->constrained( 'invoice_statuses' )->restrictOnDelete();
            $table->string( 'code', 50 )->unique();
            $table->string( 'public_hash', 64 )->nullable();
            $table->decimal( 'subtotal', 10, 2 );
            $table->decimal( 'discount', 10, 2 );
            $table->decimal( 'total', 10, 2 );
            $table->date( 'due_date' )->nullable();
            $table->string( 'payment_method', 50 )->nullable();
            $table->string( 'payment_id', 255 )->nullable();
            $table->decimal( 'transaction_amount', 10, 2 )->nullable();
            $table->dateTime( 'transaction_date' )->nullable();
            $table->text( 'notes' )->nullable();
            $table->timestamps();
        } );

        Schema::create( 'invoice_items', function ( Blueprint $table ) {
            $table->id();
            $table->foreignId( 'tenant_id' )->constrained( 'tenants' )->cascadeOnDelete();
            $table->foreignId( 'invoice_id' )->constrained( 'invoices' )->cascadeOnDelete();
            $table->foreignId( 'product_id' )->constrained( 'products' )->restrictOnDelete();
            $table->string( 'description', 255 )->nullable();
            $table->integer( 'quantity' );
            $table->decimal( 'unit_price', 10, 2 );
            $table->decimal( 'total', 10, 2 );
            $table->timestamps();
        } );

        // 8) Pagamentos MercadoPago
        Schema::create( 'plan_subscriptions', function ( Blueprint $table ) {
            $table->id();
            $table->enum( 'status', [ 'active', 'cancelled', 'pending', 'expired' ] );
            $table->decimal( 'transaction_amount', 10, 2 );
            $table->dateTime( 'start_date' );
            $table->dateTime( 'end_date' )->nullable();
            $table->dateTime( 'transaction_date' )->nullable();
            $table->string( 'payment_method', 50 )->nullable();
            $table->string( 'payment_id', 50 )->nullable();
            $table->string( 'public_hash', 255 )->nullable();
            $table->dateTime( 'last_payment_date' )->nullable();
            $table->dateTime( 'next_payment_date' )->nullable();
            $table->foreignId( 'tenant_id' )->constrained( 'tenants' )->cascadeOnDelete();
            $table->foreignId( 'provider_id' )->constrained( 'providers' )->cascadeOnDelete();
            $table->foreignId( 'plan_id' )->constrained( 'plans' )->restrictOnDelete();
            $table->timestamps();
        } );

        Schema::create( 'payment_mercado_pago_plans', function ( Blueprint $table ) {
            $table->id();
            $table->string( 'payment_id', 255 );
            $table->foreignId( 'tenant_id' )->constrained( 'tenants' )->cascadeOnDelete();
            $table->foreignId( 'provider_id' )->constrained( 'providers' )->cascadeOnDelete();
            $table->foreignId( 'plan_subscription_id' )->constrained( 'plan_subscriptions' )->cascadeOnDelete();
            $table->string( 'status', 20 );
            $table->string( 'payment_method', 50 );
            $table->decimal( 'transaction_amount', 10, 2 );
            $table->dateTime( 'transaction_date' )->nullable();
            $table->timestamps();
        } );

        Schema::create( 'merchant_orders_mercado_pago', function ( Blueprint $table ) {
            $table->id();
            $table->foreignId( 'tenant_id' )->constrained( 'tenants' )->cascadeOnDelete();
            $table->foreignId( 'provider_id' )->constrained( 'providers' )->cascadeOnDelete();
            $table->string( 'merchant_order_id', 255 );
            $table->foreignId( 'plan_subscription_id' )->constrained( 'plan_subscriptions' )->cascadeOnDelete();
            $table->string( 'status', 20 );
            $table->string( 'order_status', 50 );
            $table->decimal( 'total_amount', 10, 2 );
            $table->timestamps();
        } );

        Schema::create( 'payment_mercado_pago_invoices', function ( Blueprint $table ) {
            $table->id();
            $table->string( 'payment_id', 255 );
            $table->foreignId( 'tenant_id' )->constrained( 'tenants' )->cascadeOnDelete();
            $table->foreignId( 'invoice_id' )->constrained( 'invoices' )->cascadeOnDelete();
            $table->string( 'status', 20 );
            $table->string( 'payment_method', 50 );
            $table->decimal( 'transaction_amount', 10, 2 );
            $table->dateTime( 'transaction_date' )->nullable();
            $table->timestamps();
        } );

        // 9) Relatórios, notificações, recursos, alertas/metricas, atividades, sessões
        Schema::create( 'reports', function ( Blueprint $table ) {
            $table->id();
            $table->foreignId( 'tenant_id' )->constrained( 'tenants' )->cascadeOnDelete();
            $table->foreignId( 'user_id' )->constrained( 'users' )->cascadeOnDelete();
            $table->string( 'hash', 64 )->nullable();
            $table->string( 'type', 50 );
            $table->text( 'description' )->nullable();
            $table->string( 'file_name', 255 );
            $table->string( 'status', 20 ); // pending, processing, completed, failed
            $table->string( 'format', 10 ); // pdf, xlsx, csv
            $table->float( 'size' );
            $table->timestamps();
        } );

        Schema::create( 'notifications', function ( Blueprint $table ) {
            $table->id();
            $table->foreignId( 'tenant_id' )->constrained( 'tenants' )->cascadeOnDelete();
            $table->string( 'type', 50 );
            $table->string( 'email', 255 );
            $table->text( 'message' );
            $table->string( 'subject', 255 );
            $table->dateTime( 'sent_at' )->nullable();
            $table->timestamps();
        } );

        Schema::create( 'resources', function ( Blueprint $table ) {
            $table->id();
            $table->string( 'name', 100 );
            $table->string( 'slug', 100 )->unique();
            $table->boolean( 'in_dev' )->default( false );
            $table->string( 'status', 20 ); // active, inactive, deleted
            $table->timestamps();
        } );

        Schema::create( 'alert_settings', function ( Blueprint $table ) {
            $table->id();
            $table->foreignId( 'tenant_id' )->constrained( 'tenants' )->cascadeOnDelete();
            $table->json( 'settings' );
            $table->timestamps();
        } );

        Schema::create( 'middleware_metrics_history', function ( Blueprint $table ) {
            $table->id();
            $table->foreignId( 'tenant_id' )->constrained( 'tenants' )->cascadeOnDelete();
            $table->string( 'middleware_name', 100 );
            $table->string( 'endpoint', 255 );
            $table->string( 'method', 10 );
            $table->float( 'response_time' );
            $table->unsignedBigInteger( 'memory_usage' )->nullable();
            $table->float( 'cpu_usage' )->nullable();
            $table->integer( 'status_code' );
            $table->text( 'error_message' )->nullable();
            $table->foreignId( 'user_id' )->nullable()->constrained( 'users' )->nullOnDelete();
            $table->string( 'ip_address', 45 )->nullable();
            $table->text( 'user_agent' )->nullable();
            $table->unsignedBigInteger( 'request_size' )->nullable();
            $table->unsignedBigInteger( 'response_size' )->nullable();
            $table->integer( 'database_queries' )->nullable();
            $table->integer( 'cache_hits' )->nullable();
            $table->integer( 'cache_misses' )->nullable();
            $table->timestamp( 'created_at' )->useCurrent();
        } );

        Schema::create( 'monitoring_alerts_history', function ( Blueprint $table ) {
            $table->id();
            $table->foreignId( 'tenant_id' )->constrained( 'tenants' )->cascadeOnDelete();
            $table->string( 'alert_type', 20 ); // performance,error,security,availability,resource
            $table->string( 'severity', 10 );   // low,medium,high,critical
            $table->string( 'middleware_name', 100 );
            $table->string( 'endpoint', 255 )->nullable();
            $table->string( 'metric_name', 100 );
            $table->decimal( 'metric_value', 10, 3 );
            $table->decimal( 'threshold_value', 10, 3 );
            $table->text( 'message' );
            $table->json( 'additional_data' )->nullable();
            $table->boolean( 'is_resolved' )->default( false );
            $table->dateTime( 'resolved_at' )->nullable();
            $table->foreignId( 'resolved_by' )->nullable()->constrained( 'users' )->nullOnDelete();
            $table->text( 'resolution_notes' )->nullable();
            $table->boolean( 'notification_sent' )->default( false );
            $table->dateTime( 'notification_sent_at' )->nullable();
            $table->timestamps();
        } );

        Schema::create( 'activities', function ( Blueprint $table ) {
            $table->id();
            $table->foreignId( 'tenant_id' )->constrained( 'tenants' )->cascadeOnDelete();
            $table->foreignId( 'user_id' )->constrained( 'users' )->cascadeOnDelete();
            $table->string( 'action_type', 50 );
            $table->string( 'entity_type', 50 );
            $table->unsignedBigInteger( 'entity_id' );
            $table->text( 'description' );
            $table->text( 'metadata' )->nullable();
            $table->timestamp( 'created_at' )->useCurrent();
        } );

        Schema::create( 'supports', function ( Blueprint $table ) {
            $table->id();
            $table->string( 'first_name', 255 )->nullable();
            $table->string( 'last_name', 255 )->nullable();
            $table->string( 'email', 255 );
            $table->string( 'subject', 255 );
            $table->text( 'message' );
            $table->string( 'status', 30 );
            $table->foreignId( 'tenant_id' )->constrained( 'tenants' )->cascadeOnDelete();
            $table->timestamps();
        } );

        // Tabelas de configurações
        Schema::create( 'user_settings', function ( Blueprint $table ) {
            $table->id();
            $table->foreignId( 'tenant_id' )->constrained( 'tenants' )->cascadeOnDelete();
            $table->foreignId( 'user_id' )->constrained( 'users' )->cascadeOnDelete();
            $table->string( 'avatar', 255 )->nullable();
            $table->string( 'full_name', 255 )->nullable();
            $table->text( 'bio' )->nullable();
            $table->string( 'phone', 20 )->nullable();
            $table->date( 'birth_date' )->nullable();
            $table->string( 'social_facebook', 255 )->nullable();
            $table->string( 'social_twitter', 255 )->nullable();
            $table->string( 'social_linkedin', 255 )->nullable();
            $table->string( 'social_instagram', 255 )->nullable();
            $table->string( 'theme', 20 )->default( 'auto' );
            $table->string( 'primary_color', 7 )->default( '#3B82F6' );
            $table->string( 'layout_density', 20 )->default( 'normal' );
            $table->string( 'sidebar_position', 10 )->default( 'left' );
            $table->boolean( 'animations_enabled' )->default( true );
            $table->boolean( 'sound_enabled' )->default( true );
            $table->boolean( 'email_notifications' )->default( true );
            $table->boolean( 'transaction_notifications' )->default( true );
            $table->boolean( 'weekly_reports' )->default( false );
            $table->boolean( 'security_alerts' )->default( true );
            $table->boolean( 'newsletter_subscription' )->default( false );
            $table->boolean( 'push_notifications' )->default( false );
            $table->json( 'custom_preferences' )->nullable();
            $table->timestamps();

            $table->unique( [ 'tenant_id', 'user_id' ], 'uq_user_settings_tenant_user' );
        } );

        Schema::create( 'system_settings', function ( Blueprint $table ) {
            $table->id();
            $table->foreignId( 'tenant_id' )->constrained( 'tenants' )->cascadeOnDelete();
            $table->string( 'company_name', 255 )->nullable();
            $table->string( 'contact_email', 255 )->nullable();
            $table->string( 'phone', 20 )->nullable();
            $table->string( 'website', 255 )->nullable();
            $table->string( 'logo', 255 )->nullable();
            $table->string( 'currency', 3 )->default( 'BRL' );
            $table->string( 'timezone', 50 )->default( 'America/Sao_Paulo' );
            $table->string( 'language', 10 )->default( 'pt-BR' );
            $table->string( 'address_street', 255 )->nullable();
            $table->string( 'address_number', 20 )->nullable();
            $table->string( 'address_complement', 100 )->nullable();
            $table->string( 'address_neighborhood', 100 )->nullable();
            $table->string( 'address_city', 100 )->nullable();
            $table->string( 'address_state', 50 )->nullable();
            $table->string( 'address_zip_code', 10 )->nullable();
            $table->string( 'address_country', 50 )->nullable();
            $table->boolean( 'maintenance_mode' )->default( false );
            $table->text( 'maintenance_message' )->nullable();
            $table->boolean( 'registration_enabled' )->default( true );
            $table->boolean( 'email_verification_required' )->default( true );
            $table->integer( 'session_lifetime' )->default( 120 );
            $table->integer( 'max_login_attempts' )->default( 5 );
            $table->integer( 'lockout_duration' )->default( 15 );
            $table->json( 'allowed_file_types' )->nullable();
            $table->integer( 'max_file_size' )->default( 2048 );
            $table->json( 'system_preferences' )->nullable();
            $table->timestamps();

            $table->unique( [ 'tenant_id' ], 'uq_system_settings_tenant' );
        } );

        // Tabelas de cache padrão do Laravel
        Schema::create( 'cache', function ( Blueprint $table ) {
            $table->string( 'key' )->primary();
            $table->mediumText( 'value' );
            $table->integer( 'expiration' );
        } );

        Schema::create( 'jobs', function ( Blueprint $table ) {
            $table->id();
            $table->string( 'queue' )->index();
            $table->longText( 'payload' );
            $table->unsignedTinyInteger( 'attempts' );
            $table->unsignedInteger( 'reserved_at' )->nullable();
            $table->unsignedInteger( 'available_at' );
            $table->timestamp( 'created_at' )->useCurrent();

            $table->index( [ 'queue', 'reserved_at' ] );
        } );

        Schema::create( 'failed_jobs', function ( Blueprint $table ) {
            $table->id();
            $table->string( 'uuid' )->unique();
            $table->text( 'connection' );
            $table->text( 'queue' );
            $table->longText( 'payload' );
            $table->longText( 'exception' );
            $table->timestamp( 'failed_at' )->useCurrent();

            $table->index( [ 'uuid' ] );
        } );

        Schema::create( 'cache_locks', function ( Blueprint $table ) {
            $table->string( 'key' )->primary();
            $table->string( 'owner' );
            $table->integer( 'expiration' );
        } );

        // Tabela de auditoria completa
        Schema::create( 'audit_logs', function ( Blueprint $table ) {
            $table->id();
            $table->foreignId( 'tenant_id' )->constrained( 'tenants' )->onDelete( 'cascade' );
            $table->foreignId( 'user_id' )->constrained( 'users' )->onDelete( 'cascade' );

            // Informações da ação
            $table->string( 'action', 100 );
            $table->string( 'model_type', 255 )->nullable();
            $table->unsignedBigInteger( 'model_id' )->nullable();

            // Valores antes e depois da mudança
            $table->json( 'old_values' )->nullable();
            $table->json( 'new_values' )->nullable();

            // Informações de contexto
            $table->string( 'ip_address', 45 )->nullable();
            $table->text( 'user_agent' )->nullable();
            $table->json( 'metadata' )->nullable();

            // Descrição adicional
            $table->text( 'description' )->nullable();

            // Classificação da ação
            $table->enum( 'severity', [ 'low', 'info', 'warning', 'high', 'critical' ] )->default( 'info' );
            $table->string( 'category', 50 )->nullable();
            $table->boolean( 'is_system_action' )->default( false );

            $table->timestamps();

            // Índices para performance
            $table->index( [ 'tenant_id', 'created_at' ] );
            $table->index( [ 'user_id', 'created_at' ] );
            $table->index( [ 'tenant_id', 'severity' ] );
            $table->index( [ 'tenant_id', 'category' ] );
            $table->index( [ 'tenant_id', 'action' ] );
            $table->index( [ 'model_type', 'model_id' ] );

            // Índice composto para consultas comuns
            $table->index( [ 'tenant_id', 'user_id', 'created_at' ] );
        } );

        // Tabela sessions padrão do Laravel
        Schema::create( 'sessions', function ( Blueprint $table ) {
            $table->string( 'id' )->primary();
            $table->foreignId( 'user_id' )->nullable()->index()->constrained( 'users' )->nullOnDelete();
            $table->string( 'ip_address', 45 )->nullable();
            $table->text( 'user_agent' )->nullable();
            $table->longText( 'payload' );
            $table->integer( 'last_activity' )->index();
        } );

        // Tabela password_reset_tokens padrão do Laravel
        Schema::create( 'password_reset_tokens', function ( Blueprint $table ) {
            $table->string( 'email' )->primary();
            $table->string( 'token' );
            $table->timestamp( 'created_at' )->nullable();
        } );

    }

    public function down(): void
    {
        Schema::dropIfExists( 'audit_logs' );
        Schema::dropIfExists( 'failed_jobs' );
        Schema::dropIfExists( 'jobs' );
        Schema::dropIfExists( 'cache_locks' );
        Schema::dropIfExists( 'cache' );
        Schema::dropIfExists( 'password_reset_tokens' );
        Schema::dropIfExists( 'sessions' );
        Schema::dropIfExists( 'supports' );
        Schema::dropIfExists( 'activities' );
        Schema::dropIfExists( 'monitoring_alerts_history' );
        Schema::dropIfExists( 'middleware_metrics_history' );
        Schema::dropIfExists( 'alert_settings' );
        Schema::dropIfExists( 'resources' );
        Schema::dropIfExists( 'notifications' );
        Schema::dropIfExists( 'reports' );
        Schema::dropIfExists( 'payment_mercado_pago_invoices' );
        Schema::dropIfExists( 'merchant_orders_mercado_pago' );
        Schema::dropIfExists( 'payment_mercado_pago_plans' );
        Schema::dropIfExists( 'plan_subscriptions' );
        Schema::dropIfExists( 'invoice_items' );
        Schema::dropIfExists( 'invoices' );
        Schema::dropIfExists( 'service_items' );
        Schema::dropIfExists( 'services' );
        Schema::dropIfExists( 'budgets' );
        Schema::dropIfExists( 'schedules' );
        Schema::dropIfExists( 'user_confirmation_tokens' );
        Schema::dropIfExists( 'inventory_movements' );
        Schema::dropIfExists( 'product_inventory' );
        Schema::dropIfExists( 'products' );
        Schema::dropIfExists( 'provider_credentials' );
        Schema::dropIfExists( 'providers' );
        Schema::dropIfExists( 'customers' );
        Schema::dropIfExists( 'common_datas' );
        Schema::dropIfExists( 'contacts' );
        Schema::dropIfExists( 'addresses' );
        Schema::dropIfExists( 'user_settings' );
        Schema::dropIfExists( 'system_settings' );
        Schema::dropIfExists( 'user_roles' );
        Schema::dropIfExists( 'role_permissions' );
        Schema::dropIfExists( 'users' );
        Schema::dropIfExists( 'categories' );
        Schema::dropIfExists( 'plans' );
        Schema::dropIfExists( 'invoice_statuses' );
        Schema::dropIfExists( 'service_statuses' );
        Schema::dropIfExists( 'budget_statuses' );
        Schema::dropIfExists( 'permissions' );
        Schema::dropIfExists( 'roles' );
        Schema::dropIfExists( 'professions' );
        Schema::dropIfExists( 'areas_of_activity' );
        Schema::dropIfExists( 'units' );
        Schema::dropIfExists( 'tenants' );
    }

};
