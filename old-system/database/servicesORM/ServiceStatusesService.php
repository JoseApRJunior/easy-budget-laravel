<?php

namespace app\database\servicesORM;

use app\database\entitiesORM\ServiceStatusesEntity;
use app\database\repositories\ServiceStatusesRepository;
use app\interfaces\ServiceNoTenantInterface;
use app\support\ServiceResult;
use app\enums\OperationStatus;
use Exception;

/**
 * Serviço para gerenciamento de status de serviços
 * Implementa ServiceNoTenantInterface para operações sem tenant_id
 */
class ServiceStatusesService implements ServiceNoTenantInterface
{
    public function __construct(
        private ServiceStatusesRepository $serviceStatusesRepository,
    ) {}

    /**
     * Busca um status de serviço pelo seu ID.
     *
     * @param int $id ID do status de serviço
     * @return ServiceResult Resultado da operação
     */
    public function getById( int $id ): ServiceResult
    {
        try {
            $entity = $this->serviceStatusesRepository->findById( $id );

            if ( $entity === null ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Status de serviço não encontrado.' );
            }

            return ServiceResult::success( $entity, 'Status de serviço encontrado com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao buscar status de serviço: ' . $e->getMessage() );
        }
    }

    /**
     * Lista todos os status de serviços.
     *
     * @param array<string, mixed> $filters Filtros a serem aplicados
     * @return ServiceResult Resultado da operação
     */
    public function list( array $filters = [] ): ServiceResult
    {
        try {
            $criteria = [];
            $orderBy  = [ 'orderIndex' => 'ASC', 'name' => 'ASC' ];

            // Aplicar filtros se existirem
            if ( !empty( $filters[ 'name' ] ) ) {
                $criteria[ 'name' ] = $filters[ 'name' ];
            }

            if ( !empty( $filters[ 'slug' ] ) ) {
                $criteria[ 'slug' ] = $filters[ 'slug' ];
            }

            if ( isset( $filters[ 'isActive' ] ) ) {
                $criteria[ 'isActive' ] = $filters[ 'isActive' ];
            }

            $entities = $this->serviceStatusesRepository->findAll( $criteria, $orderBy );

            return ServiceResult::success( $entities, 'Status de serviços listados com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao listar status de serviços: ' . $e->getMessage() );
        }
    }

    /**
     * Cria um novo status de serviço.
     *
     * @param array<string, mixed> $data Dados para criação do status
     * @return ServiceResult Resultado da operação
     */
    public function create( array $data ): ServiceResult
    {
        try {
            // Validar dados de entrada
            $validation = $this->validate( $data );
            if ( !$validation->isSuccess() ) {
                return $validation;
            }

            // Criar nova entidade
            $entity = new ServiceStatusesEntity(
                $data[ 'slug' ] ?? $this->generateSlug( $data[ 'name' ] ),
                $data[ 'name' ],
                $data[ 'description' ] ?? '',
                $data[ 'color' ] ?? '#6c757d',
                $data[ 'icon' ] ?? 'fas fa-circle',
                $data[ 'orderIndex' ] ?? 0,
                $data[ 'isActive' ] ?? true
            );

            // Salvar via repository
            $result = $this->serviceStatusesRepository->save( $entity );

            if ( $result !== false ) {
                return ServiceResult::success( $result, 'Status de serviço criado com sucesso.' );
            } else {
                return ServiceResult::error( OperationStatus::ERROR, 'Falha ao salvar status de serviço no banco de dados.' );
            }
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao criar status de serviço: ' . $e->getMessage() );
        }
    }

    /**
     * Atualiza um status de serviço existente.
     *
     * @param int $id ID do status
     * @param array<string, mixed> $data Dados para atualização
     * @return ServiceResult Resultado da operação
     */
    public function update( int $id, array $data ): ServiceResult
    {
        try {
            // Validar dados de entrada
            $validation = $this->validate( $data, true );
            if ( !$validation->isSuccess() ) {
                return $validation;
            }

            // Buscar status existente
            $entity = $this->serviceStatusesRepository->findById( $id );
            if ( $entity === null ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Status de serviço não encontrado.' );
            }

            // Atualizar dados
            /** @var ServiceStatusesEntity $entity */
            $oldName = $entity->getName();
            $entity->setName( $data[ 'name' ] );

            if ( isset( $data[ 'slug' ] ) ) {
                $entity->setSlug( $data[ 'slug' ] );
            } elseif ( $oldName !== $data[ 'name' ] ) {
                // Atualizar slug apenas se o nome foi alterado e slug não foi fornecido
                $entity->setSlug( $this->generateSlug( $data[ 'name' ] ) );
            }

            if ( isset( $data[ 'description' ] ) ) {
                $entity->setDescription( $data[ 'description' ] );
            }

            if ( isset( $data[ 'color' ] ) ) {
                $entity->setColor( $data[ 'color' ] );
            }

            if ( isset( $data[ 'icon' ] ) ) {
                $entity->setIcon( $data[ 'icon' ] );
            }

            if ( isset( $data[ 'orderIndex' ] ) ) {
                $entity->setOrderIndex( $data[ 'orderIndex' ] );
            }

            if ( isset( $data[ 'isActive' ] ) ) {
                $entity->setIsActive( $data[ 'isActive' ] );
            }

            // Salvar via repository
            $result = $this->serviceStatusesRepository->save( $entity );

            if ( $result !== false ) {
                return ServiceResult::success( $result, 'Status de serviço atualizado com sucesso.' );
            } else {
                return ServiceResult::error( OperationStatus::ERROR, 'Falha ao salvar status de serviço no banco de dados.' );
            }
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao atualizar status de serviço: ' . $e->getMessage() );
        }
    }

    /**
     * Remove um status de serviço.
     *
     * @param int $id ID do status
     * @return ServiceResult Resultado da operação
     */
    public function delete( int $id ): ServiceResult
    {
        try {
            // Verificar se a entidade existe antes de deletar
            $entity = $this->serviceStatusesRepository->findById( $id );
            if ( $entity === null ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Status de serviço não encontrado.' );
            }

            // Executar exclusão via repository
            $result = $this->serviceStatusesRepository->delete( $id );

            if ( $result ) {
                return ServiceResult::success( null, 'Status de serviço removido com sucesso.' );
            } else {
                return ServiceResult::error( OperationStatus::ERROR, 'Falha ao remover status de serviço do banco de dados.' );
            }
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao excluir status de serviço: ' . $e->getMessage() );
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
            $errors[] = "O nome do status é obrigatório.";
        } elseif ( strlen( $data[ 'name' ] ) > 100 ) {
            $errors[] = "O nome do status deve ter no máximo 100 caracteres.";
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

        // Validar descrição (se fornecida)
        if ( isset( $data[ 'description' ] ) && strlen( $data[ 'description' ] ) > 255 ) {
            $errors[] = "A descrição deve ter no máximo 255 caracteres.";
        }

        // Validar cor (se fornecida)
        if ( isset( $data[ 'color' ] ) ) {
            if ( !preg_match( '/^#[0-9A-Fa-f]{6}$/', $data[ 'color' ] ) ) {
                $errors[] = "A cor deve estar no formato hexadecimal (#RRGGBB).";
            }
        }

        // Validar ícone (se fornecido)
        if ( isset( $data[ 'icon' ] ) && strlen( $data[ 'icon' ] ) > 50 ) {
            $errors[] = "O ícone deve ter no máximo 50 caracteres.";
        }

        // Validar orderIndex (se fornecido)
        if ( isset( $data[ 'orderIndex' ] ) && !is_numeric( $data[ 'orderIndex' ] ) ) {
            $errors[] = "O índice de ordenação deve ser um número.";
        }

        // Validar isActive (se fornecido)
        if ( isset( $data[ 'isActive' ] ) && !is_bool( $data[ 'isActive' ] ) ) {
            $errors[] = "O campo ativo deve ser verdadeiro ou falso.";
        }

        if ( count( $errors ) > 0 ) {
            return ServiceResult::error( OperationStatus::INVALID_DATA, "Dados inválidos: " . implode( ', ', $errors ) );
        }

        return ServiceResult::success( null, "Dados válidos." );
    }

    /**
     * Gera um slug a partir do nome do status.
     *
     * @param string $name Nome do status
     * @return string Slug gerado
     */
    private function generateSlug( string $name ): string
    {
        // Converter para minúsculas
        $slug = mb_strtolower( trim( $name ), 'UTF-8' );

        // Remover acentos
        $slug = iconv( 'UTF-8', 'ASCII//TRANSLIT', $slug );

        // Substituir espaços e caracteres especiais por hífens
        $slug = preg_replace( '/[^a-z0-9]+/', '-', $slug );

        // Remover hífens duplos e das extremidades
        $slug = trim( preg_replace( '/-+/', '-', $slug ), '-' );

        return $slug;
    }

}
