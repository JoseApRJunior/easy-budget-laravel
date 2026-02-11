<?php

namespace Database\Seeders;

use App\Models\Resource;
use Illuminate\Database\Seeder;

class ResourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $features = config('features');

        foreach ($features as $slug => $details) {
            // Usamos updateOrCreate para garantir que o status 'in_dev' e 'status' 
            // do config sejam sincronizados, mas permitindo que nome/descrição 
            // possam ser editados no banco se desejado (opcional).
            Resource::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $details['name'],
                    'description' => $details['description'] ?? null,
                    'status' => $details['status'] ?? Resource::STATUS_ACTIVE,
                    'in_dev' => $details['in_dev'] ?? false,
                ]
            );
        }
    }
}
