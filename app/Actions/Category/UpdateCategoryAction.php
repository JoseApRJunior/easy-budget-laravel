<?php

declare(strict_types=1);

namespace App\Actions\Category;

use App\DTOs\Category\CategoryDTO;
use App\Models\Category;
use App\Repositories\CategoryRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UpdateCategoryAction
{
    public function __construct(
        private CategoryRepository $repository
    ) {}

    public function execute(Category $category, CategoryDTO $dto, int $tenantId): Category
    {
        $data = $dto->toArray();

        if (empty($data['slug'])) {
            $data['slug'] = $this->generateUniqueSlug($dto->name, $tenantId, $category->id);
            $dto = CategoryDTO::fromArray($data);
        }

        return DB::transaction(function () use ($category, $dto, $tenantId, $data) {
            // Se estiver desativando uma categoria pai, desativa todos os filhos em cascata
            if (isset($data['is_active']) && $data['is_active'] === false && $category->is_active === true) {
                Category::where('parent_id', $category->id)
                    ->where('tenant_id', $tenantId)
                    ->update(['is_active' => false]);
            }

            return $this->repository->updateFromDTO($category->id, $dto);
        });
    }

    private function generateUniqueSlug(string $name, int $tenantId, int $excludeId): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;

        while ($this->repository->existsBySlug($slug, $excludeId)) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }
}
