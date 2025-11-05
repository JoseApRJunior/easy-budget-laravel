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
        Schema::create( 'budgets', function ( Blueprint $table ) {
            $table->id();
            $table->unsignedBigInteger( 'tenant_id' );
            $table->unsignedBigInteger( 'customer_id' );
            $table->string( 'status', 20 )->default( 'DRAFT' ); // BudgetStatus enum
            $table->unsignedBigInteger( 'user_confirmation_token_id' )->nullable();
            $table->string( 'code', 50 )->unique();
            $table->date( 'due_date' )->nullable();
            $table->decimal( 'discount', 10, 2 )->default( 0.00 );
            $table->decimal( 'total', 10, 2 )->default( 0.00 );
            $table->text( 'description' )->nullable();
            $table->text( 'payment_terms' )->nullable();
            $table->string( 'attachment', 255 )->nullable();
            $table->longText( 'history' )->nullable();
            $table->string( 'pdf_verification_hash', 64 )->nullable()->unique();
            $table->string( 'public_token', 43 )->nullable()->unique();
            $table->timestamp( 'public_expires_at' )->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign( 'tenant_id' )->references( 'id' )->on( 'tenants' )->onDelete( 'cascade' );
            $table->foreign( 'customer_id' )->references( 'id' )->on( 'customers' )->onDelete( 'restrict' );
            $table->foreign( 'user_confirmation_token_id' )->references( 'id' )->on( 'user_confirmation_tokens' )->onDelete( 'set null' );

            // Indexes for performance
            $table->index( [ 'tenant_id', 'status', 'created_at' ], 'idx_budgets_tenant_status_date' );
            $table->index( [ 'customer_id', 'tenant_id' ], 'idx_budgets_customer_tenant' );
            $table->index( [ 'tenant_id', 'status' ], 'idx_budgets_tenant_active' );
        } );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists( 'budgets' );
    }

};
