<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Executa as migrações.
     */
    public function up(): void
    {
        Schema::create( 'budgets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger( 'tenant_id' );
            $table->unsignedBigInteger( 'customer_id' );
            $table->string( 'code', 100 );
            $table->unsignedBigInteger( 'budget_statuses_id' );
            $table->date( 'due_date' )->nullable();
            $table->decimal( 'discount', 12, 2 )->default( 0.00 );
            $table->decimal( 'total', 12, 2 )->default( 0.00 );
            $table->text( 'description' )->nullable();
            $table->string( 'payment_terms', 255 )->nullable();
            $table->json( 'attachment' )->nullable();
            $table->json( 'history' )->nullable();
            $table->string( 'pdf_verification_hash', 100 )->unique()->nullable();
            $table->timestamps();

            // Chaves estrangeiras
            $table->foreign( 'tenant_id' )->references( 'id' )->on( 'tenants' )->onDelete( 'cascade' );
            $table->foreign( 'customer_id' )->references( 'id' )->on( 'customers' )->onDelete( 'restrict' );
            $table->foreign( 'budget_statuses_id' )->references( 'id' )->on( 'budget_statuses' )->onDelete( 'restrict' );

            // Unicidade e índices
            $table->unique( [ 'tenant_id', 'code' ] );
            $table->index( [ 'tenant_id', 'customer_id' ] );
            $table->index( 'budget_statuses_id' );
            $table->index( 'due_date' );
        } );
    }

    /**
     * Reverte as migrações.
     */
    public function down(): void
    {
        Schema::dropIfExists( 'budgets' );
    }

};
