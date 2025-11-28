<?php

namespace App\Observers;

use App\Models\Category;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CategoryObserver
{
    /**
     * Handle the Category "created" event.
     */
    public function created(Category $category): void
    {
        $this->invalidateCache($category);
    }

    /**
     * Handle the Category "updated" event.
     */
    public function updated(Category $category): void
    {
        $this->invalidateCache($category);
    }

    /**
     * Handle the Category "deleted" event.
     */
    public function deleted(Category $category): void
    {
        $this->invalidateCache($category);
    }

    /**
     * Handle the Category "restored" event.
     */
    public function restored(Category $category): void
    {
        $this->invalidateCache($category);
    }

    /**
     * Handle the Category "force deleted" event.
     */
    public function forceDeleted(Category $category): void
    {
        $this->invalidateCache($category);
    }

    /**
     * Invalidate cache based on category scope (Global or Tenant).
     */
    protected function invalidateCache(Category $category): void
    {
        try {
            Cache::increment('global_categories_version');
            Log::info('Category cache invalidated (pivot-only)', ['category_id' => $category->id]);
        } catch (\Exception $e) {
            Log::error('Failed to invalidate category cache', [
                'category_id' => $category->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
