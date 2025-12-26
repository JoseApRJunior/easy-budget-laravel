<?php

declare(strict_types=1);

namespace App\Services\Core\Contracts;

/**
 * Interface BaseServiceInterface
 *
 * Contrato de composição que agrega todas as responsabilidades básicas do sistema.
 *
 * Esta interface representa o contrato mais completo disponível, combinando
 * operações CRUD, comandos avançados, validações e funcionalidades utilitárias.
 * É ideal para serviços que precisam de todas essas capacidades.
 *
 * IMPORTANTE: Para seguir o SOLID (Interface Segregation Principle),
 * prefira injetar interfaces menores (CrudServiceInterface, ValidationServiceInterface)
 * sempre que possível. Use esta interface de composição apenas para classes
 * que realmente precisam de todas as funcionalidades.
 */
interface BaseServiceInterface extends CommandServiceInterface, CrudServiceInterface, UtilityServiceInterface, ValidationServiceInterface
{
    // A interface fica vazia. Ela apenas herda e agrupa todos os métodos das sub-interfaces.

    // --------------------------------------------------------------------------
    // EXEMPLOS DE USO PRÁTICOS
    // --------------------------------------------------------------------------

    /**
     * Exemplo completo de uso de um serviço que implementa BaseServiceInterface:
     *
     * ```php
     * class ProductService implements BaseServiceInterface
     * {
     *     public function create(array $data): ServiceResult
     *     {
     *         // 1. Validar dados
     *         $validation = $this->validate($data);
     *         if (!$validation->isSuccess()) {
     *             return $validation;
     *         }
     *
     *         // 2. Verificar regras de negócio
     *         $businessRules = $this->validateBusinessRules($data);
     *         if (!$businessRules->isSuccess()) {
     *             return $businessRules;
     *         }
     *
     *         // 3. Criar entidade
     *         return $this->repository->create($data);
     *     }
     *
     *     public function processOrder(array $orderData): ServiceResult
     *     {
     *         // 1. Validar pedido
     *         $validation = $this->validate($orderData);
     *
     *         // 2. Executar operações em lote
     *         $operations = [
     *             ['operation' => 'update', 'id' => $orderData['product_id'], 'data' => ['stock' => -1]],
     *             ['operation' => 'create', 'data' => ['order_id' => $orderData['id'], 'status' => 'processing']]
     *         ];
     *
     *         return $this->batchOperation($operations);
     *     }
     * }
     * ```
     */

    /**
     * Cenários típicos de uso:
     *
     * **Cenário 1: Gestão completa de produtos**
     * ```php
     * $productService = new ProductService($productRepository);
     *
     * // Criar produto com validação completa
     * $result = $productService->create([
     *     'name' => 'Produto Exemplo',
     *     'price' => 99.90,
     *     'category_id' => 1
     * ]);
     *
     * // Listar produtos com filtros avançados
     * $filters = [
     *     'category_id' => 1,
     *     'price' => ['operator' => '>', 'value' => 50],
     *     'order_by' => 'name',
     *     'order_direction' => 'asc'
     * ];
     * $products = $productService->list($filters);
     * ```
     *
     * **Cenário 2: Operações administrativas**
     * ```php
     * // Verificar saúde do sistema
     * $health = $adminService->healthCheck([
     *     'database' => true,
     *     'cache' => true,
     *     'external_apis' => true
     * ]);
     *
     * // Limpeza de dados antigos
     * $cleanup = $adminService->cleanup([
     *     'older_than' => '2024-01-01',
     *     'types' => ['temp_files', 'old_logs']
     * ]);
     * ```
     *
     * **Cenário 3: Processamento em lote**
     * ```php
     * // Atualizar preços em lote
     * $operations = [];
     * foreach ($products as $product) {
     *     $operations[] = [
     *         'operation' => 'update',
     *         'id' => $product->id,
     *         'data' => ['price' => $product->price * 1.1]
     *     ];
     * }
     *
     * $result = $productService->batchOperation($operations, [
     *     'transactional' => true,
     *     'continue_on_error' => false
     * ]);
     * ```
     */

    /**
     * Benefícios de usar BaseServiceInterface:
     *
     * ✅ **Funcionalidades Completas**: Acesso a todas as operações necessárias
     * ✅ **Consistência**: Interface padronizada em toda aplicação
     * ✅ **Flexibilidade**: Pode ser facilmente substituída por mocks em testes
     * ✅ **Manutenibilidade**: Mudanças na interface são refletidas em todos os serviços
     * ✅ **Documentação Viva**: Exemplos práticos de uso incluídos
     *
     * Quando usar:
     * - Serviços principais que precisam de operações completas
     * - Casos onde todas as funcionalidades são necessárias
     * - Ponto de entrada único para operações complexas
     *
     * Quando NÃO usar:
     * - Serviços simples que só precisam de operações CRUD básicas
     * - Para seguir Interface Segregation Principle (prefira interfaces menores)
     * - Quando apenas validação ou apenas operações utilitárias são necessárias
     */
}
