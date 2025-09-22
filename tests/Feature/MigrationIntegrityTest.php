<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class MigrationIntegrityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Testa se as migrações executam sem erros.
     */
    public function test_migrations_run_without_errors(): void
    {
        // Test relies on RefreshDatabase trait - no explicit migrate:fresh needed
        $this->assertTrue( true );
    }

    /**
     * Verifica se as colunas tenant_id são string de 36 caracteres (UUID).
     */
    public function test_tenant_id_columns_are_string_36(): void
    {
        $this->runOnMysqlConnection( function () {
            $tables = [ 
                'area_of_activities',
                'professions',
                'common_data',
                'contacts',
                'addresses',
                'user_confirmation_tokens',
                'budget_statuses',
                'categories',
                'invoice_statuses',
                'providers'
            ];

            foreach ( $tables as $table ) {
                if ( !Schema::hasTable( $table ) ) {
                    $this->markTestSkipped( "Tabela {$table} não existe." );
                }
                $col = DB::selectOne( "SHOW COLUMNS FROM `{$table}` LIKE 'tenant_id'" );
                $this->assertNotNull( $col, "Coluna tenant_id não encontrada em {$table}." );
                $this->assertStringContainsString( 'char(36)', $col->Type, "tenant_id em {$table} não é char(36) para UUID." );
            }
        } );
    }

    /**
     * Verifica se as foreign keys de tenant_id referenciam tenants.id corretamente.
     */
    public function test_tenant_foreign_keys_reference_tenants_table(): void
    {
        $this->runOnMysqlConnection( function () {
            $tables = [ 
                'area_of_activities',
                'professions',
                'common_data',
                'contacts',
                'addresses',
                'user_confirmation_tokens',
                'budget_statuses',
                'categories',
                'invoice_statuses',
                'providers'
            ];

            foreach ( $tables as $table ) {
                if ( !Schema::hasTable( $table ) ) {
                    $this->markTestSkipped( "Tabela {$table} não existe." );
                }
                $createTable = DB::select( "SHOW CREATE TABLE {$table}" )[ 0 ]->{'Create Table'};
                $this->assertStringContainsString( "FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`)", $createTable, "FK tenant_id em {$table} não referencia tenants.id corretamente." );
            }
        } );
    }

    /**
     * Verifica se outras foreign keys (ex: user_id) são bigint unsigned.
     */
    public function test_other_foreign_keys_are_bigint_unsigned(): void
    {
        $this->runOnMysqlConnection( function () {
            $tables = [ 
                'providers' => [ 'user_id' ],
            ];

            // Guard for budgets and customers
            if ( Schema::hasTable( 'budgets' ) ) {
                $tables[ 'budgets' ] = [ 'user_id' ];
            }
            if ( Schema::hasTable( 'customers' ) ) {
                $tables[ 'customers' ] = [ 'user_id' ];
            }

            foreach ( $tables as $table => $columns ) {
                foreach ( $columns as $column ) {
                    $this->assertTrue( Schema::hasColumn( $table, $column ), "Coluna {$column} não existe em {$table}." );
                    $col = DB::selectOne( "SHOW COLUMNS FROM `{$table}` LIKE '{$column}'" );
                    $this->assertNotNull( $col, "Coluna {$column} não encontrada em {$table}." );
                    $this->assertStringContainsString( 'bigint unsigned', $col->Type, "Coluna {$column} em {$table} não é bigint unsigned." );
                }
            }
        } );
    }

    /**
     * Verifica índices únicos compostos em tabelas multi-tenant.
     */
    public function test_composite_unique_indexes_exist(): void
    {
        $this->runOnMysqlConnection( function () {
            $indexes = [ 
                'area_of_activities' => [ 'tenant_id', 'slug' ],
                'professions'        => [ 'tenant_id', 'slug' ],
            ];

            foreach ( $indexes as $table => $columns ) {
                if ( !Schema::hasTable( $table ) ) {
                    $this->markTestSkipped( "Tabela {$table} não existe." );
                }
                $columnList = implode( '`, `', $columns );
                $result     = DB::select( "SELECT * FROM information_schema.statistics WHERE TABLE_NAME = ? AND TABLE_SCHEMA = ? AND INDEX_TYPE = 'BTREE' AND NON_UNIQUE = 0 AND COLUMN_NAME IN ('{$columnList}') ORDER BY SEQ_IN_INDEX", [ $table, DB::getDatabaseName() ] );
                $this->assertGreaterThanOrEqual( count( $columns ), count( $result ), "Índice único composto em {$table} para " . implode( ', ', $columns ) . " não existe." );
            }
        } );
    }

    /**
     * Testa o cascade delete para tenants.
     */
    public function test_tenant_cascade_delete_works(): void
    {
        $this->runOnMysqlConnection( function () {
            // Criar tenant
            $tenant = Tenant::factory()->create( [ 'id' => 'uuid-test-123' ] );

            // Inserir dados relacionados (omitir 'id' para auto-increment)
            DB::table( 'area_of_activities' )->insert( [ 
                'tenant_id'  => $tenant->id,
                'name'       => 'Test Activity',
                'slug'       => Str::slug( 'Test Activity' ),
                'created_at' => now(),
                'updated_at' => now(),
            ] );

            DB::table( 'professions' )->insert( [ 
                'tenant_id'  => $tenant->id,
                'name'       => 'Test Profession',
                'slug'       => Str::slug( 'Test Profession' ),
                'created_at' => now(),
                'updated_at' => now(),
            ] );

            $initialCount = DB::table( 'area_of_activities' )->where( 'tenant_id', $tenant->id )->count() +
                DB::table( 'professions' )->where( 'tenant_id', $tenant->id )->count();

            $this->assertEquals( 2, $initialCount, 'Dados iniciais não inseridos corretamente.' );

            // Deletar tenant
            $tenant->delete();

            // Assertar contagem zero
            $this->assertEquals( 0, DB::table( 'area_of_activities' )->where( 'tenant_id', $tenant->id )->count(), 'Cascade delete falhou para area_of_activities.' );
            $this->assertEquals( 0, DB::table( 'professions' )->where( 'tenant_id', $tenant->id )->count(), 'Cascade delete falhou para professions.' );
        } );
    }

    /**
     * Verifica que plans não tem tenant_id, mas outras tabelas têm.
     */
    public function test_plans_lack_tenant_id_while_others_have_it(): void
    {
        // Plans não deve ter tenant_id
        $this->assertFalse( Schema::hasColumn( 'plans', 'tenant_id' ), 'Tabela plans não deve ter coluna tenant_id.' );

        // Outras tabelas devem ter
        $tenantTables = [ 'providers' ];
        if ( Schema::hasTable( 'customers' ) ) {
            $tenantTables[] = 'customers';
        }
        if ( Schema::hasTable( 'budgets' ) ) {
            $tenantTables[] = 'budgets';
        }
        foreach ( $tenantTables as $table ) {
            $this->assertTrue( Schema::hasColumn( $table, 'tenant_id' ), "Tabela {$table} deve ter coluna tenant_id." );
        }
    }

    protected function runOnMysqlConnection( callable $callback ): void
    {
        $originalConnection = config( 'database.default' );
        config( [ 'database.default' => 'mysql' ] );

        try {
            $callback();
        } finally {
            config( [ 'database.default' => $originalConnection ] );
            DB::purge( 'mysql' );
            DB::setDefaultConnection( $originalConnection );
        }
    }

}