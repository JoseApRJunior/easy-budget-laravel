<?php

declare(strict_types=1);

namespace App\Services\Core\Traits;

/**
 * Trait para normalização de filtros na camada de Service.
 *
 * Esta trait fornece métodos para converter filtros vindos do Request (strings, flags)
 * para o formato esperado pelos Repositories e Query Builders.
 */
trait HasFilterNormalization
{
    /**
     * Normaliza filtros do request para o formato aceito pelo repositório.
     *
     * @param  array  $filters  Filtros originais do request.
     * @param  array  $config  Configurações extras:
     *                         - 'aliases': array de mapeamento [request_key => internal_key]
     *                         - 'booleans': array de chaves que devem ser convertidas para bool
     *                         - 'likes': array de chaves que devem ser convertidas para array ['operator' => 'like', 'value' => '%val%']
     * @return array Filtros normalizados.
     */
    protected function normalizeFilters(array $filters, array $config = []): array
    {
        $normalized = [];
        $aliases = $config['aliases'] ?? [];
        $booleans = $config['booleans'] ?? [];
        $likes = $config['likes'] ?? [];

        // 1. Processamento de Filtros Padrão do Sistema

        // Filtro 'all' (ignorar escopos se verdadeiro)
        if (isset($filters['all'])) {
            $normalized['all'] = filter_var($filters['all'], FILTER_VALIDATE_BOOLEAN);
        }

        // Filtro 'deleted' (Soft Deletes) - Padronizado conforme match solicitado
        if (isset($filters['deleted'])) {
            $normalized['deleted'] = match ((string)$filters['deleted']) {
                'only' => 'only',
                'current' => 'current',
                'all' => 'all',
                default => 'current',
            };
        }

        // Filtro de Busca Genérico
        if (! empty($filters['search'])) {
            $normalized['search'] = (string) $filters['search'];
        }

        // 2. Processamento Iterativo com Mapeamentos e Tipagem
        foreach ($filters as $key => $value) {
            // Ignorar valores vazios ou já processados
            if ($value === null || $value === '' || $value === 'all' || in_array($key, ['all', 'deleted', 'search', 'page', 'per_page'])) {
                continue;
            }

            // Resolver chave (alias ou original)
            $targetKey = $aliases[$key] ?? $key;

            // A. Conversão para Like Query
            if (in_array($key, $likes) || in_array($targetKey, $likes)) {
                $normalized[$targetKey] = ['operator' => 'like', 'value' => '%'.$value.'%'];

                continue;
            }

            // B. Conversão para Booleano
            if (in_array($key, $booleans) || in_array($targetKey, $booleans) || $this->shouldBeBoolean($targetKey)) {
                $normalized[$targetKey] = filter_var($value, FILTER_VALIDATE_BOOLEAN);

                continue;
            }

            // C. Fallback: Valor original (se ainda não preenchido)
            if (! isset($normalized[$targetKey])) {
                $normalized[$targetKey] = $value;
            }
        }

        return $normalized;
    }

    /**
     * Heurística para identificar se um campo deve ser tratado como booleano.
     */
    private function shouldBeBoolean(string $key): bool
    {
        $indicators = ['is_', 'active', 'enabled', 'has_', 'should_', 'visible'];
        foreach ($indicators as $indicator) {
            if (str_contains($key, $indicator)) {
                return true;
            }
        }

        return false;
    }
}
