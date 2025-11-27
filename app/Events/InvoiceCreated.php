<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Tenant;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento disparado quando uma fatura é criada no sistema.
 *
 * Este evento é usado para acionar notificações de fatura,
 * processamento de pagamentos e outras ações relacionadas à criação de faturas.
 */
class InvoiceCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Invoice $invoice;

    public Customer $customer;

    public ?Tenant $tenant;

    /**
     * Cria uma nova instância do evento.
     *
     * @param  Invoice  $invoice  Fatura criada
     * @param  Customer  $customer  Cliente da fatura
     * @param  Tenant|null  $tenant  Tenant do usuário (opcional)
     */
    public function __construct(Invoice $invoice, Customer $customer, ?Tenant $tenant = null)
    {
        $this->invoice = $invoice;
        $this->customer = $customer;
        $this->tenant = $tenant;
    }
}
