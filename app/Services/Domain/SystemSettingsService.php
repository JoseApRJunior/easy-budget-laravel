<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Enums\OperationStatus;
use App\Models\SystemSettings;
use App\Repositories\SystemSettingsRepository;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Support\ServiceResult;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Serviço para gerenciamento de configurações do sistema
 *
 * Este serviço gerencia as configurações globais do sistema,
 * incluindo preferências de tenant, integrações, notificações
 * e outras configurações administrativas.
 */
class SystemSettingsService extends AbstractBaseService
{
    private const CACHE_PREFIX = 'system_settings_';

    private const CACHE_TTL = 3600; // 1 hora

    public function __construct(
        private SystemSettingsRepository $systemSettingsRepository,
    ) {
        parent::__construct($systemSettingsRepository);
    }

    /**
     * Obtém configuração específica por chave
     */
    public function getSetting(string $key, mixed $default = null): ServiceResult
    {
        try {
            $cacheKey = self::CACHE_PREFIX.$this->tenantId().'_'.$key;

            $setting = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($key) {
                return SystemSettings::where('tenant_id', $this->tenantId())
                    ->where('key', $key)
                    ->first();
            });

            if (! $setting) {
                return $this->success($default, 'Configuração não encontrada, retornando valor padrão.');
            }

            // Decodifica valor baseado no tipo
            $value = $this->decodeSettingValue($setting->value, $setting->type);

            return $this->success($value, 'Configuração obtida com sucesso.');

        } catch (\Exception $e) {
            Log::error('Erro ao obter configuração', [
                'key' => $key,
                'tenant_id' => $this->tenantId(),
                'error' => $e->getMessage(),
            ]);

            return $this->error(OperationStatus::ERROR, 'Erro ao obter configuração.', null, $e);
        }
    }

    /**
     * Define configuração específica
     */
    public function setSetting(string $key, mixed $value, string $type = 'string', array $metadata = []): ServiceResult
    {
        try {
            // Valida chave
            if (! $this->validateSettingKey($key)) {
                return $this->error(OperationStatus::VALIDATION_ERROR, 'Chave de configuração inválida.');
            }

            // Valida tipo
            if (! $this->validateSettingType($type)) {
                return $this->error(OperationStatus::VALIDATION_ERROR, 'Tipo de configuração inválido.');
            }

            // Codifica valor baseado no tipo
            $encodedValue = $this->encodeSettingValue($value, $type);

            // Busca configuração existente ou cria nova
            $setting = SystemSettings::firstOrNew([
                'tenant_id' => $this->tenantId(),
                'key' => $key,
            ]);

            $setting->fill([
                'value' => $encodedValue,
                'type' => $type,
                'metadata' => $metadata,
                'updated_by' => $this->authUser()->id ?? null,
            ]);

            $setting->save();

            // Limpa cache
            $this->clearSettingCache($key);

            // Registra log de auditoria
            Log::info('Configuração do sistema alterada', [
                'key' => $key,
                'tenant_id' => $this->tenantId(),
                'user_id' => $this->authUser()->id ?? null,
                'type' => $type,
            ]);

            return $this->success($setting, 'Configuração salva com sucesso.');

        } catch (\Exception $e) {
            Log::error('Erro ao salvar configuração', [
                'key' => $key,
                'tenant_id' => $this->tenantId(),
                'error' => $e->getMessage(),
            ]);

            return $this->error(OperationStatus::ERROR, 'Erro ao salvar configuração.', null, $e);
        }
    }

    /**
     * Define múltiplas configurações de uma vez
     */
    public function setMultipleSettings(array $settings): ServiceResult
    {
        try {
            DB::beginTransaction();

            $results = [];
            foreach ($settings as $setting) {
                $result = $this->setSetting(
                    $setting['key'],
                    $setting['value'],
                    $setting['type'] ?? 'string',
                    $setting['metadata'] ?? []
                );

                if (! $result->isSuccess()) {
                    DB::rollBack();

                    return $this->error(
                        OperationStatus::ERROR,
                        "Erro ao definir configuração '{$setting['key']}': ".$result->getMessage()
                    );
                }

                $results[$setting['key']] = $result->getData();
            }

            DB::commit();

            return $this->success($results, 'Configurações salvas com sucesso.');

        } catch (\Exception $e) {
            DB::rollBack();

            return $this->error(OperationStatus::ERROR, 'Erro ao salvar configurações.', null, $e);
        }
    }

    /**
     * Obtém todas as configurações de um grupo específico
     */
    public function getSettingsByGroup(string $group): ServiceResult
    {
        try {
            $settings = SystemSettings::where('tenant_id', $this->tenantId())
                ->where('key', 'like', $group.'.%')
                ->get();

            $groupedSettings = [];
            foreach ($settings as $setting) {
                $subKey = str_replace($group.'.', '', $setting->key);
                $groupedSettings[$subKey] = $this->decodeSettingValue($setting->value, $setting->type);
            }

            return $this->success($groupedSettings, "Configurações do grupo '{$group}' obtidas com sucesso.");

        } catch (\Exception $e) {
            return $this->error(OperationStatus::ERROR, 'Erro ao obter configurações do grupo.', null, $e);
        }
    }

    /**
     * Define configurações de um grupo específico
     */
    public function setSettingsByGroup(string $group, array $settings): ServiceResult
    {
        try {
            $settingsToSave = [];
            foreach ($settings as $subKey => $value) {
                $fullKey = $group.'.'.$subKey;
                $type = is_bool($value) ? 'boolean' : (is_int($value) ? 'integer' : (is_array($value) || is_object($value) ? 'json' : 'string'));

                $settingsToSave[] = [
                    'key' => $fullKey,
                    'value' => $value,
                    'type' => $type,
                ];
            }

            return $this->setMultipleSettings($settingsToSave);

        } catch (\Exception $e) {
            return $this->error(OperationStatus::ERROR, 'Erro ao salvar configurações do grupo.', null, $e);
        }
    }

    /**
     * Remove configuração específica
     */
    public function deleteSetting(string $key): ServiceResult
    {
        try {
            $setting = SystemSettings::where('tenant_id', $this->tenantId())
                ->where('key', $key)
                ->first();

            if (! $setting) {
                return $this->error(OperationStatus::NOT_FOUND, 'Configuração não encontrada.');
            }

            $setting->delete();
            $this->clearSettingCache($key);

            Log::info('Configuração do sistema removida', [
                'key' => $key,
                'tenant_id' => $this->tenantId(),
                'user_id' => $this->authUser()->id ?? null,
            ]);

            return $this->success(null, 'Configuração removida com sucesso.');

        } catch (\Exception $e) {
            return $this->error(OperationStatus::ERROR, 'Erro ao remover configuração.', null, $e);
        }
    }

    /**
     * Importa configurações de arquivo
     */
    public function importSettings(array $settings, bool $overwrite = false): ServiceResult
    {
        try {
            $imported = 0;
            $skipped = 0;
            $errors = [];

            foreach ($settings as $setting) {
                try {
                    // Verifica se já existe
                    $exists = SystemSettings::where('tenant_id', $this->tenantId())
                        ->where('key', $setting['key'])
                        ->exists();

                    if ($exists && ! $overwrite) {
                        $skipped++;

                        continue;
                    }

                    $result = $this->setSetting(
                        $setting['key'],
                        $setting['value'],
                        $setting['type'] ?? 'string',
                        $setting['metadata'] ?? []
                    );

                    if ($result->isSuccess()) {
                        $imported++;
                    } else {
                        $errors[] = "Erro em '{$setting['key']}': ".$result->getMessage();
                    }

                } catch (\Exception $e) {
                    $errors[] = "Erro em '{$setting['key']}': ".$e->getMessage();
                }
            }

            return $this->success([
                'imported' => $imported,
                'skipped' => $skipped,
                'errors' => $errors,
            ], 'Importação concluída.');

        } catch (\Exception $e) {
            return $this->error(OperationStatus::ERROR, 'Erro ao importar configurações.', null, $e);
        }
    }

    /**
     * Exporta configurações atuais
     */
    public function exportSettings(array $filters = []): ServiceResult
    {
        try {
            $query = SystemSettings::where('tenant_id', $this->tenantId());

            if (isset($filters['group'])) {
                $query->where('key', 'like', $filters['group'].'.%');
            }

            if (isset($filters['type'])) {
                $query->where('type', $filters['type']);
            }

            $settings = $query->get()->map(function ($setting) {
                return [
                    'key' => $setting->key,
                    'value' => $this->decodeSettingValue($setting->value, $setting->type),
                    'type' => $setting->type,
                    'metadata' => $setting->metadata,
                ];
            });

            return $this->success($settings, 'Configurações exportadas com sucesso.');

        } catch (\Exception $e) {
            return $this->error(OperationStatus::ERROR, 'Erro ao exportar configurações.', null, $e);
        }
    }

    /**
     * Limpa todas as configurações do tenant (use com cuidado!)
     */
    public function clearAllSettings(): ServiceResult
    {
        try {
            $count = SystemSettings::where('tenant_id', $this->tenantId())->count();

            SystemSettings::where('tenant_id', $this->tenantId())->delete();

            // Limpa cache
            $this->clearAllSettingsCache();

            Log::warning('Todas as configurações do sistema foram removidas', [
                'tenant_id' => $this->tenantId(),
                'user_id' => $this->authUser()->id ?? null,
                'removed_count' => $count,
            ]);

            return $this->success(['removed_count' => $count], "{$count} configurações removidas com sucesso.");

        } catch (\Exception $e) {
            return $this->error(OperationStatus::ERROR, 'Erro ao limpar configurações.', null, $e);
        }
    }

    /**
     * Valida chave de configuração
     */
    private function validateSettingKey(string $key): bool
    {
        // Valida formato da chave: apenas letras, números, pontos e underscores
        return preg_match('/^[a-zA-Z0-9._-]+$/', $key) === 1;
    }

    /**
     * Valida tipo de configuração
     */
    private function validateSettingType(string $type): bool
    {
        $validTypes = ['string', 'integer', 'boolean', 'float', 'json', 'array'];

        return in_array($type, $validTypes, true);
    }

    /**
     * Codifica valor baseado no tipo
     */
    private function encodeSettingValue(mixed $value, string $type): string
    {
        return match ($type) {
            'boolean' => $value ? '1' : '0',
            'integer', 'float' => (string) $value,
            'json', 'array' => json_encode($value),
            default => (string) $value,
        };
    }

    /**
     * Decodifica valor baseado no tipo
     */
    private function decodeSettingValue(string $value, string $type): mixed
    {
        return match ($type) {
            'boolean' => $value === '1',
            'integer' => (int) $value,
            'float' => (float) $value,
            'json', 'array' => json_decode($value, true) ?? $value,
            default => $value,
        };
    }

    /**
     * Limpa cache de configuração específica
     */
    private function clearSettingCache(string $key): void
    {
        $cacheKey = self::CACHE_PREFIX.$this->tenantId().'_'.$key;
        Cache::forget($cacheKey);
    }

    /**
     * Limpa cache de todas as configurações do tenant
     */
    private function clearAllSettingsCache(): void
    {
        // Como não temos uma forma direta de limpar por padrão,
        // podemos usar tags se o cache driver suportar
        // Por enquanto, limpamos individualmente
        $settings = SystemSettings::where('tenant_id', $this->tenantId())->pluck('key');
        foreach ($settings as $key) {
            $this->clearSettingCache($key);
        }
    }

    /**
     * Define filtros suportados
     */
    protected function getSupportedFilters(): array
    {
        return [
            'id',
            'key',
            'type',
            'tenant_id',
            'created_by',
            'updated_by',
            'created_at',
            'updated_at',
        ];
    }
}
