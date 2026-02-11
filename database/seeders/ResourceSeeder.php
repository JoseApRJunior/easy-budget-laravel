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
            // Usamos firstOrCreate para respeitar as edições feitas via Admin.
            // O seeder só vai criar se a feature NOVA ainda não existir no banco.
            Resource::firstOrCreate(
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
