<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Enums\OperationStatus;
use App\Models\Budget;
use App\Models\BudgetShare;
use App\Repositories\BudgetShareRepository;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Services\Infrastructure\EmailService;
use App\Support\ServiceResult;
use Illuminate\Support\Str;
use InvalidArgumentException;

class BudgetShareService extends AbstractBaseService
{
    public function __construct(
        private BudgetShareRepository $budgetShareRepository,
        private EmailService $emailService,
    ) {
        parent::__construct($budgetShareRepository);
    }

    /**
     * Rejeita um compartilhamento (recusa o acesso)
     */
    public function rejectShare(string $token): ServiceResult
    {
        try {
            $share = BudgetShare::where('share_token', $token)->first();

            if (!$share) {
                return $this->error(OperationStatus::NOT_FOUND, 'Compartilhamento não encontrado.');
            }

            // Verifica se já está rejeitado
            if ($share->status === 'rejected') {
                return $this->error(OperationStatus::VALIDATION_ERROR, 'Compartilhamento já rejeitado.');
            }

            // Atualiza o status para rejeitado
            $share->update([
                'status' => 'rejected',
                'is_active' => false,
                'rejected_at' => now()
            ]);

            return $this->success($share, 'Compartilhamento rejeitado com sucesso.');
        } catch (\Exception $e) {
            return $this->error(OperationStatus::ERROR, 'Erro ao rejeitar compartilhamento.', null, $e);
        }
    }

    /**
     * Cria um novo compartilhamento de orçamento
     */
    public function createShare(array $data): ServiceResult
    {
        try {
            // Validações de negócio
            $validation = $this->validateShareData($data);
            if (!$validation['valid']) {
                return $this->error(OperationStatus::VALIDATION_ERROR, $validation['message']);
            }

            // Verifica se o orçamento existe e pertence ao tenant
            $budget = Budget::where('id', $data['budget_id'])
                ->where('tenant_id', $this->tenantId())
                ->first();

            if (!$budget) {
                return $this->error(OperationStatus::NOT_FOUND, 'Orçamento não encontrado ou não pertence ao tenant atual.');
            }

            // Gera token único
            $data['share_token'] = $this->generateUniqueToken();
            $data['tenant_id'] = $this->tenantId();
            $data['is_active'] = true;
            $data['access_count'] = 0;

            // Cria o compartilhamento
            $share = $this->create($data);

            if (!$share->isSuccess()) {
                return $share;
            }

            // Envia email de notificação
            $this->sendShareNotification($share->getData(), $budget);

            return $this->success($share->getData(), 'Compartilhamento criado com sucesso.');
        } catch (\Exception $e) {
            return $this->error(OperationStatus::ERROR, 'Erro ao criar compartilhamento.', null, $e);
        }
    }

    /**
     * Valida acesso ao orçamento compartilhado
     */
    public function validateAccess(string $token): ServiceResult
    {
        try {
            $share = BudgetShare::where('share_token', $token)
                ->where('is_active', true)
                ->where('tenant_id', $this->tenantId())
                ->first();

            if (!$share) {
                return $this->error(OperationStatus::NOT_FOUND, 'Token de compartilhamento inválido ou inativo.');
            }

            // Verifica expiração
            if ($share->expires_at && now()->gt($share->expires_at)) {
                $share->update(['is_active' => false]);
                return $this->error(OperationStatus::EXPIRED, 'Token de compartilhamento expirado.');
            }

            // Incrementa contador de acesso
            $share->increment('access_count');
            $share->update(['last_accessed_at' => now()]);

            // Carrega o orçamento com relacionamentos necessários
            $budget = Budget::with(['customer', 'items', 'user'])
                ->where('id', $share->budget_id)
                ->where('tenant_id', $this->tenantId())
                ->first();

            if (!$budget) {
                return $this->error(OperationStatus::NOT_FOUND, 'Orçamento não encontrado.');
            }

            return $this->success([
                'share' => $share,
                'budget' => $budget,
                'permissions' => $share->permissions ?? ['view'],
            ], 'Acesso válido.');
        } catch (\Exception $e) {
            return $this->error(OperationStatus::ERROR, 'Erro ao validar acesso.', null, $e);
        }
    }

    /**
     * Revoga um compartilhamento
     */
    public function revokeShare(int $shareId): ServiceResult
    {
        try {
            $share = BudgetShare::where('id', $shareId)
                ->where('tenant_id', $this->tenantId())
                ->first();

            if (!$share) {
                return $this->error(OperationStatus::NOT_FOUND, 'Compartilhamento não encontrado.');
            }

            $share->update(['is_active' => false]);

            return $this->success($share, 'Compartilhamento revogado com sucesso.');
        } catch (\Exception $e) {
            return $this->error(OperationStatus::ERROR, 'Erro ao revogar compartilhamento.', null, $e);
        }
    }

    /**
     * Lista compartilhamentos de um orçamento específico
     */
    public function getSharesByBudget(int $budgetId, array $filters = []): ServiceResult
    {
        try {
            // Verifica se o orçamento pertence ao tenant
            $budget = Budget::where('id', $budgetId)
                ->where('tenant_id', $this->tenantId())
                ->first();

            if (!$budget) {
                return $this->error(OperationStatus::NOT_FOUND, 'Orçamento não encontrado.');
            }

            $query = BudgetShare::where('budget_id', $budgetId)
                ->where('tenant_id', $this->tenantId());

            // Aplica filtros adicionais
            if (isset($filters['is_active'])) {
                $query->where('is_active', $filters['is_active']);
            }

            if (isset($filters['recipient_email'])) {
                $query->where('recipient_email', 'like', '%' . $filters['recipient_email'] . '%');
            }

            $shares = $query->orderBy('created_at', 'desc')->get();

            return $this->success($shares, 'Compartilhamentos listados com sucesso.');
        } catch (\Exception $e) {
            return $this->error(OperationStatus::ERROR, 'Erro ao listar compartilhamentos.', null, $e);
        }
    }

    /**
     * Renova um token de compartilhamento
     */
    public function renewToken(int $shareId, ?string $newExpiry = null): ServiceResult
    {
        try {
            $share = BudgetShare::where('id', $shareId)
                ->where('tenant_id', $this->tenantId())
                ->first();

            if (!$share) {
                return $this->error(OperationStatus::NOT_FOUND, 'Compartilhamento não encontrado.');
            }

            $updateData = [
                'share_token' => $this->generateUniqueToken(),
                'access_count' => 0,
                'last_accessed_at' => null,
            ];

            if ($newExpiry) {
                $updateData['expires_at'] = $newExpiry;
            }

            $share->update($updateData);

            return $this->success($share, 'Token renovado com sucesso.');
        } catch (\Exception $e) {
            return $this->error(OperationStatus::ERROR, 'Erro ao renovar token.', null, $e);
        }
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

        if (!filter_var($data['recipient_email'], FILTER_VALIDATE_EMAIL)) {
            return ['valid' => false, 'message' => 'Email inválido.'];
        }

        if (isset($data['expires_at']) && !strtotime($data['expires_at'])) {
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
            $shareUrl = config('app.url') . "/budgets/shared/{$share->share_token}";

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
            \Log::error('Erro ao enviar notificação de compartilhamento', [
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
                'access_count' => $totalAccesses
            ];

            return $this->success($stats);
        } catch (\Exception $e) {
            return $this->error(OperationStatus::ERROR, 'Erro ao obter estatísticas: ' . $e->getMessage());
        }
    }
}
