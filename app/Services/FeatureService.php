<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Resource;
use Illuminate\Support\Collection;

class FeatureService
{
    /**
     * Get all features (resources)
     */
    public function getAllFeatures(): Collection
    {
        return Resource::orderBy('name')->get();
    }

    /**
     * Toggle the status of a feature
     */
    public function toggleFeature(Resource $resource): void
    {
        $newStatus = $resource->status === Resource::STATUS_ACTIVE 
            ? Resource::STATUS_INACTIVE 
            : Resource::STATUS_ACTIVE;
        
        $resource->status = $newStatus;

        // Regra de Negócio: Se desativar o recurso, ele volta para "Em Desenvolvimento" automaticamente.
        // Isso previne que um recurso seja reativado acidentalmente para produção sem revisão.
        if ($newStatus === Resource::STATUS_INACTIVE) {
            $resource->in_dev = true;
        }
        
        $resource->save();
    }

    /**
     * Create a new feature
     */
    public function createFeature(array $data): Resource
    {
        return Resource::create([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'status' => Resource::STATUS_INACTIVE,
            'in_dev' => isset($data['in_dev']),
        ]);
    }

    /**
     * Delete a feature
     */
    public function deleteFeature(Resource $resource): void
    {
        $resource->delete();
    }
}
