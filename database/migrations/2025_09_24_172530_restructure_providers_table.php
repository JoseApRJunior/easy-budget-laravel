<?php

declare(strict_types=1);

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
        Schema::table( 'providers', function ( Blueprint $table ) {
            // Verificar se a constraint única antiga existe antes de tentar removê-la
            $indexes = DB::select( "SHOW INDEX FROM providers WHERE Key_name = 'providers_tenant_id_email_unique'" );
            if ( !empty( $indexes ) ) {
                $table->dropUnique( 'providers_tenant_id_email_unique' );
            }

            // Verificar se os campos antigos existem antes de removê-los
            $columns         = Schema::getColumnListing( 'providers' );
            $columnsToRemove = [];

            if ( in_array( 'name', $columns ) ) $columnsToRemove[] = 'name';
            if ( in_array( 'email', $columns ) ) $columnsToRemove[] = 'email';
            if ( in_array( 'phone', $columns ) ) $columnsToRemove[] = 'phone';
            if ( in_array( 'address', $columns ) ) $columnsToRemove[] = 'address';
            if ( in_array( 'cnpj', $columns ) ) $columnsToRemove[] = 'cnpj';
            if ( in_array( 'is_active', $columns ) ) $columnsToRemove[] = 'is_active';

            if ( !empty( $columnsToRemove ) ) {
                $table->dropColumn( $columnsToRemove );
            }

            // Adicionar novos campos apenas se não existirem
            if ( !in_array( 'user_id', $columns ) ) {
                $table->unsignedBigInteger( 'user_id' )->after( 'tenant_id' );
            }
            if ( !in_array( 'common_data_id', $columns ) ) {
                $table->unsignedBigInteger( 'common_data_id' )->nullable()->after( 'user_id' );
            }
            if ( !in_array( 'contact_id', $columns ) ) {
                $table->unsignedBigInteger( 'contact_id' )->nullable()->after( 'common_data_id' );
            }
            if ( !in_array( 'address_id', $columns ) ) {
                $table->unsignedBigInteger( 'address_id' )->nullable()->after( 'contact_id' );
            }
            if ( !in_array( 'terms_accepted', $columns ) ) {
                $table->boolean( 'terms_accepted' )->default( false )->after( 'address_id' );
            }

            // Verificar se as foreign keys já existem antes de adicioná-las
            $foreignKeys         = DB::select( "SHOW INDEX FROM providers WHERE Key_name LIKE '%_foreign'" );
            $existingForeignKeys = array_column( $foreignKeys, 'Key_name' );

            if ( !in_array( 'providers_user_id_foreign', $existingForeignKeys ) ) {
                $table->foreign( 'user_id' )->references( 'id' )->on( 'users' )->onDelete( 'cascade' );
            }
            if ( !in_array( 'providers_common_data_id_foreign', $existingForeignKeys ) ) {
                $table->foreign( 'common_data_id' )->references( 'id' )->on( 'common_data' )->onDelete( 'cascade' );
            }
            if ( !in_array( 'providers_contact_id_foreign', $existingForeignKeys ) ) {
                $table->foreign( 'contact_id' )->references( 'id' )->on( 'contacts' )->onDelete( 'cascade' );
            }
            if ( !in_array( 'providers_address_id_foreign', $existingForeignKeys ) ) {
                $table->foreign( 'address_id' )->references( 'id' )->on( 'addresses' )->onDelete( 'cascade' );
            }

            // Verificar se a constraint única nova já existe
            $uniqueIndexes = DB::select( "SHOW INDEX FROM providers WHERE Key_name = 'providers_tenant_id_user_id_unique'" );
            if ( empty( $uniqueIndexes ) ) {
                $table->unique( [ 'tenant_id', 'user_id' ] );
            }

            // Verificar se os índices já existem antes de adicioná-los
            $allIndexes         = DB::select( "SHOW INDEX FROM providers" );
            $existingIndexNames = array_column( $allIndexes, 'Key_name' );

            if ( !in_array( 'providers_user_id_index', $existingIndexNames ) ) {
                $table->index( 'user_id' );
            }
            if ( !in_array( 'providers_common_data_id_index', $existingIndexNames ) ) {
                $table->index( 'common_data_id' );
            }
            if ( !in_array( 'providers_contact_id_index', $existingIndexNames ) ) {
                $table->index( 'contact_id' );
            }
            if ( !in_array( 'providers_address_id_index', $existingIndexNames ) ) {
                $table->index( 'address_id' );
            }
            if ( !in_array( 'providers_terms_accepted_index', $existingIndexNames ) ) {
                $table->index( 'terms_accepted' );
            }
        } );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remover TODAS as foreign keys de outras tabelas que referenciam providers
        $this->removeAllExternalForeignKeys();

        // Remover foreign keys da própria tabela providers ANTES de entrar no Schema::table
        $this->removeProvidersForeignKeys();

        // Remover TODOS os índices e foreign keys usando SQL direto
        $this->forceRemoveAllIndexesAndForeignKeys();

        Schema::table( 'providers', function ( Blueprint $table ) {
            // Verificar se a constraint única nova existe antes de tentar removê-la
            $uniqueIndexes = DB::select( "SHOW INDEX FROM providers WHERE Key_name = 'providers_tenant_id_user_id_unique'" );
            if ( !empty( $uniqueIndexes ) ) {
                $table->dropUnique( 'providers_tenant_id_user_id_unique' );
            }

            // Remover TODOS os índices restantes usando SQL direto
            try {
                DB::statement( 'ALTER TABLE providers DROP INDEX IF EXISTS providers_user_id_index' );
                DB::statement( 'ALTER TABLE providers DROP INDEX IF EXISTS providers_common_data_id_index' );
                DB::statement( 'ALTER TABLE providers DROP INDEX IF EXISTS providers_contact_id_index' );
                DB::statement( 'ALTER TABLE providers DROP INDEX IF EXISTS providers_address_id_index' );
                DB::statement( 'ALTER TABLE providers DROP INDEX IF EXISTS providers_terms_accepted_index' );
                DB::statement( 'ALTER TABLE providers DROP INDEX IF EXISTS providers_tenant_id_index' );
            } catch ( Exception $e ) {
                // Ignorar erros
            }

            // Verificar se os campos novos existem antes de removê-los (um por vez)
            $columns = Schema::getColumnListing( 'providers' );

            // Remover colunas uma por vez usando SQL direto
            if ( in_array( 'user_id', $columns ) ) {
                try {
                    DB::statement( 'ALTER TABLE providers DROP COLUMN user_id' );
                } catch ( Exception $e ) {
                    // Ignorar se a coluna não existir
                }
            }
            if ( in_array( 'common_data_id', $columns ) ) {
                try {
                    DB::statement( 'ALTER TABLE providers DROP COLUMN common_data_id' );
                } catch ( Exception $e ) {
                    // Ignorar se a coluna não existir
                }
            }
            if ( in_array( 'contact_id', $columns ) ) {
                try {
                    DB::statement( 'ALTER TABLE providers DROP COLUMN contact_id' );
                } catch ( Exception $e ) {
                    // Ignorar se a coluna não existir
                }
            }
            if ( in_array( 'address_id', $columns ) ) {
                try {
                    DB::statement( 'ALTER TABLE providers DROP COLUMN address_id' );
                } catch ( Exception $e ) {
                    // Ignorar se a coluna não existir
                }
            }
            if ( in_array( 'terms_accepted', $columns ) ) {
                try {
                    DB::statement( 'ALTER TABLE providers DROP COLUMN terms_accepted' );
                } catch ( Exception $e ) {
                    // Ignorar se a coluna não existir
                }
            }

            // Recriar campos antigos
            $table->string( 'name', 100 );
            $table->string( 'email', 100 );
            $table->string( 'phone', 20 )->nullable();
            $table->text( 'address' )->nullable();
            $table->string( 'cnpj', 20 )->nullable();
            $table->boolean( 'is_active' )->default( true );

            // Recriar constraint único antigo
            $table->unique( [ 'tenant_id', 'email' ] );

            // Recriar índice para name
            $table->index( 'name' );
        } );
    }

    /**
     * Remove TODAS as foreign keys de outras tabelas que referenciam providers
     * E também remove as foreign keys da própria tabela providers
     */
    private function removeAllExternalForeignKeys(): void
    {
        // Remover foreign keys da tabela contacts
        if ( Schema::hasTable( 'contacts' ) ) {
            $foreignKeys = DB::select( "SHOW CREATE TABLE contacts" );
            if ( !empty( $foreignKeys ) ) {
                $createTable = $foreignKeys[ 0 ]->{'Create Table'};
                if ( str_contains( $createTable, 'providers' ) ) {
                    try {
                        DB::statement( 'ALTER TABLE contacts DROP FOREIGN KEY contacts_provider_id_foreign' );
                    } catch ( Exception $e ) {
                        // Ignorar se a foreign key não existir
                    }
                }
            }
        }

        // Remover foreign keys da tabela plan_subscriptions
        if ( Schema::hasTable( 'plan_subscriptions' ) ) {
            $foreignKeys = DB::select( "SHOW CREATE TABLE plan_subscriptions" );
            if ( !empty( $foreignKeys ) ) {
                $createTable = $foreignKeys[ 0 ]->{'Create Table'};
                if ( str_contains( $createTable, 'providers' ) ) {
                    try {
                        DB::statement( 'ALTER TABLE plan_subscriptions DROP FOREIGN KEY plan_subscriptions_provider_id_foreign' );
                    } catch ( Exception $e ) {
                        // Ignorar se a foreign key não existir
                    }
                }
            }
        }

        // Remover foreign keys das tabelas de pagamento
        if ( Schema::hasTable( 'payment_plans' ) ) {
            $foreignKeys = DB::select( "SHOW CREATE TABLE payment_plans" );
            if ( !empty( $foreignKeys ) ) {
                $createTable = $foreignKeys[ 0 ]->{'Create Table'};
                if ( str_contains( $createTable, 'providers' ) ) {
                    try {
                        DB::statement( 'ALTER TABLE payment_plans DROP FOREIGN KEY fk_payment_plans_provider' );
                    } catch ( Exception $e ) {
                        // Ignorar se a foreign key não existir
                    }
                }
            }
        }

        if ( Schema::hasTable( 'merchant_orders_mercado_pago' ) ) {
            try {
                DB::statement( 'ALTER TABLE merchant_orders_mercado_pago DROP FOREIGN KEY fk_merchant_orders_provider' );
            } catch ( Exception $e ) {
                // Ignorar se a foreign key não existir
            }
        }

        if ( Schema::hasTable( 'payment_mercado_pago_plans' ) ) {
            try {
                DB::statement( 'ALTER TABLE payment_mercado_pago_plans DROP FOREIGN KEY fk_payment_plans_provider' );
            } catch ( Exception $e ) {
                // Ignorar se a foreign key não existir
            }
        }

        // Remover foreign keys da própria tabela providers
        if ( Schema::hasTable( 'providers' ) ) {
            $foreignKeys         = DB::select( "SHOW INDEX FROM providers WHERE Key_name LIKE '%_foreign'" );
            $existingForeignKeys = array_column( $foreignKeys, 'Key_name' );

            if ( in_array( 'providers_user_id_foreign', $existingForeignKeys ) ) {
                try {
                    DB::statement( 'ALTER TABLE providers DROP FOREIGN KEY providers_user_id_foreign' );
                } catch ( Exception $e ) {
                    // Ignorar se a foreign key não existir
                }
            }
            if ( in_array( 'providers_common_data_id_foreign', $existingForeignKeys ) ) {
                try {
                    DB::statement( 'ALTER TABLE providers DROP FOREIGN KEY providers_common_data_id_foreign' );
                } catch ( Exception $e ) {
                    // Ignorar se a foreign key não existir
                }
            }
            if ( in_array( 'providers_contact_id_foreign', $existingForeignKeys ) ) {
                try {
                    DB::statement( 'ALTER TABLE providers DROP FOREIGN KEY providers_contact_id_foreign' );
                } catch ( Exception $e ) {
                    // Ignorar se a foreign key não existir
                }
            }
            if ( in_array( 'providers_address_id_foreign', $existingForeignKeys ) ) {
                try {
                    DB::statement( 'ALTER TABLE providers DROP FOREIGN KEY providers_address_id_foreign' );
                } catch ( Exception $e ) {
                    // Ignorar se a foreign key não existir
                }
            }
        }
    }

    /**
     * Remove as foreign keys da própria tabela providers
     */
    private function removeProvidersForeignKeys(): void
    {
        if ( Schema::hasTable( 'providers' ) ) {
            $foreignKeys         = DB::select( "SHOW INDEX FROM providers WHERE Key_name LIKE '%_foreign'" );
            $existingForeignKeys = array_column( $foreignKeys, 'Key_name' );

            if ( in_array( 'providers_user_id_foreign', $existingForeignKeys ) ) {
                try {
                    DB::statement( 'ALTER TABLE providers DROP FOREIGN KEY providers_user_id_foreign' );
                } catch ( Exception $e ) {
                    // Ignorar se a foreign key não existir
                }
            }
            if ( in_array( 'providers_common_data_id_foreign', $existingForeignKeys ) ) {
                try {
                    DB::statement( 'ALTER TABLE providers DROP FOREIGN KEY providers_common_data_id_foreign' );
                } catch ( Exception $e ) {
                    // Ignorar se a foreign key não existir
                }
            }
            if ( in_array( 'providers_contact_id_foreign', $existingForeignKeys ) ) {
                try {
                    DB::statement( 'ALTER TABLE providers DROP FOREIGN KEY providers_contact_id_foreign' );
                } catch ( Exception $e ) {
                    // Ignorar se a foreign key não existir
                }
            }
            if ( in_array( 'providers_address_id_foreign', $existingForeignKeys ) ) {
                try {
                    DB::statement( 'ALTER TABLE providers DROP FOREIGN KEY providers_address_id_foreign' );
                } catch ( Exception $e ) {
                    // Ignorar se a foreign key não existir
                }
            }
        }
    }

    /**
     * Remove TODOS os índices e foreign keys usando SQL direto
     */
    private function forceRemoveAllIndexesAndForeignKeys(): void
    {
        if ( Schema::hasTable( 'providers' ) ) {
            try {
                // Remover foreign keys usando SQL direto
                DB::statement( 'ALTER TABLE providers DROP FOREIGN KEY IF EXISTS providers_user_id_foreign' );
                DB::statement( 'ALTER TABLE providers DROP FOREIGN KEY IF EXISTS providers_common_data_id_foreign' );
                DB::statement( 'ALTER TABLE providers DROP FOREIGN KEY IF EXISTS providers_contact_id_foreign' );
                DB::statement( 'ALTER TABLE providers DROP FOREIGN KEY IF EXISTS providers_address_id_foreign' );
                DB::statement( 'ALTER TABLE providers DROP FOREIGN KEY IF EXISTS providers_tenant_id_foreign' );

                // Remover índices usando SQL direto
                DB::statement( 'ALTER TABLE providers DROP INDEX IF EXISTS providers_user_id_index' );
                DB::statement( 'ALTER TABLE providers DROP INDEX IF EXISTS providers_common_data_id_index' );
                DB::statement( 'ALTER TABLE providers DROP INDEX IF EXISTS providers_contact_id_index' );
                DB::statement( 'ALTER TABLE providers DROP INDEX IF EXISTS providers_address_id_index' );
                DB::statement( 'ALTER TABLE providers DROP INDEX IF EXISTS providers_terms_accepted_index' );
                DB::statement( 'ALTER TABLE providers DROP INDEX IF EXISTS providers_tenant_id_index' );
                DB::statement( 'ALTER TABLE providers DROP INDEX IF EXISTS providers_tenant_id_user_id_unique' );
            } catch ( Exception $e ) {
                // Ignorar erros - alguns índices/foreign keys podem não existir
            }
        }
    }

};