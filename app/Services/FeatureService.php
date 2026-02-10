<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Resource;
use Laravel\Pennant\Feature;
use Illuminate\Support\Collection;

class FeatureService
{
    /**
     * Get all features (resources)
     */
    public function getAllFeatures(): Collection
    {
        return Resource::all();
    }

    /**
     * Toggle the status of a feature
     */
    public function toggleFeature(Resource $resource): void
    {
        $resource->status = $resource->status === Resource::STATUS_ACTIVE 
            ? Resource::STATUS_INACTIVE 
            : Resource::STATUS_ACTIVE;
        
        $resource->save();

        // Clear Pennant cache for this resource
        Feature::forget('feature', $resource->slug);
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
        Feature::forget('feature', $resource->slug);
        $resource->delete();
    }
}
