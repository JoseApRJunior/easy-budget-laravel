<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Seeder refatorado para criar categorias com subcategorias para um tenant específico.
 *
 * Uso:
 * - Seed para todos os tenants: php artisan db:seed --class=CategorySeeder
 * - Seed para tenant específico: php artisan tinker CategorySeeder::seedForTenant(1)
 * - Atualizar categorias: php artisan tinker CategorySeeder::updateCategoriesForTenant(1)
 * - Limpar categorias: php artisan tinker CategorySeeder::clearCategoriesForTenant(1)
 */
class CategorySeeder extends Seeder
{
    /**
     * Executa o seeder para todos os tenants existentes
     */
    public function run(): void
    {
        $this->command->info( 'Iniciando criação de categorias para todos os tenants...' );

        // Para cada tenant existente, criar as categorias padrão
        $tenants = DB::table( 'tenants' )->where( 'is_active', true )->get();

        foreach ( $tenants as $tenant ) {
            $this->seedForTenant( $tenant->id );
            $this->command->info( "Categorias criadas para tenant: {$tenant->name} (ID: {$tenant->id})" );
        }
    }

    /**
     * Cria categorias para um tenant específico
     */
    public static function seedForTenant( int $tenantId ): void
    {
        $now = now();

        // Categorias principais com suas subcategorias
        $categoryData = [
            [
                'slug'          => 'servicos-gerais',
                'name'          => 'Serviços Gerais',
                'subcategories' => [
                    [ 'slug' => 'limpeza-geral', 'name' => 'Limpeza Geral' ],
                    [ 'slug' => 'manutencao-preventiva', 'name' => 'Manutenção Preventiva' ],
                    [ 'slug' => 'reparos-emergenciais', 'name' => 'Reparos Emergenciais' ],
                ]
            ],
            [
                'slug'          => 'construcao-civil',
                'name'          => 'Construção Civil',
                'subcategories' => [
                    [ 'slug' => 'fundacao-estrutura', 'name' => 'Fundação e Estrutura' ],
                    [ 'slug' => 'alvenaria-reboco', 'name' => 'Alvenaria e Reboco' ],
                    [ 'slug' => 'cobertura-telhado', 'name' => 'Cobertura e Telhado' ],
                    [ 'slug' => 'impermeabilizacao', 'name' => 'Impermeabilização' ],
                ]
            ],
            [
                'slug'          => 'instalacoes',
                'name'          => 'Instalações',
                'subcategories' => [
                    [ 'slug' => 'instalacao-eletrica', 'name' => 'Instalação Elétrica' ],
                    [ 'slug' => 'instalacao-hidraulica', 'name' => 'Instalação Hidráulica' ],
                    [ 'slug' => 'instalacao-gas', 'name' => 'Instalação de Gás' ],
                    [ 'slug' => 'instalacao-ar-condicionado', 'name' => 'Ar Condicionado' ],
                ]
            ],
            [
                'slug'          => 'acabamentos',
                'name'          => 'Acabamentos',
                'subcategories' => [
                    [ 'slug' => 'pintura-residencial', 'name' => 'Pintura Residencial' ],
                    [ 'slug' => 'revestimentos-ceramica', 'name' => 'Revestimentos e Cerâmica' ],
                    [ 'slug' => 'pisos-porcelanato', 'name' => 'Pisos e Porcelanato' ],
                    [ 'slug' => 'pisos-laminados', 'name' => 'Pisos Laminados' ],
                ]
            ],
            [
                'slug'          => 'produtos-materiais',
                'name'          => 'Produtos e Materiais',
                'subcategories' => [
                    [ 'slug' => 'materiais-construcao', 'name' => 'Materiais de Construção' ],
                    [ 'slug' => 'ferramentas-equipamentos', 'name' => 'Ferramentas e Equipamentos' ],
                    [ 'slug' => 'tintas-acessorios', 'name' => 'Tintas e Acessórios' ],
                    [ 'slug' => 'eletrica-hidraulica', 'name' => 'Material Elétrico e Hidráulico' ],
                ]
            ],
            [
                'slug'          => 'manutencao-predial',
                'name'          => 'Manutenção Predial',
                'subcategories' => [
                    [ 'slug' => 'manutencao-portoes', 'name' => 'Manutenção de Portões' ],
                    [ 'slug' => 'jardim-paisagismo', 'name' => 'Jardim e Paisagismo' ],
                    [ 'slug' => 'limpeza-predial', 'name' => 'Limpeza Predial' ],
                    [ 'slug' => 'portaria-zeladoria', 'name' => 'Portaria e Zeladoria' ],
                ]
            ],
            [
                'slug'          => 'consultoria-tecnica',
                'name'          => 'Consultoria Técnica',
                'subcategories' => [
                    [ 'slug' => 'consultoria-obras', 'name' => 'Consultoria em Obras' ],
                    [ 'slug' => 'pericias-tecnicas', 'name' => 'Perícias Técnicas' ],
                    [ 'slug' => 'projetos-arquitetura', 'name' => 'Projetos e Arquitetura' ],
                    [ 'slug' => 'orcamentos-tecnicos', 'name' => 'Orçamentos Técnicos' ],
                ]
            ],
            [
                'slug'          => 'servicos-digitais',
                'name'          => 'Serviços Digitais',
                'subcategories' => [
                    [ 'slug' => 'desenvolvimento-web', 'name' => 'Desenvolvimento Web' ],
                    [ 'slug' => 'marketing-digital', 'name' => 'Marketing Digital' ],
                    [ 'slug' => 'gestao-redes-sociais', 'name' => 'Gestão de Redes Sociais' ],
                    [ 'slug' => 'suporte-tecnico', 'name' => 'Suporte Técnico' ],
                ]
            ],
        ];

        $categoryIds = [];

        foreach ( $categoryData as $category ) {
            // Verificar se categoria principal já existe
            $existingMain = DB::table( 'categories' )
                ->where( 'tenant_id', $tenantId )
                ->where( 'slug', $category[ 'slug' ] )
                ->first();

            if ( !$existingMain ) {
                $mainId = DB::table( 'categories' )->insertGetId( [
                    'tenant_id'  => $tenantId,
                    'slug'       => $category[ 'slug' ],
                    'name'       => $category[ 'name' ],
                    'parent_id'  => null,
                    'is_active'  => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ] );
            } else {
                $mainId = $existingMain->id;
            }

            $categoryIds[ $category[ 'slug' ] ] = $mainId;

            // Criar subcategorias
            foreach ( $category[ 'subcategories' ] as $subcategory ) {
                $existingSub = DB::table( 'categories' )
                    ->where( 'tenant_id', $tenantId )
                    ->where( 'slug', $subcategory[ 'slug' ] )
                    ->first();

                if ( !$existingSub ) {
                    DB::table( 'categories' )->insert( [
                        'tenant_id'  => $tenantId,
                        'slug'       => $subcategory[ 'slug' ],
                        'name'       => $subcategory[ 'name' ],
                        'parent_id'  => $mainId,
                        'is_active'  => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ] );
                }
            }
        }

        // Adicionar categorias especiais sem subcategorias
        $specialCategories = [
            [
                'slug' => 'outros-servicos',
                'name' => 'Outros Serviços',
            ],
            [
                'slug' => 'servicos-emergenciais',
                'name' => 'Serviços Emergenciais',
            ],
            [
                'slug' => 'orcamentos-rapidos',
                'name' => 'Orçamentos Rápidos',
            ]
        ];

        foreach ( $specialCategories as $special ) {
            $existing = DB::table( 'categories' )
                ->where( 'tenant_id', $tenantId )
                ->where( 'slug', $special[ 'slug' ] )
                ->first();

            if ( !$existing ) {
                DB::table( 'categories' )->insert( [
                    'tenant_id'  => $tenantId,
                    'slug'       => $special[ 'slug' ],
                    'name'       => $special[ 'name' ],
                    'parent_id'  => null,
                    'is_active'  => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ] );
            }
        }

        self::logCategoryCreation( $tenantId, count( $categoryData ), count( $specialCategories ) );
    }

    /**
     * Log da criação das categorias para auditoria
     */
    private static function logCategoryCreation( int $tenantId, int $mainCategories, int $specialCategories ): void
    {
        // Buscar um usuário ativo do tenant para associar ao log
        $systemUser = DB::table( 'users' )
            ->where( 'tenant_id', $tenantId )
            ->where( 'is_active', true )
            ->first();

        if ( !$systemUser ) {
            // Se não encontrar usuário ativo, usar o primeiro usuário do tenant
            $systemUser = DB::table( 'users' )
                ->where( 'tenant_id', $tenantId )
                ->first();
        }

        DB::table( 'audit_logs' )->insert( [
            'tenant_id'        => $tenantId,
            'user_id'          => $systemUser ? $systemUser->id : 1, // Usar usuário existente ou 1 como fallback
            'action'           => 'seed_categories',
            'model_type'       => 'Category',
            'model_id'         => null,
            'old_values'       => null,
            'new_values'       => json_encode( [
                'main_categories'    => $mainCategories,
                'special_categories' => $specialCategories,
                'total_created'      => ( $mainCategories * 4 ) + $specialCategories // 4 subcategorias por categoria principal
            ] ),
            'ip_address'       => '127.0.0.1',
            'user_agent'       => 'CategorySeeder',
            'description'      => "Categorias criadas automaticamente pelo CategorySeeder - {$mainCategories} principais com subcategorias + {$specialCategories} especiais",
            'severity'         => 'info',
            'category'         => 'data_seeding',
            'is_system_action' => true,
            'created_at'       => now(),
            'updated_at'       => now(),
        ] );
    }

    /**
     * Remove todas as categorias de um tenant (útil para recriar)
     */
    public static function clearCategoriesForTenant( int $tenantId ): void
    {
        $deleted = DB::table( 'categories' )
            ->where( 'tenant_id', $tenantId )
            ->delete();

        self::logCategoryDeletion( $tenantId, $deleted );
    }

    /**
     * Log da remoção das categorias
     */
    private static function logCategoryDeletion( int $tenantId, int $deletedCount ): void
    {
        // Buscar um usuário ativo do tenant para associar ao log
        $systemUser = DB::table( 'users' )
            ->where( 'tenant_id', $tenantId )
            ->where( 'is_active', true )
            ->first();

        if ( !$systemUser ) {
            // Se não encontrar usuário ativo, usar o primeiro usuário do tenant
            $systemUser = DB::table( 'users' )
                ->where( 'tenant_id', $tenantId )
                ->first();
        }

        DB::table( 'audit_logs' )->insert( [
            'tenant_id'        => $tenantId,
            'user_id'          => $systemUser ? $systemUser->id : 1, // Usar usuário existente ou 1 como fallback
            'action'           => 'clear_categories',
            'model_type'       => 'Category',
            'model_id'         => null,
            'old_values'       => json_encode( [ 'total_deleted' => $deletedCount ] ),
            'new_values'       => null,
            'ip_address'       => '127.0.0.1',
            'user_agent'       => 'CategorySeeder',
            'description'      => "Categorias removidas automaticamente pelo CategorySeeder - {$deletedCount} categorias",
            'severity'         => 'warning',
            'category'         => 'data_seeding',
            'is_system_action' => true,
            'created_at'       => now(),
            'updated_at'       => now(),
        ] );
    }

    /**
     * Atualiza categorias existentes para um tenant (adiciona novas, mantém existentes)
     */
    public static function updateCategoriesForTenant( int $tenantId ): void
    {
        self::seedForTenant( $tenantId );
    }

    /**
     * Método estático para uso externo
     */
    public static function runForTenant( int $tenantId ): void
    {
        ( new self )->seedForTenant( $tenantId );
    }

}
