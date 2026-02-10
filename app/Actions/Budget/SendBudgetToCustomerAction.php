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
            $budget->loadMissing(['customer.commonData', 'tenant.provider.commonData', 'tenant.provider.address', 'tenant.provider.contact']);
            $customer = $budget->customer;

            if (! $customer || ! $customer->email) {
                return ServiceResult::error('Cliente sem e-mail cadastrado.');
            }

            $recipientName = $customer->commonData->name ?? $customer->email;

            // Preparar dados da empresa (Tenant/Provider) para o e-mail
            $provider = $budget->tenant->provider;
            $companyData = [];

            if ($provider) {
                $commonData = $provider->commonData;
                $address = $provider->address;
                $contact = $provider->contact;

                $addressLine1 = null;
                $addressLine2 = null;
                if ($address) {
                    $addressLine1 = "{$address->address}, {$address->address_number}";
                    if ($address->neighborhood) {
                        $addressLine1 .= " | {$address->neighborhood}";
                    }

                    $addressLine2 = "{$address->city}/{$address->state}";
                    if ($address->cep) {
                        $addressLine2 .= " - CEP: {$address->cep}";
                    }
                }

                $companyData = [
                    'company_name' => $commonData?->company_name ?: ($commonData ? trim($commonData->first_name.' '.$commonData->last_name) : $budget->tenant->name),
                    'email' => $contact?->email_personal ?: $contact?->email_business,
                    'phone' => $contact?->phone_personal ?: $contact?->phone_business,
                    'address_line1' => $addressLine1,
                    'address_line2' => $addressLine2,
                    'document' => $commonData ? ($commonData->cnpj ? 'CNPJ: '.\App\Helpers\DocumentHelper::formatCnpj($commonData->cnpj) : ($commonData->cpf ? 'CPF: '.\App\Helpers\DocumentHelper::formatCpf($commonData->cpf) : null)) : null,
                ];
            } else {
                $companyData = [
                    'company_name' => $budget->tenant->name,
                ];
            }

            // Variáveis para uso fora da transação
            $publicUrl = null;
            $pdfPath = null;

            $oldStatus = $budget->status->value;

            // Executar operações de banco dentro da transação
            DB::transaction(function () use ($budget, $customer, $recipientName, &$publicUrl, &$pdfPath, $provider, $oldStatus, $customMessage) {
                // 1. Gerar ou recuperar Token Público via BudgetShareService
                $shareResult = $this->shareService->createShare([
                    'budget_id' => $budget->id,
                    'tenant_id' => $budget->tenant_id,
                    'recipient_email' => $customer->email,
                    'recipient_name' => $recipientName,
                    'permissions' => ['view', 'comment', 'approve', 'reject', 'print'],
                    'expires_at' => now()->addDays(7)->toDateTimeString(),
                    'message' => 'Orçamento enviado para aprovação.',
                ], false); // Não envia notificação duplicada

                if ($shareResult->isError()) {
                    throw new Exception('Erro ao gerar link de compartilhamento: '.$shareResult->getMessage());
                }

                $share = $shareResult->getData();
                $publicUrl = route('budgets.public.shared.view', ['token' => $share->share_token]);

                // 2. Preparar dados para o PDF
                $pdfPath = $this->pdfService->generatePdf($budget, ['provider' => $provider]);

                // 3. Atualizar orçamento: status para pendente e anexo
                // Bugfix: Se o orçamento já estiver aprovado, não voltamos para pendente ao reenviar
                $newStatus = $budget->status;
                if ($budget->status->value !== \App\Enums\BudgetStatus::APPROVED->value) {
                    $newStatus = \App\Enums\BudgetStatus::PENDING;
                }

                // Suprimir notificação de status pois enviaremos e-mail dedicado abaixo
                $budget->suppressStatusNotification = true;
                $budget->update([
                    'status' => $newStatus,
                    'attachment' => $pdfPath,
                ]);

                // 5. Registrar histórico
                if (method_exists($budget, 'actionHistory')) {
                    $budget->actionHistory()->create([
                        'tenant_id' => $budget->tenant_id,
                        'budget_id' => $budget->id,
                        'user_id' => auth()->id(),
                        'action' => 'sent',
                        'old_status' => $oldStatus,
                        'new_status' => $newStatus->value,
                        'description' => $newStatus->value === $oldStatus ? 'Link de compartilhamento reenviado.' : 'Orçamento processado para envio.',
                        'metadata' => [
                            'custom_message' => $customMessage,
                            'via' => 'email',
                        ],
                        'ip_address' => request()->ip(),
                        'user_agent' => request()->userAgent(),
                    ]);
                }
            });

            // 5. Enviar E-mail (FORA DA TRANSAÇÃO para evitar timeout de banco) usando fila
            \Illuminate\Support\Facades\Log::info('[SendBudgetToCustomerAction] Enfileirando email', ['customMessage' => $customMessage]);
            Mail::to($customer->email)->queue(new BudgetNotificationMail(
                budget: $budget,
                customer: $customer,
                notificationType: 'sent',
                tenant: $budget->tenant,
                company: $companyData,
                publicUrl: $publicUrl,
                customMessage: $customMessage,
                status: $budget->status->value
            ));

            $msg = 'Orçamento enviado e produtos reservados com sucesso!';

            return ServiceResult::success(null, $msg);

        } catch (Exception $e) {
            // Retorna apenas a mensagem da exceção, que agora está mais limpa
            return ServiceResult::error($e->getMessage());
        }
    }
}
