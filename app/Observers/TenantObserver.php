<?php

namespace App\Observers;

use App\Models\Category;
use App\Models\Tenant;

class TenantObserver
{
    public function created( Tenant $tenant ): void
    {
        // Sistema simplificado: cada tenant cria suas próprias categorias
        // Não há categorias globais para copiar
        // As categorias padrão são criadas pelo CategorySeeder para cada tenant

        // Se necessário, pode-se implementar lógica para criar categorias padrão
        // baseadas em templates específicos por tipo de negócio
        // $this->createDefaultCategoriesForTenant($tenant);
    }

}
