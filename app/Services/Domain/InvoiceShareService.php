<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Enums\InvoiceShareStatus;
use App\Enums\OperationStatus;
use App\Models\Invoice;
use App\Models\InvoiceShare;
use App\Repositories\InvoiceRepository;
use App\Repositories\InvoiceShareRepository;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Services\Infrastructure\EmailService;
use App\Support\ServiceResult;
use Illuminate\Support\Str;

class InvoiceShareService extends AbstractBaseService
{
    public function __construct(
        private InvoiceShareRepository $invoiceShareRepository,
        private InvoiceRepository $invoiceRepository,
        private EmailService $emailService,
    ) {
        parent::__construct($invoiceShareRepository);
    }

    /**
     * Cria um novo compartilhamento de fatura
     */
    public function createShare(array $data, bool $sendNotification = true): ServiceResult
    {
        return $this->safeExecute(function () use ($data, $sendNotification) {
            // Validações básicas
            if (empty($data['invoice_id'])) {
                return $this->error(OperationStatus::VALIDATION_ERROR, 'Invoice ID é obrigatório.');
            }

            // Verifica se a fatura existe (tenant isolation via global scope)
            $invoice = $this->invoiceRepository->find($data['invoice_id']);

            if (! $invoice) {
                return $this->error(OperationStatus::NOT_FOUND, 'Fatura não encontrada.');
            }

            // Gera token único
            $token = Str::random(43);

            $shareData = [
                'tenant_id' => $invoice->tenant_id,
                'invoice_id' => $invoice->id,
                'share_token' => $token,
                'recipient_email' => $data['recipient_email'] ?? null,
                'recipient_name' => $data['recipient_name'] ?? null,
                'message' => $data['message'] ?? null,
                'permissions' => $data['permissions'] ?? ['view' => true, 'download' => true, 'pay' => true],
                'expires_at' => $data['expires_at'] ?? now()->addDays(30),
                'is_active' => true,
                'status' => InvoiceShareStatus::ACTIVE->value,
                'access_count' => 0,
            ];

            $share = $this->invoiceShareRepository->create($shareData);

            if ($sendNotification && ! empty($share->recipient_email)) {
                $shareUrl = route('services.public.invoices.public.show', ['hash' => $share->share_token]);
                $this->emailService->sendInvoiceShareNotification($share->recipient_email, [
                    'invoice_id' => $invoice->id,
                    'share_url' => $shareUrl,
                    'message' => $share->message,
                ]);
            }

            return $this->success($share, 'Link de fatura gerado com sucesso.');
        }, 'Erro ao gerar link da fatura.');
    }

    /**
     * Obtém uma fatura através do token de compartilhamento.
     * Valida se o token é válido e ativo.
     */
    public function getInvoiceByToken(string $token): ServiceResult
    {
        return $this->safeExecute(function () use ($token) {
            $share = $this->invoiceShareRepository->findActiveByToken($token);

            if (! $share) {
                return $this->error(OperationStatus::NOT_FOUND, 'Link inválido ou expirado.');
            }

            $invoice = Invoice::withoutGlobalScopes()->find($share->invoice_id);

            if (! $invoice) {
                return $this->error(OperationStatus::NOT_FOUND, 'Fatura não encontrada.');
            }

            // Incrementa contador de acesso
            $share->increment('access_count');
            $share->update(['last_accessed_at' => now()]);

            // Anexa as permissões ao objeto da fatura para uso no controller/view
            $invoice->share_permissions = $share->permissions;
            $invoice->share_token = $share->share_token;

            return $this->success($invoice);
        }, 'Erro ao recuperar fatura pelo link.');
    }

    /**
     * Revoga um link de compartilhamento.
     */
    public function revokeShare(string $token): ServiceResult
    {
        return $this->safeExecute(function () use ($token) {
            $share = $this->invoiceShareRepository->findByToken($token);

            if (! $share) {
                return $this->error(OperationStatus::NOT_FOUND, 'Link não encontrado.');
            }

            $share->update([
                'is_active' => false,
                'status' => InvoiceShareStatus::EXPIRED->value,
                'expires_at' => now(),
            ]);

            return $this->success($share, 'Link revogado com sucesso.');
        }, 'Erro ao revogar link.');
    }
}
