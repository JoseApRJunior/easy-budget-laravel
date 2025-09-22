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
        Schema::create( 'services', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger( 'tenant_id' );
            $table->unsignedBigInteger( 'budget_id' );
            $table->unsignedBigInteger( 'category_id' );
            $table->unsignedBigInteger( 'service_statuses_id' );
            $table->string( 'code', 50 )->unique();
            $table->text( 'description' )->nullable();
            $table->decimal( 'discount', 10, 2 )->nullable()->default( 0.00 );
            $table->decimal( 'total', 10, 2 )->default( 0.00 );
            $table->date( 'due_date' )->nullable();
            $table->string( 'pdf_verification_hash', 255 )->nullable();
            $table->text( 'observation' )->nullable();
            $table->timestamps();

            // Chaves estrangeiras
            $table->foreign( 'tenant_id' )->references( 'id' )->on( 'tenants' )->onDelete( 'cascade' );
            $table->foreign( 'budget_id' )->references( 'id' )->on( 'budgets' )->onDelete( 'restrict' );
            $table->foreign( 'category_id' )->references( 'id' )->on( 'categories' )->onDelete( 'restrict' );
            $table->foreign( 'service_statuses_id' )->references( 'id' )->on( 'service_statuses' )->onDelete( 'restrict' );

            // Unicidade e índices
            $table->unique( [ 'tenant_id', 'code' ] );
            $table->index( 'budget_id' );
            $table->index( 'service_statuses_id' );
            $table->index( 'code' );
            $table->index( [ 'tenant_id', 'budget_id' ] );
            $table->index( [ 'tenant_id', 'category_id' ] );
            $table->index( 'due_date' );
        } );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists( 'services' );
    }

};
