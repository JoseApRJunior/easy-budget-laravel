<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\Service;

class ServiceObserver
{
    public function created(Service $service): void
    {
        $this->log($service, 'service_created', 'Serviço criado');
    }

    public function updated(Service $service): void
    {
        $this->log($service, 'service_updated', 'Serviço atualizado', [
            'old_values' => $service->getOriginal(),
            'new_values' => $service->getChanges(),
        ]);
    }

    public function deleted(Service $service): void
    {
        $this->log($service, 'service_deleted', 'Serviço excluído');
    }

    public function restored(Service $service): void
    {
        $this->log($service, 'service_restored', 'Serviço restaurado');
    }

    private function log(Service $service, string $action, string $description, array $extra = []): void
    {
        try {
            AuditLog::withoutTenant()->create([
                'tenant_id' => $service->tenant_id,
                'user_id' => auth()->id(),
                'action' => $action,
                'model_type' => Service::class,
                'model_id' => $service->id,
                'description' => $description,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => $extra,
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to create audit log', [
                'action' => $action,
                'service_id' => $service->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
