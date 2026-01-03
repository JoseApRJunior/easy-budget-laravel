<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\ServiceItem;
use App\Models\Service;
use Illuminate\Support\Facades\Log;

class ServiceItemObserver
{
    /**
     * Handle the ServiceItem "saved" event.
     */
    public function saved(ServiceItem $serviceItem): void
    {
        $this->updateServiceTotal($serviceItem->service_id);
    }

    /**
     * Handle the ServiceItem "deleted" event.
     */
    public function deleted(ServiceItem $serviceItem): void
    {
        $this->updateServiceTotal($serviceItem->service_id);
    }

    /**
     * Atualiza o total do serviço pai.
     */
    private function updateServiceTotal(int $serviceId): void
    {
        $service = Service::find($serviceId);
        if ($service) {
            $total = $service->serviceItems()->sum('total');
            $service->update(['total' => $total]);
            
            Log::info('Service total synchronized via ServiceItemObserver', [
                'service_id' => $serviceId,
                'new_total' => $total
            ]);
        }
    }
}
