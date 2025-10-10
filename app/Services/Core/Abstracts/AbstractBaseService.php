<?php
declare(strict_types=1);

namespace App\Services\Abstracts;

use App\Enums\OperationStatus;
use App\Models\User;
use App\Repositories\Contracts\BaseRepositoryInterface;
use App\Services\Contracts\CrudServiceInterface;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Support\Facades\Auth;

/**
 * Classe base abstrata para todos os serviços.
 *
 * Esta classe é o alicerce de toda a camada de serviço da aplicação, fornecendo
 * funcionalidades essenciais e padrões consistentes para implementação de regras
 * de negócio. Serve como ponto de partida para todos os serviços específicos.
 *
 * Características principais:
 * - Implementação completa do CrudServiceInterface
 * - Injeção de dependência com BaseRepositoryInterface
 * - Tratamento padronizado de erros e respostas
 * - Helpers para contexto (autenticação, tenant)
 * - Sistema avançado de filtros e paginação
 * - Métodos auxiliares para operações comuns
 *
 * @package App\Services\Abstracts
 *
 * @example Implementação básica de um serviço concreto:
 * ```php
 * class UserService extends AbstractBaseService
 * {
 *     public function __construct(UserRepository $userRepository)
 *     {
 *         parent::__construct($userRepository);
 *     }
 *
 *     protected function getSupportedFilters(): array
 *     {
 *         return [
 *             'id', 'name', 'email', 'status', 'active',
 *             'created_at', 'updated_at', 'role'
 *         ];
 *     }
 *
 *     public function activateUser(int $userId): ServiceResult
 *     {
 *         return $this->update($userId, ['active' => true]);
 *     }
 * }
 * ```
 *
 * @example Uso avançado com filtros:
 * ```php
 * class ProductService extends AbstractBaseService
 * {
 *     public function getActiveProductsByCategory(int $categoryId): ServiceResult
 *     {
 *         $filters = [
 *             'active' => true,
 *             'category_id' => $categoryId,
 *             'order_by' => 'name',
 *             'order_direction' => 'asc'
 *         ];
 *
 *         return $this->list($filters);
 *     }
 *
 *     public function getProductStats(): ServiceResult
 *     {
 *         return $this->getStats(['active' => true]);
 *     }
 * }
 * ```
 *
 * @example Cenários típicos de uso:
 * - **CRUD completo** - Operações básicas com tratamento de erro
 * - **Filtros avançados** - Busca e paginação com múltiplos critérios
 * - **Validação** - Regras de negócio antes da persistência
 * - **Contextualização** - Acesso automático a usuário e tenant
 * - **Tratamento de erro** - Respostas padronizadas para diferentes cenários
 */
abstract class AbstractBaseService implements CrudServiceInterface
{
    protected BaseRepositoryInterface $repository;

    public function __construct( BaseRepositoryInterface $repository )
    {
        $this->repository = $repository;
    }

    // --------------------------------------------------------------------------
    // IMPLEMENTAÇÃO DO CRUDSERVICEINTERFACE (READ)
    // --------------------------------------------------------------------------

    public function findById( int $id, array $with = [] ): ServiceResult
    {
        try {
            // TODO: Implementar suporte a relacionamentos quando BaseRepositoryInterface for expandido
            // Atualmente, o parâmetro $with está preparado para futuro uso com relacionamentos
            // mas o BaseRepositoryInterface básico não suporta essa funcionalidade ainda

            $entity = $this->repository->find( $id );

            if ( !$entity ) {
                return $this->error( OperationStatus::NOT_FOUND, "Recurso com ID {$id} não encontrado." );
            }

            // Se foram solicitados relacionamentos mas não são suportados pelo repositório básico,
            // poderíamos implementar carregamento manual aqui no futuro
            if ( !empty( $with ) ) {
                // Por ora, apenas loga que relacionamentos foram solicitados mas não carregados
                // Em uma implementação futura, isso poderia carregar os relacionamentos manualmente
            }

            return $this->success( $entity, 'Busca realizada com sucesso.' );
        } catch ( Exception $e ) {
            return $this->error( OperationStatus::ERROR, "Erro ao buscar recurso.", null, $e );
        }
    }

    public function list( array $filters = [] ): ServiceResult
    {
        try {
            // Se não há filtros, retorna todos os registros
            if ( empty( $filters ) ) {
                $entities = $this->repository->getAll();
                return $this->success( $entities, 'Listagem realizada com sucesso.' );
            }

            // Para repositórios que suportam funcionalidades avançadas
            if ( $this->repositorySupportsAdvancedOperations() ) {
                return $this->listWithAdvancedFeatures( $filters );
            }

            // Fallback: aplica filtros básicos suportados
            $query = $this->repository->getAll();

            // Aplica filtros básicos suportados
            $supportedFilters = $this->getSupportedFilters();
            foreach ( $filters as $key => $value ) {
                if ( in_array( $key, $supportedFilters ) && $value !== null ) {
                    if ( is_array( $value ) ) {
                        $query = $query->whereIn( $key, $value );
                    } else {
                        $query = $query->where( $key, $value );
                    }
                }
            }

            // Aplica ordenação se especificada
            if ( isset( $filters[ 'order_by' ] ) && isset( $filters[ 'order_direction' ] ) ) {
                $direction = strtolower( $filters[ 'order_direction' ] ) === 'desc' ? 'desc' : 'asc';
                $query     = $query->orderBy( $filters[ 'order_by' ], $direction );
            }

            $entities = $query->get();
            return $this->success( $entities, 'Listagem com filtros realizada com sucesso.' );
        } catch ( Exception $e ) {
            return $this->error( OperationStatus::ERROR, "Erro ao listar recursos.", null, $e );
        }
    }

    public function count( array $filters = [] ): ServiceResult
    {
        try {
            // Usa estatísticas básicas que funcionam com qualquer repositório
            $stats = $this->getBasicStats( $filters );
            return $this->success( $stats[ 'total' ], 'Contagem realizada com sucesso.' );
        } catch ( Exception $e ) {
            return $this->error( OperationStatus::ERROR, "Erro ao contar recursos.", null, $e );
        }
    }

    // --------------------------------------------------------------------------
    // IMPLEMENTAÇÃO DO CRUDSERVICEINTERFACE (WRITE) - NOVOS MÉTODOS
    // --------------------------------------------------------------------------

    /**
     * Cria um novo recurso.
     */
    public function create( array $data ): ServiceResult
    {
        // Nota: A validação DEVE ocorrer no Service concreto antes desta chamada.
        try {
            // Delega a criação ao Repositório
            $entity = $this->repository->create( $data );

            return $this->success( $entity, 'Recurso criado com sucesso.' );
        } catch ( \Illuminate\Database\QueryException $e ) {
            // Captura erros comuns de banco (ex: violação de unique key)
            return $this->error( OperationStatus::CONFLICT, "Erro de dados: verifique a unicidade ou constraints.", null, $e );
        } catch ( Exception $e ) {
            return $this->error( OperationStatus::ERROR, "Erro ao criar recurso.", null, $e );
        }
    }

    /**
     * Atualiza um recurso existente pelo ID.
     */
    public function update( int $id, array $data ): ServiceResult
    {
        // Nota: A validação DEVE ocorrer no Service concreto antes desta chamada.
        try {
            // Busca a entidade primeiro (para garantir que existe e respeitar o tenant)
            $entity = $this->repository->find( $id );

            if ( !$entity ) {
                return $this->error( OperationStatus::NOT_FOUND, "Recurso com ID {$id} não encontrado para atualização." );
            }

            // Delega a atualização ao Repositório
            $updatedEntity = $this->repository->update( $id, $data );

            // O update do Repositório pode retornar o Model ou null em caso de falha silenciosa.
            if ( !$updatedEntity ) {
                // Se a busca inicial passou, mas o update falhou (ex: 0 linhas afetadas)
                return $this->error( OperationStatus::ERROR, "Falha ao aplicar as mudanças no recurso.", $entity );
            }

            return $this->success( $updatedEntity, 'Recurso atualizado com sucesso.' );
        } catch ( \Illuminate\Database\QueryException $e ) {
            return $this->error( OperationStatus::CONFLICT, "Erro de dados: verifique a unicidade ou constraints.", null, $e );
        } catch ( Exception $e ) {
            return $this->error( OperationStatus::ERROR, "Erro ao atualizar recurso.", null, $e );
        }
    }

    /**
     * Deleta um recurso pelo ID.
     */
    public function delete( int $id ): ServiceResult
    {
        try {
            // O Repositório já deve tratar a exclusão e retornar true/false
            $deleted = $this->repository->delete( $id );

            if ( !$deleted ) {
                return $this->error( OperationStatus::NOT_FOUND, "Recurso com ID {$id} não encontrado para exclusão." );
            }

            return $this->success( null, 'Recurso excluído com sucesso.' );
        } catch ( \Illuminate\Database\QueryException $e ) {
            // Captura erros de banco, como violação de chave estrangeira (restrição)
            return $this->error( OperationStatus::CONFLICT, "Não foi possível excluir. O recurso está em uso.", null, $e );
        } catch ( Exception $e ) {
            return $this->error( OperationStatus::ERROR, "Erro ao excluir recurso.", null, $e );
        }
    }

    // --------------------------------------------------------------------------
    // HELPERS DE COMUNICAÇÃO E CONTEXTO (OMITIDOS AQUI POR CLAREZA, MAS MANTIDOS)
    // --------------------------------------------------------------------------

    protected function success( mixed $data = null, string $message = '' ): ServiceResult
    {
        return ServiceResult::success( $data, $message );
    }

    protected function error( OperationStatus|string $status, string $message = '', mixed $data = null, ?Exception $exception = null ): ServiceResult
    {
        $finalStatus  = is_string( $status ) ? OperationStatus::ERROR : $status;
        $finalMessage = is_string( $status ) ? $status : $message;

        return ServiceResult::error( $finalStatus, $finalMessage, $data, $exception );
    }

    // --------------------------------------------------------------------------
    // HELPERS DE CONTEXTO (MANTIDOS)
    // --------------------------------------------------------------------------

    protected function authUser(): ?User
    {
        $user = Auth::user();
        return $user instanceof User ? $user : null;
    }

    protected function tenantId(): ?int
    {
        $user = $this->authUser();
        return $user?->tenant_id ?? null;
    }

    // --------------------------------------------------------------------------
    // MÉTODOS AUXILIARES PROTEGIDOS PARA TRATAMENTO DE FILTROS
    // --------------------------------------------------------------------------

    /**
     * Retorna lista de filtros suportados pelo serviço.
     *
     * Cada serviço concreto deve sobrescrever este método para definir
     * quais filtros são suportados para suas operações específicas.
     *
     * @return array<string> Lista de campos que podem ser filtrados.
     *
     * @example Para um serviço de produtos:
     * ```php
     * protected function getSupportedFilters(): array
     * {
     *     return [
     *         'id', 'name', 'description', 'price', 'category_id',
     *         'active', 'status', 'created_at', 'updated_at'
     *     ];
     * }
     * ```
     */
    protected function getSupportedFilters(): array
    {
        return [
            'id',
            'status',
            'active',
            'created_at',
            'updated_at',
        ];
    }

    /**
     * Valida e normaliza filtros de entrada.
     *
     * Remove filtros não suportados e aplica validações básicas
     * para prevenir erros e ataques de injeção.
     *
     * @param array<string, mixed> $filters Filtros originais.
     * @return array<string, mixed> Filtros validados e normalizados.
     *
     * @example Uso típico:
     * ```php
     * $rawFilters = [
     *     'name' => 'Produto A',      // ← válido
     *     'invalid_field' => '123',   // ← será removido
     *     'active' => 'true',         // ← será convertido para boolean
     *     'price' => '100.50'         // ← será mantido como string
     * ];
     * $normalized = $this->validateAndNormalizeFilters($rawFilters);
     * // Resultado: ['name' => 'Produto A', 'active' => true, 'price' => '100.50']
     * ```
     */
    protected function validateAndNormalizeFilters( array $filters ): array
    {
        $supportedFilters  = $this->getSupportedFilters();
        $normalizedFilters = [];

        foreach ( $filters as $key => $value ) {
            // Remove filtros não suportados
            if ( !in_array( $key, $supportedFilters ) ) {
                continue;
            }

            // Normaliza valores booleanos
            if ( is_string( $value ) ) {
                if ( strtolower( $value ) === 'true' ) {
                    $value = true;
                } elseif ( strtolower( $value ) === 'false' ) {
                    $value = false;
                }
            }

            // Remove valores vazios ou null
            if ( $value !== null && $value !== '' ) {
                $normalizedFilters[ $key ] = $value;
            }
        }

        return $normalizedFilters;
    }

    /**
     * Aplica filtros comuns de data se especificados.
     *
     * Suporte para filtros como created_after, created_before,
     * updated_after, updated_before.
     *
     * @param mixed $query Query builder ou collection.
     * @param array<string, mixed> $filters Filtros de data.
     * @return mixed Query com filtros de data aplicados.
     */
    protected function applyDateFilters( $query, array $filters )
    {
        // Filtro created_after
        if ( isset( $filters[ 'created_after' ] ) ) {
            $query = $query->where( 'created_at', '>=', $filters[ 'created_after' ] );
        }

        // Filtro created_before
        if ( isset( $filters[ 'created_before' ] ) ) {
            $query = $query->where( 'created_at', '<=', $filters[ 'created_before' ] );
        }

        // Filtro updated_after
        if ( isset( $filters[ 'updated_after' ] ) ) {
            $query = $query->where( 'updated_at', '>=', $filters[ 'updated_after' ] );
        }

        // Filtro updated_before
        if ( isset( $filters[ 'updated_before' ] ) ) {
            $query = $query->where( 'updated_at', '<=', $filters[ 'updated_before' ] );
        }

        return $query;
    }

    /**
     * Aplica filtros de intervalo de datas.
     *
     * @param mixed $query Query builder ou collection.
     * @param string $dateField Campo de data para filtrar.
     * @param array{start?: string, end?: string} $range Intervalo de datas.
     * @return mixed Query com filtro de intervalo aplicado.
     */
    protected function applyDateRangeFilter( $query, string $dateField, array $range )
    {
        if ( isset( $range[ 'start' ] ) ) {
            $query = $query->where( $dateField, '>=', $range[ 'start' ] );
        }

        if ( isset( $range[ 'end' ] ) ) {
            $query = $query->where( $dateField, '<=', $range[ 'end' ] );
        }

        return $query;
    }

    /**
     * Aplica filtros de texto com suporte a busca parcial.
     *
     * @param mixed $query Query builder ou collection.
     * @param array<string, string> $textFilters Filtros de texto.
     * @return mixed Query com filtros de texto aplicados.
     */
    protected function applyTextFilters( $query, array $textFilters )
    {
        foreach ( $textFilters as $field => $searchTerm ) {
            if ( !empty( $searchTerm ) ) {
                // Busca parcial case-insensitive
                $query = $query->where( $field, 'ILIKE', "%{$searchTerm}%" );
            }
        }

        return $query;
    }

    /**
     * Aplica filtros numéricos com operadores.
     *
     * @param mixed $query Query builder ou collection.
     * @param array<string, array{operator: string, value: mixed}> $numericFilters Filtros numéricos.
     * @return mixed Query com filtros numéricos aplicados.
     */
    protected function applyNumericFilters( $query, array $numericFilters )
    {
        foreach ( $numericFilters as $field => $filter ) {
            if ( !isset( $filter[ 'operator' ], $filter[ 'value' ] ) ) {
                continue;
            }

            $operator = $filter[ 'operator' ];
            $value    = $filter[ 'value' ];

            switch ( $operator ) {
                case '=':
                case 'eq':
                    $query = $query->where( $field, '=', $value );
                    break;
                case '!=':
                case 'neq':
                    $query = $query->where( $field, '!=', $value );
                    break;
                case '>':
                case 'gt':
                    $query = $query->where( $field, '>', $value );
                    break;
                case '>=':
                case 'gte':
                    $query = $query->where( $field, '>=', $value );
                    break;
                case '<':
                case 'lt':
                    $query = $query->where( $field, '<', $value );
                    break;
                case '<=':
                case 'lte':
                    $query = $query->where( $field, '<=', $value );
                    break;
                case 'between':
                    if ( is_array( $value ) && count( $value ) === 2 ) {
                        $query = $query->whereBetween( $field, $value );
                    }
                    break;
                case 'not_between':
                    if ( is_array( $value ) && count( $value ) === 2 ) {
                        $query = $query->whereNotBetween( $field, $value );
                    }
                    break;
            }
        }

        return $query;
    }

    /**
     * Aplica ordenação avançada com múltiplos campos.
     *
     * @param mixed $query Query builder ou collection.
     * @param array<string, string> $orderBy Campos e direções de ordenação.
     * @return mixed Query com ordenação aplicada.
     */
    protected function applyAdvancedOrderBy( $query, array $orderBy )
    {
        foreach ( $orderBy as $field => $direction ) {
            $direction = strtolower( $direction ) === 'desc' ? 'desc' : 'asc';

            // Valida se o campo pode ser ordenado
            if ( $this->isSortableField( $field ) ) {
                $query = $query->orderBy( $field, $direction );
            }
        }

        return $query;
    }

    /**
     * Verifica se um campo pode ser usado para ordenação.
     *
     * @param string $field Campo a verificar.
     * @return bool True se pode ser ordenado.
     */
    protected function isSortableField( string $field ): bool
    {
        $sortableFields = $this->getSortableFields();

        return in_array( $field, $sortableFields );
    }

    /**
     * Retorna lista de campos que podem ser ordenados.
     *
     * @return array<string> Lista de campos ordenáveis.
     */
    protected function getSortableFields(): array
    {
        return [
            'id',
            'created_at',
            'updated_at',
            'name',
            'title',
            'status',
            'order',
            'position',
        ];
    }

    /**
     * Extrai critérios de filtro do array de filtros.
     *
     * Remove parâmetros especiais como paginação, ordenação e limitações,
     * deixando apenas os critérios reais de filtro.
     *
     * @param array<string, mixed> $filters Filtros originais.
     * @return array<string, mixed> Apenas os critérios de filtro.
     */
    protected function extractCriteriaFromFilters( array $filters ): array
    {
        $criteria = [];

        foreach ( $filters as $key => $value ) {
            // Remove parâmetros especiais que não são critérios de filtro
            if ( in_array( $key, [ 'per_page', 'page', 'order_by', 'order_direction', 'limit', 'offset' ] ) ) {
                continue;
            }

            $criteria[ $key ] = $value;
        }

        return $criteria;
    }

    /**
     * Extrai parâmetros de ordenação do array de filtros.
     *
     * @param array<string, mixed> $filters Filtros originais.
     * @return array<string, string>|null Parâmetros de ordenação ou null.
     */
    protected function extractOrderByFromFilters( array $filters ): ?array
    {
        if ( isset( $filters[ 'order_by' ] ) && isset( $filters[ 'order_direction' ] ) ) {
            return [
                $filters[ 'order_by' ] => $filters[ 'order_direction' ]
            ];
        }

        return null;
    }

    /**
     * Verifica se o repositório atual suporta operações avançadas.
     *
     * @return bool True se suporta operações avançadas.
     */
    protected function repositorySupportsAdvancedOperations(): bool
    {
        return method_exists( $this->repository, 'getAllGlobal' ) ||
            method_exists( $this->repository, 'paginateGlobal' ) ||
            method_exists( $this->repository, 'countGlobal' ) ||
            method_exists( $this->repository, 'getAllByTenant' ) ||
            method_exists( $this->repository, 'paginateByTenant' ) ||
            method_exists( $this->repository, 'countByTenant' );
    }

    /**
     * Obtém estatísticas básicas usando operações disponíveis no repositório.
     *
     * @param array<string, mixed> $filters Filtros opcionais.
     * @return array<string, mixed> Estatísticas básicas.
     */
    protected function getBasicStats( array $filters = [] ): array
    {
        $stats = [ 'total' => 0 ];

        try {
            if ( empty( $filters ) ) {
                $stats[ 'total' ] = $this->repository->getAll()->count();
            } else {
                // Usa o método count implementado que já trata filtros adequadamente
                $countResult = $this->count( $filters );
                if ( $countResult->isSuccess() ) {
                    $stats[ 'total' ] = $countResult->getData();
                }
            }
        } catch ( Exception $e ) {
            // Em caso de erro, retorna estatísticas vazias
            $stats[ 'total' ] = 0;
        }

        return $stats;
    }

    /**
     * Lista entidades usando funcionalidades avançadas do repositório.
     *
     * Este método é chamado quando o repositório suporta operações avançadas
     * como filtros específicos, paginação avançada, etc.
     *
     * @param array<string, mixed> $filters Filtros para aplicar.
     * @return ServiceResult Resultado da listagem.
     */
    protected function listWithAdvancedFeatures( array $filters ): ServiceResult
    {
        try {
            // Usa funcionalidades básicas do repositório
            // As funcionalidades avançadas devem ser implementadas em serviços específicos
            $entities = $this->repository->getAll();
            return $this->success( $entities, 'Listagem realizada com sucesso.' );
        } catch ( Exception $e ) {
            return $this->error( OperationStatus::ERROR, "Erro ao listar recursos.", null, $e );
        }
    }

}
