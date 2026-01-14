<?php

declare(strict_types=1);

namespace App\Actions\Category;

use App\DTOs\Category\CategoryDTO;
use App\Models\Category;
use App\Repositories\CategoryRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateCategoryAction
{
    public function __construct(
        private CategoryRepository $repository
    ) {}

    public function execute(CategoryDTO $dto, int $tenantId): Category
    {
        $data = $dto->toArray();

        if (empty($data['slug'])) {
            $data['slug'] = $this->generateUniqueSlug($dto->name, $tenantId);
            $dto = CategoryDTO::fromArray($data);
        }

        return DB::transaction(fn () => $this->repository->createFromDTO($dto));
    }

    private function generateUniqueSlug(string $name, int $tenantId): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;

        while ($this->repository->existsBySlug($slug)) {
            $slug = $base . '-' . $i;
            $i++;
        }

        return $slug;
    }
}
