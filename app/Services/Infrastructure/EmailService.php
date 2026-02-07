<?php

declare(strict_types=1);

namespace App\Services\Infrastructure;

use App\Mail\BudgetNotificationMail;
use App\Mail\InvoiceNotification;
use App\Models\Budget;
use App\Models\Invoice;
use App\Support\ServiceResult;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailService
{
    /**
     * Send email notification (Generic/Legacy)
     */
    public function send(string $to, string $subject, string $content, array $data = []): ServiceResult
    {
        try {
            // Basic email implementation
            // In a real application, you would integrate with your email service
            Log::info('Email notification sent', [
                'to' => $to,
                'subject' => $subject,
                'content' => $content,
                'data' => $data,
            ]);

            return ServiceResult::success([], 'Email sent successfully');
        } catch (\Exception $e) {
            return ServiceResult::error('Failed to send email: '.$e->getMessage());
        }
    }

    /**
     * Envia notificação de compartilhamento de orçamento
     */
    public function sendBudgetShareNotification(string $to, array $data): ServiceResult
    {
        try {
            $budget = Budget::find($data['budget_id'] ?? null);
            if (! $budget && isset($data['budget_code'])) {
                $budget = Budget::where('code', $data['budget_code'])->first();
            }

            if (! $budget) {
                return ServiceResult::error('Orçamento não encontrado para envio de e-mail.');
            }

            $mailable = new BudgetNotificationMail(
                $budget,
                $budget->customer,
                'share',
                $budget->tenant,
                [],
                $data['share_url'] ?? null,
                $data['message'] ?? null
            );

            Mail::to($to)->send($mailable);

            return ServiceResult::success([], 'E-mail de compartilhamento de orçamento enviado.');
        } catch (\Exception $e) {
            Log::error('Erro ao enviar e-mail de compartilhamento de orçamento', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);

            return ServiceResult::error('Falha ao enviar e-mail: '.$e->getMessage());
        }
    }

    /**
     * Envia notificação de compartilhamento de fatura
     */
    public function sendInvoiceShareNotification(string $to, array $data): ServiceResult
    {
        try {
            $invoice = Invoice::with(['customer', 'tenant'])->find($data['invoice_id'] ?? null);
            if (! $invoice && isset($data['invoice_code'])) {
                $invoice = Invoice::with(['customer', 'tenant'])->where('code', $data['invoice_code'])->first();
            }

            if (! $invoice) {
                return ServiceResult::error('Fatura não encontrada para envio de e-mail.');
            }

            $mailable = new InvoiceNotification(
                $invoice,
                $invoice->customer,
                $invoice->tenant,
                [],
                $data['share_url'] ?? null,
                $data['message'] ?? null
            );

            Mail::to($to)->send($mailable);

            return ServiceResult::success([], 'E-mail de compartilhamento de fatura enviado.');
        } catch (\Exception $e) {
            Log::error('Erro ao enviar e-mail de compartilhamento de fatura', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);

            return ServiceResult::error('Falha ao enviar e-mail: '.$e->getMessage());
        }
    }
}
