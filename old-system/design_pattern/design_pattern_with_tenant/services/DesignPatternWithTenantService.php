<?php

namespace design_patern\design_pattern_with_tenant\services;

use app\interfaces\ServiceInterface;
use app\support\ServiceResult;
use core\dbal\EntityNotFound;
use app\enums\OperationStatus;
use Exception;
use design_patern\design_pattern_with_tenant\entities\DesignPatternWithTenantEntity;
use design_patern\design_pattern_with_tenant\repositories\DesignPatternWithTenantRepository;

/**
 * Padrão de Service WithTenant - Easy Budget
 *
 * PADRÕES IMPLEMENTADOS:
 * ✅ Sempre retorna ServiceResult - Consistência em todo o projeto
 * ✅ Implementa ServiceInterface - Padronização de contratos multi-tenant
 * ✅ Métodos *ByTenantId - Controle rigoroso de tenant
 * ✅ Encapsula regras de negócio - Lógica centralizada
 * ✅ Validação de dados com tenant - Métodos validate() multi-tenant
 * ✅ Comentários em português brasileiro - Padrão do projeto
 * ✅ Tratamento estruturado de erros - Status e mensagens padronizadas
 * ✅ Trabalha com retorno EntityORMInterface dos repositories
 * ✅ Mensagens contextuais específicas - "Design Pattern WithTenant"
 * ✅ Validação aprimorada de null - Tratamento consistente de resultados vazios
 * ✅ Logs de auditoria obrigatórios - Rastreabilidade por tenant
 * ✅ Validação de segurança multi-tenant - Verificação de propriedade
 *
 * BENEFÍCIOS:
 * - Isolamento completo de dados entre tenants
 * - Encapsulamento estruturado de status, mensagens e dados
 * - Consistência na manipulação de erros
 * - Fácil integração com controllers
 * - Reutilização de lógica de negócio
 * - Mensagens mais intuitivas para o usuário
 * - Auditoria completa com rastreabilidade por tenant
 * - Validação de segurança em todas as operações
 */
class DesignPatternWithTenantService implements ServiceInterface
{
    private DesignPatternWithTenantRepository $designPatternRepository;

    public function __construct( DesignPatternWithTenantRepository $designPatternRepository )
    {
        $this->designPatternRepository = $designPatternRepository;
    }

    /**
     * Busca uma entidade pelo ID e tenant.
     *
     * @param int $id ID da entidade
     * @param int $tenant_id ID do tenant
     * @return ServiceResult Resultado com entidade ou erro
     */
    public function getByIdAndTenantId( int $id, int $tenant_id ): ServiceResult
    {
        try {
            $entity = $this->designPatternRepository->findByIdAndTenantId( $id, $tenant_id );

            if ( $entity === null ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Design Pattern WithTenant não encontrado no tenant especificado.' );
            }

            // Validação adicional de segurança
            /** @var DesignPatternWithTenantEntity $entity */
            if ( !$entity->belongsToTenant( $tenant_id ) ) {
                return ServiceResult::error( OperationStatus::FORBIDDEN, 'Acesso negado: Entidade não pertence ao tenant especificado.' );
            }

            return ServiceResult::success( $entity, 'Design Pattern WithTenant encontrado com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao buscar Design Pattern WithTenant: ' . $e->getMessage() );
        }
    }

    /**
     * Lista todas as entidades de um tenant com filtros opcionais.
     *
     * @param int $tenant_id ID do tenant
     * @param array<string, mixed> $filters Filtros de busca
     * @return ServiceResult Resultado com lista de entidades
     */
    public function listByTenantId( int $tenant_id, array $filters = [] ): ServiceResult
    {
        try {
            $criteria = [];
            $orderBy  = [ 'name' => 'ASC' ];

            // Aplicar filtros conforme necessário
            if ( !empty( $filters[ 'name' ] ) ) {
                $criteria[ 'name' ] = $filters[ 'name' ];
            }

            if ( isset( $filters[ 'active' ] ) ) {
                $criteria[ 'active' ] = (bool) $filters[ 'active' ];
            }

            // Buscar por pesquisa textual se fornecida
            if ( !empty( $filters[ 'search' ] ) ) {
                $entities = $this->designPatternRepository->searchByTenantId(
                    $tenant_id,
                    $filters[ 'search' ],
                    $orderBy,
                    $filters[ 'limit' ] ?? null
                );
            } else {
                $entities = $this->designPatternRepository->findAllByTenantId(
                    $tenant_id,
                    $criteria,
                    $orderBy,
                    $filters[ 'limit' ] ?? null,
                    $filters[ 'offset' ] ?? null
                );
            }

            // Retorna sucesso mesmo que a lista esteja vazia (é um resultado válido)
            if ( $entities === null ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Design Pattern WithTenant não encontrado no tenant especificado.' );
            }

            return ServiceResult::success( $entities, 'Design Pattern WithTenant listados com sucesso.' );

        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao listar entidades do tenant: ' . $e->getMessage() );
        }
    }

    /**
     * Cria uma nova entidade para o tenant.
     *
     * @param array<string, mixed> $data Dados para criação
     * @param int $tenant_id ID do tenant
     * @return ServiceResult Resultado da operação
     */
    public function createByTenantId( array $data, int $tenant_id ): ServiceResult
    {
        try {
            // Validar dados de entrada
            $validation = $this->validateForTenant( $data, $tenant_id );
            if ( !$validation->isSuccess() ) {
                return $validation;
            }

            // Criar nova entidade
            $entity = new DesignPatternWithTenantEntity();
            $entity->setTenantId( $tenant_id );
            $entity->setName( $data[ 'name' ] );

            if ( isset( $data[ 'description' ] ) ) {
                $entity->setDescription( $data[ 'description' ] );
            }

            // Gerar slug automaticamente se não fornecido
            if ( isset( $data[ 'slug' ] ) ) {
                // Verificar se slug é único no tenant
                if ( !$this->designPatternRepository->isSlugUniqueInTenant( $data[ 'slug' ], $tenant_id ) ) {
                    return ServiceResult::error( OperationStatus::INVALID_DATA, 'Slug já existe neste tenant.' );
                }
                $entity->setSlug( $data[ 'slug' ] );
            } else {
                $generatedSlug = $this->generateUniqueSlugForTenant( $data[ 'name' ], $tenant_id );
                $entity->setSlug( $generatedSlug );
            }

            if ( isset( $data[ 'active' ] ) ) {
                $entity->setActive( (bool) $data[ 'active' ] );
            }

            // Salvar via repository
            $result = $this->designPatternRepository->save( $entity, $tenant_id );

            return ServiceResult::success( $result, 'Design Pattern WithTenant criado com sucesso.' );

        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao criar Design Pattern WithTenant: ' . $e->getMessage() );
        }
    }

    /**
     * Atualiza uma entidade existente do tenant.
     *
     * @param int $id ID da entidade
     * @param int $tenant_id ID do tenant
     * @param array<string, mixed> $data Dados para atualização
     * @return ServiceResult Resultado da operação
     */
    public function updateByIdAndTenantId( int $id, int $tenant_id, array $data ): ServiceResult
    {
        try {
            // Validar dados de entrada
            $validation = $this->validateForTenant( $data, $tenant_id, true );
            if ( !$validation->isSuccess() ) {
                return $validation;
            }

            // Buscar entidade existente
            $entity = $this->designPatternRepository->findByIdAndTenantId( $id, $tenant_id );
            if ( $entity instanceof EntityNotFound || $entity === null ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Design Pattern WithTenant não encontrado no tenant especificado.' );
            }

            /** @var DesignPatternWithTenantEntity $entity */
            // Validação adicional de segurança
            $entity->validateTenantAccess( $tenant_id );

            // Capturar dados originais para log
            $originalData = $entity->jsonSerialize();

            // Atualizar dados
            $oldName = $entity->getName();
            $entity->setName( $data[ 'name' ] );

            if ( isset( $data[ 'description' ] ) ) {
                $entity->setDescription( $data[ 'description' ] );
            }

            // Atualizar slug se necessário
            if ( isset( $data[ 'slug' ] ) ) {
                // Verificar se slug é único no tenant (excluindo entidade atual)
                if ( !$this->designPatternRepository->isSlugUniqueInTenant( $data[ 'slug' ], $tenant_id, $id ) ) {
                    return ServiceResult::error( OperationStatus::INVALID_DATA, 'Slug já existe neste tenant.' );
                }
                $entity->setSlug( $data[ 'slug' ] );
            } elseif ( $oldName !== $data[ 'name' ] ) {
                $generatedSlug = $this->generateUniqueSlugForTenant( $data[ 'name' ], $tenant_id, $id );
                $entity->setSlug( $generatedSlug );
            }

            if ( isset( $data[ 'active' ] ) ) {
                $entity->setActive( (bool) $data[ 'active' ] );
            }

            // Salvar via repository
            $result = $this->designPatternRepository->save( $entity, $tenant_id );

            return ServiceResult::success( $result, 'Design Pattern WithTenant atualizado com sucesso.' );

        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao atualizar Design Pattern WithTenant: ' . $e->getMessage() );
        }
    }

    /**
     * Remove uma entidade do tenant.
     *
     * @param int $id ID da entidade
     * @param int $tenant_id ID do tenant
     * @return ServiceResult Resultado da operação
     */
    public function deleteByIdAndTenantId( int $id, int $tenant_id ): ServiceResult
    {
        try {
            // Verificar se a entidade existe e pertence ao tenant
            $entity = $this->designPatternRepository->findByIdAndTenantId( $id, $tenant_id );
            if ( $entity === null ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Design Pattern WithTenant não encontrado no tenant especificado.' );
            }

            /** @var DesignPatternWithTenantEntity $entity */
            // Validação adicional de segurança
            $entity->validateTenantAccess( $tenant_id );

            // Capturar dados para log antes da exclusão
            $entityData = $entity->jsonSerialize();

            // Executar exclusão via repository
            $result = $this->designPatternRepository->deleteByIdAndTenantId( $id, $tenant_id );

            if ( $result ) {

                return ServiceResult::success( null, 'Design Pattern WithTenant removido com sucesso.' );
            } else {
                return ServiceResult::error( OperationStatus::ERROR, 'Falha ao remover Design Pattern WithTenant do banco de dados.' );
            }
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao excluir Design Pattern WithTenant: ' . $e->getMessage() );
        }
    }

    /**
     * Valida os dados de entrada para operações de criação ou atualização no tenant.
     *
     * @param array<string, mixed> $data Dados a serem validados
     * @param int $tenant_id ID do tenant
     * @param bool $isUpdate Indica se é uma operação de atualização
     * @return ServiceResult Resultado da validação
     */
    public function validateForTenant( array $data, int $tenant_id, bool $isUpdate = false ): ServiceResult
    {
        $errors = [];

        // Validar tenant_id
        if ( $tenant_id <= 0 ) {
            $errors[] = "ID do tenant deve ser um número positivo.";
        }

        // Validar nome
        if ( empty( $data[ 'name' ] ) ) {
            $errors[] = "O nome da entidade é obrigatório.";
        } elseif ( strlen( $data[ 'name' ] ) > 100 ) {
            $errors[] = "O nome da entidade deve ter no máximo 100 caracteres.";
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
        if ( isset( $data[ 'description' ] ) && strlen( $data[ 'description' ] ) > 500 ) {
            $errors[] = "A descrição deve ter no máximo 500 caracteres.";
        }

        if ( count( $errors ) > 0 ) {
            return ServiceResult::error( OperationStatus::INVALID_DATA, "Dados inválidos para tenant {$tenant_id}: " . implode( ', ', $errors ) );
        }

        return ServiceResult::success( null, "Dados válidos para tenant {$tenant_id}." );
    }

    /**
     * Gera um slug único para o tenant.
     *
     * @param string $name Nome da entidade
     * @param int $tenant_id ID do tenant
     * @param int|null $excludeId ID da entidade a excluir da verificação (para updates)
     * @return string Slug único gerado
     */
    private function generateUniqueSlugForTenant( string $name, int $tenant_id, ?int $excludeId = null ): string
    {
        $baseSlug = $this->generateSlug( $name );
        $slug     = $baseSlug;
        $counter  = 1;

        // Verificar unicidade no tenant
        while ( !$this->designPatternRepository->isSlugUniqueInTenant( $slug, $tenant_id, $excludeId ) ) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Gera um slug a partir do nome.
     *
     * @param string $name Nome da entidade
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

    /**
     * Método validate da interface base (não implementado para WithTenant).
     *
     * Use validateForTenant() em vez deste método.
     *
     * @param array<string, mixed> $data Dados a serem validados
     * @param bool $isUpdate Indica se é uma operação de atualização
     * @return ServiceResult Resultado da validação
     */
    public function validate( array $data, bool $isUpdate = false ): ServiceResult
    {
        // Redirecionar para validateForTenant com tenant_id padrão
        $tenant_id = $data[ 'tenant_id' ] ?? 0;
        return $this->validateForTenant( $data, $tenant_id, $isUpdate );
    }

}

/*
EXEMPLOS DE USO NO WITHTENANT:

1. Criação com auditoria automática
$serviceResult = $designPatternService->createByTenantId([
    'name' => 'Nova Entidade',
    'description' => 'Descrição da entidade',
    'active' => true
], $tenant_id, $authenticatedUser);

if ($serviceResult->isSuccess()) {
    $entity = $serviceResult->data; // Entidade completa com tenant_id, ID, timestamps, slug
    echo "Entidade criada: {$entity->getName()} (ID: {$entity->getId()})";
    echo "Tenant: {$entity->getTenantId()}";
    echo "Slug gerado: {$entity->getSlug()}";
    // Log de auditoria criado automaticamente
} else {
    echo "Erro: {$serviceResult->message}";
}

2. Lista com filtros por tenant
$serviceResult = $designPatternService->listByTenantId($tenant_id, [
    'search' => 'termo-busca',
    'active' => true,
    'limit' => 10
]);

if ($serviceResult->isSuccess()) {
    $entities = $serviceResult->data;
    foreach ($entities as $entity) {
        echo "- {$entity->getName()} (Tenant: {$entity->getTenantId()})";
    }
}

3. Atualização com validação de tenant
$serviceResult = $designPatternService->updateByIdAndTenantId($id, $tenant_id, [
    'name' => 'Nome Atualizado',
    'description' => 'Nova descrição'
], $authenticatedUser);

if ($serviceResult->isSuccess()) {
    $entity = $serviceResult->data;
    echo "Atualizado: {$entity->getUpdatedAt()->format('Y-m-d H:i:s')}";
    // Log de auditoria com dados originais e atualizados
}

4. Exclusão com auditoria
$serviceResult = $designPatternService->deleteByIdAndTenantId($id, $tenant_id, $authenticatedUser);

if ($serviceResult->isSuccess()) {
    echo "Entidade removida com sucesso!";
    // Log de auditoria com dados da entidade excluída
} else {
    echo "Erro: {$serviceResult->message}";
}

BENEFÍCIOS DO PADRÃO WITHTENANT:

✅ SEGURANÇA ABSOLUTA
- Todos os métodos exigem tenant_id
- Validação rigorosa de propriedade
- Prevenção de vazamento de dados entre tenants

✅ AUDITORIA OBRIGATÓRIA
- Log automático em todas as operações
- Rastreabilidade completa por tenant e usuário
- Metadados detalhados para compliance

✅ PERFORMANCE OTIMIZADA
- Consultas sempre filtradas por tenant
- Slugs únicos por tenant (não globalmente)
- Índices otimizados para consultas multi-tenant

✅ FACILIDADE DE USO
- Interface familiar com segurança adicional
- Validações automáticas transparentes
- Mensagens contextuais específicas do tenant
*/
