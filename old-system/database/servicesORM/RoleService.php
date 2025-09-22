<?php

namespace app\database\servicesORM;

use app\database\entitiesORM\RoleEntity;
use app\database\repositories\RoleRepository;
use app\database\servicesORM\ActivityService;
use app\enums\OperationStatus;
use app\interfaces\ServiceNoTenantInterface;
use app\support\ServiceResult;
use Doctrine\ORM\EntityManager;
use Exception;

/**
 * Serviço para gerenciar operações relacionadas à entidade RoleEntity.
 *
 * Esta classe implementa a lógica de negócio para roles (funções/papéis),
 * incluindo validação, criação, atualização e exclusão de registros.
 *
 * @package app\database\servicesORM
 */
class RoleService extends BaseNoTenantService implements ServiceNoTenantInterface
{
    private RoleRepositoryInterface $repository;
    private ActivityService         $activityService;

    /**
     * Construtor do serviço.
     *
     * @param EntityManager $entityManager Gerenciador de entidades do Doctrine
     * @param ActivityService $activityService Serviço de atividades para logging
     */
    public function __construct( EntityManager $entityManager, ActivityService $activityService )
    {
        parent::__construct( $entityManager );
        $this->repository      = $entityManager->getRepository( RoleEntity::class);
        $this->activityService = $activityService;
    }

    /**
     * Busca uma entidade pelo seu ID.
     *
     * @param int $id ID da entidade
     * @return ServiceResult Resultado da operação com status, mensagem e dados
     */
    public function getById( mixed $id ): ServiceResult
    {
        try {
            // Buscar role pelo ID
            $entity = $this->repository->findById( $id );

            if ( !$entity ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Role não encontrado.' );
            }

            return ServiceResult::success( $entity, 'Role encontrado com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao buscar role: ' . $e->getMessage() );
        }
    }

    public function updateById( mixed $id, array $data ): ServiceResult
    {
        return $this->update( $id, $data );
    }

    public function deleteById( mixed $id ): ServiceResult
    {
        return $this->delete( $id );
    }

    /**
     * Lista todas as entidades com possibilidade de filtros.
     *
     * @param array<string, mixed> $filters Filtros a serem aplicados
     * @return ServiceResult Resultado da operação com status, mensagem e dados
     */
    public function list( array $filters = [] ): ServiceResult
    {
        try {
            // Buscar roles com filtros opcionais
            $entities = $this->repository->findBy( $filters, [ 'name' => 'ASC' ] );

            if ( empty( $entities ) ) {
                return ServiceResult::success( [], 'Nenhum role encontrado.' );
            }

            return ServiceResult::success( $entities, 'Roles listados com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao listar roles: ' . $e->getMessage() );
        }
    }

    /**
     * Cria uma nova entidade.
     *
     * @param array<string, mixed> $data Dados para criação da entidade
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

            // Gerar slug se não fornecido
            if ( empty( $data[ 'slug' ] ) ) {
                $data[ 'slug' ] = $this->generateSlug( $data[ 'name' ] );
            }

            // Verificar se o slug já existe
            $existingEntities = $this->repository->findBy( [ 'slug' => $data[ 'slug' ] ] );
            if ( !empty( $existingEntities ) ) {
                return ServiceResult::error( OperationStatus::CONFLICT, 'Já existe um role com este slug.' );
            }

            // Prepare data for base create
            $preparedData                = $data;
            $preparedData[ 'description' ] = $data[ 'description' ] ?? '';
            $preparedData[ 'isActive' ]    = $data[ 'isActive' ] ?? true;

            $result = parent::create( $preparedData );
            if ( $result->isSuccess() ) {
                // Post-create logic if needed (e.g., activity log)
                $this->activityService->log( 'Role criado', $result->data );
            }
            return $result;
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao criar role: ' . $e->getMessage() );
        }
    }

    /**
     * Atualiza uma entidade existente.
     *
     * @param int $id ID da entidade
     * @param array<string, mixed> $data Dados para atualização
     * @return ServiceResult Resultado da operação
     */
    public function update( int $id, array $data ): ServiceResult
    {
        try {
            // Buscar entidade existente
            $entity = $this->repository->findById( $id );
            if ( !$entity ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Role não encontrado.' );
            }

            // Validar dados de entrada
            $validation = $this->validate( $data, true );
            if ( !$validation->isSuccess() ) {
                return $validation;
            }

            // Prepare data for base update
            $preparedData                = $data;
            $preparedData[ 'description' ] = $data[ 'description' ] ?? $entity->getDescription();
            $preparedData[ 'isActive' ]    = isset( $data[ 'isActive' ] ) ? $data[ 'isActive' ] : $entity->getIsActive();

            // Handle slug logic
            if ( isset( $data[ 'name' ] ) ) {
                if ( empty( $data[ 'slug' ] ) ) {
                    $newSlug = $this->generateSlug( $data[ 'name' ] );
                    if ( $newSlug !== $entity->getSlug() ) {
                        $existingEntities = $this->repository->findBy( [ 'slug' => $newSlug ] );
                        $slugExists       = false;
                        foreach ( $existingEntities as $existingEntity ) {
                            if ( $existingEntity && $existingEntity->getId() !== $id ) {
                                $slugExists = true;
                                break;
                            }
                        }
                        if ( $slugExists ) {
                            return ServiceResult::error( OperationStatus::CONFLICT, 'Já existe um role com este slug.' );
                        }
                        $preparedData[ 'slug' ] = $newSlug;
                    }
                }
            }

            if ( isset( $data[ 'slug' ] ) ) {
                $existingEntities = $this->repository->findBy( [ 'slug' => $data[ 'slug' ] ] );
                $slugExists       = false;
                foreach ( $existingEntities as $existingEntity ) {
                    if ( $existingEntity && $existingEntity->getId() !== $id ) {
                        $slugExists = true;
                        break;
                    }
                }
                if ( $slugExists ) {
                    return ServiceResult::error( OperationStatus::CONFLICT, 'Já existe um role com este slug.' );
                }
                $preparedData[ 'slug' ] = $data[ 'slug' ];
            }

            $result = parent::update( $id, $preparedData );
            if ( $result->isSuccess() ) {
                $this->activityService->log( 'Role atualizado', $result->data );
            }
            return $result;
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao atualizar role: ' . $e->getMessage() );
        }
    }

    /**
     * Remove uma entidade.
     *
     * @param int $id ID da entidade
     * @return ServiceResult Resultado da operação
     */
    public function delete( int $id ): ServiceResult
    {
        try {
            $entity = $this->repository->findById( $id );
            if ( !$entity ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Role não encontrado.' );
            }

            $result = $this->repository->delete( $id );

            if ( !$result ) {
                return ServiceResult::error( OperationStatus::ERROR, 'Erro ao remover role.' );
            }

            return ServiceResult::success( $entity, 'Role removido com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao remover role: ' . $e->getMessage() );
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
            $errors[] = 'O nome do role é obrigatório.';
        } elseif ( strlen( $data[ 'name' ] ) < 2 ) {
            $errors[] = 'O nome do role deve ter pelo menos 2 caracteres.';
        } elseif ( strlen( $data[ 'name' ] ) > 100 ) {
            $errors[] = 'O nome do role deve ter no máximo 100 caracteres.';
        }

        // Validar slug se fornecido
        if ( !empty( $data[ 'slug' ] ) ) {
            if ( !preg_match( '/^[a-z0-9-]+$/', $data[ 'slug' ] ) ) {
                $errors[] = 'O slug deve conter apenas letras minúsculas, números e hífens.';
            } elseif ( strlen( $data[ 'slug' ] ) > 100 ) {
                $errors[] = 'O slug deve ter no máximo 100 caracteres.';
            }
        }

        // Validar descrição se fornecida
        if ( isset( $data[ 'description' ] ) && strlen( $data[ 'description' ] ) > 500 ) {
            $errors[] = 'A descrição deve ter no máximo 500 caracteres.';
        }

        // Validar isActive se fornecido
        if ( isset( $data[ 'isActive' ] ) && !is_bool( $data[ 'isActive' ] ) ) {
            $errors[] = 'O campo isActive deve ser um valor booleano.';
        }

        if ( !empty( $errors ) ) {
            return ServiceResult::error( OperationStatus::INVALID_DATA, implode( ' ', $errors ) );
        }

        return ServiceResult::success( null, 'Dados válidos.' );
    }

    // Métodos auxiliares privados mantidos para suportar as operações principais

    /**
     * Gera um slug a partir do nome do role.
     *
     * @param string $name Nome do role
     * @return string Slug gerado
     */
    private function generateSlug( string $name ): string
    {
        // Carregar traduções específicas para roles
        $translations = $this->loadRoleTranslations();

        // Tentar encontrar uma tradução
        $translatedSlug = $this->findBestTranslation( $name, $translations );
        if ( $translatedSlug ) {
            return $translatedSlug;
        }

        // Se não encontrou tradução, gerar slug padrão
        return $this->generateDefaultSlug( $name );
    }

    /**
     * Carrega o dicionário de traduções para roles.
     *
     * @return array Dicionário de traduções
     */
    private function loadRoleTranslations(): array
    {
        return [ 
            // Administração
            'administrador'          => 'admin',
            'admin'                  => 'admin',
            'gerente'                => 'manager',
            'supervisor'             => 'supervisor',
            'coordenador'            => 'coordinator',
            'diretor'                => 'director',
            'presidente'             => 'president',
            'ceo'                    => 'ceo',
            'cto'                    => 'cto',
            'cfo'                    => 'cfo',

            // Usuários
            'usuário'                => 'user',
            'cliente'                => 'client',
            'visitante'              => 'visitor',
            'convidado'              => 'guest',
            'membro'                 => 'member',
            'assinante'              => 'subscriber',

            // Técnicos
            'desenvolvedor'          => 'developer',
            'programador'            => 'programmer',
            'analista'               => 'analyst',
            'designer'               => 'designer',
            'arquiteto'              => 'architect',
            'engenheiro'             => 'engineer',
            'técnico'                => 'technician',
            'suporte'                => 'support',
            'helpdesk'               => 'helpdesk',

            // Vendas e Marketing
            'vendedor'               => 'salesperson',
            'consultor'              => 'consultant',
            'representante'          => 'representative',
            'marketing'              => 'marketing',
            'comercial'              => 'sales',
            'atendimento'            => 'customer-service',

            // Financeiro
            'contador'               => 'accountant',
            'financeiro'             => 'financial',
            'tesoureiro'             => 'treasurer',
            'auditor'                => 'auditor',
            'analista financeiro'    => 'financial-analyst',

            // Recursos Humanos
            'rh'                     => 'hr',
            'recursos humanos'       => 'human-resources',
            'recrutador'             => 'recruiter',
            'psicólogo'              => 'psychologist',

            // Operacional
            'operador'               => 'operator',
            'operacional'            => 'operational',
            'produção'               => 'production',
            'qualidade'              => 'quality',
            'logística'              => 'logistics',
            'almoxarife'             => 'warehouse',

            // Jurídico
            'advogado'               => 'lawyer',
            'jurídico'               => 'legal',
            'compliance'             => 'compliance',

            // Educação
            'professor'              => 'teacher',
            'instrutor'              => 'instructor',
            'tutor'                  => 'tutor',
            'coordenador pedagógico' => 'pedagogical-coordinator',

            // Saúde
            'médico'                 => 'doctor',
            'enfermeiro'             => 'nurse',
            'fisioterapeuta'         => 'physiotherapist',
            'psicologo_saude'        => 'psychologist_health',
            'dentista'               => 'dentist',

            // Moderação
            'moderador'              => 'moderator',
            'editor'                 => 'editor',
            'revisor'                => 'reviewer',
            'curador'                => 'curator'
        ];
    }

    /**
     * Encontra a melhor tradução para um nome baseado em palavras-chave.
     *
     * @param string $name Nome original
     * @param array $translations Dicionário de traduções
     * @return string|null Slug traduzido ou null se não encontrado
     */
    private function findBestTranslation( string $name, array $translations ): ?string
    {
        $nameLower = strtolower( $name );

        // Busca exata
        if ( isset( $translations[ $nameLower ] ) ) {
            return $translations[ $nameLower ];
        }

        // Busca por palavras-chave
        foreach ( $translations as $keyword => $translation ) {
            if ( strpos( $nameLower, $keyword ) !== false ) {
                return $translation;
            }
        }

        return null;
    }

    /**
     * Gera um slug padrão a partir de um texto.
     *
     * @param string $text Texto original
     * @return string Slug gerado
     */
    private function generateDefaultSlug( string $text ): string
    {
        // Converter para minúsculas
        $slug = strtolower( $text );

        // Remover acentos
        $slug = iconv( 'UTF-8', 'ASCII//TRANSLIT', $slug );

        // Remover caracteres especiais e substituir por hífen
        $slug = preg_replace( '/[^a-z0-9]+/', '-', $slug );

        // Remover hífens do início e fim
        $slug = trim( $slug, '-' );

        // Limitar o tamanho
        if ( strlen( $slug ) > 100 ) {
            $slug = substr( $slug, 0, 100 );
            $slug = rtrim( $slug, '-' );
        }

        return $slug;
    }

}
