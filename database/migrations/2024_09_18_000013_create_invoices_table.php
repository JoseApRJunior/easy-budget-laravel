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
        Schema::create( 'invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger( 'tenant_id' );
            $table->unsignedBigInteger( 'service_id' );
            $table->unsignedBigInteger( 'customer_id' );
            $table->string( 'code', 50 )->unique();
            $table->unsignedBigInteger( 'invoice_statuses_id' );
            $table->decimal( 'subtotal', 10, 2 );
            $table->decimal( 'discount', 10, 2 )->nullable();
            $table->decimal( 'total', 10, 2 );
            $table->decimal( 'transaction_amount', 10, 2 );
            $table->date( 'due_date' );
            $table->date( 'transaction_date' )->nullable();
            $table->string( 'payment_method', 50 )->nullable();
            $table->string( 'payment_id', 255 )->nullable();
            $table->string( 'public_hash', 255 )->unique()->nullable();
            $table->text( 'notes' )->nullable();
            $table->text( 'description' )->nullable();
            $table->timestamps();

            // Chaves estrangeiras
            $table->foreign( 'tenant_id' )->references( 'id' )->on( 'tenants' )->onDelete( 'cascade' );
            $table->foreign( 'service_id' )->references( 'id' )->on( 'services' )->onDelete( 'restrict' );
            $table->foreign( 'customer_id' )->references( 'id' )->on( 'customers' )->onDelete( 'restrict' );
            $table->foreign( 'invoice_statuses_id' )->references( 'id' )->on( 'invoice_statuses' )->onDelete( 'restrict' );

            // Unicidade e índices
            $table->unique( [ 'tenant_id', 'code' ] );
            $table->index( 'code' );
            $table->index( 'invoice_statuses_id' );
            $table->index( 'public_hash' );
            $table->index( [ 'tenant_id', 'service_id' ] );
            $table->index( [ 'tenant_id', 'customer_id' ] );
            $table->index( 'due_date' );
        } );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists( 'invoices' );
    }

};
