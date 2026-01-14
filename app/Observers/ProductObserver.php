<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Product;
use App\Services\Application\AuditLogService;

class ProductObserver
{
    public function __construct(protected AuditLogService $auditLogService) {}

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
        $oldValues = $extra['old_values'] ?? null;
        $newValues = $extra['new_values'] ?? null;

        $metadata = array_merge($extra, ['description' => $description]);

        $this->auditLogService->log($action, $product, $oldValues, $newValues, $metadata);
    }
}
