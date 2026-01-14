<?php

namespace App\Observers;

use Illuminate\Support\Facades\DB;

class TenantObserver
{
    /**
     * Handle the Tenant "created" event.
     */
    public function created($tenant): void
    {
        // Criar categoria padrão "Outros" automaticamente para novos tenants
        $now = now();

        DB::table('categories')->insert([
            'tenant_id' => $tenant->id,
            'slug' => 'outros',
            'name' => 'Outros',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
