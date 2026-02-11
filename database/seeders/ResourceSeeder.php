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
            Resource::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $details['name'],
                    'status' => Resource::STATUS_ACTIVE,
                    'in_dev' => $details['in_dev'] ?? false,
                ]
            );
        }
    }
}
