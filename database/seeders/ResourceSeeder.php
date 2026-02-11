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
            $resource = Resource::where('slug', $slug)->first();

            if ($resource) {
                // Se já existe, atualizamos apenas nome e descrição (traduções)
                // Preservamos 'status' e 'in_dev' definidos via Painel Admin
                $resource->update([
                    'name' => $details['name'],
                    'description' => $details['description'] ?? null,
                ]);
            } else {
                // Se não existe, criamos com os padrões do config
                Resource::create([
                    'slug' => $slug,
                    'name' => $details['name'],
                    'description' => $details['description'] ?? null,
                    'status' => $details['status'] ?? Resource::STATUS_ACTIVE,
                    'in_dev' => $details['in_dev'] ?? false,
                ]);
            }
        }
    }
}
