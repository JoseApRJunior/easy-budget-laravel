<?php
declare(strict_types=1);

namespace App\Services\Domain;

use App\Models\Category;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Support\ServiceResult;
use Illuminate\Support\Str;

class CategoryService extends AbstractBaseService
{
    public function __construct(CategoryRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    protected function getSupportedFilters(): array
    {
        return [
            'id', 'name', 'slug', 'is_active', 'created_at', 'updated_at',
        ];
    }

    public function generateUniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;
        while ($this->repository->isUniqueInTenant('slug', $slug)) {
            $slug = $base.'-'.$i;
            $i++;
        }
        return $slug;
    }

    public function createCategory(array $data): ServiceResult
    {
        $tenantId = $this->tenantId();
        if (!$tenantId) {
            return $this->error('Tenant não identificado');
        }
        $data['tenant_id'] = $tenantId;
        if (!isset($data['slug']) || empty($data['slug'])) {
            $data['slug'] = $this->generateUniqueSlug($data['name'] ?? '');
        }
        return $this->create($data);
    }

    public function updateCategory(int $id, array $data): ServiceResult
    {
        if (isset($data['name']) && empty($data['slug'])) {
            $data['slug'] = $this->generateUniqueSlug($data['name']);
        }
        return $this->update($id, $data);
    }

    public function deleteCategory(int $id): ServiceResult
    {
        $categoryResult = $this->findById($id);
        if ($categoryResult->isError()) {
            return $categoryResult;
        }
        /** @var Category $category */
        $category = $categoryResult->getData();
        if ($category->services()->exists()) {
            return $this->error('Não é possível excluir: possui serviços associados');
        }
        return $this->delete($id);
    }

    public function findBySlug(string $slug): ServiceResult
    {
        $entity = $this->repository->findByTenantAndSlug($slug);
        if (!$entity) {
            return $this->error('Categoria não encontrada');
        }
        return $this->success($entity);
    }

    public function listActive(): ServiceResult
    {
        $list = $this->repository->listActive(['name' => 'asc']);
        return $this->success($list);
    }
}
