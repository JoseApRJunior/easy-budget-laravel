<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Budget;
use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification class para mudanças de status de orçamento.
 *
 * Esta classe implementa o padrão ShouldQueue para processamento assíncrono,
 * garantindo melhor performance e confiabilidade no envio de notificações.
 * Usa internacionalização e templates profissionais.
 */
class BudgetStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Orçamento relacionado à notificação.
     */
    public Budget $budget;

    /**
     * Cliente do orçamento.
     */
    public Customer $customer;

    /**
     * Status anterior do orçamento.
     */
    public string $oldStatus;

    /**
     * Novo status do orçamento.
     */
    public string $newStatus;

    /**
     * Nome do status anterior para exibição.
     */
    public string $oldStatusName;

    /**
     * Nome do novo status para exibição.
     */
    public string $newStatusName;

    /**
     * Usuário que realizou a mudança (opcional).
     */
    public ?User $changedBy;

    /**
     * Tenant do usuário (opcional, para contexto multi-tenant).
     */
    public ?Tenant $tenant;

    /**
     * URL para acessar o orçamento.
     */
    public ?string $budgetUrl;

    /**
     * Mensagem personalizada para a notificação.
     */
    public ?string $customMessage;

    /**
     * Locale para internacionalização (pt-BR, en, etc).
     * @var string
     */
    public $locale;

    /**
     * Cria uma nova instância da notification.
     *
     * @param  Budget  $budget  Orçamento relacionado
     * @param  Customer  $customer  Cliente do orçamento
     * @param  string  $oldStatus  Status anterior
     * @param  string  $newStatus  Novo status
     * @param  string  $oldStatusName  Nome do status anterior para exibição
     * @param  string  $newStatusName  Nome do novo status para exibição
     * @param  User|null  $changedBy  Usuário que realizou a mudança (opcional)
     * @param  Tenant|null  $tenant  Tenant do usuário (opcional)
     * @param  string|null  $budgetUrl  URL do orçamento (opcional)
     * @param  string|null  $customMessage  Mensagem personalizada (opcional)
     * @param  string  $locale  Locale para internacionalização (opcional, padrão: pt-BR)
     */
    public function __construct(
        Budget $budget,
        Customer $customer,
        string $oldStatus,
        string $newStatus,
        string $oldStatusName,
        string $newStatusName,
        ?User $changedBy = null,
        ?Tenant $tenant = null,
        ?string $budgetUrl = null,
        ?string $customMessage = null,
        string $locale = 'pt-BR',
    ) {
        $this->budget = $budget;
        $this->customer = $customer;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
        $this->oldStatusName = $oldStatusName;
        $this->newStatusName = $newStatusName;
        $this->changedBy = $changedBy;
        $this->tenant = $tenant;
        $this->budgetUrl = $budgetUrl;
        $this->customMessage = $customMessage;
        $this->locale = $locale;

        // Configurar locale para internacionalização
        app()->setLocale($this->locale);
    }

    /**
     * Define os canais de entrega da notificação.
     *
     * @param  mixed  $notifiable  Usuário que receberá a notificação
     * @return array Canais de entrega
     */
    public function via(mixed $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Define a representação da notificação para e-mail.
     *
     * @param  mixed  $notifiable  Usuário que receberá a notificação
     * @return MailMessage Mensagem de e-mail
     */
    public function toMail(mixed $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject($this->generateSubject())
            ->greeting($this->generateGreeting($notifiable))
            ->line(__('notifications.budget_status.line1', [
                'budget_code' => $this->budget->code,
                'old_status' => $this->oldStatusName,
                'new_status' => $this->newStatusName,
            ], $this->locale))
            ->line(__('notifications.budget_status.line2', [
                'customer_name' => $this->getCustomerName(),
                'budget_total' => number_format($this->budget->total, 2, ',', '.'),
            ], $this->locale));

        // Adicionar informações adicionais se disponíveis
        if ($this->budget->description) {
            $message->line(__('notifications.budget_status.description', [
                'description' => $this->budget->description,
            ], $this->locale));
        }

        if ($this->budget->due_date) {
            $message->line(__('notifications.budget_status.due_date', [
                'due_date' => $this->budget->due_date->format('d/m/Y'),
            ], $this->locale));
        }

        // Adicionar mensagem personalizada se fornecida
        if ($this->customMessage) {
            $message->line($this->customMessage);
        }

        // Adicionar ação se URL fornecida
        if ($this->budgetUrl) {
            $message->action(__('notifications.budget_status.action', [], $this->locale), $this->budgetUrl);
        }

        // Adicionar informações do usuário que fez a mudança
        if ($this->changedBy) {
            $message->line(__('notifications.budget_status.changed_by', [
                'user_name' => $this->changedBy->name ?? $this->changedBy->email,
            ], $this->locale));
        }

        $message->line(__('notifications.budget_status.footer', [
            'app_name' => config('app.name', 'Easy Budget'),
        ], $this->locale));

        return $message;
    }

    /**
     * Define a representação da notificação para banco de dados.
     *
     * @param  mixed  $notifiable  Usuário que receberá a notificação
     * @return array Dados para armazenamento
     */
    public function toArray(mixed $notifiable): array
    {
        return [
            'budget_id' => $this->budget->id,
            'budget_code' => $this->budget->code,
            'customer_id' => $this->customer->id,
            'customer_name' => $this->getCustomerName(),
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'old_status_name' => $this->oldStatusName,
            'new_status_name' => $this->newStatusName,
            'changed_by' => $this->changedBy?->id,
            'changed_by_name' => $this->changedBy?->name ?? $this->changedBy?->email,
            'budget_total' => $this->budget->total,
            'budget_url' => $this->budgetUrl,
            'custom_message' => $this->customMessage,
            'tenant_id' => $this->tenant?->id,
            'locale' => $this->locale,
            'notification_type' => 'budget_status_change',
        ];
    }

    /**
     * Gera o assunto do e-mail baseado na mudança de status.
     *
     * @return string Assunto do e-mail
     */
    private function generateSubject(): string
    {
        return __('notifications.budget_status.subject', [
            'budget_code' => $this->budget->code,
            'old_status' => $this->oldStatusName,
            'new_status' => $this->newStatusName,
            'app_name' => config('app.name', 'Easy Budget'),
        ], $this->locale);
    }

    /**
     * Gera a saudação personalizada do e-mail.
     *
     * @param  mixed  $notifiable  Usuário que receberá a notificação
     * @return string Saudação personalizada
     */
    private function generateGreeting(mixed $notifiable): string
    {
        if (isset($notifiable->name)) {
            return __('notifications.budget_status.greeting_with_name', [
                'name' => $notifiable->name,
            ], $this->locale);
        }

        if (isset($notifiable->email)) {
            $firstName = explode('@', $notifiable->email)[0];

            return __('notifications.budget_status.greeting_with_name', [
                'name' => $firstName,
            ], $this->locale);
        }

        return __('notifications.budget_status.greeting_default', [], $this->locale);
    }

    /**
     * Obtém o nome do cliente.
     *
     * @return string Nome do cliente
     */
    private function getCustomerName(): string
    {
        if ($this->customer->commonData) {
            return trim($this->customer->commonData->first_name.' '.$this->customer->commonData->last_name);
        }

        return 'Cliente';
    }
}
