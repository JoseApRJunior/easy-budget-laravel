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
                ->where('id',