<?php
declare(strict_types=1);

namespace App\Services\Contracts;

use App\Support\ServiceResult;

/**
 * Interface UtilityServiceInterface
 *
 * Contrato especializado para funcionalidades de infraestrutura, cache, monitoramento
 * e operações utilitárias. Define métodos para gerenciar cache inteligente,
 * monitorar saúde do sistema, processar recursos externos e obter metadados.
 *
 * Esta interface é essencial para:
 * - Gerenciamento inteligente de cache com invalidação automática
 * - Monitoramento de saúde e performance do sistema
 * - Processamento assíncrono de recursos externos
 * - Geração de metadados e estatísticas operacionais
 * - Operações de manutenção e limpeza de dados
 *
 * @package App\Services\Contracts
 */
interface UtilityServiceInterface
{
    /**
     * Verifica a saúde e disponibilidade do serviço.
     *
     * Realiza verificações abrangentes incluindo conectividade com banco,
     * disponibilidade de cache, permissões de arquivos e outros recursos críticos.
     *
     * @param array<string, mixed> $checks Verificações específicas a realizar:
     *        - 'database': bool - verificar conexão com banco
     *        - 'cache': bool - verificar sistema de cache
     *        - 'storage': bool - verificar permissões de storage
     *        - 'external_apis': bool - verificar APIs externas
     * @return ServiceResult Resultado contendo status de saúde detalhado.
     *
     * @example
     * $checks = ['database' => true, 'cache' => true];
     * $result = $service->healthCheck($checks);
     * $health = $result->getData();
     * // ['database' => 'healthy', 'cache' => 'healthy', 'overall' => 'healthy']
     */
    public function healthCheck( array $checks = [] ): ServiceResult;

    /**
     * Obtém dados cacheáveis com invalidação inteligente baseada em eventos.
     *
     * Implementa cache inteligente que considera eventos do sistema para
     * invalidação automática (ex: invalida cache quando dados relacionados mudam).
     *
     * @param string $key Chave única para o cache.
     * @param int $ttl Tempo de vida em segundos.
     * @param callable|array $dataSource Fonte dos dados (callback ou parâmetros).
     * @param array<string> $invalidationEvents Eventos que devem invalidar este cache.
     * @return ServiceResult Resultado contendo dados em cache ou recém-gerados.
     *
     * @example
     * $invalidationEvents = ['product.updated', 'category.updated'];
     * $result = $service->getCacheableData('products_list', 3600, function() {
     *     return Product::active()->get();
     * }, $invalidationEvents);
     */
    public function getCacheableData(
        string $key,
        int $ttl,
        callable|array $dataSource,
        array $invalidationEvents = [],
    ): ServiceResult;

    /**
     * Invalida cache específico do serviço com padrões avançados.
     *
     * Permite invalidação seletiva baseada em padrões, tags ou eventos,
     * oferecendo controle fino sobre o que deve ser limpo.
     *
     * @param string $pattern Padrão para invalidação (suporta wildcards).
     * @param array<string, mixed> $options Opções de invalidação:
     *        - 'tags': array - tags específicas para invalidar
     *        - 'recursive': bool - invalidar padrões relacionados
     *        - 'delay': int - atraso antes da invalidação (para jobs)
     * @return ServiceResult Resultado contendo estatísticas da invalidação.
     *
     * @example
     * $options = ['tags' => ['products', 'categories'], 'recursive' => true];
     * $result = $service->invalidateCache('products:*', $options);
     */
    public function invalidateCache( string $pattern = '*', array $options = [] ): ServiceResult;

    /**
     * Obtém metadados e estatísticas operacionais do serviço.
     *
     * Fornece informações detalhadas sobre o estado atual do serviço,
     * incluindo métricas de performance, uso de recursos e estatísticas.
     *
     * @param array<string, mixed> $options Configurações para metadados:
     *        - 'include_performance': bool - incluir métricas de performance
     *        - 'include_usage': bool - incluir estatísticas de uso
     *        - 'time_range': string - período para estatísticas
     * @return ServiceResult Resultado contendo metadados estruturados.
     *
     * @example
     * $options = ['include_performance' => true, 'time_range' => '24h'];
     * $result = $service->getMetadata($options);
     * $metadata = $result->getData();
     */
    public function getMetadata( array $options = [] ): ServiceResult;

    /**
     * Processa recursos externos de forma assíncrona com retry automático.
     *
     * Gerencia processamento de recursos externos (APIs, arquivos, serviços)
     * com mecanismos robustos de retry, timeout e tratamento de erro.
     *
     * @param string $resourceId Identificador único do recurso.
     * @param array<string, mixed> $options Configurações do processamento:
     *        - 'timeout': int - timeout em segundos
     *        - 'retries': int - número máximo de tentativas
     *        - 'retry_delay': int - atraso entre tentativas
     *        - 'priority': string - prioridade do processamento
     * @return ServiceResult Resultado contendo ID do job e status inicial.
     *
     * @example
     * $options = ['timeout' => 30, 'retries' => 3, 'priority' => 'high'];
     * $result = $service->processExternalResource('api_sync_123', $options);
     */
    public function processExternalResource( string $resourceId, array $options = [] ): ServiceResult;

    /**
     * Limpa e otimiza dados antigos ou desnecessários.
     *
     * Remove dados obsoletos, arquivos temporários e registros órfãos
     * para manter o sistema limpo e performático.
     *
     * @param array<string, mixed> $options Configurações de limpeza:
     *        - 'older_than': string - data limite para limpeza
     *        - 'types': array - tipos de dados para limpar
     *        - 'dry_run': bool - apenas simular limpeza
     * @return ServiceResult Resultado contendo estatísticas da limpeza.
     *
     * @example
     * $options = [
     *     'older_than' => '2024-01-01',
     *     'types' => ['temp_files', 'old_logs'],
     *     'dry_run' => false
     * ];
     * $result = $service->cleanup($options);
     */
    public function cleanup( array $options = [] ): ServiceResult;

    /**
     * Gera dados de exemplo para desenvolvimento e testes.
     *
     * Cria dados fictícios realistas para popular o sistema durante
     * desenvolvimento, testes ou demonstrações.
     *
     * @param array<string, mixed> $options Configurações para geração:
     *        - 'count': int - quantidade de registros
     *        - 'locale': string - localização para dados regionais
     *        - 'relations': bool - gerar relacionamentos
     * @return ServiceResult Resultado contendo dados gerados.
     *
     * @example
     * $options = ['count' => 100, 'locale' => 'pt_BR', 'relations' => true];
     * $result = $service->generateSampleData($options);
     */
    public function generateSampleData( array $options = [] ): ServiceResult;

    /**
     * Monitora métricas de performance em tempo real.
     *
     * Coleta e retorna métricas de performance atuais do serviço,
     * incluindo uso de memória, tempo de resposta e throughput.
     *
     * @param array<string, mixed> $metrics Métricas específicas para monitorar.
     * @return ServiceResult Resultado contendo métricas atuais.
     *
     * @example
     * $metrics = ['response_time', 'memory_usage', 'database_queries'];
     * $result = $service->monitorPerformance($metrics);
     */
    public function monitorPerformance( array $metrics = [] ): ServiceResult;

    /**
     * Executa manutenção programada do serviço.
     *
     * Realiza tarefas de manutenção como rebuild de índices, limpeza
     * de cache e otimização de banco de dados.
     *
     * @param string $task Tarefa específica de manutenção.
     * @param array<string, mixed> $options Opções da manutenção.
     * @return ServiceResult Resultado contendo status da manutenção.
     *
     * @example
     * $result = $service->runMaintenance('optimize_indexes', ['tables' => ['products', 'orders']]);
     */
    public function runMaintenance( string $task, array $options = [] ): ServiceResult;

    /**
     * Gera relatórios de diagnóstico detalhados.
     *
     * Cria relatórios abrangentes sobre o estado do serviço,
     * útil para troubleshooting e auditoria.
     *
     * @param array<string, mixed> $options Configurações do diagnóstico:
     *        - 'include_logs': bool - incluir análise de logs
     *        - 'include_performance': bool - incluir métricas de performance
     *        - 'time_range': string - período para análise
     * @return ServiceResult Resultado contendo relatório de diagnóstico.
     *
     * @example
     * $options = ['include_logs' => true, 'time_range' => '7d'];
     * $result = $service->generateDiagnosticReport($options);
     */
    public function generateDiagnosticReport( array $options = [] ): ServiceResult;
}
