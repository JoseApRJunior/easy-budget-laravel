<?php
declare(strict_types=1);

namespace App\Services\Core\Contracts;

use App\Support\ServiceResult;

/**
 * Interface CommandServiceInterface
 *
 * Contrato especializado para operações complexas de escrita, execução de comandos
 * e processamento em lote. Define operações que vão além do CRUD básico e envolvem
 * lógica de negócio mais elaborada.
 *
 * Esta interface é ideal para:
 * - Operações que afetam múltiplas entidades
 * - Processamentos que requerem validações complexas
 * - Execução de workflows e processos de negócio
 * - Operações em lote com tratamento de erro granular
 *
 * @package App\Services\Contracts
 */
interface CommandServiceInterface
{
    /**
     * Executa operações em lote com tratamento avançado de erros.
     *
     * Permite executar múltiplas operações em uma única transação, com
     * controle fino sobre rollback e tratamento de erros individuais.
     *
     * @param array<array{operation: string, data: array<string, mixed>}> $operations
     *        Lista de operações a executar. Cada operação deve conter:
     *        - 'operation': string - tipo da operação (create, update, delete)
     *        - 'data': array - dados necessários para a operação
     *        - 'id': int|null - ID para operações update/delete
     * @param array<string, mixed> $options Opções de execução:
     *        - 'transactional': bool - se deve usar transação (padrão: true)
     *        - 'continue_on_error': bool - continuar mesmo com erros (padrão: false)
     *        - 'max_errors': int - máximo de erros antes de parar (padrão: 0 = ilimitado)
     * @return ServiceResult Resultado contendo:
     *                       - Success: Array com resultados de cada operação
     *                       - Error: Detalhes do primeiro erro encontrado
     *
     * @example
     * $operations = [
     *     ['operation' => 'create', 'data' => ['name' => 'Produto 1', 'price' => 10.0]],
     *     ['operation' => 'create', 'data' => ['name' => 'Produto 2', 'price' => 20.0]],
     *     ['operation' => 'update', 'id' => 1, 'data' => ['price' => 15.0]]
     * ];
     * $options = ['transactional' => true, 'continue_on_error' => false];
     * $result = $productService->batchOperation($operations, $options);
     */
    public function batchOperation( array $operations, array $options = [] ): ServiceResult;

    /**
     * Executa uma operação específica de negócio.
     *
     * Método genérico para operações customizadas que não se encaixam
     * no padrão CRUD tradicional.
     *
     * @param string $command Nome do comando a executar.
     * @param array<string, mixed> $params Parâmetros necessários para o comando.
     * @return ServiceResult Resultado da execução do comando.
     *
     * @example
     * // Comando para processar pedido
     * $result = $orderService->executeCommand('process_order', [
     *     'order_id' => 123,
     *     'payment_method' => 'credit_card'
     * ]);
     */
    public function executeCommand( string $command, array $params = [] ): ServiceResult;

    /**
     * Importa dados de uma fonte externa.
     *
     * Processa e importa grandes volumes de dados de fontes externas,
     * com validação e tratamento de erros robusto.
     *
     * @param array<array<string, mixed>> $data Dados a serem importados.
     * @param array<string, mixed> $options Configurações da importação:
     *        - 'validate_before_import': bool - validar antes de importar
     *        - 'skip_duplicates': bool - pular registros duplicados
     *        - 'update_existing': bool - atualizar registros existentes
     * @return ServiceResult Resultado contendo estatísticas da importação.
     *
     * @example
     * $data = [['name' => 'Produto A'], ['name' => 'Produto B']];
     * $options = ['validate_before_import' => true, 'skip_duplicates' => true];
     * $result = $productService->import($data, $options);
     */
    public function import( array $data, array $options = [] ): ServiceResult;

    /**
     * Exporta dados para um formato específico.
     *
     * Gera arquivos de exportação com filtros e formatação customizada.
     *
     * @param array<string, mixed> $filters Filtros para seleção de dados.
     * @param array<string, mixed> $options Configurações da exportação:
     *        - 'format': string - formato (csv, xlsx, pdf)
     *        - 'include_relations': array - relacionamentos a incluir
     *        - 'fields': array - campos específicos a exportar
     * @return ServiceResult Resultado contendo dados ou caminho do arquivo gerado.
     *
     * @example
     * $filters = ['status' => 'active', 'created_after' => '2024-01-01'];
     * $options = ['format' => 'xlsx', 'include_relations' => ['category']];
     * $result = $productService->export($filters, $options);
     */
    public function export( array $filters = [], array $options = [] ): ServiceResult;

    /**
     * Sincroniza dados entre sistemas diferentes.
     *
     * Mantém dados sincronizados entre sistemas externos ou módulos internos.
     *
     * @param string $source Fonte dos dados ('api', 'database', 'file').
     * @param array<string, mixed> $config Configurações da sincronização.
     * @return ServiceResult Resultado contendo estatísticas da sincronização.
     *
     * @example
     * $config = [
     *     'api_endpoint' => 'https://api.externa.com/products',
     *     'last_sync' => '2024-01-01 10:00:00'
     * ];
     * $result = $productService->sync('api', $config);
     */
    public function sync( string $source, array $config = [] ): ServiceResult;

    /**
     * Processa dados de forma assíncrona.
     *
     * Dispara processamento em background para operações pesadas.
     *
     * @param string $process Nome do processo a executar.
     * @param array<string, mixed> $data Dados para processamento.
     * @return ServiceResult Resultado contendo ID do job criado.
     *
     * @example
     * $result = $reportService->processAsync('generate_report', [
     *     'report_type' => 'sales',
     *     'date_range' => ['start' => '2024-01-01', 'end' => '2024-01-31']
     * ]);
     */
    public function processAsync( string $process, array $data = [] ): ServiceResult;

    /**
     * Valida integridade de dados.
     *
     * Verifica consistência e integridade dos dados, identificando
     * problemas e inconsistências.
     *
     * @param array<string, mixed> $options Configurações da validação:
     *        - 'check_relations': bool - verificar relacionamentos
     *        - 'check_constraints': bool - verificar constraints
     *        - 'check_orphans': bool - identificar registros órfãos
     * @return ServiceResult Resultado contendo relatório de integridade.
     *
     * @example
     * $options = ['check_relations' => true, 'check_orphans' => true];
     * $result = $dataService->validateIntegrity($options);
     */
    public function validateIntegrity( array $options = [] ): ServiceResult;
}
