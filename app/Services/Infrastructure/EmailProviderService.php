<?php

declare(strict_types=1);

namespace App\Services\Infrastructure;

use App\Enums\OperationStatus;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Serviço avançado para gerenciamento de provedores de e-mail no sistema Easy Budget.
 *
 * Funcionalidades principais:
 * - Gerenciamento inteligente de provedores de e-mail
 * - Sistema de alternância automática entre provedores
 * - Configuração segura com credenciais separadas
 * - Sistema de fallback para provedor padrão
 * - Monitoramento de saúde dos provedores
 * - Cache inteligente de configurações
 *
 * Este service integra com Mailtrap para desenvolvimento e permite
 * configuração fácil de provedores de produção (SMTP, SES, etc.).
 */
class EmailProviderService
{
    /**
     * Configurações padrão para diferentes provedores de e-mail.
     */
    private array $providerConfigs = [
        'mailtrap' => [
            'name' => 'Mailtrap',
            'description' => 'Provedor de e-mail para desenvolvimento e testes',
            'type' => 'smtp',
            'host' => 'smtp.mailtrap.io',
            'port' => 2525,
            'encryption' => 'tls',
            'username' => 'required',
            'password' => 'required',
            'timeout' => 30,
            'priority' => 1, // Mais alta prioridade para desenvolvimento
            'is_testing' => true,
            'is_production' => false,
        ],
        'smtp' => [
            'name' => 'SMTP Personalizado',
            'description' => 'Servidor SMTP personalizado',
            'type' => 'smtp',
            'host' => 'required',
            'port' => 587,
            'encryption' => 'tls',
            'username' => 'required',
            'password' => 'required',
            'timeout' => 30,
            'priority' => 2,
            'is_testing' => false,
            'is_production' => true,
        ],
        'ses' => [
            'name' => 'Amazon SES',
            'description' => 'Amazon Simple Email Service',
            'type' => 'ses',
            'region' => 'us-east-1',
            'timeout' => 60,
            'priority' => 3,
            'is_testing' => false,
            'is_production' => true,
        ],
        'sendmail' => [
            'name' => 'Sendmail',
            'description' => 'Sistema sendmail local',
            'type' => 'sendmail',
            'path' => '/usr/sbin/sendmail -bs -i',
            'timeout' => 60,
            'priority' => 4,
            'is_testing' => false,
            'is_production' => true,
        ],
        'log' => [
            'name' => 'Log Only',
            'description' => 'Apenas registra e-mails no log (para testes)',
            'type' => 'log',
            'channel' => 'mail',
            'priority' => 5,
            'is_testing' => true,
            'is_production' => false,
        ],
    ];

    /**
     * Cache key para configurações de provedor.
     */
    private string $cacheKey = 'email_provider_config';

    /**
     * TTL do cache em minutos.
     */
    private int $cacheTtl = 60;

    /**
     * Obtém configuração atual do provedor de e-mail ativo.
     */
    public function getCurrentProvider(): array
    {
        try {
            $currentProvider = $this->determineCurrentProvider();

            if (! $currentProvider) {
                Log::warning('Nenhum provedor de e-mail válido encontrado, usando configuração padrão');

                return $this->getDefaultProvider();
            }

            $config = $this->getProviderConfig($currentProvider);

            Log::info('Provedor de e-mail atual obtido', [
                'provider' => $currentProvider,
                'type' => $config['type'] ?? 'unknown',
            ]);

            return [
                'provider' => $currentProvider,
                'config' => $config,
                'is_testing' => $config['is_testing'] ?? false,
                'is_production' => $config['is_production'] ?? true,
                'timestamp' => now()->toDateTimeString(),
            ];

        } catch (Exception $e) {
            Log::error('Erro ao obter provedor atual de e-mail', [
                'error' => $e->getMessage(),
            ]);

            return [
                'provider' => 'log',
                'config' => $this->getProviderConfig('log'),
                'error' => 'Erro ao determinar provedor: '.$e->getMessage(),
                'timestamp' => now()->toDateTimeString(),
            ];
        }
    }

    /**
     * Determina qual provedor deve ser usado baseado no ambiente e configurações.
     */
    private function determineCurrentProvider(): ?string
    {
        // Cache para evitar múltiplas verificações
        return Cache::remember($this->cacheKey.'_current', $this->cacheTtl, function () {
            $environment = app()->environment();

            // Em ambiente local, prioriza Mailtrap se configurado
            if ($environment === 'local' || $environment === 'testing') {
                if ($this->isMailtrapConfigured()) {
                    return 'mailtrap';
                }

                return 'log';
            }

            // Em produção, usa ordem de prioridade
            foreach ($this->providerConfigs as $provider => $config) {
                if ($this->isProviderConfigured($provider) && $config['is_production']) {
                    return $provider;
                }
            }

            // Fallback para log se nenhum provedor estiver configurado
            return 'log';
        });
    }

    /**
     * Verifica se o Mailtrap está configurado corretamente.
     */
    private function isMailtrapConfigured(): bool
    {
        return ! empty(env('MAILTRAP_USERNAME')) && ! empty(env('MAILTRAP_PASSWORD'));
    }

    /**
     * Verifica se um provedor específico está configurado.
     */
    private function isProviderConfigured(string $provider): bool
    {
        $config = $this->providerConfigs[$provider] ?? [];

        if (empty($config)) {
            return false;
        }

        switch ($provider) {
            case 'mailtrap':
                return $this->isMailtrapConfigured();

            case 'smtp':
                return ! empty(env('EMAIL_HOST')) &&
                    ! empty(env('EMAIL_USERNAME')) &&
                    ! empty(env('EMAIL_PASSWORD'));

            case 'ses':
                return ! empty(env('AWS_ACCESS_KEY_ID')) &&
                    ! empty(env('AWS_SECRET_ACCESS_KEY')) &&
                    ! empty(env('AWS_DEFAULT_REGION'));

            case 'sendmail':
                return true; // Sempre disponível

            case 'log':
                return true; // Sempre disponível

            default:
                return false;
        }
    }

    /**
     * Obtém configuração completa de um provedor específico.
     */
    public function getProviderConfig(string $provider): array
    {
        $baseConfig = $this->providerConfigs[$provider] ?? [];

        if (empty($baseConfig)) {
            return [];
        }

        // Mescla configurações específicas do ambiente
        return array_merge($baseConfig, $this->getEnvironmentSpecificConfig($provider));
    }

    /**
     * Obtém configurações específicas do ambiente para um provedor.
     */
    private function getEnvironmentSpecificConfig(string $provider): array
    {
        switch ($provider) {
            case 'mailtrap':
                return [
                    'host' => env('MAILTRAP_HOST', 'smtp.mailtrap.io'),
                    'port' => (int) env('MAILTRAP_PORT', 2525),
                    'username' => env('MAILTRAP_USERNAME'),
                    'password' => env('MAILTRAP_PASSWORD'),
                ];

            case 'smtp':
                return [
                    'host' => env('EMAIL_HOST'),
                    'port' => (int) env('EMAIL_PORT', 587),
                    'username' => env('EMAIL_USERNAME'),
                    'password' => env('EMAIL_PASSWORD'),
                    'encryption' => env('EMAIL_ENCRYPTION', 'tls'),
                ];

            case 'ses':
                return [
                    'key' => env('AWS_ACCESS_KEY_ID'),
                    'secret' => env('AWS_SECRET_ACCESS_KEY'),
                    'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
                ];

            default:
                return [];
        }
    }

    /**
     * Obtém lista de todos os provedores disponíveis.
     */
    public function getAvailableProviders(): array
    {
        $providers = [];

        foreach ($this->providerConfigs as $provider => $config) {
            $providers[] = [
                'id' => $provider,
                'name' => $config['name'],
                'description' => $config['description'],
                'type' => $config['type'],
                'is_configured' => $this->isProviderConfigured($provider),
                'is_testing' => $config['is_testing'],
                'is_production' => $config['is_production'],
                'priority' => $config['priority'],
            ];
        }

        // Ordena por prioridade
        usort($providers, fn ($a, $b) => $a['priority'] <=> $b['priority']);

        return $providers;
    }

    /**
     * Testa conectividade com um provedor específico.
     */
    public function testProvider(string $provider): ServiceResult
    {
        try {
            if (! isset($this->providerConfigs[$provider])) {
                return ServiceResult::error(
                    OperationStatus::INVALID_DATA,
                    "Provedor '{$provider}' não é suportado.",
                );
            }

            if (! $this->isProviderConfigured($provider)) {
                return ServiceResult::error(
                    OperationStatus::INVALID_DATA,
                    "Provedor '{$provider}' não está configurado corretamente.",
                );
            }

            $config = $this->getProviderConfig($provider);

            // Testa conexão baseado no tipo de provedor
            $testResult = match ($config['type']) {
                'smtp' => $this->testSmtpConnection($config),
                'ses' => $this->testSesConnection($config),
                'sendmail' => $this->testSendmailConnection($config),
                'log' => ServiceResult::success(null, 'Provedor de log sempre disponível'),
                default => ServiceResult::error(
                    OperationStatus::ERROR,
                    "Tipo de provedor '{$config['type']}' não suportado para teste.",
                ),
            };

            Log::info('Teste de conectividade de provedor executado', [
                'provider' => $provider,
                'type' => $config['type'],
                'is_success' => $testResult->isSuccess(),
                'message' => $testResult->getMessage(),
            ]);

            return $testResult;

        } catch (Exception $e) {
            Log::error('Erro ao testar provedor de e-mail', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao testar provedor: '.$e->getMessage()
            );
        }
    }

    /**
     * Testa conexão SMTP.
     */
    private function testSmtpConnection(array $config): ServiceResult
    {
        try {
            // Usa conexão de teste para validar configurações
            $connection = @fsockopen(
                $config['host'],
                $config['port'],
                $errorNumber,
                $errorString,
                10,
            );

            if ($connection) {
                fclose($connection);

                return ServiceResult::success(
                    $config,
                    "Conexão SMTP estabelecida com sucesso em {$config['host']}:{$config['port']}",
                );
            } else {
                return ServiceResult::error(
                    OperationStatus::ERROR,
                    "Falha na conexão SMTP: {$errorString} ({$errorNumber})",
                );
            }

        } catch (Exception $e) {
            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao testar conexão SMTP: '.$e->getMessage()
            );
        }
    }

    /**
     * Testa conexão SES.
     */
    private function testSesConnection(array $config): ServiceResult
    {
        try {
            // Para SES, apenas valida se as credenciais estão presentes
            // Em produção, seria usado o SDK da AWS para teste real
            if (! empty($config['key']) && ! empty($config['secret'])) {
                return ServiceResult::success(
                    $config,
                    'Credenciais SES configuradas corretamente',
                );
            } else {
                return ServiceResult::error(
                    OperationStatus::INVALID_DATA,
                    'Credenciais AWS ausentes para SES',
                );
            }

        } catch (Exception $e) {
            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao testar conexão SES: '.$e->getMessage()
            );
        }
    }

    /**
     * Testa conexão Sendmail.
     */
    private function testSendmailConnection(array $config): ServiceResult
    {
        try {
            if (function_exists('exec')) {
                $output = [];
                $returnVar = 0;

                exec("which {$config['path']}", $output, $returnVar);

                if ($returnVar === 0) {
                    return ServiceResult::success(
                        $config,
                        'Sendmail encontrado e disponível no sistema',
                    );
                } else {
                    return ServiceResult::error(
                        OperationStatus::ERROR,
                        'Sendmail não encontrado no caminho especificado',
                    );
                }
            } else {
                return ServiceResult::success(
                    $config,
                    'Sendmail disponível (função exec não disponível para teste)',
                );
            }

        } catch (Exception $e) {
            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao testar sendmail: '.$e->getMessage()
            );
        }
    }

    /**
     * Obtém provedor padrão baseado no ambiente.
     */
    private function getDefaultProvider(): array
    {
        $environment = app()->environment();

        return match ($environment) {
            'local', 'testing' => [
                'provider' => 'log',
                'config' => $this->getProviderConfig('log'),
                'reason' => 'Ambiente de desenvolvimento sem Mailtrap configurado',
            ],
            'production' => [
                'provider' => 'smtp',
                'config' => $this->getProviderConfig('smtp'),
                'reason' => 'Ambiente de produção usando SMTP padrão',
            ],
            default => [
                'provider' => 'log',
                'config' => $this->getProviderConfig('log'),
                'reason' => 'Ambiente desconhecido usando log como fallback',
            ],
        };
    }

    /**
     * Limpa cache de configurações de provedor.
     */
    public function clearProviderCache(): ServiceResult
    {
        try {
            Cache::forget($this->cacheKey.'_current');

            Log::info('Cache de provedor de e-mail limpo');

            return ServiceResult::success(
                null,
                'Cache de provedor limpo com sucesso',
            );

        } catch (Exception $e) {
            Log::error('Erro ao limpar cache de provedor', [
                'error' => $e->getMessage(),
            ]);

            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao limpar cache: '.$e->getMessage()
            );
        }
    }

    /**
     * Obtém estatísticas de uso dos provedores.
     */
    public function getProviderStats(): array
    {
        try {
            $currentProvider = $this->getCurrentProvider();

            $stats = [
                'current_provider' => $currentProvider['provider'],
                'available_providers' => [],
                'environment' => app()->environment(),
                'timestamp' => now()->toDateTimeString(),
            ];

            foreach ($this->getAvailableProviders() as $provider) {
                $stats['available_providers'][] = [
                    'id' => $provider['id'],
                    'name' => $provider['name'],
                    'is_configured' => $provider['is_configured'],
                    'is_current' => $provider['id'] === $currentProvider['provider'],
                    'type' => $provider['type'],
                ];
            }

            return $stats;

        } catch (Exception $e) {
            Log::error('Erro ao obter estatísticas de provedor', [
                'error' => $e->getMessage(),
            ]);

            return [
                'error' => 'Erro ao obter estatísticas: '.$e->getMessage(),
                'timestamp' => now()->toDateTimeString(),
            ];
        }
    }
}
