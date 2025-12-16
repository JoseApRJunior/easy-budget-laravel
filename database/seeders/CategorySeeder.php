<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Seeder simplificado para categorias padrão por tenant.
 *
 * Cria categorias básicas que cada tenant pode usar como ponto de partida.
 */
class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // Categorias padrão para novos tenants
        $defaultCategories = [
            [
                'slug'        => 'servicos-gerais',
                'name'        => 'Serviços Gerais',
                'description' => 'Serviços gerais de manutenção e construção',
            ],
            [
                'slug'        => 'produtos',
                'name'        => 'Produtos',
                'description' => 'Venda de produtos e materiais',
            ],
            [
                'slug'        => 'manutencao',
                'name'        => 'Manutenção',
                'description' => 'Serviços de manutenção preventiva e corretiva',
            ],
            [
                'slug'        => 'consultoria',
                'name'        => 'Consultoria',
                'description' => 'Serviços de consultoria e assessoria técnica',
            ],
            [
                'slug'        => 'instalacao',
                'name'        => 'Instalação',
                'description' => 'Serviços de instalação de equipamentos e sistemas',
            ],
            [
                'slug'        => 'limpeza',
                'name'        => 'Limpeza',
                'description' => 'Serviços de limpeza residencial e comercial',
            ],
            [
                'slug'        => 'jardim',
                'name'        => 'Jardinagem',
                'description' => 'Serviços de jardinagem e paisagismo',
            ],
            [
                'slug'        => 'seguranca',
                'name'        => 'Segurança',
                'description' => 'Serviços de segurança e monitoramento',
            ],
            [
                'slug'        => 'tecnologia',
                'name'        => 'Tecnologia',
                'description' => 'Serviços de tecnologia da informação',
            ],
            [
                'slug'        => 'outros',
                'name'        => 'Outros',
                'description' => 'Outros serviços não categorizados',
            ],
        ];

        $now = now();

        // Para cada tenant existente, criar as categorias padrão
        $tenants = DB::table( 'tenants' )->get();

        foreach ( $tenants as $tenant ) {
            foreach ( $defaultCategories as $categoryData ) {
                // Verificar se categoria já existe para este tenant
                $existing = DB::table( 'categories' )
                    ->where( 'tenant_id', $tenant->id )
                    ->where( 'slug', $categoryData[ 'slug' ] )
                    ->first();

                if ( !$existing ) {
                    DB::table( 'categories' )->insert( [
                        'tenant_id'  => $tenant->id,
                        'slug'       => $categoryData[ 'slug' ],
                        'name'       => $categoryData[ 'name' ],
                        'is_active'  => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ] );
                }
            }
        }

        // Criar algumas categorias hierárquicas como exemplo
        $this->createHierarchicalCategories();
    }

    /**
     * Cria categorias hierárquicas como exemplo para o tenant 1
     */
    private function createHierarchicalCategories(): void
    {
        $tenantId = 1; // Tenant de exemplo
        $now      = now();

        // Categorias principais
        $mainCategories = [
            [
                'slug'      => 'construcao-civil',
                'name'      => 'Construção Civil',
                'parent_id' => null,
            ],
            [
                'slug'      => 'instalacoes',
                'name'      => 'Instalações',
                'parent_id' => null,
            ],
            [
                'slug'      => 'acabamentos',
                'name'      => 'Acabamentos',
                'parent_id' => null,
            ],
        ];

        $categoryIds = [];

        foreach ( $mainCategories as $categoryData ) {
            $existing = DB::table( 'categories' )
                ->where( 'tenant_id', $tenantId )
                ->where( 'slug', $categoryData[ 'slug' ] )
                ->first();

            if ( !$existing ) {
                $id                                 = DB::table( 'categories' )->insertGetId( [
                    'tenant_id'  => $tenantId,
                    'slug'       => $categoryData[ 'slug' ],
                    'name'       => $categoryData[ 'name' ],
                    'parent_id'  => $categoryData[ 'parent_id' ],
                    'is_active'  => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ] );
                $categoryIds[ $categoryData[ 'slug' ] ] = $id;
            } else {
                $categoryIds[ $categoryData[ 'slug' ] ] = $existing->id;
            }
        }

        // Subcategorias
        $subCategories = [
            [
                'slug'      => 'fundacao',
                'name'      => 'Fundação',
                'parent_id' => $categoryIds[ 'construcao-civil' ],
            ],
            [
                'slug'      => 'estrutura',
                'name'      => 'Estrutura',
                'parent_id' => $categoryIds[ 'construcao-civil' ],
            ],
            [
                'slug'      => 'alvenaria',
                'name'      => 'Alvenaria',
                'parent_id' => $categoryIds[ 'construcao-civil' ],
            ],
            [
                'slug'      => 'eletrica',
                'name'      => 'Elétrica',
                'parent_id' => $categoryIds[ 'instalacoes' ],
            ],
            [
                'slug'      => 'hidraulica',
                'name'      => 'Hidráulica',
                'parent_id' => $categoryIds[ 'instalacoes' ],
            ],
            [
                'slug'      => 'gas',
                'name'      => 'Gás',
                'parent_id' => $categoryIds[ 'instalacoes' ],
            ],
            [
                'slug'      => 'pintura',
                'name'      => 'Pintura',
                'parent_id' => $categoryIds[ 'acabamentos' ],
            ],
            [
                'slug'      => 'revestimento',
                'name'      => 'Revestimento',
                'parent_id' => $categoryIds[ 'acabamentos' ],
            ],
            [
                'slug'      => 'pisos',
                'name'      => 'Pisos',
                'parent_id' => $categoryIds[ 'acabamentos' ],
            ],
        ];

        foreach ( $subCategories as $categoryData ) {
            $existing = DB::table( 'categories' )
                ->where( 'tenant_id', $tenantId )
                ->where( 'slug', $categoryData[ 'slug' ] )
                ->first();

            if ( !$existing ) {
                DB::table( 'categories' )->insert( [
                    'tenant_id'  => $tenantId,
                    'slug'       => $categoryData[ 'slug' ],
                    'name'       => $categoryData[ 'name' ],
                    'parent_id'  => $categoryData[ 'parent_id' ],
                    'is_active'  => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ] );
            }
        }
    }

}
