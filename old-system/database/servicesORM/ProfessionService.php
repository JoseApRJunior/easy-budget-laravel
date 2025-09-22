<?php

namespace app\database\servicesORM;

use app\interfaces\ServiceNoTenantInterface;
use app\support\ServiceResult;
use app\enums\OperationStatus;
use core\dbal\EntityNotFound;
use core\library\Session;
use Exception;
use app\database\entitiesORM\ProfessionEntity;
use app\database\repositories\ProfessionRepository;

/**
 * Classe ProfessionService
 *
 * Implementa a interface ServiceNoTenantInterface para fornecer operações de serviço para profissões.
 * Como a entidade Profession não possui tenant_id, esta implementação é adequada para entidades sem controle multi-tenant.
 */
class ProfessionService implements ServiceNoTenantInterface
{
    /**
     * Usuário autenticado
     * @var mixed
     */
    private mixed $authenticated = null;

    /**
     * Construtor da classe ProfessionService
     *
     * @param ProfessionRepository $professionRepository Repositório de profissões
     */
    public function __construct(
        private readonly ProfessionRepository $professionRepository,
    ) {
        if ( Session::has( 'auth' ) ) {
            $this->authenticated = Session::get( 'auth' );
        }
    }

    /**
     * Busca uma profissão pelo seu ID.
     *
     * @param int $id ID da profissão
     * @return ServiceResult Resultado da operação
     */
    public function getById( int $id ): ServiceResult
    {
        try {
            $entity = $this->professionRepository->findById( $id );

            if ( $entity === null ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Profissão não encontrada.' );
            }

            return ServiceResult::success( $entity, 'Profissão encontrada com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao buscar profissão: ' . $e->getMessage() );
        }
    }

    /**
     * Lista todas as profissões.
     *
     * @param array<string, mixed> $filters Filtros a serem aplicados
     * @return ServiceResult Resultado da operação
     */
    public function list( array $filters = [] ): ServiceResult
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

            if ( isset( $filters[ 'is_active' ] ) ) {
                $criteria[ 'isActive' ] = (bool) $filters[ 'is_active' ];
            }

            $entities = $this->professionRepository->findAll( $criteria, $orderBy );

            return ServiceResult::success( $entities, 'Profissões listadas com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao listar profissões: ' . $e->getMessage() );
        }
    }

    /**
     * Cria uma nova profissão.
     *
     * @param array<string, mixed> $data Dados para criação da profissão
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
            $entity = new ProfessionEntity();
            $entity->setName( $data[ 'name' ] );
            $entity->setSlug( $data[ 'slug' ] ?? $this->generateSlug( $data[ 'name' ] ) );
            $entity->setIsActive( $data[ 'is_active' ] ?? true );

            // Salvar no repositório
            $result = $this->professionRepository->save( $entity );

            return ServiceResult::success( $result, 'Profissão criada com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao criar profissão: ' . $e->getMessage() );
        }
    }

    /**
     * Atualiza uma profissão existente.
     *
     * @param int $id ID da profissão
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

            // Buscar profissão existente
            $entity = $this->professionRepository->findById( $id );
            if ( $entity === null ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Profissão não encontrada.' );
            }

            // Atualizar dados
            /** @var ProfessionEntity $entity */
            $oldName = $entity->getName();
            $entity->setName( $data[ 'name' ] );

            if ( isset( $data[ 'slug' ] ) ) {
                $entity->setSlug( $data[ 'slug' ] );
            } elseif ( $oldName !== $data[ 'name' ] ) {
                // Atualizar slug apenas se o nome foi alterado e slug não foi fornecido
                $entity->setSlug( $this->generateSlug( $data[ 'name' ] ) );
            }

            if ( isset( $data[ 'is_active' ] ) ) {
                $entity->setIsActive( (bool) $data[ 'is_active' ] );
            }

            // Salvar no repositório
            $result = $this->professionRepository->save( $entity );

            return ServiceResult::success( $result, 'Profissão atualizada com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao atualizar profissão: ' . $e->getMessage() );
        }
    }

    /**
     * Remove uma profissão.
     *
     * @param int $id ID da profissão
     * @return ServiceResult Resultado da operação
     */
    public function delete( int $id ): ServiceResult
    {
        try {
            // Buscar profissão existente
            $entity = $this->professionRepository->findById( $id );
            if ( $entity === null ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Profissão não encontrada.' );
            }

            // Remover do repositório
            $result = $this->professionRepository->delete( $id );

            if ( $result ) {
                return ServiceResult::success( null, 'Profissão removida com sucesso.' );
            } else {
                return ServiceResult::error( OperationStatus::ERROR, 'Falha ao remover profissão.' );
            }
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao excluir profissão: ' . $e->getMessage() );
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
            $errors[] = "O nome da profissão é obrigatório.";
        } elseif ( strlen( $data[ 'name' ] ) > 100 ) {
            $errors[] = "O nome da profissão deve ter no máximo 100 caracteres.";
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

        // Validar is_active (se fornecido)
        if ( isset( $data[ 'is_active' ] ) && !is_bool( $data[ 'is_active' ] ) && !in_array( $data[ 'is_active' ], [ '0', '1', 0, 1, 'true', 'false' ] ) ) {
            $errors[] = "O campo ativo deve ser um valor booleano válido.";
        }

        if ( count( $errors ) > 0 ) {
            return ServiceResult::error( OperationStatus::INVALID_DATA, "Dados inválidos: " . implode( ', ', $errors ) );
        }

        return ServiceResult::success( null, "Dados válidos." );
    }

    /**
     * Gera um slug a partir do nome da profissão.
     *
     * @param string $name Nome da profissão
     * @return string Slug gerado
     */
    private function generateSlug( string $name ): string
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

    // Métodos específicos mantidos por compatibilidade

    /**
     * Lista apenas profissões ativas.
     *
     * @return ServiceResult Lista de profissões ativas
     */
    public function listActive(): ServiceResult
    {
        try {
            $entities = $this->professionRepository->findActiveProfessions( [ 'name' => 'ASC' ] );
            return ServiceResult::success( $entities, 'Profissões ativas listadas com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao listar profissões ativas: ' . $e->getMessage() );
        }
    }

    /**
     * Busca todas as profissões.
     *
     * @return ServiceResult Resultado da operação com status, mensagem e dados
     */
    public function findAll(): ServiceResult
    {
        return $this->list();
    }

    /**
     * Busca uma profissão pelo slug.
     *
     * @param string $slug Slug da profissão
     * @return ServiceResult A profissão encontrada ou erro
     */
    public function getBySlug( string $slug ): ServiceResult
    {
        try {
            $entity = $this->professionRepository->findBySlug( $slug );

            if ( $entity === null ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Profissão não encontrada.' );
            }

            return ServiceResult::success( $entity, 'Profissão encontrada com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao buscar profissão pelo slug: ' . $e->getMessage() );
        }
    }

}
