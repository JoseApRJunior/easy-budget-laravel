<?php

declare(strict_types=1);

namespace App\Actions\Budget;

use App\Mail\BudgetNotificationMail;
use App\Models\Budget;
use App\Services\Infrastructure\BudgetPdfService;
use App\Services\Infrastructure\BudgetTokenService;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SendBudgetToCustomerAction
{
    public function __construct(
        private BudgetPdfService $pdfService,
        private BudgetTokenService $tokenService,
        private ReserveBudgetProductsAction $reserveAction
    ) {}

    /**
     * Envia o orçamento para o e-mail do cliente e reserva os produtos automaticamente.
     */
    public function execute(Budget $budget, ?string $customMessage = null): ServiceResult
    {
        try {
            // 0. Carregar relações iniciais
            $budget->loadMissing(['customer', 'tenant', 'tenant.provider.commonData']);
            $customer = $budget->customer;

            if (! $customer || ! $customer->email) {
                return ServiceResult::error('Cliente sem e-mail cadastrado.');
            }

            // Variáveis para uso fora da transação
            $publicUrl = null;
            $pdfPath = null;
            $reserveResult = null;
            $company = [];

            // Executar operações de banco dentro da transação
            DB::transaction(function () use ($budget, &$publicUrl, &$pdfPath, &$reserveResult, &$company) {
                // 1. Reserva Automática de Produtos
                $reserveResult = $this->reserveAction->execute($budget);

                // 2. Gerar ou recuperar Token Público
                $token = $this->tokenService->generateToken($budget);
                $publicUrl = route('budgets.public.shared.view', ['token' => $token]);

                // 3. Preparar dados para o PDF
                $provider = $budget->tenant->provider()->with(['commonData', 'address', 'contact'])->first();
                $pdfPath = $this->pdfService->generatePdf($budget, ['provider' => $provider]);

                // Atualizar o anexo no orçamento
                $budget->update(['attachment' => $pdfPath]);

                // 4. Preparar dados da empresa
                $company = [
                    'company_name' => $budget->tenant->name ?? config('app.name'),
                    'email' => $budget->tenant->email ?? config('mail.from.address'),
                ];

                // 6. Registrar histórico
                if (method_exists($budget, 'actionHistory')) {
                    $budget->actionHistory()->create([
                        'tenant_id' => $budget->tenant_id,
                        'action' => 'sent_and_reserved',
                        'description' => 'Orçamento processado para envio e produtos reservados automaticamente.',
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
            if ($reserveResult && $reserveResult->isError()) {
                $msg = 'Orçamento enviado com sucesso! (Aviso: '.$reserveResult->getMessage().')';
            }

            return ServiceResult::success(null, $msg);

        } catch (Exception $e) {
            return ServiceResult::error('Erro ao processar envio e reserva: '.$e->getMessage());
        }
    }
}
