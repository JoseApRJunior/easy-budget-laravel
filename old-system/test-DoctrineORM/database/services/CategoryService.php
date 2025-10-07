<?php

namespace app\database\services;

use app\database\entitiesORM\CategoryEntity;
use app\interfaces\RepositoryNoTenantInterface;
use app\interfaces\ServiceNoTenantInterface;
use core\dbal\EntityNotFound;
use core\library\Session;
use Exception;
use RuntimeException;
use app\support\ServiceResult;
use app\enums\OperationStatus;

/**
 * Classe CategoryService
 *
 * Implementa a interface ServiceNoTenantInterface para fornecer operações de serviço para categorias.
 * Como a entidade Category não possui tenant_id, esta implementação é adequada para entidades sem controle multi-tenant.
 */
class CategoryService implements ServiceNoTenantInterface
{
    /**
     * Usuário autenticado
     * @var mixed
     */
    private mixed $authenticated = null;

    /**
     * Construtor da classe CategoryService
     *
     * @param RepositoryNoTenantInterface $categoryRepository Repositório de categorias
     */
    public function __construct(
        private readonly RepositoryNoTenantInterface $categoryRepository,
    ) {
        if ( Session::has( 'auth' ) ) {
            $this->authenticated = Session::get( 'auth' );
        }
    }

    /**
     * Busca uma categoria pelo seu ID.
     *
     * @param int $id ID da categoria
     * @return CategoryEntity|EntityNotFound A categoria encontrada ou EntityNotFound
     */
    public function getById( int $id ): mixed
    {
        try {
            return $this->categoryRepository->findById( $id );
        } catch ( Exception $e ) {
            throw new RuntimeException( "Falha ao buscar categoria, tente mais tarde ou entre em contato com suporte.", 0, $e );
        }
    }

    /**
     * Lista todas as categorias.
     *
     * @param array<string, mixed> $filters Filtros a serem aplicados
     * @return array<int, CategoryEntity> Lista de categorias
     */
    public function list( array $filters = [] ): array
    {
        try {
            $criteria = [];
            $orderBy  = [ 'name' => 'ASC' ];

            // Aplicar filtros se existirem
            if ( !empty( $filters[ 'name' ] ) ) {
                $criteria[ 'name' ] = $filters[ 'name' ];
            }

            if ( !empty( $filters[ 'slug' ] ) ) {
                $criteria[ 'slug' ] = $filters[ 'slug' ];
            }

            return $this->categoryRepository->findAllByTenant( $criteria, $orderBy );
        } catch ( Exception $e ) {
            throw new RuntimeException( "Falha ao listar categorias, tente mais tarde ou entre em contato com suporte.", 0, $e );
        }
    }

    /**
     * Cria uma nova categoria.
     *
     * @param array<string, mixed> $data Dados para criação da categoria
     * @return array<string, mixed> Resultado da operação
     */
    public function create( array $data ): array
    {
        try {
            // Validar dados de entrada
            $validation = $this->validate( $data );
            if ( !$validation[ 'valid' ] ) {
                return [ 
                    'success' => false,
                    'message' => $validation[ 'message' ],
                    'errors'  => $validation[ 'errors' ]
                ];
            }

            // Criar nova entidade
            $category = new CategoryEntity();
            $category->setName( $data[ 'name' ] );
            $category->setSlug( $data[ 'slug' ] ?? $this->generateSlug( $data[ 'name' ] ) );

            // Salvar no repositório
            return $this->categoryRepository->save( $category );
        } catch ( Exception $e ) {
            return [ 
                'success' => false,
                'message' => "Falha ao criar categoria, tente mais tarde ou entre em contato com suporte.",
                'error'   => $e->getMessage()
            ];
        }
    }

    /**
     * Atualiza uma categoria existente.
     *
     * @param int $id ID da categoria
     * @param array<string, mixed> $data Dados para atualização
     * @return array<string, mixed> Resultado da operação
     */
    public function update( int $id, array $data ): array
    {
        try {
            // Validar dados de entrada
            $validation = $this->validate( $data, true );
            if ( !$validation[ 'valid' ] ) {
                return [ 
                    'success' => false,
                    'message' => $validation[ 'message' ],
                    'errors'  => $validation[ 'errors' ]
                ];
            }

            // Buscar categoria existente
            $category = $this->categoryRepository->findById( $id );
            if ( $category instanceof EntityNotFound ) {
                return [ 
                    'success' => false,
                    'message' => "Categoria não encontrada."
                ];
            }

            // Atualizar dados
            /** @var CategoryEntity $category */
            $category->setName( $data[ 'name' ] );
            if ( isset( $data[ 'slug' ] ) ) {
                $category->setSlug( $data[ 'slug' ] );
            } elseif ( $category->getName() !== $data[ 'name' ] ) {
                // Atualizar slug apenas se o nome foi alterado e slug não foi fornecido
                $category->setSlug( $this->generateSlug( $data[ 'name' ] ) );
            }

            // Salvar no repositório
            return $this->categoryRepository->save( $category );
        } catch ( Exception $e ) {
            return [ 
                'success' => false,
                'message' => "Falha ao atualizar categoria, tente mais tarde ou entre em contato com suporte.",
                'error'   => $e->getMessage()
            ];
        }
    }

    /**
     * Remove uma categoria.
     *
     * @param int $id ID da categoria
     * @return array<string, mixed> Resultado da operação
     */
    public function delete( int $id ): array
    {
        try {
            return $this->categoryRepository->delete( $id );
        } catch ( Exception $e ) {
            return [ 
                'success' => false,
                'message' => "Falha ao excluir categoria, tente mais tarde ou entre em contato com suporte.",
                'error'   => $e->getMessage()
            ];
        }
    }

    /**
     * Valida os dados de entrada para operações de criação ou atualização.
     *
     * @param array<string, mixed> $data Dados a serem validados
     * @param bool $isUpdate Indica se é uma operação de atualização
     * @return ServiceResult Resultado da validação
     */
    public function validate( array $data, bool $isUpdate = false ): ServiceResult
    {
        $errors = [];

        // Validar nome
        if ( empty( $data[ 'name' ] ) ) {
            $errors[] = "O nome da categoria é obrigatório.";
        } elseif ( strlen( $data[ 'name' ] ) > 100 ) {
            $errors[] = "O nome da categoria deve ter no máximo 100 caracteres.";
        }

        // Validar slug (se fornecido)
        if ( isset( $data[ 'slug' ] ) ) {
            if ( empty( $data[ 'slug' ] ) ) {
                $errors[] = "O slug não pode estar vazio quando fornecido.";
            } elseif ( strlen( $data[ 'slug' ] ) > 100 ) {
                $errors[] = "O slug deve ter no máximo 100 caracteres.";
            } elseif ( !preg_match( '/^[a-z0-9-]+$/', $data[ 'slug' ] ) ) {
                $errors[] = "O slug deve conter apenas letras minúsculas, números e hífens.";
            }
        }

        if ( count( $errors ) > 0 ) {
            return ServiceResult::error(
                OperationStatus::INVALID_DATA,
                'Dados inválidos: ' . implode( ', ', $errors )
            );
        }

        return ServiceResult::success( [], 'Dados válidos.' );
    }

    /**
     * Gera um slug a partir do nome da categoria.
     * 
     * Este método implementa tradução automática do português para inglês,
     * garantindo que os slugs sejam sempre salvos em inglês, independentemente
     * do idioma de entrada.
     *
     * @param string $name Nome da categoria (pode estar em português)
     * @return string Slug gerado em inglês minúsculo
     */
    private function generateSlug( string $name ): string
    {
        // Carregar dicionário de traduções
        $translations = $this->loadCategoryTranslations();
        
        // Converter para minúsculas para comparação
        $nameKey = mb_strtolower( trim( $name ), 'UTF-8' );
        
        // Verificar se existe tradução direta
        if ( isset( $translations[ $nameKey ] ) ) {
            return $translations[ $nameKey ];
        }
        
        // Se não encontrou tradução direta, tentar busca por palavras-chave
        $translatedSlug = $this->findBestTranslation( $nameKey, $translations );
        
        if ( $translatedSlug ) {
            return $translatedSlug;
        }
        
        // Se não encontrou tradução, gerar slug padrão em inglês
        return $this->generateDefaultSlug( $name );
    }
    
    /**
     * Carrega o dicionário de traduções de categorias.
     *
     * @return array<string, string> Dicionário de traduções
     */
    private function loadCategoryTranslations(): array
    {
        $translationsFile = BASE_PATH . '/config/category_translations.php';
        
        if ( file_exists( $translationsFile ) ) {
            return require $translationsFile;
        }
        
        return [];
    }
    
    /**
     * Busca a melhor tradução baseada em palavras-chave.
     *
     * @param string $name Nome da categoria em minúsculas
     * @param array<string, string> $translations Dicionário de traduções
     * @return string|null Slug traduzido ou null se não encontrado
     */
    private function findBestTranslation( string $name, array $translations ): ?string
    {
        // Buscar por correspondência parcial (palavras-chave)
        foreach ( $translations as $portuguese => $english ) {
            // Verificar se o nome contém a palavra-chave portuguesa
            if ( str_contains( $name, $portuguese ) || str_contains( $portuguese, $name ) ) {
                return $english;
            }
        }
        
        return null;
    }
    
    /**
     * Gera um slug padrão quando não há tradução disponível.
     * 
     * Este método converte o texto para um formato de slug válido,
     * removendo acentos e caracteres especiais.
     *
     * @param string $name Nome original da categoria
     * @return string Slug gerado
     */
    private function generateDefaultSlug( string $name ): string
    {
        // Converter para minúsculas
        $slug = mb_strtolower( $name, 'UTF-8' );

        // Remover acentos
        $slug = preg_replace( '/[áàãâä]/u', 'a', $slug );
        $slug = preg_replace( '/[éèêë]/u', 'e', $slug );
        $slug = preg_replace( '/[íìîï]/u', 'i', $slug );
        $slug = preg_replace( '/[óòõôö]/u', 'o', $slug );
        $slug = preg_replace( '/[úùûü]/u', 'u', $slug );
        $slug = preg_replace( '/[ç]/u', 'c', $slug );

        // Substituir espaços e caracteres especiais por hífens
        $slug = preg_replace( '/[^a-z0-9\-]/', '-', $slug );

        // Remover hífens duplicados
        $slug = preg_replace( '/-+/', '-', $slug );

        // Remover hífens no início e fim
        $slug = trim( $slug, '-' );

        return $slug;
    }

}
