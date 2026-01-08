<?php

declare(strict_types=1);

namespace App\Actions\Budget;

use App\Mail\BudgetNotificationMail;
use App\Models\Budget;
use App\Services\Domain\BudgetShareService;
use App\Services\Infrastructure\BudgetPdfService;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SendBudgetToCustomerAction
{
    public function __construct(
        private BudgetPdfService $pdfService,
        private BudgetShareService $shareService
    ) {}

    /**
     * Envia o orçamento para o e-mail do cliente.
     */
    public function execute(Budget $budget, ?string $customMessage = null): ServiceResult
    {
        try {
            // 0. Carregar relações iniciais
            $budget->loadMissing(['customer.commonData', 'tenant', 'tenant.provider.commonData']);
            $customer = $budget->customer;

            if (! $customer || ! $customer->email) {
                return ServiceResult::error('Cliente sem e-mail cadastrado.');
            }

            $recipientName = $customer->commonData->name ?? $customer->email;

            // Variáveis para uso fora da transação
            $publicUrl = null;
            $pdfPath = null;
            $company = [];

            // Executar operações de banco dentro da transação
            DB::transaction(function () use ($budget, $customer, $recipientName, &$publicUrl, &$pdfPath, &$company) {
                // 1. Gerar ou recuperar Token Público via BudgetShareService
                $shareResult = $this->shareService->createShare([
                    'budget_id' => $budget->id,
                    'tenant_id' => $budget->tenant_id,
                    'recipient_email' => $customer->email,
                    'recipient_name' => $recipientName,
                    'permissions' => ['view', 'comment', 'approve'],
                    'expires_at' => now()->addDays(7)->toDateTimeString(),
                    'message' => 'Orçamento enviado para aprovação.',
                ], false); // Não envia notificação duplicada

                if ($shareResult->isError()) {
                    throw new Exception('Erro ao gerar link de compartilhamento: ' . $shareResult->getMessage());
                }

                $share = $shareResult->getData();
                $publicUrl = route('budgets.public.shared.view', ['token' => $share->share_token]);

                // 2. Preparar dados para o PDF
                $provider = $budget->tenant->provider()->with(['commonData', 'address', 'contact'])->first();
                $pdfPath = $this->pdfService->generatePdf($budget, ['provider' => $provider]);

                // 3. Atualizar orçamento: status para pendente e anexo
                $budget->update([
                    'status' => \App\Enums\BudgetStatus::PENDING,
                    'attachment' => $pdfPath,
                ]);

                // 4. Preparar dados da empresa
                $company = [
                    'company_name' => $budget->tenant->name ?? config('app.name'),
                    'email' => $budget->tenant->email ?? config('mail.from.address'),
                ];

                // 5. Registrar histórico
                if (method_exists($budget, 'actionHistory')) {
                    $budget->actionHistory()->create([
                        'tenant_id' => $budget->tenant_id,
                        'action' => 'sent',
                        'description' => 'Orçamento processado para envio.',
                        'user_id' => auth()->id(),
                    ]);
                }
            });

            // 5. Enviar E-mail (FORA DA TRANSAÇÃO para evitar timeout de banco)
            Mail::to($customer->email)->send(new BudgetNotificationMail(
                budget: $budget,
                customer: $customer,
                notificationType: 'sent_to_customer',
                tenant: $budget->tenant,
                company: $company,
                publicUrl: $publicUrl,
                customMessage: $customMessage
            ));

            $msg = 'Orçamento enviado e produtos reservados com sucesso!';

            return ServiceResult::success(null, $msg);

        } catch (Exception $e) {
            // Retorna apenas a mensagem da exceção, que agora está mais limpa
            return ServiceResult::error($e->getMessage());
        }
    }
}
