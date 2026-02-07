<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\Customer;

class CustomerObserver
{
    public function created(Customer $customer): void
    {
        $this->log($customer, 'customer_created', 'Cliente criado');
    }

    public function updated(Customer $customer): void
    {
        $this->log($customer, 'customer_updated', 'Dados do cliente atualizados', [
            'old_values' => $customer->getOriginal(),
            'new_values' => $customer->getChanges(),
        ]);
    }

    public function deleted(Customer $customer): void
    {
        $this->log($customer, 'customer_deleted', 'Cliente excluído');
    }

    public function restored(Customer $customer): void
    {
        $this->log($customer, 'customer_restored', 'Cliente restaurado');
    }

    private function log(Customer $customer, string $action, string $description, array $extra = []): void
    {
        try {
            AuditLog::withoutTenant()->create([
                'tenant_id' => $customer->tenant_id,
                'user_id' => auth()->id(),
                'action' => $action,
                'model_type' => Customer::class,
                'model_id' => $customer->id,
                'description' => $description,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => $extra,
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to create audit log', [
                'action' => $action,
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
