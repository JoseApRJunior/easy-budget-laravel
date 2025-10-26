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
            Schema::create( 'services_temp', function ( Blueprint $table ) {
                $table->id();
                $table->foreignId( 'tenant_id' )->constrained()->onDelete( 'cascade' );
                $table->foreignId( 'budget_id' )->nullable()->constrained()->onDelete( 'restrict' );
                $table->foreignId( 'category_id' )->constrained()->onDelete( 'restrict' );
                $table->string( 'service_statuses_id', 20 )->default( 'scheduled' ); // Changed from integer to string
                $table->string( 'code', 50 )->unique();
                $table->text( 'description' )->nullable();
                $table->decimal( 'discount', 10, 2 )->default( 0 );
                $table->decimal( 'total', 10, 2 )->default( 0 );
                $table->date( 'due_date' )->nullable();
                $table->string( 'pdf_verification_hash', 64 )->nullable();
                $table->timestamps();
            } );

            // Copy data with converted status IDs to enum values
            DB::statement( "
                INSERT INTO services_temp (
                    id, tenant_id, budget_id, category_id, service_statuses_id, code,
                    description, discount, total, due_date, pdf_verification_hash,
                    created_at, updated_at
                )
                SELECT
                    s.id, s.tenant_id, s.budget_id, s.category_id,
                    CASE s.service_statuses_id
                        WHEN 1 THEN 'scheduled'
                        WHEN 2 THEN 'preparing'
                        WHEN 3 THEN 'on-hold'
                        WHEN 4 THEN 'in-progress'
                        WHEN 5 THEN 'partially-completed'
                        WHEN 6 THEN 'approved'    -- ID 6 agora mapeia para 'approved'
                        WHEN 7 THEN 'rejected'    -- ID 7 agora mapeia para 'rejected'
                        WHEN 8 THEN 'completed'   -- ID 8 agora mapeia para 'completed'
                        WHEN 9 THEN 'cancelled'   -- ID 9 agora mapeia para 'cancelled'
                        ELSE 'scheduled'
                    END as service_statuses_id,
                    s.code, s.description, s.discount, s.total, s.due_date,
                    s.pdf_verification_hash, s.created_at, s.updated_at
                FROM services s
            " );

            // Drop old table and rename new one
            Schema::drop( 'services' );
            Schema::rename( 'services_temp', 'services' );

            // Recreate indexes
            Schema::table( 'services', function ( Blueprint $table ) {
                $table->index( [ 'tenant_id', 'service_statuses_id' ] );
                $table->index( [ 'budget_id', 'tenant_id' ] );
                $table->index( [ 'category_id', 'tenant_id' ] );
                $table->index( 'code' );
                $table->index( 'due_date' );
            } );

        } else {
            // Handle MySQL/PostgreSQL (alter column)
            // First, drop the foreign key constraint if it exists
            try {
                // Try to find and drop the foreign key constraint
                $foreignKeys = DB::select( "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'services' AND COLUMN_NAME = 'service_statuses_id' AND CONSTRAINT_NAME != 'PRIMARY' AND REFERENCED_TABLE_NAME IS NOT NULL" );

                if ( !empty( $foreignKeys ) ) {
                    foreach ( $foreignKeys as $fk ) {
                        DB::statement( "ALTER TABLE services DROP FOREIGN KEY {$fk->CONSTRAINT_NAME}" );
                    }
                }
            } catch ( Exception $e ) {
                // Foreign key might not exist, continue
            }

            // Update existing data to use enum values instead of IDs
            DB::statement( "
                UPDATE services SET service_statuses_id = CASE service_statuses_id
                    WHEN 1 THEN 'scheduled'
                    WHEN 2 THEN 'preparing'
                    WHEN 3 THEN 'on-hold'
                    WHEN 4 THEN 'in-progress'
                    WHEN 5 THEN 'partially-completed'
                    WHEN 6 THEN 'approved'    -- ID 6 agora mapeia para 'approved'
                    WHEN 7 THEN 'rejected'    -- ID 7 agora mapeia para 'rejected'
                    WHEN 8 THEN 'completed'   -- ID 8 agora mapeia para 'completed'
                    WHEN 9 THEN 'cancelled'   -- ID 9 agora mapeia para 'cancelled'
                    ELSE 'scheduled'
                END
            " );

            // Change column type from integer to string
            Schema::table( 'services', function ( Blueprint $table ) {
                $table->string( 'service_statuses_id', 20 )->change();
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
            Schema::create( 'services_temp', function ( Blueprint $table ) {
                $table->id();
                $table->foreignId( 'tenant_id' )->constrained()->onDelete( 'cascade' );
                $table->foreignId( 'budget_id' )->nullable()->constrained()->onDelete( 'restrict' );
                $table->foreignId( 'category_id' )->constrained()->onDelete( 'restrict' );
                $table->unsignedBigInteger( 'service_statuses_id' )->default( 1 ); // Back to integer
                $table->string( 'code', 50 )->unique();
                $table->text( 'description' )->nullable();
                $table->decimal( 'discount', 10, 2 )->default( 0 );
                $table->decimal( 'total', 10, 2 )->default( 0 );
                $table->date( 'due_date' )->nullable();
                $table->string( 'pdf_verification_hash', 64 )->nullable();
                $table->timestamps();
            } );

            // Copy data with converted enum values back to IDs
            DB::statement( "
                INSERT INTO services_temp (
                    id, tenant_id, budget_id, category_id, service_statuses_id, code,
                    description, discount, total, due_date, pdf_verification_hash,
                    created_at, updated_at
                )
                SELECT
                    s.id, s.tenant_id, s.budget_id, s.category_id,
                    CASE s.service_statuses_id
                        WHEN 'scheduled' THEN 1
                        WHEN 'preparing' THEN 2
                        WHEN 'on-hold' THEN 3
                        WHEN 'in-progress' THEN 4
                        WHEN 'partially-completed' THEN 5
                        WHEN 'approved' THEN 6    -- APPROVED maps to ID 6
                        WHEN 'rejected' THEN 7    -- REJECTED maps to ID 7
                        WHEN 'completed' THEN 8   -- COMPLETED maps to ID 8
                        WHEN 'cancelled' THEN 9   -- CANCELLED maps to ID 9
                        ELSE 1
                    END as service_statuses_id,
                    s.code, s.description, s.discount, s.total, s.due_date,
                    s.pdf_verification_hash, s.created_at, s.updated_at
                FROM services s
            " );

            // Drop new table and rename old one
            Schema::drop( 'services' );
            Schema::rename( 'services_temp', 'services' );

            // Recreate indexes
            Schema::table( 'services', function ( Blueprint $table ) {
                $table->index( [ 'tenant_id', 'service_statuses_id' ] );
                $table->index( [ 'budget_id', 'tenant_id' ] );
                $table->index( [ 'category_id', 'tenant_id' ] );
                $table->index( 'code' );
                $table->index( 'due_date' );
            } );

        } else {
            // Handle MySQL/PostgreSQL (alter column back)
            // Update data back to IDs first
            DB::statement( "
                UPDATE services SET service_statuses_id = CASE service_statuses_id
                    WHEN 'scheduled' THEN 1
                    WHEN 'preparing' THEN 2
                    WHEN 'on-hold' THEN 3
                    WHEN 'in-progress' THEN 4
                    WHEN 'partially-completed' THEN 5
                    WHEN 'approved' THEN 6    -- APPROVED maps to ID 6
                    WHEN 'rejected' THEN 7    -- REJECTED maps to ID 7
                    WHEN 'completed' THEN 8   -- COMPLETED maps to ID 8
                    WHEN 'cancelled' THEN 9   -- CANCELLED maps to ID 9
                    ELSE 1
                END
            " );

            // Change column type back from string to integer
            Schema::table( 'services', function ( Blueprint $table ) {
                $table->unsignedBigInteger( 'service_statuses_id' )->default( 1 )->change();
            } );

            // Add back the foreign key constraint if service_statuses table exists
            try {
                Schema::table( 'services', function ( Blueprint $table ) {
                    $table->foreign( 'service_statuses_id' )->references( 'id' )->on( 'service_statuses' )->restrictOnDelete();
                } );
            } catch ( Exception $e ) {
                // Foreign key might not be applicable or table might not exist
            }
        }
    }

};
