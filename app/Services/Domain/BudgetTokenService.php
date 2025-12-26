<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Enums\BudgetStatus;
use App\Models\Budget;
use App\Models\UserConfirmationToken;
use App\Repositories\UserConfirmationTokenRepository;
use App\Support\ServiceResult;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Serviço para gestão de tokens públicos de orçamentos.
 *
 * Substitui o sistema legacy SharedService para tokens de aprovação,
 * implementando geração segura, validação e expiração automática.
 */
class BudgetTokenService
{
    /**
     * Tipos de token suportados.
     */
    private const TOKEN_TYPE = 'budget_approval';

    /**
     * Expiração padrão em horas.
     */
    private const DEFAULT_EXPIRATION_HOURS = 24;

    /**
     * Expiração extendida em dias.
     */
    private const EXTENDED_EXPIRATION_DAYS = 7;

    /**
     * Comprimento do token gerado.
     */
    private const TOKEN_LENGTH = 43;

    public function __construct(
        private UserConfirmationTokenRepository $tokenRepository,
    ) {}

    /**
     * Gera token público para orçamento.
     *
     * Remove tokens antigos do usuário e cria novo token com expiração
     * para evitar acúmulo de tokens inválidos.
     *
     * @param  Budget  $budget  Orçamento
     * @param  int  $expirationHours  Horas de expiração
     * @return ServiceResult Resultado com token ou erro
     */
    public function generatePublicToken(Budget $budget, int $expirationHours = self::DEFAULT_EXPIRATION_HOURS): ServiceResult
    {
        try {
            $userId = $budget->customer?->user_id;

            if (! $userId) {
                return ServiceResult::error('Cliente não possui usuário associado para gerar token');
            }

            // Remove tokens antigos do usuário
            $this->cleanOldTokens($userId);

            // Gera novo token
            $token = $this->generateSecureToken();
            $expiresAt = now()->addHours($expirationHours);

            // Cria registro na tabela de confirmação de usuário
            $tokenRecord = $this->tokenRepository->create([
                'user_id' => $userId,
                'tenant_id' => $budget->tenant_id,
                'token' => $token,
                'expires_at' => $expiresAt,
                'type' => self::TOKEN_TYPE,
            ]);

            // Atualiza orçamento com token e expiração
            $budget->update([
                'public_token' => $token,
                'public_expires_at' => $expiresAt,
            ]);

            return ServiceResult::success([
                'token' => $token,
                'expires_at' => $expiresAt,
                'url' => $this->buildApprovalUrl($token, $budget->code),
            ], 'Token público gerado com sucesso');

        } catch (\Exception $e) {
            return ServiceResult::error('Erro ao gerar token público: '.$e->getMessage());
        }
    }

    /**
     * Valida token público para orçamento.
     *
     * @param  string  $token  Token público
     * @param  string  $budgetCode  Código do orçamento
     * @return ServiceResult Resultado com dados do orçamento ou erro
     */
    public function validatePublicToken(string $token, string $budgetCode): ServiceResult
    {
        try {
            // Busca token no repositório
            $tokenRecord = $this->tokenRepository->findByToken($token);

            if (! $tokenRecord) {
                return ServiceResult::error('Token inválido ou não encontrado');
            }

            // Verifica expiração
            if ($tokenRecord->expires_at->isPast()) {
                return ServiceResult::error('Token expirado');
            }

            // Busca orçamento pelo código
            $budget = Budget::where('code', $budgetCode)
                ->where('public_token', $token)
                ->first();

            if (! $budget) {
                return ServiceResult::error('Orçamento não encontrado ou token inválido');
            }

            // Verifica se orçamento está em status válido para aprovação
            if ($budget->status !== BudgetStatus::PENDING->value) {
                return ServiceResult::error('Orçamento não está disponível para aprovação');
            }

            return ServiceResult::success([
                'budget' => $budget,
                'customer' => $budget->customer,
                'token_record' => $tokenRecord,
                'expires_at' => $tokenRecord->expires_at,
            ], 'Token válido');

        } catch (\Exception $e) {
            return ServiceResult::error('Erro ao validar token: '.$e->getMessage());
        }
    }

    /**
     * Regenera token com nova expiração.
     *
     * @param  Budget  $budget  Orçamento
     * @param  int  $expirationHours  Novas horas de expiração
     * @return ServiceResult Resultado com novo token ou erro
     */
    public function regenerateToken(Budget $budget, int $expirationHours = self::DEFAULT_EXPIRATION_HOURS): ServiceResult
    {
        try {
            // Remove token atual
            $budget->update([
                'public_token' => null,
                'public_expires_at' => null,
            ]);

            // Remove tokens relacionados na tabela de confirmação
            $userId = $budget->customer?->user_id;
            if ($userId) {
                $this->tokenRepository->deleteByUserId($userId);
            }

            // Gera novo token
            return $this->generatePublicToken($budget, $expirationHours);

        } catch (\Exception $e) {
            return ServiceResult::error('Erro ao regenerar token: '.$e->getMessage());
        }
    }

    /**
     * Revoga token público.
     *
     * @param  Budget  $budget  Orçamento
     * @return ServiceResult Resultado da operação
     */
    public function revokePublicToken(Budget $budget): ServiceResult
    {
        try {
            $userId = $budget->customer?->user_id;

            // Remove token do orçamento
            $budget->update([
                'public_token' => null,
                'public_expires_at' => null,
            ]);

            // Remove token da tabela de confirmação se existir
            if ($userId) {
                $this->tokenRepository->deleteByUserId($userId);
            }

            return ServiceResult::success(null, 'Token revogado com sucesso');

        } catch (\Exception $e) {
            return ServiceResult::error('Erro ao revogar token: '.$e->getMessage());
        }
    }

    /**
     * Limpa tokens expirados automaticamente.
     *
     * @param  int  $tenantId  ID do tenant
     * @return int Número de tokens removidos
     */
    public function cleanupExpiredTokens(int $tenantId): int
    {
        try {
            return UserConfirmationToken::where('tenant_id', $tenantId)
                ->where('expires_at', '<', now())
                ->where('type', self::TOKEN_TYPE)
                ->delete();

        } catch (\Exception $e) {
            Log::error('Erro ao limpar tokens expirados', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * Verifica se token está expirado.
     *
     * @param  string  $token  Token público
     * @return bool True se expirado
     */
    public function isTokenExpired(string $token): bool
    {
        $tokenRecord = $this->tokenRepository->findByToken($token);

        return ! $tokenRecord || $tokenRecord->expires_at->isPast();
    }

    /**
     * Estende expiração do token.
     *
     * @param  string  $token  Token público
     * @param  int  $additionalDays  Dias adicionais
     * @return ServiceResult Resultado da operação
     */
    public function extendTokenExpiration(string $token, int $additionalDays = self::EXTENDED_EXPIRATION_DAYS): ServiceResult
    {
        try {
            $tokenRecord = $this->tokenRepository->findByToken($token);

            if (! $tokenRecord) {
                return ServiceResult::error('Token não encontrado');
            }

            $newExpiration = now()->addDays($additionalDays);
            $tokenRecord->update(['expires_at' => $newExpiration]);

            return ServiceResult::success([
                'expires_at' => $newExpiration,
            ], 'Expiração do token estendida');

        } catch (\Exception $e) {
            return ServiceResult::error('Erro ao estender expiração: '.$e->getMessage());
        }
    }

    /**
     * Obtém estatísticas de tokens do tenant.
     *
     * @param  int  $tenantId  ID do tenant
     * @return array Estatísticas
     */
    public function getTokenStatistics(int $tenantId): array
    {
        $totalTokens = UserConfirmationToken::where('tenant_id', $tenantId)
            ->where('type', self::TOKEN_TYPE)
            ->count();

        $expiredTokens = UserConfirmationToken::where('tenant_id', $tenantId)
            ->where('type', self::TOKEN_TYPE)
            ->where('expires_at', '<', now())
            ->count();

        $activeTokens = UserConfirmationToken::where('tenant_id', $tenantId)
            ->where('type', self::TOKEN_TYPE)
            ->where('expires_at', '>=', now())
            ->count();

        return [
            'total_tokens' => $totalTokens,
            'active_tokens' => $activeTokens,
            'expired_tokens' => $expiredTokens,
            'expiration_rate' => $totalTokens > 0 ? round(($expiredTokens / $totalTokens) * 100, 2) : 0,
        ];
    }

    /**
     * Gera token seguro aleatório.
     *
     * @return string Token gerado
     */
    private function generateSecureToken(): string
    {
        return Str::random(self::TOKEN_LENGTH);
    }

    /**
     * Constrói URL de aprovação para o token.
     *
     * @param  string  $token  Token público
     * @param  string  $budgetCode  Código do orçamento
     * @return string URL completa
     */
    private function buildApprovalUrl(string $token, string $budgetCode): string
    {
        return route('public.budget.approve', [
            'code' => $budgetCode,
            'token' => $token,
        ]);
    }

    /**
     * Remove tokens antigos do usuário.
     *
     * @param  int  $userId  ID do usuário
     */
    private function cleanOldTokens(int $userId): void
    {
        $this->tokenRepository->deleteByUserId($userId);
    }

    /**
     * Obtém informações do token para debug.
     *
     * @param  string  $token  Token público
     * @return array|null Informações do token ou null se não encontrado
     */
    public function getTokenInfo(string $token): ?array
    {
        $tokenRecord = $this->tokenRepository->findByToken($token);

        if (! $tokenRecord) {
            return null;
        }

        return [
            'token' => substr($token, 0, 8).'...', // Parcial por segurança
            'user_id' => $tokenRecord->user_id,
            'tenant_id' => $tokenRecord->tenant_id,
            'type' => $tokenRecord->type,
            'expires_at' => $tokenRecord->expires_at,
            'is_expired' => $tokenRecord->expires_at->isPast(),
            'created_at' => $tokenRecord->created_at,
        ];
    }
}
