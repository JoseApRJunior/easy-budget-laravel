<?php

declare(strict_types=1);

namespace App\Contracts\Interfaces;

/**
 * Interface para enums de status padronizados
 *
 * Define o contrato que todos os enums de status devem implementar
 * para garantir consistência e reutilização de funcionalidades.
 */
interface StatusEnumInterface
{
    /**
     * Retorna uma descrição para o status
     */
    public function getDescription(): string;

    /**
     * Retorna a cor associada ao status para interface
     *
     * @return string Cor em formato hexadecimal
     */
    public function getColor(): string;

    /**
     * Retorna o ícone associado ao status
     *
     * @return string Nome do ícone para interface
     */
    public function getIcon(): string;

    /**
     * Verifica se o status indica atividade
     *
     * @return bool True se estiver ativo
     */
    public function isActive(): bool;

    /**
     * Verifica se o status indica finalização
     *
     * @return bool True se estiver finalizado
     */
    public function isFinished(): bool;

    /**
     * Retorna metadados completos do status
     *
     * @return array<string, mixed> Array com descrição, cor, ícone e flags
     */
    public function getMetadata(): array;

    /**
     * Cria instância do enum a partir de string
     *
     * @param  string  $value  Valor do status
     * @return self|null Instância do enum ou null se inválido
     */
    public static function fromString(string $value): ?self;

    /**
     * Retorna opções formatadas para uso em formulários/selects
     *
     * @param  bool  $includeFinished  Incluir status finalizados
     * @return array<string, string> Array associativo [valor => descrição]
     */
    public static function getOptions(bool $includeFinished = true): array;

    /**
     * Ordena status por prioridade para exibição
     *
     * @param  bool  $includeFinished  Incluir status finalizados na ordenação
     * @return array<self> Status ordenados por prioridade
     */
    public static function getOrdered(bool $includeFinished = true): array;

    /**
     * Calcula métricas de status para dashboards
     *
     * @param  array<self>  $statuses  Lista de status para análise
     * @return array<string, mixed> Métricas calculadas
     */
    public static function calculateMetrics(array $statuses): array;
}
