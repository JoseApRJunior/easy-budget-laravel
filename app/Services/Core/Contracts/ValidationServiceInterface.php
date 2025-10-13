<?php
declare(strict_types=1);

namespace App\Services\Core\Contracts;

use App\Support\ServiceResult;

/**
 * Interface ValidationServiceInterface
 *
 * Contrato especializado para operações de validação, verificação de regras
 * e sanitização de dados. Define métodos para validar dados de entrada,
 * regras de negócio específicas do domínio e integridade referencial.
 *
 * Esta interface é essencial para:
 * - Validação robusta de dados de entrada
 * - Verificação de regras de negócio complexas
 * - Sanitização e limpeza de dados
 * - Verificação de integridade referencial
 * - Validação de permissões e autorizações
 *
 * @package App\Services\Contracts
 */
interface ValidationServiceInterface
{
    /**
     * Valida dados de entrada para operações de criação ou atualização.
     *
     * Realiza validação completa dos dados, incluindo regras básicas (required,
     * formato, tamanho) e regras específicas do domínio.
     *
     * @param array<string, mixed> $data Dados a serem validados.
     * @param bool $isUpdate Se é uma operação de atualização (pode ter regras diferentes).
     * @return ServiceResult Resultado contendo:
     *                       - Success: Dados validados e sanitizados
     *                       - Error: Lista detalhada de erros de validação
     *
     * @example
     * $data = ['name' => 'Produto A', 'price' => 'invalid'];
     * $result = $productService->validate($data, false);
     * if (!$result->isSuccess()) {
     *     $errors = $result->getErrors(); // ['price' => ['Deve ser um número válido']]
     * }
     */
    public function validate( array $data, bool $isUpdate = false ): ServiceResult;

    /**
     * Verifica se uma entidade existe baseado nos critérios fornecidos.
     *
     * Útil para validações de existência antes de operações críticas.
     *
     * @param array<string, mixed> $criteria Critérios de busca.
     * @param int|null $excludeId ID a ser excluído da busca (útil para updates).
     * @return ServiceResult Resultado booleano indicando existência.
     *
     * @example
     * $criteria = ['email' => 'user@example.com'];
     * $result = $userService->exists($criteria);
     * if ($result->getData()) {
     *     echo "Usuário já existe";
     * }
     */
    public function exists( array $criteria, ?int $excludeId = null ): ServiceResult;

    /**
     * Valida regras de negócio específicas do domínio.
     *
     * Aplica regras complexas que vão além da validação básica de formato,
     * considerando contexto de negócio, relacionamentos e estado do sistema.
     *
     * @param array<string, mixed> $data Dados para validação.
     * @param array<string, mixed> $context Contexto adicional:
     *        - 'user_id': ID do usuário que está fazendo a operação
     *        - 'tenant_id': ID do tenant atual
     *        - 'current_entity': Entidade atual (para updates)
     *        - Outros dados contextuais específicos
     * @return ServiceResult Resultado contendo validação das regras de negócio.
     *
     * @example
     * $data = ['price' => 100, 'category_id' => 1];
     * $context = ['user_id' => 123, 'tenant_id' => 456];
     * $result = $productService->validateBusinessRules($data, $context);
     */
    public function validateBusinessRules( array $data, array $context = [] ): ServiceResult;

    /**
     * Valida permissões do usuário para executar uma operação.
     *
     * Verifica se o usuário atual tem permissão para executar a operação
     * específica nos dados fornecidos.
     *
     * @param string $operation Operação a ser validada (create, read, update, delete).
     * @param array<string, mixed> $data Dados relacionados à operação.
     * @param array<string, mixed> $context Contexto adicional.
     * @return ServiceResult Resultado indicando se a operação é permitida.
     *
     * @example
     * $result = $productService->validatePermission('delete', ['id' => 123]);
     * if (!$result->isSuccess()) {
     *     echo "Você não tem permissão para excluir este produto";
     * }
     */
    public function validatePermission( string $operation, array $data = [], array $context = [] ): ServiceResult;

    /**
     * Valida integridade referencial dos dados.
     *
     * Verifica se os relacionamentos e dependências dos dados são válidos
     * e consistentes com o estado atual do sistema.
     *
     * @param array<string, mixed> $data Dados para validação referencial.
     * @return ServiceResult Resultado contendo problemas de integridade encontrados.
     *
     * @example
     * $data = ['customer_id' => 999, 'product_id' => 123];
     * $result = $orderService->validateReferentialIntegrity($data);
     */
    public function validateReferentialIntegrity( array $data ): ServiceResult;

    /**
     * Sanitiza dados de entrada removendo ou corrigindo valores inválidos.
     *
     * Limpa e normaliza dados de entrada, aplicando correções automáticas
     * quando possível e seguro.
     *
     * @param array<string, mixed> $data Dados a serem sanitizados.
     * @return ServiceResult Resultado contendo dados sanitizados.
     *
     * @example
     * $dirtyData = ['name' => '  Produto com espaços  ', 'price' => '100.50'];
     * $result = $productService->sanitize($dirtyData);
     * $cleanData = $result->getData(); // ['name' => 'Produto com espaços', 'price' => 100.50]
     */
    public function sanitize( array $data ): ServiceResult;

    /**
     * Valida formato e estrutura de arquivos enviados.
     *
     * Verifica se arquivos atendem aos critérios de tamanho, tipo e formato
     * especificados para o contexto de negócio.
     *
     * @param array $files Arquivos a serem validados (formato $_FILES).
     * @param array<string, mixed> $rules Regras de validação:
     *        - 'max_size': int - tamanho máximo em bytes
     *        - 'allowed_types': array - tipos MIME permitidos
     *        - 'allowed_extensions': array - extensões permitidas
     * @return ServiceResult Resultado contendo arquivos válidos ou erros.
     *
     * @example
     * $files = $_FILES['documents'];
     * $rules = ['max_size' => 5242880, 'allowed_types' => ['application/pdf']];
     * $result = $documentService->validateFiles($files, $rules);
     */
    public function validateFiles( array $files, array $rules = [] ): ServiceResult;

    /**
     * Valida dados únicos dentro do contexto do tenant.
     *
     * Verifica unicidade de campos considerando o isolamento por tenant
     * e excluindo o registro atual em caso de updates.
     *
     * @param array<string, mixed> $data Dados para validação de unicidade.
     * @param int|null $currentId ID atual (para operações de update).
     * @return ServiceResult Resultado indicando campos únicos ou conflitos.
     *
     * @example
     * $data = ['email' => 'user@example.com', 'code' => 'PROD-001'];
     * $result = $service->validateUniqueness($data, 123);
     */
    public function validateUniqueness( array $data, ?int $currentId = null ): ServiceResult;

    /**
     * Valida limites e cotas do sistema.
     *
     * Verifica se a operação respeita os limites estabelecidos (quantidade,
     * valor, tempo) para o contexto atual.
     *
     * @param string $limitType Tipo de limite a validar.
     * @param array<string, mixed> $data Dados para cálculo do limite.
     * @return ServiceResult Resultado indicando se está dentro do limite.
     *
     * @example
     * $result = $service->validateLimits('monthly_orders', ['customer_id' => 123]);
     */
    public function validateLimits( string $limitType, array $data = [] ): ServiceResult;

    /**
     * Valida formato de dados externos (APIs, imports).
     *
     * Verifica se dados provenientes de fontes externas estão no formato
     * esperado e são seguros para processamento.
     *
     * @param array<string, mixed> $externalData Dados externos.
     * @param string $source Fonte dos dados ('api', 'csv', 'xml', etc.).
     * @return ServiceResult Resultado contendo dados validados ou erros de formato.
     *
     * @example
     * $apiData = json_decode($response, true);
     * $result = $service->validateExternalData($apiData, 'api');
     */
    public function validateExternalData( array $externalData, string $source ): ServiceResult;
}
