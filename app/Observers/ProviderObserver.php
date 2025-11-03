<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\Provider;

class ProviderObserver
{
    public function created(Provider $provider): void
    {
        $this->log($provider, 'provider_created', 'Prestador criado');
    }

    public function updated(Provider $provider): void
    {
        $this->log($provider, 'provider_updated', 'Dados do prestador atualizados', [
            'old_values' => $provider->getOriginal(),
            'new_values' => $provider->getChanges(),
        ]);
    }

    public function deleted(Provider $provider): void
    {
        $this->log($provider, 'provider_deleted', 'Prestador excluído');
    }

    public function restored(Provider $provider): void
    {
        $this->log($provider, 'provider_restored', 'Prestador restaurado');
    }

    private function log(Provider $provider, string $action, string $description, array $extra = []): void
    {
        try {
            AuditLog::withoutTenant()->create([
                'tenant_id' => $provider->tenant_id,
                'user_id' => auth()->id() ?? $provider->user_id,
                'action' => $action,
                'model_type' => Provider::class,
                'model_id' => $provider->id,
                'description' => $description,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => $extra,
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to create audit log', [
                'action' => $action,
                'provider_id' => $provider->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
