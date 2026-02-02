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
     * Aprova o orçamento vinculado ao compartilhamento
     */
    public function approveBudget(string $token, ?string $comment = null): ServiceResult
    {
        return $this->updateBudgetStatus($token, \App\Enums\BudgetStatus::APPROVED, $comment);
    }

    /**
     * Rejeita o orçamento vinculado ao compartilhamento
     */
    public function rejectBudget(string $token, ?string $comment = null): ServiceResult
    {
        return $this->updateBudgetStatus($token, \App\Enums\BudgetStatus::REJECTED, $comment);
    }

    /**
     * Cancela o orçamento vinculado ao compartilhamento
     */
    public function cancelBudget(string $token, ?string $comment = null): ServiceResult
    {
        return $this->updateBudgetStatus($token, \App\Enums\BudgetStatus::CANCELLED, $comment);
    }

    /**
     * Atualiza o status do orçamento via token
     */
    private function updateBudgetStatus(string $token, \App\Enums\BudgetStatus $newStatus, ?string $comment = null): ServiceResult
    {
        return $this->safeExecute(function () use ($token, $newStatus, $comment) {
            return \Illuminate\Support\Facades\DB::transaction(function () use ($token, $newStatus, $comment) {
                $share = $this->budgetShareRepository->findByToken($token);
                $budget = null;

                if (! $share || ! $share->is_active) {
                    return $this->error(OperationStatus::NOT_FOUND, 'Compartilhamento inválido ou inativo.');
                }
                $budget = Budget::withoutGlobalScopes()->find($share->budget_id);

                if (! $budget) {
                    return $this->error(OperationStatus::NOT_FOUND, 'Orçamento não encontrado.');
                }

                $oldStatus = $budget->status instanceof \App\Enums\BudgetStatus ? $budget->status->value : $budget->status;

                // Atualiza o status do orçamento sem escopo global (ação do cliente)
                $budget->status = $newStatus;
                // Usamos a propriedade transiente para passar o comentário para o Observer
                // sem tentar salvar na tabela budgets (coluna removida)
                $budget->transient_customer_comment = $comment;
                $budget->status_updated_at = now();
                $budget->status_updated_by = null;
                $budget->save();

                // Sincroniza o status dos serviços vinculados
                $serviceStatus = match ($newStatus) {
                    \App\Enums\BudgetStatus::APPROVED => \App\Enums\ServiceStatus::SCHEDULING,
                    \App\Enums\BudgetStatus::REJECTED => \App\Enums\ServiceStatus::CANCELLED,
                    \App\Enums\BudgetStatus::CANCELLED => \App\Enums\ServiceStatus::CANCELLED,
                    default => null,
                };

                if ($serviceStatus) {
                    // Buscar serviços para atualizar individualmente através do model
                    // Isso garante que os Observers sejam disparados corretamente
                    $services = $budget->services()->withoutGlobalScopes()->get();
                    foreach ($services as $service) {
                        // Suprimir notificações individuais para evitar flood
                        $service->suppressStatusNotification = true;
                        $service->update([
                            'status' => $serviceStatus->value,
                        ]);
                    }
                }

                // Se o orçamento foi aprovado, desativamos TODOS os outros links ativos deste orçamento
                // mas NÃO mudamos o status deles para approved/rejected, apenas inativamos.
                // O status APPROVED/REJECTED deve ser apenas para o link que efetivou a ação.
                if ($newStatus === \App\Enums\BudgetStatus::APPROVED) {
                    BudgetShare::withoutGlobalScopes()
                        ->where('budget_id', $budget->id)
                        ->where('id', '!=', $share->id ?? 0) // Exclui o share atual
                        ->where('is_active', true)
                        ->update([
                            'is_active' => false,
                            // Mantemos o status original ou mudamos para EXPIRED se preferir,
                            // mas nunca para APPROVED/REJECTED pois eles não realizaram a ação.
                            // 'status' => \App\Enums\BudgetShareStatus::EXPIRED->value
                        ]);
                }

                // Marca o compartilhamento atual com os dados específicos (redundante mas garante o objeto atual)
                if ($share) {
                    $share->status = $newStatus === \App\Enums\BudgetStatus::APPROVED
                        ? \App\Enums\BudgetShareStatus::APPROVED->value
                        : \App\Enums\BudgetShareStatus::REJECTED->value;

                    if ($newStatus === \App\Enums\BudgetStatus::APPROVED) {
                        $share->is_active = true;
                        $share->expires_at = now()->addDays(30);
                    } else {
                        $share->is_active = false;
                        if ($newStatus === \App\Enums\BudgetStatus::REJECTED) {
                            $share->rejected_at = now();
                        }
                    }
                    $share->save();
                } else {
                    // Se for fallback, limpa o token do orçamento apenas se aprovado
                    if ($newStatus === \App\Enums\BudgetStatus::APPROVED) {
                        $budget->public_token = null;
                        $budget->public_expires_at = null;
                        $budget->save();
                    }
                }

                // Registra no histórico de ações do orçamento
                \App\Models\BudgetActionHistory::create([
                    'tenant_id' => $budget->tenant_id,
                    'budget_id' => $budget->id,
                    'action' => $newStatus->value, // Usa o valor do enum (approved/rejected/etc)
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus->value,
                    'description' => $comment ?? match ($newStatus) {
                        \App\Enums\BudgetStatus::APPROVED => 'Orçamento aprovado pelo cliente via link público.',
                        \App\Enums\BudgetStatus::REJECTED => 'Orçamento rejeitado pelo cliente via link público.',
                        \App\Enums\BudgetStatus::CANCELLED => 'Orçamento cancelado pelo cliente via link público.',
                        default => "Status alterado para {$newStatus->value} via link público.",
                    },
                    'metadata' => [
                        'via' => 'public_share',
                        'share_token' => $token,
                        'customer_comment' => $comment,
                    ],
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);

                $message = match ($newStatus) {
                    \App\Enums\BudgetStatus::APPROVED => 'Orçamento aprovado com sucesso.',
                    \App\Enums\BudgetStatus::REJECTED => 'Orçamento rejeitado com sucesso.',
                    \App\Enums\BudgetStatus::CANCELLED => 'Orçamento cancelado com sucesso.',
                    default => 'Status atualizado com sucesso.',
                };

                return $this->success($budget, $message);
            });
        }, 'Erro ao atualizar status do orçamento.');
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

            // Verifica se já existe um compartilhamento ATIVO para este mesmo orçamento e destinatário
            $existingShare = BudgetShare::where('budget_id', $data['budget_id'])
                ->where('recipient_email', $data['recipient_email'])
                ->where('is_active', true)
                ->where(function ($query) {
                    $query->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })
                ->orderBy('created_at', 'desc')
                ->first();

            if ($existingShare) {
                // Se o usuário estiver tentando criar um novo com permissões diferentes ou expiração diferente,
                // podemos decidir atualizar o existente ou retornar o atual.
                // Para manter a inteligência solicitada, vamos retornar o existente para evitar duplicidade de links ativos.

                // Opcional: Atualizar a mensagem ou expiração se foram enviadas novas
                $updateData = [];
                if (isset($data['expires_at'])) {
                    $updateData['expires_at'] = $data['expires_at'];
                }
                if (isset($data['message'])) {
                    $updateData['message'] = $data['message'];
                }
                if (isset($data['permissions'])) {
                    $updateData['permissions'] = $data['permissions'];
                }

                if (! empty($updateData)) {
                    $existingShare->update($updateData);
                }

                // Re-envia a notificação se solicitado
                if ($sendNotification) {
                    $this->sendShareNotification($existingShare, $budget);
                }

                return $this->success($existingShare, 'Link de compartilhamento ativo já existente reutilizado.');
            }

            // Prepara dados para o DTO
            $data['share_token'] = $this->generateUniqueToken();
            $data['is_active'] = true;
            $data['access_count'] = 0;
            $data['status'] = \App\Enums\BudgetShareStatus::ACTIVE->value;

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

            if (! $share) {
                return $this->error(OperationStatus::NOT_FOUND, 'Link de compartilhamento inválido ou expirado.');
            }

            // Verifica se o compartilhamento foi revogado manualmente
            if (! $share->is_active && $share->status !== \App\Enums\BudgetStatus::APPROVED->value) {
                return $this->error(OperationStatus::EXPIRED, 'Este link de compartilhamento não está mais ativo.');
            }

            // Garante que o orçamento está carregado sem escopo global para acesso público
            $budget = $share->budget ?? Budget::withoutGlobalScopes()->find($share->budget_id);

            // Verifica expiração para BudgetShare real
            if ($share->id && $share->expires_at && now()->gt($share->expires_at)) {
                $this->budgetShareRepository->update($share->id, ['is_active' => false, 'status' => \App\Enums\BudgetShareStatus::EXPIRED->value]);

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
            if (! isset($budget)) {
                $budget = $this->budgetRepository->find($share->budget_id, [
                    'tenant.provider.commonData',
                    'tenant.provider.contact',
                    'tenant.provider.address',
                    'tenant.provider.businessData',
                    'customer.commonData',
                    'customer.contact',
                    'customer.address',
                    'services.serviceItems',
                    'services.category',
                ]);
            } else {
                $budget->load([
                    'tenant.provider.commonData',
                    'tenant.provider.contact',
                    'tenant.provider.address',
                    'tenant.provider.businessData',
                    'customer.commonData',
                    'customer.contact',
                    'customer.address',
                    'services.serviceItems',
                    'services.category',
                ]);
            }

            if (! $budget) {
                return $this->error(OperationStatus::NOT_FOUND, 'Orçamento não encontrado.');
            }

            $rawPermissions = $share->permissions ?? ['view', 'print', 'comment', 'approve', 'reject'];
            $formattedPermissions = [
                'can_view' => in_array('view', $rawPermissions),
                'can_download' => in_array('print', $rawPermissions),
                'can_print' => in_array('print', $rawPermissions),
                'can_comment' => in_array('comment', $rawPermissions),
                'can_approve' => in_array('approve', $rawPermissions),
                'can_reject' => in_array('reject', $rawPermissions),
            ];

            return $this->success([
                'share' => $share,
                'budget' => $budget,
                'permissions' => $formattedPermissions,
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
                $budget = Budget::withoutGlobalScopes()->where('public_token', $token)->first();
                if (! $budget || ($budget->public_expires_at && now()->gt($budget->public_expires_at))) {
                    return $this->error(OperationStatus::NOT_FOUND, 'Compartilhamento inválido ou inativo.');
                }
            } else {
                if (! $share->is_active) {
                    return $this->error(OperationStatus::NOT_FOUND, 'Compartilhamento inválido ou inativo.');
                }
                $budget = Budget::withoutGlobalScopes()->find($share->budget_id);
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
                ->orderBy('is_active', 'desc')
                ->orderByRaw('COALESCE(last_accessed_at, created_at) DESC')
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
