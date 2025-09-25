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
        // Verificar se a tabela customers existe antes de prosseguir
        if ( !Schema::hasTable( 'customers' ) ) {
            return;
        }

        // Verificar se a tabela contacts existe para migração de dados
        if ( !Schema::hasTable( 'contacts' ) ) {
            throw new \Exception( 'Tabela contacts não encontrada. Execute primeiro a migração de criação da tabela contacts.' );
        }

        // PRIMEIRO: Migrar dados de email/phone para tabela contacts ANTES de remover as colunas
        $this->migrateCustomerContactsToContactsTable();

        Schema::table( 'customers', function ( Blueprint $table ) {
            // Verificações de segurança antes de dropar colunas
            $columnsToDrop = [];

            // Verificar se as colunas existem antes de tentar removê-las
            if ( Schema::hasColumn( 'customers', 'email' ) ) {
                $columnsToDrop[] = 'email';
            }

            if ( Schema::hasColumn( 'customers', 'phone' ) ) {
                $columnsToDrop[] = 'phone';
            }

            if ( Schema::hasColumn( 'customers', 'is_active' ) ) {
                $columnsToDrop[] = 'is_active';
            }

            // Adicionar novos campos ANTES de remover os antigos
            if ( !Schema::hasColumn( 'customers', 'contact_id' ) ) {
                $table->unsignedBigInteger( 'contact_id' )->nullable();
            }
            
            if ( !Schema::hasColumn( 'customers', 'address_id' ) ) {
                $table->unsignedBigInteger( 'address_id' )->nullable();
            }
            
            if ( !Schema::hasColumn( 'customers', 'status' ) ) {
                $table->enum( 'status', [ 'active', 'inactive', 'suspended' ] )->default( 'active' );
            }

            // Remover campos antigos apenas se existirem
            if ( !empty( $columnsToDrop ) ) {
                $table->dropColumn( $columnsToDrop );
            }

            // Adicionar foreign keys
            try {
                $table->foreign( 'contact_id' )->references( 'id' )->on( 'contacts' )->onDelete( 'set null' );
            } catch ( \Exception $e ) {
                // Foreign key já existe, continuar
            }
            
            try {
                $table->foreign( 'address_id' )->references( 'id' )->on( 'addresses' )->onDelete( 'set null' );
            } catch ( \Exception $e ) {
                // Foreign key já existe, continuar
            }

            // Adicionar índices
            $table->index( 'contact_id' );
            $table->index( 'address_id' );
            $table->index( 'status' );
        } );
    }

    /**
     * Migrar dados de email e phone dos customers para a tabela contacts.
     * Esta operação preserva os dados existentes antes da reestruturação.
     */
    private function migrateCustomerContactsToContactsTable(): void
    {
        // Verificar se as colunas email e phone ainda existem na tabela customers
        if ( !Schema::hasColumn( 'customers', 'email' ) || !Schema::hasColumn( 'customers', 'phone' ) ) {
            return; // Colunas já foram removidas, não há dados para migrar
        }

        // Buscar customers que têm email ou phone
        $customersWithContacts = DB::table( 'customers' )
            ->where( function ( $query ) {
                $query->whereNotNull( 'email' )
                    ->orWhereNotNull( 'phone' );
            } )
            ->get();

        foreach ( $customersWithContacts as $customer ) {
            // Criar registro na tabela contacts
            $contactId = DB::table( 'contacts' )->insertGetId( [
                'tenant_id'  => $customer->tenant_id,
                'name'       => 'Cliente #' . $customer->id,
                'email'      => $customer->email,
                'phone'      => $customer->phone,
                'type'       => 'primary',
                'created_at' => now(),
                'updated_at' => now(),
            ] );

            // Atualizar customer com o novo contact_id apenas se a coluna contact_id já existir
            if ( Schema::hasColumn( 'customers', 'contact_id' ) ) {
                DB::table( 'customers' )
                    ->where( 'id', $customer->id )
                    ->update( [ 'contact_id' => $contactId ] );
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Verificar se a tabela customers existe antes de prosseguir
        if ( !Schema::hasTable( 'customers' ) ) {
            return;
        }

        Schema::table( 'customers', function ( Blueprint $table ) {
            // Remover foreign keys apenas se existirem
            $foreignKeys = [ 'customers_contact_id_foreign', 'customers_address_id_foreign' ];

            foreach ( $foreignKeys as $foreignKey ) {
                try {
                    $table->dropForeign( $foreignKey );
                } catch ( \Exception $e ) {
                    // Foreign key não existe, continuar
                    continue;
                }
            }

            // Remover índices apenas se existirem
            $indexes = [ 'customers_contact_id_index', 'customers_address_id_index', 'customers_status_index' ];

            foreach ( $indexes as $index ) {
                try {
                    $table->dropIndex( $index );
                } catch ( \Exception $e ) {
                    // Índice não existe, continuar
                    continue;
                }
            }

            // Remover novos campos
            $columnsToDrop   = [ 'contact_id', 'address_id', 'status' ];
            $existingColumns = [];

            foreach ( $columnsToDrop as $column ) {
                if ( Schema::hasColumn( 'customers', $column ) ) {
                    $existingColumns[] = $column;
                }
            }

            if ( !empty( $existingColumns ) ) {
                $table->dropColumn( $existingColumns );
            }

            // Recriar campos antigos apenas se não existirem
            if ( !Schema::hasColumn( 'customers', 'email' ) ) {
                $table->string( 'email' )->unique();
            }

            if ( !Schema::hasColumn( 'customers', 'phone' ) ) {
                $table->string( 'phone' )->nullable();
            }

            if ( !Schema::hasColumn( 'customers', 'is_active' ) ) {
                $table->boolean( 'is_active' )->default( true );
            }
        } );

        // Reverter migração de dados - opcional, dependendo da estratégia de rollback
        $this->rollbackCustomerContactsMigration();
    }

    /**
     * Rollback da migração de dados de contacts.
     * Remove os registros de contato criados durante a migração.
     */
    private function rollbackCustomerContactsMigration(): void
    {
        // Buscar contacts que foram criados para customers durante a migração
        $contactsToDelete = DB::table( 'contacts' )
            ->where( 'name', 'LIKE', 'Cliente #%' )
            ->whereNotNull( 'email' )
            ->orWhereNotNull( 'phone' )
            ->get();

        foreach ( $contactsToDelete as $contact ) {
            // Remover o contact_id dos customers que referenciam este contato
            DB::table( 'customers' )
                ->where( 'contact_id', $contact->id )
                ->update( [ 'contact_id' => null ] );

            // Remover o registro de contato
            DB::table( 'contacts' )->where( 'id', $contact->id )->delete();
        }
    }

};