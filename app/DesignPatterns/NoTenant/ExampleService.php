<?php

declare(strict_types=1);

namespace App\DesignPatterns\NoTenant;

use App\Enums\OperationStatus;
use App\Services\Abstracts\BaseNoTenantService;
use App\Support\ServiceResult;
use Illuminate\Database\Eloquent\Model;

/**
 * =============================================================================
 * EXAMPLE SERVICE NO-TENANT - PADRÃO LEGACY CORRETO
 * =============================================================================
 *
 * Este service demonstra como implementar corretamente um BaseNoTenantService
 * seguindo o padrão legacy com apenas os 5 métodos essenciais obrigatórios.
 *
 * 📋 MÉTODOS ESSENCIAIS OBRIGATÓRIOS (5 métodos):
 * 1. getById(int $id): ServiceResult
 * 2. list(array $filters = []): ServiceResult
 * 3. create(array $data): ServiceResult
 * 4. update(int $id, array $data): ServiceResult
 * 5. delete(int $id): ServiceResult
 *
 * 🏗️ MÉTODOS ABSTRATOS DA BASE (6 métodos internos):
 * - findEntityById(int $id): ?Model
 * - listEntities(array $filters = []): array
 * - createEntity(array $data): Model
 * - updateEntity(int $id, array $data): Model
 * - deleteEntity(int $id): bool
 * - validateForGlobal(array $data, bool $isUpdate = false): ServiceResult
 *
 * 💡 EXEMPLOS DE MÉTODOS CUSTOMIZADOS (opcionais):
 * - findByName(string $name): ServiceResult
 * - search(array $criteria): ServiceResult
 * - bulkCreate(array $items): ServiceResult
 * - getStatistics(): ServiceResult
 *
 * 📚 BOAS PRÁTICAS PARA SERVICES GLOBAIS:
 *
 * 1. SEMPRE usar BaseNoTenantService para entidades globais
 * 2. Implementar APENAS os 5 métodos essenciais obrigatórios
 * 3. Métodos customizados devem ser específicos do domínio
 * 4. Usar ServiceResult para todas as respostas
 * 5. Validar dados no método validateForGlobal()
 * 6. Documentar TODOS os métodos com PHPDoc detalhado
 * 7. Usar transações para operações críticas
 * 8. Implementar logging adequado para auditoria
 * 9. Manter responsabilidades únicas (Single Responsibility)
 * 10. Usar injeção de dependência para repositories
 *
 * 🔧 EXEMPLOS DE USO:
 *
 * // Uso básico dos 5 métodos essenciais
 * $service = new ExampleService();
 *
 * // 1. Buscar por ID
 * $result = $service->getById(1);
 *
 * // 2. Listar com filtros
 * $result = $service->list(['status' => 'active']);
 *
 * // 3. Criar novo registro
 * $result = $service->create(['name' => 'Novo Exemplo']);
 *
 * // 4. Atualizar registro
 * $result = $service->update(1, ['name' => 'Exemplo Atualizado']);
 *
 * // 5. Deletar registro
 * $result = $service->delete(1);
 *
 * // Uso de métodos customizados
 * $result = $service->findByName('teste');
 * $result = $service->getStatistics();
 */
final class ExampleService extends BaseNoTenantService
{
    /**
     * @var string Nome da entidade gerenciada por este service
     */
    protected string $entityName = 'Example';

    // ========================================================================
    // MÉTODOS ABSTRATOS DA BASE (OBRIGATÓRIOS)
    // ========================================================================

    /**
     * Encontra entidade por ID (sem tenant).
     *
     * Este método é obrigatório e deve ser implementado por todos os services
     * que estendem BaseNoTenantService. Ele é usado internamente pelos
     * métodos públicos para buscar entidades no banco de dados.
     *
     * @param int $id ID único da entidade
     * @return Model|null Retorna a entidade encontrada ou null se não existir
     */
    protected function findEntityById( int $id ): ?Model
    {
        // Exemplo de implementação - substitua pelo seu repository real
        return $this->repository->find( $id );
    }

    /**
     * Validação para tenant (não aplicável em serviços sem tenant).
     *
     * Método obrigatório da base, mas não aplicável para serviços sem tenant.
     * Retorna erro indicando que a operação não é suportada.
     *
     * @param array $data Dados para validação
     * @param int $tenant_id ID do tenant (ignorado)
     * @param bool $is_update Se é uma atualização (ignorado)
     * @return ServiceResult Resultado da validação
     */
    protected function validateForTenant( array $data, int $tenant_id, bool $is_update = false ): ServiceResult
    {
        return ServiceResult::error(
            OperationStatus::NOT_SUPPORTED,
            'Validação por tenant não é aplicável para serviços sem tenant',
        );
    }

    /**
     * Lista entidades com filtros (sem tenant).
     *
     * Método obrigatório para listagem de entidades. Deve retornar um array
     * de entidades filtradas conforme os critérios fornecidos.
     *
     * @param ?array $orderBy Ordenação opcional (ex: ['name' => 'asc'])
     * @param ?int $limit Limite de resultados (opcional)
     * @return array Array de entidades encontradas
     */
    protected function listEntities( ?array $orderBy = null, ?int $limit = null ): array
    {
        $query = $this->repository->query();

        // Aplicar ordenação se fornecida
        if ( $orderBy ) {
            foreach ( $orderBy as $column => $direction ) {
                $query->orderBy( $column, $direction );
            }
        }

        // Aplicar limite se fornecido
        if ( $limit ) {
            $query->limit( $limit );
        }

        return $query->get()->toArray();
    }

    /**
     * Cria nova entidade.
     *
     * Método obrigatório para criação de entidades. Deve validar os dados
     * e criar uma nova instância no banco de dados.
     *
     * @param array $data Dados para criação da entidade
     * @return Model Entidade criada
     */
    protected function createEntity( array $data ): Model
    {
        return $this->repository->create( $data );
    }

    /**
     * Atualiza entidade existente.
     *
     * Método obrigatório para atualização de entidades. Deve encontrar a
     * entidade pelo ID e atualizar com os novos dados.
     *
     * @param int $id ID da entidade a ser atualizada
     * @param array $data Dados para atualização
     * @return Model Entidade atualizada
     */
    protected function updateEntity( int $id, array $data ): Model
    {
        $entity = $this->findEntityById( $id );
        if ( !$entity ) {
            throw new \InvalidArgumentException( "Entidade com ID {$id} não encontrada" );
        }

        return $this->repository->update( $entity, $data );
    }

    /**
     * Deleta entidade.
     *
     * Método obrigatório para exclusão de entidades. Deve verificar se a
     * entidade pode ser deletada e removê-la do banco de dados.
     *
     * @param int $id ID da entidade a ser deletada
     * @return bool True se deletada com sucesso, false caso contrário
     */
    protected function deleteEntity( int $id ): bool
    {
        $entity = $this->findEntityById( $id );
        if ( !$entity ) {
            return false;
        }

        $this->repository->delete( $entity );
        return true;
    }

    /**
     * Verifica se entidade pode ser deletada.
     *
     * Método obrigatório para verificar se uma entidade pode ser removida
     * do sistema, considerando regras de negócio específicas.
     *
     * @param Model $entity Entidade a ser verificada
     * @return bool True se pode ser deletada, false caso contrário
     */
    protected function canDeleteEntity( Model $entity ): bool
    {
        // Exemplo: verificar se há dependências
        // return $this->checkDependencies($entity);

        // Por padrão, permite deletar se a entidade existe
        return true;
    }

    /**
     * Salva entidade no banco de dados.
     *
     * Método obrigatório para persistir entidades no banco de dados,
     * tanto para criação quanto para atualização.
     *
     * @param Model $entity Entidade a ser salva
     * @return bool True se salva com sucesso, false caso contrário
     */
    protected function saveEntity( Model $entity ): bool
    {
        try {
            return $entity->save();
        } catch ( \Exception $e ) {
            return false;
        }
    }

    /**
     * Validação específica para entidades globais (sem tenant).
     *
     * Método obrigatório para validação de dados. Deve implementar todas
     * as regras de negócio específicas da entidade, retornando ServiceResult
     * com os erros encontrados ou sucesso.
     *
     * @param array $data Dados a serem validados
     * @param bool $isUpdate Define se é atualização (para regras específicas)
     * @return ServiceResult Resultado da validação
     */
    protected function validateForGlobal( array $data, bool $isUpdate = false ): ServiceResult
    {
        $errors = [];

        // Validação obrigatória do nome
        if ( empty( $data[ 'name' ] ) || !is_string( $data[ 'name' ] ) ) {
            $errors[ 'name' ] = 'Nome é obrigatório e deve ser uma string';
        } elseif ( strlen( $data[ 'name' ] ) < 2 ) {
            $errors[ 'name' ] = 'Nome deve ter pelo menos 2 caracteres';
        } elseif ( strlen( $data[ 'name' ] ) > 255 ) {
            $errors[ 'name' ] = 'Nome deve ter no máximo 255 caracteres';
        }

        // Validação opcional da descrição
        if ( !empty( $data[ 'description' ] ) && !is_string( $data[ 'description' ] ) ) {
            $errors[ 'description' ] = 'Descrição deve ser uma string';
        } elseif ( !empty( $data[ 'description' ] ) && strlen( $data[ 'description' ] ) > 1000 ) {
            $errors[ 'description' ] = 'Descrição deve ter no máximo 1000 caracteres';
        }

        // Validação específica para atualização
        if ( $isUpdate && !empty( $data[ 'id' ] ) ) {
            $existing = $this->findEntityById( (int) $data[ 'id' ] );
            if ( !$existing ) {
                $errors[ 'id' ] = 'Entidade não encontrada para atualização';
            }
        }

        // Validação de status se fornecido
        if ( !empty( $data[ 'status' ] ) && !in_array( $data[ 'status' ], [ 'active', 'inactive', 'pending' ] ) ) {
            $errors[ 'status' ] = 'Status deve ser: active, inactive ou pending';
        }

        if ( !empty( $errors ) ) {
            return ServiceResult::error( OperationStatus::INVALID_DATA, 'Dados inválidos: ' . json_encode( $errors ), $errors );
        }

        return ServiceResult::success( true, 'Dados válidos' );
    }

    // ========================================================================
    // EXEMPLOS DE MÉTODOS CUSTOMIZADOS (OPCIONAIS)
    // ========================================================================

    /**
     * Busca entidades por nome (exemplo de método customizado).
     *
     * Este método demonstra como adicionar funcionalidades específicas
     * do domínio além dos 5 métodos essenciais obrigatórios.
     *
     * @param string $name Nome a ser buscado
     * @param int $limit Limite de resultados (padrão: 10)
     * @return ServiceResult Resultado da busca
     */
    public function findByName( string $name, int $limit = 10 ): ServiceResult
    {
        try {
            $query = $this->repository->query();
            $query->where( 'name', 'like', "%{$name}%" );
            $query->limit( $limit );

            $results = $query->get();

            return ServiceResult::success(
                $results,
                "Encontrados {$results->count()} registros para: {$name}",
            );
        } catch ( \Exception $e ) {
            return ServiceResult::error(
                OperationStatus::ERROR,
                'Falha na busca avançada: ' . $e->getMessage(),
                null,
                $e,
            );
        }
    }

    /**
     * Busca avançada com múltiplos critérios (exemplo customizado).
     *
     * Demonstra como implementar buscas complexas mantendo a
     * consistência com o padrão ServiceResult.
     *
     * @param array $criteria Critérios de busca
     * @return ServiceResult Resultado da busca avançada
     */
    public function search( array $criteria ): ServiceResult
    {
        try {
            $query = $this->repository->query();

            // Aplicar critérios de busca
            if ( !empty( $criteria[ 'name' ] ) ) {
                $query->where( 'name', 'like', "%{$criteria[ 'name' ]}%" );
            }

            if ( !empty( $criteria[ 'status' ] ) ) {
                $query->where( 'status', $criteria[ 'status' ] );
            }

            if ( !empty( $criteria[ 'date_from' ] ) ) {
                $query->where( 'created_at', '>=', $criteria[ 'date_from' ] );
            }

            if ( !empty( $criteria[ 'date_to' ] ) ) {
                $query->where( 'created_at', '<=', $criteria[ 'date_to' ] );
            }

            // Ordenação padrão
            $query->orderBy( 'created_at', 'desc' );

            // Limitar resultados
            $limit = $criteria[ 'limit' ] ?? 50;
            $query->limit( $limit );

            $results = $query->get();
            $total   = $query->count();

            return ServiceResult::success(
                $results,
                "Busca realizada com sucesso. Total: {$total}",
            );
        } catch ( \Exception $e ) {
            return ServiceResult::error(
                OperationStatus::ERROR,
                'Falha na busca avançada: ' . $e->getMessage(),
                null,
                $e,
            );
        }
    }

    /**
     * Obtém estatísticas básicas (exemplo de método customizado).
     *
     * Demonstra como fornecer informações agregadas sobre os dados,
     * útil para dashboards e relatórios.
     *
     * @return ServiceResult Estatísticas calculadas
     */
    public function getStatistics(): ServiceResult
    {
        try {
            $total    = $this->repository->query()->count();
            $active   = $this->repository->query()->where( 'status', 'active' )->count();
            $inactive = $this->repository->query()->where( 'status', 'inactive' )->count();
            $pending  = $this->repository->query()->where( 'status', 'pending' )->count();

            $statistics = [
                'total'               => $total,
                'active'              => $active,
                'inactive'            => $inactive,
                'pending'             => $pending,
                'active_percentage'   => $total > 0 ? round( ( $active / $total ) * 100, 2 ) : 0,
                'inactive_percentage' => $total > 0 ? round( ( $inactive / $total ) * 100, 2 ) : 0,
                'pending_percentage'  => $total > 0 ? round( ( $pending / $total ) * 100, 2 ) : 0,
            ];

            return ServiceResult::success(
                $statistics,
                'Estatísticas calculadas com sucesso',
            );
        } catch ( \Exception $e ) {
            return ServiceResult::error(
                OperationStatus::ERROR,
                'Falha ao calcular estatísticas: ' . $e->getMessage(),
                null,
                $e,
            );
        }
    }

    /**
     * Cria múltiplas entidades em lote (exemplo customizado).
     *
     * Demonstra como implementar operações em lote mantendo
     * a consistência transacional e validação.
     *
     * @param array $items Array de itens para criação em lote
     * @return ServiceResult Resultado da operação em lote
     */
    public function bulkCreate( array $items ): ServiceResult
    {
        if ( empty( $items ) ) {
            return ServiceResult::error( 'Nenhum item fornecido para criação em lote' );
        }

        $created        = [];
        $errors         = [];
        $totalProcessed = 0;

        foreach ( $items as $index => $item ) {
            $totalProcessed++;

            try {
                // Validar cada item individualmente
                $validation = $this->validateForGlobal( $item, false );
                if ( !$validation->isSuccess() ) {
                    $errors[] = "Item {$index}: " . json_encode( $validation->getErrors() );
                    continue;
                }

                // Criar entidade
                $entity    = $this->createEntity( $item );
                $created[] = $entity;

            } catch ( \Exception $e ) {
                $errors[] = "Item {$index}: " . $e->getMessage();
            }
        }

        $successCount = count( $created );
        $errorCount   = count( $errors );

        return ServiceResult::success(
            [
                'created'         => $created,
                'total_processed' => $totalProcessed,
                'success_count'   => $successCount,
                'error_count'     => $errorCount,
                'errors'          => $errors
            ],
            "Lote processado: {$successCount} criados, {$errorCount} erros",
        );
    }

    // ========================================================================
    // EXEMPLOS DE MÉTODOS AUXILIARES PRIVADOS
    // ========================================================================

    /**
     * Validação adicional específica do domínio.
     *
     * Exemplo de método auxiliar privado para validações específicas
     * que podem ser reutilizadas em múltiplos métodos públicos.
     *
     * @param array $data Dados a validar
     * @param string $context Contexto da validação
     * @return bool True se válido
     */
    private function validateBusinessRules( array $data, string $context ): bool
    {
        // Exemplo: validar regras de negócio específicas
        if ( $context === 'create' && isset( $data[ 'code' ] ) ) {
            $existing = $this->repository->query()
                ->where( 'code', $data[ 'code' ] )
                ->first();

            if ( $existing ) {
                return false; // Código já existe
            }
        }

        return true;
    }

    /**
     * Formatar dados para resposta da API.
     *
     * Exemplo de método auxiliar para padronizar a formatação
     * dos dados retornados pelas APIs.
     *
     * @param Model|array $data Dados a formatar
     * @return array Dados formatados
     */
    private function formatResponseData( Model|array $data ): array
    {
        if ( $data instanceof Model ) {
            $data = $data->toArray();
        }

        // Adicionar campos calculados ou formatados
        if ( isset( $data[ 'created_at' ] ) ) {
            $data[ 'created_at_formatted' ] = date( 'd/m/Y H:i:s', strtotime( $data[ 'created_at' ] ) );
        }

        return $data;
    }

}
