<?php
namespace App\Http\Controllers\Traits;

trait HandlesCategoryContext
{
    protected function isAdminContext(): bool
    {
        return request()->routeIs('admin.*');
    }

    protected function categoryView(string $name): string
    {
        return $this->isAdminContext() ? ('admin.categories.' . $name) : ('pages.category.' . $name);
    }
}

