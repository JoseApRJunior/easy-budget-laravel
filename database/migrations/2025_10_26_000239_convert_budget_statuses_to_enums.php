<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Convert budget_statuses_id from foreign key integer to enum string values
     */
    public function up(): void
    {
        $databaseConnection = config( 'database.default' );

        // Check if migration has already been applied (column is already varchar)
        if (
            Schema::hasColumn( 'budgets', 'budget_statuses_id' ) &&
            Schema::getColumnType( 'budgets', 'budget_statuses_id' ) === 'string'
        ) {
            return; // Migration already applied
        }

        if ( $databaseConnection === 'sqlite' ) {
            // SQLite approach: Recreate table without foreign key constraints
            // This is the only reliable way to handle column changes in SQLite

            // Step 1: Disable foreign key constraints
            DB::statement( 'PRAGMA foreign_keys = OFF' );

            // Step 2: Create new table structure without foreign key to budget_statuses
            Schema::create( 'budgets_new', function ( Blueprint $table ) {
                $table->id();
                $table->foreignId( 'tenant_id' )->constrained( 'tenants' )->cascadeOnDelete();
                $table->foreignId( 'customer_id' )->constrained( 'customers' )->restrictOnDelete();
                $table->string( 'budget_statuses_id', 20 )->nullable(); // Changed to string enum
                $table->foreignId( 'user_confirmation_token_id' )->nullable()->constrained( 'user_confirmation_tokens' )->nullOnDelete();
                $table->string( 'code', 50 )->unique();
                $table->date( 'due_date' )->nullable();
                $table->decimal( 'discount', 10, 2 );
                $table->decimal( 'total', 10, 2 );
                $table->text( 'description' )->nullable();
                $table->text( 'payment_terms' )->nullable();
                $table->string( 'attachment' )->nullable();
                $table->longText( 'history' )->nullable();
                $table->string( 'pdf_verification_hash', 64 )->unique()->nullable();
                $table->timestamps();
            } );

            // Step 3: Copy data from old table to new table with enum conversion
            $budgetMappings = [
                1 => 'draft',
                2 => 'sent',
                3 => 'approved',
                4 => 'rejected',
                5 => 'expired',
                6 => 'revised',
                7 => 'cancelled'
            ];

            foreach ( $budgetMappings as $oldId => $newValue ) {
                DB::statement( "
                    INSERT INTO budgets_new (
                        id, tenant_id, customer_id, budget_statuses_id, user_confirmation_token_id,
                        code, due_date, discount, total, description, payment_terms, attachment,
                        history, pdf_verification_hash, created_at, updated_at
                    )
                    SELECT
                        id, tenant_id, customer_id, '{$newValue}', user_confirmation_token_id,
                        code, due_date, discount, total, description, payment_terms, attachment,
                        history, pdf_verification_hash, created_at, updated_at
                    FROM budgets WHERE budget_statuses_id = {$oldId}
                " );
            }

            // Step 4: Copy any remaining records (set to 'draft' as default)
            DB::statement( "
                INSERT INTO budgets_new (
                    id, tenant_id, customer_id, budget_statuses_id, user_confirmation_token_id,
                    code, due_date, discount, total, description, payment_terms, attachment,
                    history, pdf_verification_hash, created_at, updated_at
                )
                SELECT
                    id, tenant_id, customer_id, 'draft', user_confirmation_token_id,
                    code, due_date, discount, total, description, payment_terms, attachment,
                    history, pdf_verification_hash, created_at, updated_at
                FROM budgets WHERE budget_statuses_id NOT IN (1,2,3,4,5,6,7)
            " );

            // Step 5: Drop old table and rename new table
            Schema::drop( 'budgets' );
            Schema::rename( 'budgets_new', 'budgets' );

            // Step 6: Re-enable foreign key constraints
            DB::statement( 'PRAGMA foreign_keys = ON' );

        } else {
            // MySQL approach - drop foreign key, update data, change column type

            // First, drop the foreign key constraint if it exists
            try {
                // Try to find and drop the foreign key constraint
                $foreignKeys = DB::select( "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'budgets' AND COLUMN_NAME = 'budget_statuses_id' AND CONSTRAINT_NAME != 'PRIMARY' AND REFERENCED_TABLE_NAME IS NOT NULL" );

                if ( !empty( $foreignKeys ) ) {
                    foreach ( $foreignKeys as $fk ) {
                        DB::statement( "ALTER TABLE budgets DROP FOREIGN KEY {$fk->CONSTRAINT_NAME}" );
                    }
                }
            } catch ( Exception $e ) {
                // Foreign key might not exist, continue
            }

            // Update existing data to use enum values instead of IDs
            DB::table( 'budgets' )->where( 'budget_statuses_id', 1 )->update( [ 'budget_statuses_id' => 'draft' ] );
            DB::table( 'budgets' )->where( 'budget_statuses_id', 2 )->update( [ 'budget_statuses_id' => 'sent' ] );
            DB::table( 'budgets' )->where( 'budget_statuses_id', 3 )->update( [ 'budget_statuses_id' => 'approved' ] );
            DB::table( 'budgets' )->where( 'budget_statuses_id', 4 )->update( [ 'budget_statuses_id' => 'rejected' ] );
            DB::table( 'budgets' )->where( 'budget_statuses_id', 5 )->update( [ 'budget_statuses_id' => 'expired' ] );
            DB::table( 'budgets' )->where( 'budget_statuses_id', 6 )->update( [ 'budget_statuses_id' => 'revised' ] );
            DB::table( 'budgets' )->where( 'budget_statuses_id', 7 )->update( [ 'budget_statuses_id' => 'cancelled' ] );

            // Change column type from integer to string
            Schema::table( 'budgets', function ( Blueprint $table ) {
                $table->string( 'budget_statuses_id', 20 )->change();
            } );
        }
    }

    /**
     * Reverse the migrations.
     * Convert budget_statuses_id back from enum string values to foreign key integers
     */
    public function down(): void
    {
        $databaseConnection = config( 'database.default' );

        if ( $databaseConnection === 'sqlite' ) {
            // SQLite approach: Recreate table with foreign key constraint

            // Step 1: Disable foreign key constraints
            DB::statement( 'PRAGMA foreign_keys = OFF' );

            // Step 2: Create new table with original structure (including foreign key)
            Schema::create( 'budgets_new', function ( Blueprint $table ) {
                $table->id();
                $table->foreignId( 'tenant_id' )->constrained( 'tenants' )->cascadeOnDelete();
                $table->foreignId( 'customer_id' )->constrained( 'customers' )->restrictOnDelete();
                $table->unsignedBigInteger( 'budget_statuses_id' )->nullable(); // Changed back to integer foreign key
                $table->foreignId( 'user_confirmation_token_id' )->nullable()->constrained( 'user_confirmation_tokens' )->nullOnDelete();
                $table->string( 'code', 50 )->unique();
                $table->date( 'due_date' )->nullable();
                $table->decimal( 'discount', 10, 2 );
                $table->decimal( 'total', 10, 2 );
                $table->text( 'description' )->nullable();
                $table->text( 'payment_terms' )->nullable();
                $table->string( 'attachment' )->nullable();
                $table->longText( 'history' )->nullable();
                $table->string( 'pdf_verification_hash', 64 )->unique()->nullable();
                $table->timestamps();
            } );

            // Step 3: Copy data from old table to new table with ID conversion
            $budgetMappings = [
                'draft'     => 1,
                'sent'      => 2,
                'approved'  => 3,
                'rejected'  => 4,
                'expired'   => 5,
                'revised'   => 6,
                'cancelled' => 7
            ];

            foreach ( $budgetMappings as $oldValue => $newId ) {
                DB::statement( "
                    INSERT INTO budgets_new (
                        id, tenant_id, customer_id, budget_statuses_id, user_confirmation_token_id,
                        code, due_date, discount, total, description, payment_terms, attachment,
                        history, pdf_verification_hash, created_at, updated_at
                    )
                    SELECT
                        id, tenant_id, customer_id, {$newId}, user_confirmation_token_id,
                        code, due_date, discount, total, description, payment_terms, attachment,
                        history, pdf_verification_hash, created_at, updated_at
                    FROM budgets WHERE budget_statuses_id = '{$oldValue}'
                " );
            }

            // Step 4: Copy any remaining records (set to 1 as default)
            DB::statement( "
                INSERT INTO budgets_new (
                    id, tenant_id, customer_id, budget_statuses_id, user_confirmation_token_id,
                    code, due_date, discount, total, description, payment_terms, attachment,
                    history, pdf_verification_hash, created_at, updated_at
                )
                SELECT
                    id, tenant_id, customer_id, 1, user_confirmation_token_id,
                    code, due_date, discount, total, description, payment_terms, attachment,
                    history, pdf_verification_hash, created_at, updated_at
                FROM budgets WHERE budget_statuses_id NOT IN ('draft','sent','approved','rejected','expired','revised','cancelled')
            " );

            // Step 5: Drop old table and rename new table
            Schema::drop( 'budgets' );
            Schema::rename( 'budgets_new', 'budgets' );

            // Step 6: Re-enable foreign key constraints
            DB::statement( 'PRAGMA foreign_keys = ON' );

        } else {
            // MySQL approach - change column type back and add foreign key

            // Update data back to use IDs instead of enum values
            DB::table( 'budgets' )->where( 'budget_statuses_id', 'draft' )->update( [ 'budget_statuses_id' => 1 ] );
            DB::table( 'budgets' )->where( 'budget_statuses_id', 'sent' )->update( [ 'budget_statuses_id' => 2 ] );
            DB::table( 'budgets' )->where( 'budget_statuses_id', 'approved' )->update( [ 'budget_statuses_id' => 3 ] );
            DB::table( 'budgets' )->where( 'budget_statuses_id', 'rejected' )->update( [ 'budget_statuses_id' => 4 ] );
            DB::table( 'budgets' )->where( 'budget_statuses_id', 'expired' )->update( [ 'budget_statuses_id' => 5 ] );
            DB::table( 'budgets' )->where( 'budget_statuses_id', 'revised' )->update( [ 'budget_statuses_id' => 6 ] );
            DB::table( 'budgets' )->where( 'budget_statuses_id', 'cancelled' )->update( [ 'budget_statuses_id' => 7 ] );

            // Change column type back to integer
            Schema::table( 'budgets', function ( Blueprint $table ) {
                $table->unsignedBigInteger( 'budget_statuses_id' )->change();
            } );

            // Add back the foreign key constraint if budget_statuses table exists
            try {
                Schema::table( 'budgets', function ( Blueprint $table ) {
                    $table->foreign( 'budget_statuses_id' )->references( 'id' )->on( 'budget_statuses' )->restrictOnDelete();
                } );
            } catch ( Exception $e ) {
                // Foreign key might not be applicable or table might not exist
            }
        }
    }

};
