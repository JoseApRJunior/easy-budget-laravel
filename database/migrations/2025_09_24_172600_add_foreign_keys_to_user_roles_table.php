<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migration para adicionar foreign keys à tabela user_roles.
 *
 * Esta migration adiciona as foreign keys necessárias para manter a integridade
 * referencial entre user_roles e as tabelas relacionadas (users, roles, tenants).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Verificar se as colunas existem antes de adicionar foreign keys
        $columns = Schema::getColumnListing( 'user_roles' );

        Schema::table( 'user_roles', function ( Blueprint $table ) use ( $columns ) {
            // Adicionar foreign key para user_id se a coluna existir
            if ( in_array( 'user_id', $columns ) ) {
                $table->foreign( 'user_id', 'fk_user_roles_user_id' )
                    ->references( 'id' )
                    ->on( 'users' )
                    ->onDelete( 'cascade' );
            }

            // Adicionar foreign key para role_id se a coluna existir
            if ( in_array( 'role_id', $columns ) ) {
                $table->foreign( 'role_id', 'fk_user_roles_role_id' )
                    ->references( 'id' )
                    ->on( 'roles' )
                    ->onDelete( 'cascade' );
            }

            // Adicionar foreign key para tenant_id se a coluna existir
            if ( in_array( 'tenant_id', $columns ) ) {
                $table->foreign( 'tenant_id', 'fk_user_roles_tenant_id' )
                    ->references( 'id' )
                    ->on( 'tenants' )
                    ->onDelete( 'cascade' );
            }
        } );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table( 'user_roles', function ( Blueprint $table ) {
            // Remover foreign keys na ordem inversa
            $table->dropForeign( 'fk_user_roles_tenant_id' );
            $table->dropForeign( 'fk_user_roles_role_id' );
            $table->dropForeign( 'fk_user_roles_user_id' );
        } );
    }

};