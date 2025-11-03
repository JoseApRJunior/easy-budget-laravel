<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\Product;

class ProductObserver
{
    public function created(Product $product): void
    {
        $this->log($product, 'product_created', 'Produto criado');
    }

    public function updated(Product $product): void
    {
        $this->log($product, 'product_updated', 'Produto atualizado', [
            'old_values' => $product->getOriginal(),
            'new_values' => $product->getChanges(),
        ]);
    }

    public function deleted(Product $product): void
    {
        $this->log($product, 'product_deleted', 'Produto excluído');
    }

    public function restored(Product $product): void
    {
        $this->log($product, 'product_restored', 'Produto restaurado');
    }

    private function log(Product $product, string $action, string $description, array $extra = []): void
    {
        try {
            AuditLog::withoutTenant()->create([
                'tenant_id' => $product->tenant_id,
                'user_id' => auth()->id(),
                'action' => $action,
                'model_type' => Product::class,
                'model_id' => $product->id,
                'description' => $description,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => $extra,
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to create audit log', [
                'action' => $action,
                'product_id' => $product->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
