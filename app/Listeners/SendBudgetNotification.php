<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\BudgetStatusChanged;
use App\Mail\BudgetNotificationMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Listener para envio de notificações por email quando o status do orçamento muda.
 */
class SendBudgetNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(BudgetStatusChanged $event): void
    {
        try {
            $budget = $event->budget;

            // 1. Deduplicação para evitar envios duplicados
            $dedupeKey = "email:budget_status:{$budget->id}:{$event->newStatus}";
            if (! Cache::add($dedupeKey, true, now()->addMinutes(30))) {
                Log::warning('Notificação de orçamento ignorada por deduplicação', [
                    'budget_id' => $budget->id,
                    'new_status' => $event->newStatus,
                    'dedupe_key' => $dedupeKey,
                ]);

                return;
            }

            $customer = $budget->customer;

            // Garantir que o token público exista
            if (empty($budget->public_token)) {
                $budget->public_token = \App\Models\Budget::generateUniquePublicToken();
                $budget->saveQuietly();
            }

            // Verificar se o cliente tem email
            if (! $customer || ! $customer->contact?->email_personal) {
                Log::info('Cliente sem email para notificação', [
                    'budget_id' => $budget->id,
                    'customer_id' => $customer->id,
                ]);

                return;
            }

            // Determinar tipo de notificação baseado no novo status
            $notificationType = match ($event->newStatus) {
                'approved' => 'approved',
                'rejected' => 'rejected',
                'sent' => 'sent',
                'expired' => 'expired',
                default => 'updated'
            };

            // Carregar relações necessárias para os dados da empresa
            $budget->loadMissing(['tenant.provider.commonData', 'tenant.provider.address', 'tenant.provider.contact']);

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

            // Enviar email
            Mail::to($customer->contact->email_personal)
                ->send(new BudgetNotificationMail(
                    budget: $budget,
                    customer: $customer,
                    notificationType: $notificationType,
                    tenant: $budget->tenant,
                    company: $companyData,
                    customMessage: $event->comment
                ));

            Log::info('Notificação de orçamento enviada', [
                'budget_id' => $budget->id,
                'customer_email' => $customer->contact->email_personal,
                'notification_type' => $notificationType,
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao enviar notificação de orçamento', [
                'budget_id' => $event->budget->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
