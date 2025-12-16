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
        // Remover campos duplicados da tabela category_tenant
        if ( Schema::hasTable( 'category_tenant' ) ) {
            Schema::table( 'category_tenant', function ( Blueprint $table ) {
                // Remover campos duplicados que não são necessários
                if ( Schema::hasColumn( 'category_tenant', 'is_custom' ) ) {
                    $table->dropColumn( 'is_custom' );
                }
                if ( Schema::hasColumn( 'category_tenant', 'is_default' ) ) {
                    $table->dropColumn( 'is_default' );
                }
            } );
        }

        // Atualizar a tabela categories para a estrutura correta
        Schema::table( 'categories', function ( Blueprint $table ) {
            // Garantir que o campo slug seja único para categorias globais
            // (Isso será implementado via lógica de validação, não via constraint)

            // Atualizar default para is_custom (categorias globais não são custom)
            if ( Schema::hasColumn( 'categories', 'is_custom' ) ) {
                $table->boolean( 'is_custom' )->default( false )->change();
            }

            // Remover is_default se existir (não é mais necessário)
            if ( Schema::hasColumn( 'categories', 'is_default' ) ) {
                $table->dropColumn( 'is_default' );
            }
        } );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverter as mudanças
        Schema::table( 'categories', function ( Blueprint $table ) {
            // Adicionar de volta o campo is_default se necessário
            if ( !Schema::hasColumn( 'categories', 'is_default' ) ) {
                $table->boolean( 'is_default' )->default( false )->after( 'is_custom' );
            }
        } );

        // Reverter a tabela category_tenant
        if ( Schema::hasTable( 'category_tenant' ) ) {
            Schema::table( 'category_tenant', function ( Blueprint $table ) {
                if ( !Schema::hasColumn( 'category_tenant', 'is_custom' ) ) {
                    $table->boolean( 'is_custom' )->default( false )->after( 'tenant_id' );
                }
                if ( !Schema::hasColumn( 'category_tenant', 'is_default' ) ) {
                    $table->boolean( 'is_default' )->default( false )->after( 'is_custom' );
                }
            } );
        }
    }

};
