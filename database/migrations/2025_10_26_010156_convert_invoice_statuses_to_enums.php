<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Handle SQLite (recreate table)
        if ( DB::getDriverName() === 'sqlite' ) {
            // Create temporary table with new structure (without soft deletes since original doesn't have them)
            Schema::create( 'invoices_temp', function ( Blueprint $table ) {
                $table->id();
                $table->foreignId( 'tenant_id' )->constrained()->onDelete( 'cascade' );
                $table->foreignId( 'service_id' )->nullable()->constrained()->onDelete( 'restrict' );
                $table->foreignId( 'customer_id' )->constrained()->onDelete( 'restrict' );
                $table->string( 'invoice_statuses_id', 20 )->default( 'pending' ); // Changed from integer to string
                $table->string( 'code', 50 )->unique();
                $table->string( 'public_hash', 64 )->nullable();
                $table->decimal( 'subtotal', 10, 2 );
                $table->decimal( 'discount', 10, 2 )->default( 0 );
                $table->decimal( 'total', 10, 2 );
                $table->date( 'due_date' )->nullable();
                $table->string( 'payment_method', 50 )->nullable();
                $table->string( 'payment_id', 255 )->nullable();
                $table->decimal( 'transaction_amount', 10, 2 )->nullable();
                $table->timestamp( 'transaction_date' )->nullable();
                $table->text( 'notes' )->nullable();
                $table->timestamps();
            } );

            // Copy data with converted status IDs to enum values
            DB::statement( "
                INSERT INTO invoices_temp (
                    id, tenant_id, service_id, customer_id, invoice_statuses_id, code,
                    public_hash, subtotal, discount, total, due_date, payment_method,
                    payment_id, transaction_amount, transaction_date, notes,
                    created_at, updated_at
                )
                SELECT
                    i.id, i.tenant_id, i.service_id, i.customer_id,
                    CASE i.invoice_statuses_id
                        WHEN 1 THEN 'pending'
                        WHEN 2 THEN 'paid'
                        WHEN 3 THEN 'overdue'
                        WHEN 4 THEN 'cancelled'
                        ELSE 'pending'
                    END as invoice_statuses_id,
                    i.code, i.public_hash, i.subtotal, i.discount, i.total, i.due_date,
                    i.payment_method, i.payment_id, i.transaction_amount, i.transaction_date,
                    i.notes, i.created_at, i.updated_at
                FROM invoices i
            " );

            // Drop old table and rename new one
            Schema::drop( 'invoices' );
            Schema::rename( 'invoices_temp', 'invoices' );

            // Recreate indexes
            Schema::table( 'invoices', function ( Blueprint $table ) {
                $table->index( [ 'tenant_id', 'invoice_statuses_id' ] );
                $table->index( [ 'service_id', 'tenant_id' ] );
                $table->index( [ 'customer_id', 'tenant_id' ] );
                $table->index( 'code' );
                $table->index( 'due_date' );
                $table->index( 'public_hash' );
            } );

        } else {
            // Handle MySQL/PostgreSQL (alter column)
            // First, drop the foreign key constraint if it exists
            try {
                // Try to find and drop the foreign key constraint
                $foreignKeys = DB::select( "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'invoices' AND COLUMN_NAME = 'invoice_statuses_id' AND CONSTRAINT_NAME != 'PRIMARY' AND REFERENCED_TABLE_NAME IS NOT NULL" );

                if ( !empty( $foreignKeys ) ) {
                    foreach ( $foreignKeys as $fk ) {
                        DB::statement( "ALTER TABLE invoices DROP FOREIGN KEY {$fk->CONSTRAINT_NAME}" );
                    }
                }
            } catch ( Exception $e ) {
                // Foreign key might not exist, continue
            }

            // Update existing data to use enum values instead of IDs
            DB::statement( "
                UPDATE invoices SET invoice_statuses_id = CASE invoice_statuses_id
                    WHEN 1 THEN 'pending'
                    WHEN 2 THEN 'paid'
                    WHEN 3 THEN 'overdue'
                    WHEN 4 THEN 'cancelled'
                    ELSE 'pending'
                END
            " );

            // Change column type from integer to string
            Schema::table( 'invoices', function ( Blueprint $table ) {
                $table->string( 'invoice_statuses_id', 20 )->change();
            } );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Handle SQLite (recreate table)
        if ( DB::getDriverName() === 'sqlite' ) {
            // Create temporary table with old structure (without soft deletes since original doesn't have them)
            Schema::create( 'invoices_temp', function ( Blueprint $table ) {
                $table->id();
                $table->foreignId( 'tenant_id' )->constrained()->onDelete( 'cascade' );
                $table->foreignId( 'service_id' )->nullable()->constrained()->onDelete( 'restrict' );
                $table->foreignId( 'customer_id' )->constrained()->onDelete( 'restrict' );
                $table->unsignedBigInteger( 'invoice_statuses_id' )->default( 1 ); // Back to integer
                $table->string( 'code', 50 )->unique();
                $table->string( 'public_hash', 64 )->nullable();
                $table->decimal( 'subtotal', 10, 2 );
                $table->decimal( 'discount', 10, 2 )->default( 0 );
                $table->decimal( 'total', 10, 2 );
                $table->date( 'due_date' )->nullable();
                $table->string( 'payment_method', 50 )->nullable();
                $table->string( 'payment_id', 255 )->nullable();
                $table->decimal( 'transaction_amount', 10, 2 )->nullable();
                $table->timestamp( 'transaction_date' )->nullable();
                $table->text( 'notes' )->nullable();
                $table->timestamps();
            } );

            // Copy data with converted enum values back to IDs
            DB::statement( "
                INSERT INTO invoices_temp (
                    id, tenant_id, service_id, customer_id, invoice_statuses_id, code,
                    public_hash, subtotal, discount, total, due_date, payment_method,
                    payment_id, transaction_amount, transaction_date, notes,
                    created_at, updated_at
                )
                SELECT
                    i.id, i.tenant_id, i.service_id, i.customer_id,
                    CASE i.invoice_statuses_id
                        WHEN 'pending' THEN 1
                        WHEN 'paid' THEN 2
                        WHEN 'overdue' THEN 3
                        WHEN 'cancelled' THEN 4
                        ELSE 1
                    END as invoice_statuses_id,
                    i.code, i.public_hash, i.subtotal, i.discount, i.total, i.due_date,
                    i.payment_method, i.payment_id, i.transaction_amount, i.transaction_date,
                    i.notes, i.created_at, i.updated_at
                FROM invoices i
            " );

            // Drop new table and rename old one
            Schema::drop( 'invoices' );
            Schema::rename( 'invoices_temp', 'invoices' );

            // Recreate indexes
            Schema::table( 'invoices', function ( Blueprint $table ) {
                $table->index( [ 'tenant_id', 'invoice_statuses_id' ] );
                $table->index( [ 'service_id', 'tenant_id' ] );
                $table->index( [ 'customer_id', 'tenant_id' ] );
                $table->index( 'code' );
                $table->index( 'due_date' );
                $table->index( 'public_hash' );
            } );

        } else {
            // Handle MySQL/PostgreSQL (alter column back)
            // Update data back to IDs first
            DB::statement( "
                UPDATE invoices SET invoice_statuses_id = CASE invoice_statuses_id
                    WHEN 'pending' THEN 1
                    WHEN 'paid' THEN 2
                    WHEN 'overdue' THEN 3
                    WHEN 'cancelled' THEN 4
                    ELSE 1
                END
            " );

            // Change column type back from string to integer
            Schema::table( 'invoices', function ( Blueprint $table ) {
                $table->unsignedBigInteger( 'invoice_statuses_id' )->default( 1 )->change();
            } );

            // Add back the foreign key constraint if invoice_statuses table exists
            try {
                Schema::table( 'invoices', function ( Blueprint $table ) {
                    $table->foreign( 'invoice_statuses_id' )->references( 'id' )->on( 'invoice_statuses' )->restrictOnDelete();
                } );
            } catch ( Exception $e ) {
                // Foreign key might not be applicable or table might not exist
            }
        }
    }

};
