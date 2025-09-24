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
        // Tabela payment_mercado_pago_invoices
        Schema::create('payment_mercado_pago_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('payment_id', 50);
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('invoice_id');
            $table->enum('status', [
                'approved', 'pending', 'authorized', 'in_process', 'in_mediation',
                'rejected', 'cancelled', 'refunded', 'charged_back', 'recovered',
                'failure', 'partially_refunded'
            ])->default('pending');
            $table->string('payment_method', 50);
            $table->decimal('transaction_amount', 10, 2);
            $table->dateTime('transaction_date')->nullable();
            $table->timestamps();

            // Índices
            $table->unique(['payment_id', 'invoice_id'], 'uk_payment_invoice');
            $table->index('payment_id', 'idx_payment_id');
            $table->index('invoice_id', 'idx_invoice');
            $table->index('tenant_id', 'idx_tenant');
            $table->index('status', 'idx_status');
            $table->index('transaction_date', 'idx_transaction_date');

            // Chaves estrangeiras
            $table->foreign('invoice_id', 'fk_payment_invoices_invoice')
                  ->references('id')->on('invoices')->onDelete('cascade');
            $table->foreign('tenant_id', 'fk_payment_invoices_tenant')
                  ->references('id')->on('tenants')->onDelete('cascade');
        });

        // Tabela payment_mercado_pago_plans
        Schema::create('payment_mercado_pago_plans', function (Blueprint $table) {
            $table->id();
            $table->string('payment_id', 50);
            $table->unsignedBigInteger('provider_id');
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('plan_subscription_id');
            $table->enum('status', [
                'approved', 'pending', 'authorized', 'in_process', 'in_mediation',
                'rejected', 'cancelled', 'refunded', 'charged_back', 'recovered'
            ])->nullable();
            $table->string('payment_method', 50);
            $table->decimal('transaction_amount', 10, 2);
            $table->dateTime('transaction_date')->nullable();
            $table->timestamps();

            // Índices
            $table->index('provider_id', 'idx_provider');
            $table->index('tenant_id', 'idx_tenant');
            $table->index('plan_subscription_id', 'idx_subscription');

            // Chaves estrangeiras
            $table->foreign('provider_id', 'fk_payment_plans_provider')
                  ->references('id')->on('providers');
            $table->foreign('plan_subscription_id', 'fk_payment_plans_subscription')
                  ->references('id')->on('plan_subscriptions');
            $table->foreign('tenant_id', 'fk_payment_plans_tenant')
                  ->references('id')->on('tenants');
        });

        // Tabela merchant_orders_mercado_pago
        Schema::create('merchant_orders_mercado_pago', function (Blueprint $table) {
            $table->id();
            $table->string('merchant_order_id', 50);
            $table->unsignedBigInteger('provider_id');
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('plan_subscription_id');
            $table->enum('status', ['opened', 'closed', 'expired', 'cancelled', 'processing']);
            $table->enum('order_status', [
                'payment_required', 'payment_in_process', 'reverted', 'paid',
                'patially_reverted', 'patially_paid', 'partially_in_process',
                'undefined', 'expired'
            ]);
            $table->decimal('total_amount', 10, 2);
            $table->timestamps();

            // Índices
            $table->index('provider_id', 'idx_provider');
            $table->index('tenant_id', 'idx_tenant');
            $table->index('plan_subscription_id', 'idx_subscription');

            // Chaves estrangeiras
            $table->foreign('provider_id', 'fk_merchant_orders_provider')
                  ->references('id')->on('providers');
            $table->foreign('plan_subscription_id', 'fk_merchant_orders_subscription')
                  ->references('id')->on('plan_subscriptions');
            $table->foreign('tenant_id', 'fk_merchant_orders_tenant')
                  ->references('id')->on('tenants');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merchant_orders_mercado_pago');
        Schema::dropIfExists('payment_mercado_pago_plans');
        Schema::dropIfExists('payment_mercado_pago_invoices');
    }
};
