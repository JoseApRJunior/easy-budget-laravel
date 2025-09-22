<?php

declare(strict_types=1);

namespace App\DesignPatterns\WithTenant;

use App\Enums\OperationStatus;
use App\Models\Example;
use App\Services\Abstracts\BaseTenantService;
use App\Support\ServiceResult;
use Illuminate\Support\Facades\DB;

/**
 * ExampleService WithTenant - Demonstração do padrão legacy correto.
 *
 * Este service demonstra como implementar corretamente a BaseTenantService
 * com apenas os 5 métodos essenciais obrigatórios, seguindo o padrão legacy
 * com snake_case para tenant_id.
 *
 * MÉTODOS OBRIGATÓRIOS (5 métodos essenciais):
 * - getByIdAndTenantId(int $id, int $tenant_id): ServiceResult
 * - listByTenantId(int $tenant_id, array $filters = []): ServiceResult
 * - createByTenantId(array $data, int $tenant_id): ServiceResult
 * - updateByIdAndTenantId(int $id, array $data, int $tenantId): ServiceResult
 * - deleteByIdAndTenantId(int $id, int $tenant_id): ServiceResult
 *
 * EXEMPLOS DE MÉTODOS CUSTOMIZADOS:
 * - getActiveExamplesByTenantId(int $tenant_id): ServiceResult
 * - getExamplesByTypeAndTenantId(string $type, int $tenant_id): ServiceResult
 * - bulkCreateByTenantId(array $data, int $tenant_id): ServiceResult
 *
 * COMO ADICIONAR MÉTODOS ESPECÍFICOS:
 * 1. Mantenha sempre os 5 métodos obrigatórios
 * 2. Adicione métodos customizados após os obrigatórios
 * 3. Use sempre snake_case para tenant_id
 * 4. Retorne sempre ServiceResult
 * 5. Mantenha isolamento por tenant
 * 6. Documente claramente cada método customizado
 */
class ExampleService extends BaseTenantService
{
    /**
     * Modelo da entidade Example.
     * Em produção, deve ser injetado via construtor.
     */
    protected Example $model;

    /**
     * Construtor do service.
     * Em produção, injete o modelo via dependency injection.
     */
    public function __construct()
    {
        $this->model = new Example();
    }

    /**
     * Busca uma entidade pelo ID e tenant_id (isolamento por tenant).
     *
     * @param int $id ID da entidade
     * @param int $tenant_id ID do tenant (snake_case conforme padrão legacy)
     * @return ServiceResult
     */
    public function getByIdAndTenantId( int $id, int $tenant_id ): ServiceResult
    {
        try {
            $entity = $this->model->where( 'id', $id )
                ->where( 'tenant_id', $tenant_id )
                ->first();

            if ( !$entity ) {
                return ServiceResult::error(
                    OperationStatus::NOT_FOUND,
                    'Exemplo não encontrado ou não pertence ao tenant especificado.',
                );
            }

            return ServiceResult::success(
                $entity,
                'Exemplo obtido com sucesso.',
            );
        } catch ( \Exception $e ) {
            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao buscar exemplo: ' . $e->getMessage()
            );
        }
    }

    /**
     * Lista entidades por tenant_id com filtros opcionais.
     *
     * @param int $tenant_id ID do tenant (snake_case conforme padrão legacy)
     * @param array $filters Filtros opcionais (status, example_type, etc.)
     * @return ServiceResult
     */
    public function listByTenantId( int $tenant_id, array $filters = [] ): ServiceResult
    {
        try {
            $query = $this->model->where( 'tenant_id', $tenant_id );

            // Aplicar filtros
            if ( isset( $filters[ 'status' ] ) ) {
                $query->where( 'status', $filters[ 'status' ] );
            }

            if ( isset( $filters[ 'example_type' ] ) ) {
                $query->where( 'example_type', $filters[ 'example_type' ] );
            }

            if ( isset( $filters[ 'name' ] ) ) {
                $query->where( 'name', 'like', '%' . $filters[ 'name' ] . '%' );
            }

            $entities = $query->orderBy( 'created_at', 'desc' )->get();

            return ServiceResult::success(
                $entities,
                'Exemplos listados com sucesso para o tenant.',
            );
        } catch ( \Exception $e ) {
            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao listar exemplos: ' . $e->getMessage()
            );
        }
    }

    /**
     * Cria entidade para tenant_id específico.
     *
     * @param array $data Dados da entidade
     * @param int $tenant_id ID do tenant (snake_case conforme padrão legacy)
     * @return ServiceResult
     */
    public function createByTenantId( array $data, int $tenant_id ): ServiceResult
    {
        DB::beginTransaction();
        try {
            // Validação básica
            if ( empty( $data[ 'name' ] ) ) {
                DB::rollBack();
                return ServiceResult::error(
                    OperationStatus::INVALID_DATA,
                    'Nome é obrigatório para criar exemplo.',
                );
            }

            // Preparar dados
            $data[ 'tenant_id' ] = $tenant_id;
            $data[ 'status' ]    = $data[ 'status' ] ?? 'active';

            $entity = $this->model->create( $data );

            DB::commit();
            return ServiceResult::success(
                $entity,
                'Exemplo criado com sucesso para o tenant.',
            );
        } catch ( \Exception $e ) {
            DB::rollBack();
            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao criar exemplo: ' . $e->getMessage()
            );
        }
    }

    /**
     * Atualiza entidade por ID e tenant_id.
     *
     * @param int $id ID da entidade
     * @param int $tenant_id ID do tenant (snake_case conforme padrão legacy)
     * @param array $data Dados de atualização
     * @return ServiceResult
     */
    public function updateByIdAndTenantId( int $id, array $data, int $tenantId ): ServiceResult
    {
        DB::beginTransaction();
        try {
            $entity = $this->model->where( 'id', $id )
                ->where( 'tenant_id', $tenant_id )
                ->first();

            if ( !$entity ) {
                DB::rollBack();
                return ServiceResult::error(
                    OperationStatus::NOT_FOUND,
                    'Exemplo não encontrado ou não pertence ao tenant especificado.',
                );
            }

            $entity->update( $data );

            DB::commit();
            return ServiceResult::success(
                $entity->fresh(),
                'Exemplo atualizado com sucesso.',
            );
        } catch ( \Exception $e ) {
            DB::rollBack();
            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao atualizar exemplo: ' . $e->getMessage()
            );
        }
    }

    /**
     * Deleta entidade por ID e tenant_id.
     *
     * @param int $id ID da entidade
     * @param int $tenant_id ID do tenant (snake_case conforme padrão legacy)
     * @return ServiceResult
     */
    public function deleteByIdAndTenantId( int $id, int $tenant_id ): ServiceResult
    {
        DB::beginTransaction();
        try {
            $entity = $this->model->where( 'id', $id )
                ->where( 'tenant_id', $tenant_id )
                ->first();

            if ( !$entity ) {
                DB::rollBack();
                return ServiceResult::error(
                    OperationStatus::NOT_FOUND,
                    'Exemplo não encontrado ou não pertence ao tenant especificado.',
                );
            }

            // Verificar se pode ser deletado (regras de negócio)
            if ( !$this->canDeleteEntity( $entity ) ) {
                DB::rollBack();
                return ServiceResult::error(
                    OperationStatus::FORBIDDEN,
                    'Exemplo não pode ser deletado devido a dependências.',
                );
            }

            $entity->delete();

            DB::commit();
            return ServiceResult::success(
                null,
                'Exemplo deletado com sucesso.',
            );
        } catch ( \Exception $e ) {
            DB::rollBack();
            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao deletar exemplo: ' . $e->getMessage()
            );
        }
    }

    // =============================================
    // MÉTODOS CUSTOMIZADOS ESPECÍFICOS DO SERVICE
    // =============================================

    /**
     * EXEMPLO DE MÉTODO CUSTOMIZADO 1:
     * Busca apenas exemplos ativos por tenant_id.
     *
     * Este método demonstra como adicionar funcionalidades específicas
     * sem quebrar a interface BaseTenantService.
     *
     * @param int $tenant_id ID do tenant (snake_case)
     * @return ServiceResult
     */
    public function getActiveExamplesByTenantId( int $tenant_id ): ServiceResult
    {
        try {
            $entities = $this->model->where( 'tenant_id', $tenant_id )
                ->where( 'status', 'active' )
                ->orderBy( 'name', 'asc' )
                ->get();

            return ServiceResult::success(
                $entities,
                'Exemplos ativos listados com sucesso para o tenant.',
            );
        } catch ( \Exception $e ) {
            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao buscar exemplos ativos: ' . $e->getMessage()
            );
        }
    }

    /**
     * EXEMPLO DE MÉTODO CUSTOMIZADO 2:
     * Busca exemplos por tipo específico e tenant_id.
     *
     * Demonstra como filtrar por campos específicos da entidade.
     *
     * @param string $type Tipo do exemplo
     * @param int $tenant_id ID do tenant (snake_case)
     * @return ServiceResult
     */
    public function getExamplesByTypeAndTenantId( string $type, int $tenant_id ): ServiceResult
    {
        try {
            $entities = $this->model->where( 'tenant_id', $tenant_id )
                ->where( 'example_type', $type )
                ->orderBy( 'created_at', 'desc' )
                ->get();

            return ServiceResult::success(
                $entities,
                "Exemplos do tipo '{$type}' listados com sucesso para o tenant.",
            );
        } catch ( \Exception $e ) {
            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao buscar exemplos por tipo: ' . $e->getMessage()
            );
        }
    }

    /**
     * EXEMPLO DE MÉTODO CUSTOMIZADO 3:
     * Cria múltiplos exemplos de uma vez para o tenant_id.
     *
     * Demonstra operações em lote mantendo o isolamento por tenant.
     *
     * @param array $data Array de dados dos exemplos
     * @param int $tenant_id ID do tenant (snake_case)
     * @return ServiceResult
     */
    public function bulkCreateByTenantId( array $data, int $tenant_id ): ServiceResult
    {
        DB::beginTransaction();
        try {
            $createdEntities = [];
            $errors          = [];

            foreach ( $data as $index => $exampleData ) {
                try {
                    // Validação individual
                    if ( empty( $exampleData[ 'name' ] ) ) {
                        $errors[] = "Exemplo {$index}: Nome é obrigatório.";
                        continue;
                    }

                    // Preparar dados
                    $exampleData[ 'tenant_id' ] = $tenant_id;
                    $exampleData[ 'status' ]    = $exampleData[ 'status' ] ?? 'active';

                    $entity            = $this->model->create( $exampleData );
                    $createdEntities[] = $entity;
                } catch ( \Exception $e ) {
                    $errors[] = "Exemplo {$index}: " . $e->getMessage();
                }
            }

            if ( !empty( $errors ) ) {
                DB::rollBack();
                return ServiceResult::error(
                    OperationStatus::INVALID_DATA,
                    'Erros na criação em lote: ' . implode( ', ', $errors )
                );
            }

            DB::commit();
            return ServiceResult::success(
                $createdEntities,
                count( $createdEntities ) . ' exemplos criados com sucesso para o tenant.'
            );
        } catch ( \Exception $e ) {
            DB::rollBack();
            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao criar exemplos em lote: ' . $e->getMessage()
            );
        }
    }

    // =============================================
    // MÉTODOS AUXILIARES PRIVADOS
    // =============================================

    /**
     * Verifica se a entidade pode ser deletada.
     *
     * @param Example $entity
     * @return bool
     */
    protected function canDeleteEntity( Example $entity ): bool
    {
        // Exemplo de regra de negócio: não deletar se tiver dados relacionados
        // Em produção, implementar verificação real de dependências
        return true;
    }

}
