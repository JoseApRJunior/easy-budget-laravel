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
        Schema::table( 'reports', function ( Blueprint $table ) {
            $table->string( 'file_path' )->nullable()->after( 'file_name' );
            $table->json( 'filters' )->nullable()->after( 'size' );
            $table->text( 'error_message' )->nullable()->after( 'filters' );
            $table->timestamp( 'generated_at' )->nullable()->after( 'error_message' );
            $table->softDeletes();

            // Make hash not nullable and add unique constraint
            $table->string( 'hash', 64 )->nullable( false )->change();
            $table->unique( [ 'tenant_id', 'hash' ], 'uq_reports_tenant_hash' );

            // Change status and format to enum
            $table->enum( 'status', [ 'pending', 'processing', 'completed', 'failed' ] )->default( 'pending' )->change();
            $table->enum( 'format', [ 'pdf', 'excel', 'csv' ] )->default( 'pdf' )->change();
        } );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table( 'reports', function ( Blueprint $table ) {
            $table->dropUnique( 'uq_reports_tenant_hash' );
            $table->dropColumn( [ 'file_path', 'filters', 'error_message', 'generated_at' ] );
            $table->dropSoftDeletes();

            // Revert to string
            $table->string( 'status' )->change();
            $table->string( 'format' )->change();
            $table->string( 'hash', 64 )->nullable()->change();
        } );
    }

};
