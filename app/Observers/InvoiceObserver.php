<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\Invoice;

class InvoiceObserver
{
    public function created(Invoice $invoice): void
    {
        $this->log($invoice, 'invoice_created', 'Fatura criada');
    }

    public function updated(Invoice $invoice): void
    {
        $changes = $invoice->getChanges();
        $action = 'invoice_updated';
        $description = 'Fatura atualizada';

        if (isset($changes['status'])) {
            $action = 'invoice_status_changed';
            $description = "Status alterado para: {$changes['status']}";
        }

        $this->log($invoice, $action, $description, [
            'old_values' => $invoice->getOriginal(),
            'new_values' => $changes,
        ]);
    }

    public function deleted(Invoice $invoice): void
    {
        $this->log($invoice, 'invoice_deleted', 'Fatura excluída');
    }

    public function restored(Invoice $invoice): void
    {
        $this->log($invoice, 'invoice_restored', 'Fatura restaurada');
    }

    private function log(Invoice $invoice, string $action, string $description, array $extra = []): void
    {
        try {
            AuditLog::withoutTenant()->create([
                'tenant_id' => $invoice->tenant_id,
                'user_id' => auth()->id(),
                'action' => $action,
                'model_type' => Invoice::class,
                'model_id' => $invoice->id,
                'description' => $description,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => $extra,
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to create audit log', [
                'action' => $action,
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
