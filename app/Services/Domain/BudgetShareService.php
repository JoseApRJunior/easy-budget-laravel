<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\DTOs\Budget\BudgetShareDTO;
use App\Enums\OperationStatus;
use App\Models\Budget;
use App\Models\BudgetShare;
use App\Repositories\BudgetRepository;
use App\Repositories\BudgetShareRepository;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Services\Infrastructure\EmailService;
use App\Support\ServiceResult;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BudgetShareService extends AbstractBaseService
{
    public function __construct(
        private BudgetShareRepository $budgetShareRepository,
        private BudgetRepository $budgetRepository,
        private EmailService $emailService,
    ) {
        parent::__construct($budgetShareRepository);
    }

    /**
     * Rejeita um compartilhamento (recusa o acesso)
     */
    public function rejectShare(string $token): ServiceResult
    {
        return $this->safeExecute(function () use ($token) {
            $share = $this->budgetShareRepository->findByToken($token);

            if (! $share) {
                // Fallback para public_token na tabela budgets
                $budget = Budget::where('public_token', $token)->first();
                if ($budget) {
                    $budget->update([
                        'status' => \App\Enums\BudgetStatus::REJECTED,
                        'public_token' => null,
                        'public_expires_at' => null,
                    ]);
                    return $this->success(null, 'Orçamento rejeitado com sucesso.');
                }
                return $this->error(OperationStatus::NOT_FOUND, 'Compartilhamento não encontrado.');
            }

            // Verifica se já está rejeitado
            if ($share->status === 'rejected') {
                return $this->error(OperationStatus::VALIDATION_ERROR, 'Compartilhamento já rejeitado.');
            }

            // Atualiza o status para rejeitado
            $this->budgetShareRepository->update($share->id, [
                'status' => 'rejected',
                'is_active' => false,
                'rejected_at' => now(),
            ]);

            return $this->success($share, 'Compartilhamento rejeitado com sucesso.');
        }, 'Erro ao rejeitar compartilhamento.');
    }

    /**
     * Aprova o orçamento vinculado ao compartilhamento
     */
    public function approveBudget(string $token): ServiceResult
    {
        return $this->safeExecute(function () use ($token) {
            $share = $this->budgetShareRepository->findByToken($token);
            $budget = null;

            if (! $share) {
                // Fallback para public_token na tabela budgets
                $budget = Budget::where('public_token', $token)->first();
                if (! $budget) {
                    return $this->error(OperationStatus::NOT_FOUND, 'Compartilhamento inválido ou inativo.');
                }
            } else {
                if (! $share->is_active) {
                    return $this->error(OperationStatus::NOT_FOUND, 'Compartilhamento inválido ou inativo.');
                }
                $budget = $this->budgetRepository->find($share->budget_id);
            }

            if (! $budget) {
                return $this->error(OperationStatus::NOT_FOUND, 'Orçamento não encontrado.');
            }

            // Atualiza o status do orçamento para aprovado
            $this->budgetRepository->update($budget->id, [
                'status' => \App\Enums\BudgetStatus::APPROVED,
                'approved_at' => now(),
            ]);

            // Marca o compartilhamento como concluído/aprovado se for um compartilhamento real
            if ($share) {
                $this->budgetShareRepository->update($share->id, [
                    'status' => 'approved',
                    'is_active' => false,
                ]);
            } else {
                // Se for fallback, limpa o token do orçamento para evitar reuso
                $budget->update([
                    'public_token' => null,
                    'public_expires_at' => null,
                ]);
            }

            return $this->success($budget, 'Orçamento aprovado com sucesso.');
        }, 'Erro ao aprovar orçamento.');
    }

    /**
     * Cria um novo compartilhamento de orçamento
     */
    public function createShare(array $data, bool $sendNotification = true): ServiceResult
    {
        return $this->safeExecute(function () use ($data, $sendNotification) {
            // Validações de negócio
            $validation = $this->validateShareData($data);
            if (! $validation['valid']) {
                return $this->error(OperationStatus::VALIDATION_ERROR, $validation['message']);
            }

            // Verifica se o orçamento existe (tenant isolation via global scope)
            $budget = $this->budgetRepository->find($data['budget_id']);

            if (! $budget) {
                return $this->error(OperationStatus::NOT_FOUND, 'Orçamento não encontrado.');
            }

            // Prepara dados para o DTO
            $data['share_token'] = $this->generateUniqueToken();
            $data['is_active'] = true;
            $data['access_count'] = 0;
            $data['status'] = 'active';

            $dto = BudgetShareDTO::fromArray($data);

            // Cria o compartilhamento
            $share = $this->budgetShareRepository->createFromDTO($dto);

            // Envia email de notificação apenas se solicitado
            if ($sendNotification) {
                $this->sendShareNotification($share, $budget);
            }

            return $this->success($share, 'Compartilhamento criado com sucesso.');
        }, 'Erro ao criar compartilhamento.');
    }

    /**
     * Valida acesso ao orçamento compartilhado
     */
    public function validateAccess(string $token): ServiceResult
    {
        return $this->safeExecute(function () use ($token) {
            $share = $this->budgetShareRepository->findByToken($token);

            // Fallback: Se não encontrar em budget_shares, procura no public_token da tabela budgets
            if (! $share) {
                $budget = Budget::where('public_token', $token)->first();

                if ($budget) {
                    // Verifica expiração do public_token
                    if ($budget->public_expires_at && now()->gt($budget->public_expires_at)) {
                        return $this->error(OperationStatus::EXPIRED, 'Este link de orçamento expirou.');
                    }

                    // Cria um objeto genérico de compartilhamento para manter a compatibilidade com a view
                    $share = new BudgetShare([
                        'budget_id' => $budget->id,
                        'share_token' => $token,
                        'permissions' => ['view', 'approve', 'comment'],
                        'is_active' => true,
                        'status' => 'active',
                        'tenant_id' => $budget->tenant_id,
                    ]);
                    
                    // Importante: Não salvar no banco aqui, apenas retornar o objeto em memória
                }
            }

            if (! $share || ! $share->is_active) {
                return $this->error(OperationStatus::NOT_FOUND, 'Token de compartilhamento inválido ou inativo.');
            }

            // Verifica expiração para BudgetShare real
            if ($share->id && $share->expires_at && now()->gt($share->expires_at)) {
                $this->budgetShareRepository->update($share->id, ['is_active' => false, 'status' => 'expired']);

                return $this->error(OperationStatus::EXPIRED, 'Token de compartilhamento expirado.');
            }

            // Incrementa contador de acesso se for um compartilhamento real (com ID)
            if ($share->id) {
                $this->budgetShareRepository->update($share->id, [
                    'access_count' => $share->access_count + 1,
                    'last_accessed_at' => now(),
                ]);
            }

            // Carrega o orçamento com relacionamentos necessários se não tiver sido carregado no fallback
            if (!isset($budget)) {
                $budget = $this->budgetRepository->find($share->budget_id, ['tenant', 'customer', 'services.serviceItems']);
            } else {
                $budget->load(['tenant', 'customer', 'services.serviceItems']);
            }

            if (! $budget) {
                return $this->error(OperationStatus::NOT_FOUND, 'Orçamento não encontrado.');
            }

            return $this->success([
                'share' => $share,
                'budget' => $budget,
                'permissions' => $share->permissions ?? ['view', 'approve', 'comment'],
            ], 'Acesso válido.');
        }, 'Erro ao validar acesso.');
    }

    /**
     * Revoga um compartilhamento
     */
    public function revokeShare(int $shareId): ServiceResult
    {
        return $this->safeExecute(function () use ($shareId) {
            $share = $this->budgetShareRepository->find($shareId);

            if (! $share) {
                return $this->error(OperationStatus::NOT_FOUND, 'Compartilhamento não encontrado.');
            }

            $this->budgetShareRepository->update($shareId, ['is_active' => false]);

            return $this->success($share, 'Compartilhamento revogado com sucesso.');
        }, 'Erro ao revogar compartilhamento.');
    }

    /**
     * Lista compartilhamentos de um orçamento específico
     */
    public function getSharesByBudget(int $budgetId, array $filters = []): ServiceResult
    {
        return $this->safeExecute(function () use ($budgetId) {
            // Verifica se o orçamento existe
            $budget = $this->budgetRepository->find($budgetId);

            if (! $budget) {
                return $this->error(OperationStatus::NOT_FOUND, 'Orçamento não encontrado.');
            }

            $shares = $this->budgetShareRepository->listByBudget(
                $budgetId,
                ['created_at' => 'desc']
            );

            return $this->success($shares, 'Compartilhamentos listados com sucesso.');
        }, 'Erro ao listar compartilhamentos.');
    }

    /**
     * Renova um token de compartilhamento
     */
    public function renewToken(int $shareId, ?string $newExpiry = null): ServiceResult
    {
        return $this->safeExecute(function () use ($shareId, $newExpiry) {
            $share = $this->budgetShareRepository->find($shareId);

            if (! $share) {
                return $this->error(OperationStatus::NOT_FOUND, 'Compartilhamento não encontrado.');
            }

            $updateData = [
                'share_token' => $this->generateUniqueToken(),
                'access_count' => 0,
                'last_accessed_at' => null,
                'is_active' => true,
                'status' => 'active',
            ];

            if ($newExpiry) {
                $updateData['expires_at'] = $newExpiry;
            }

            $this->budgetShareRepository->update($shareId, $updateData);

            return $this->success($this->budgetShareRepository->find($shareId), 'Token renovado com sucesso.');
        }, 'Erro ao renovar token.');
    }

    /**
     * Adiciona um comentário a um orçamento via compartilhamento
     */
    public function addComment(string $token, array $data): ServiceResult
    {
        return $this->safeExecute(function () use ($token, $data) {
            $share = $this->budgetShareRepository->findByToken($token);
            $budget = null;

            if (! $share) {
                // Fallback para public_token na tabela budgets
                $budget = Budget::where('public_token', $token)->first();
                if (! $budget || ($budget->public_expires_at && now()->gt($budget->public_expires_at))) {
                    return $this->error(OperationStatus::NOT_FOUND, 'Compartilhamento inválido ou inativo.');
                }
            } else {
                if (! $share->is_active) {
                    return $this->error(OperationStatus::NOT_FOUND, 'Compartilhamento inválido ou inativo.');
                }
                $budget = $this->budgetRepository->find($share->budget_id);
            }

            if (! $budget) {
                return $this->error(OperationStatus::NOT_FOUND, 'Orçamento não encontrado.');
            }

            // Registra no histórico de ações do orçamento
            \App\Models\BudgetActionHistory::create([
                'tenant_id' => $budget->tenant_id,
                'budget_id' => $budget->id,
                'action' => 'comment',
                'description' => $data['comment'] ?? '',
                'metadata' => [
                    'author_name' => $data['name'] ?? 'Cliente',
                    'author_email' => $data['email'] ?? '',
                    'via' => 'public_share',
                    'share_token' => $token,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return $this->success(null, 'Comentário enviado com sucesso.');
        }, 'Erro ao enviar comentário.');
    }

    /**
     * Valida dados do compartilhamento
     */
    private function validateShareData(array $data): array
    {
        $required = ['budget_id', 'recipient_email', 'recipient_name'];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['valid' => false, 'message' => "Campo obrigatório: {$field}"];
            }
        }

        if (! filter_var($data['recipient_email'], FILTER_VALIDATE_EMAIL)) {
            return ['valid' => false, 'message' => 'Email inválido.'];
        }

        if (isset($data['expires_at']) && ! strtotime($data['expires_at'])) {
            return ['valid' => false, 'message' => 'Data de expiração inválida.'];
        }

        return ['valid' => true, 'message' => 'Dados válidos.'];
    }

    /**
     * Gera token único para compartilhamento
     */
    private function generateUniqueToken(): string
    {
        do {
            $token = Str::random(32);
            $exists = BudgetShare::where('share_token', $token)->exists();
        } while ($exists);

        return $token;
    }

    /**
     * Envia notificação de compartilhamento
     */
    private function sendShareNotification(BudgetShare $share, Budget $budget): void
    {
        try {
            $shareUrl = config('app.url')."/budgets/shared/{$share->share_token}";

            $emailData = [
                'recipient_name' => $share->recipient_name,
                'budget_code' => $budget->code,
                'budget_total' => $budget->total,
                'share_url' => $shareUrl,
                'expires_at' => $share->expires_at,
                'message' => $share->message,
            ];

            $this->emailService->sendBudgetShareNotification(
                $share->recipient_email,
                $emailData
            );
        } catch (\Exception $e) {
            // Log do erro mas não falha a operação
            Log::error('Erro ao enviar notificação de compartilhamento', [
                'share_id' => $share->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Define filtros suportados
     */
    protected function getSupportedFilters(): array
    {
        return [
            'id',
            'budget_id',
            'recipient_email',
            'recipient_name',
            'is_active',
            'expires_at',
            'created_at',
            'updated_at',
        ];
    }

    /**
     * Obtém estatísticas de compartilhamentos para o tenant
     */
    public function getShareStats(int $tenantId): ServiceResult
    {
        try {
            $totalShares = BudgetShare::where('tenant_id', $tenantId)->count();
            $activeShares = BudgetShare::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->where(function ($query) {
                    $query->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })->count();
            $expiredShares = BudgetShare::where('tenant_id', $tenantId)
                ->where(function ($query) {
                    $query->where('is_active', false)
                        ->orWhere(function ($q) {
                            $q->whereNotNull('expires_at')
                                ->where('expires_at', '<=', now());
                        });
                })->count();
            $recentShares = BudgetShare::where('tenant_id', $tenantId)
                ->with(['budget', 'budget.customer.commonData'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
            $mostSharedBudgets = Budget::where('tenant_id', $tenantId)
                ->withCount('shares')
                ->having('shares_count', '>', 0)
                ->orderBy('shares_count', 'desc')
                ->limit(5)
                ->get();
            $totalAccesses = BudgetShare::where('tenant_id', $tenantId)
                ->sum('access_count');

            $stats = [
                'total_shares' => $totalShares,
                'active_shares' => $activeShares,
                'expired_shares' => $expiredShares,
                'recent_shares' => $recentShares,
                'most_shared_budgets' => $mostSharedBudgets,
                'access_count' => $totalAccesses,
            ];

            return $this->success($stats);
        } catch (\Exception $e) {
            return $this->error(OperationStatus::ERROR, 'Erro ao obter estatísticas: '.$e->getMessage());
        }
    }
}
