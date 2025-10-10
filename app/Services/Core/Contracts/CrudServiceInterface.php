<?php
declare(strict_types=1);

namespace App\Services\Contracts;

use App\Support\ServiceResult;

/**
 * Interface CrudServiceInterface
 *
 * Contrato fundamental e completo para operações básicas de manipulação de dados (CRUD).
 *
 * Esta interface define o contrato padrão que todos os serviços devem implementar,
 * garantindo consistência e padronização nas operações de acesso a dados.
 *
 * Seguindo o princípio da Responsabilidade Única (SRP), cada método tem uma
 * responsabilidade específica e bem definida.
 *
 * @package App\Services\Contracts
 */
interface CrudServiceInterface
{
    // --------------------------------------------------------------------------
    // CREATE - Operações de Criação
    // --------------------------------------------------------------------------

    /**
     * Cria um novo recurso no sistema.
     *
     * Este método deve validar os dados de entrada, aplicar regras de negócio
     * específicas do domínio e persistir o recurso no repositório.
     *
     * @param array<string, mixed> $data Dados validados e sanitizados para criação.
     * @return ServiceResult Resultado contendo:
     *                       - Success: Entidade criada com dados completos
     *                       - Error: Detalhes do erro (validação, conflito, servidor)
     *
     * @example
     * $data = [
     *     'name' => 'Produto Exemplo',
     *     'price' => 99.90,
     *     'category_id' => 1
     * ];
     * $result = $productService->create($data);
     * if ($result->isSuccess()) {
     *     $product = $result->getData();
     *     echo "Produto criado: " . $product->name;
     * }
     */
    public function create( array $data ): ServiceResult;

    // --------------------------------------------------------------------------
    // READ - Operações de Leitura
    // --------------------------------------------------------------------------

    /**
     * Busca uma entidade específica pelo seu identificador único.
     *
     * @param int $id Identificador único da entidade.
     * @param array<string> $with Relacionamentos para eager loading (opcional).
     * @return ServiceResult Resultado contendo:
     *                       - Success: Entidade encontrada com relacionamentos
     *                       - Error: NotFound se entidade não existir
     *
     * @example
     * $result = $userService->findById(123, ['roles', 'permissions']);
     * if ($result->isSuccess()) {
     *     $user = $result->getData();
     *     echo "Usuário: " . $user->name;
     * }
     */
    public function findById( int $id, array $with = [] ): ServiceResult;

    /**
     * Lista entidades com suporte avançado a filtros e paginação.
     *
     * @param array<string, mixed> $filters Filtros, paginação e ordenação:
     *        - 'per_page': int - itens por página
     *        - 'page': int - página atual
     *        - 'order_by': string - campo para ordenação
     *        - 'order_direction': 'asc'|'desc' - direção da ordenação
     *        - Outros filtros específicos do domínio
     * @return ServiceResult Resultado contendo:
     *                       - Success: LengthAwarePaginator ou Collection
     *                       - Error: Detalhes do erro na consulta
     *
     * @example
     * $filters = [
     *     'per_page' => 10,
     *     'page' => 1,
     *     'order_by' => 'created_at',
     *     'order_direction' => 'desc',
     *     'status' => 'active'
     * ];
     * $result = $productService->list($filters);
     */
    public function list( array $filters = [] ): ServiceResult;

    /**
     * Conta entidades baseado nos filtros aplicados.
     *
     * Útil para obter métricas e estatísticas sem carregar todos os dados.
     *
     * @param array<string, mixed> $filters Filtros para aplicar na contagem.
     * @return ServiceResult Resultado contendo:
     *                       - Success: int - número total de registros
     *                       - Error: Detalhes do erro na consulta
     *
     * @example
     * $filters = ['status' => 'active', 'category_id' => 1];
     * $result = $productService->count($filters);
     * if ($result->isSuccess()) {
     *     echo "Total: " . $result->getData();
     * }
     */
    public function count( array $filters = [] ): ServiceResult;

    /**
     * Busca múltiplas entidades por seus IDs.
     *
     * @param array<int> $ids Lista de identificadores únicos.
     * @param array<string> $with Relacionamentos para eager loading.
     * @return ServiceResult Resultado contendo Collection de entidades encontradas.
     *
     * @example
     * $result = $productService->findMany([1, 2, 3, 4], ['category']);
     */
    public function findMany( array $ids, array $with = [] ): ServiceResult;

    /**
     * Busca primeira entidade que corresponde aos critérios.
     *
     * @param array<string, mixed> $criteria Critérios de busca.
     * @param array<string> $with Relacionamentos para eager loading.
     * @return ServiceResult Resultado contendo entidade encontrada ou NotFound.
     *
     * @example
     * $criteria = ['email' => 'user@example.com', 'status' => 'active'];
     * $result = $userService->findOneBy($criteria, ['roles']);
     */
    public function findOneBy( array $criteria, array $with = [] ): ServiceResult;

    // --------------------------------------------------------------------------
    // UPDATE - Operações de Atualização
    // --------------------------------------------------------------------------

    /**
     * Atualiza um recurso existente pelo seu ID.
     *
     * @param int $id Identificador único da entidade.
     * @param array<string, mixed> $data Dados validados para atualização.
     * @return ServiceResult Resultado contendo:
     *                       - Success: Entidade atualizada
     *                       - Error: NotFound se não existir, Conflict se dados inválidos
     *
     * @example
     * $result = $productService->update(123, [
     *     'name' => 'Produto Atualizado',
     *     'price' => 149.90
     * ]);
     */
    public function update( int $id, array $data ): ServiceResult;

    /**
     * Atualiza múltiplos recursos em lote.
     *
     * @param array<int> $ids Lista de IDs a serem atualizados.
     * @param array<string, mixed> $data Dados para atualização em lote.
     * @return ServiceResult Resultado contendo número de registros afetados.
     *
     * @example
     * $result = $productService->updateMany([1, 2, 3], ['status' => 'inactive']);
     */
    public function updateMany( array $ids, array $data ): ServiceResult;

    // --------------------------------------------------------------------------
    // DELETE - Operações de Exclusão
    // --------------------------------------------------------------------------

    /**
     * Remove um recurso pelo seu ID.
     *
     * @param int $id Identificador único da entidade.
     * @return ServiceResult Resultado contendo:
     *                       - Success: Confirmação da exclusão
     *                       - Error: NotFound se não existir, Conflict se houver dependências
     *
     * @example
     * $result = $productService->delete(123);
     * if ($result->isSuccess()) {
     *     echo "Produto removido com sucesso";
     * }
     */
    public function delete( int $id ): ServiceResult;

    /**
     * Remove múltiplos recursos por IDs.
     *
     * @param array<int> $ids Lista de identificadores únicos.
     * @return ServiceResult Resultado contendo número de registros removidos.
     *
     * @example
     * $result = $productService->deleteMany([1, 2, 3, 4]);
     */
    public function deleteMany( array $ids ): ServiceResult;

    /**
     * Remove recursos baseado em critérios específicos.
     *
     * @param array<string, mixed> $criteria Critérios para identificar recursos.
     * @return ServiceResult Resultado contendo número de registros removidos.
     *
     * @example
     * $criteria = ['status' => 'expired', 'created_at' => ['operator' => '<', 'value' => '2024-01-01']];
     * $result = $productService->deleteByCriteria($criteria);
     */
    public function deleteByCriteria( array $criteria ): ServiceResult;

    // --------------------------------------------------------------------------
    // ADDITIONAL OPERATIONS - Operações Avançadas
    // --------------------------------------------------------------------------

    /**
     * Verifica se um recurso existe baseado nos critérios.
     *
     * @param array<string, mixed> $criteria Critérios de verificação.
     * @return ServiceResult Resultado booleano indicando existência.
     *
     * @example
     * $criteria = ['email' => 'user@example.com'];
     * $result = $userService->exists($criteria);
     */
    public function exists( array $criteria ): ServiceResult;

    /**
     * Duplica um recurso existente com modificações opcionais.
     *
     * @param int $id ID do recurso a ser duplicado.
     * @param array<string, mixed> $overrides Dados para sobrescrever na cópia.
     * @return ServiceResult Resultado contendo o novo recurso duplicado.
     *
     * @example
     * $result = $productService->duplicate(123, ['name' => 'Produto - Cópia']);
     */
    public function duplicate( int $id, array $overrides = [] ): ServiceResult;

    /**
     * Restaura um recurso excluído (se soft deletes estiver habilitado).
     *
     * @param int $id ID do recurso a ser restaurado.
     * @return ServiceResult Resultado contendo recurso restaurado.
     *
     * @example
     * $result = $productService->restore(123);
     */
    public function restore( int $id ): ServiceResult;

    /**
     * Obtém estatísticas básicas sobre os recursos.
     *
     * @param array<string, mixed> $filters Filtros opcionais.
     * @return ServiceResult Resultado contendo array com estatísticas.
     *
     * @example
     * $result = $productService->getStats(['category_id' => 1]);
     * $stats = $result->getData(); // ['total' => 100, 'active' => 80, 'inactive' => 20]
     */
    public function getStats( array $filters = [] ): ServiceResult;
}
