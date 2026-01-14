<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Category;
use App\Repositories\CategoryRepository;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;

class CategoryUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return \auth()->check();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $category = $this->route('category');
            if (! $category instanceof Category) {
                $categoryId = $this->route('id') ?? $this->route('category');
                $category = Category::find($categoryId);
            }

            if (! $category) {
                return; // 404 handled by controller
            }

            $user = $this->user();
            $tenantId = $user->tenant_id ?? null;

            // Na nova lógica simplificada, todas as categorias pertencem ao tenant
            // Verificar se categoria pertence ao mesmo tenant do usuário
            if ($category->tenant_id !== $tenantId) {
                $validator->errors()->add('category', 'Você não tem permissão para editar esta categoria.');

                return;
            }

            // Validar slug único (ignorando categoria atual)
            $slug = Str::slug($this->input('name'));
            $categoryRepository = app(CategoryRepository::class);
            $slugExists = $categoryRepository->existsBySlug($slug, $tenantId, $category->id);

            if ($slugExists) {
                $validator->errors()->add('name', 'Este nome já está em uso.');
            }

            // Validar parent (se fornecido)
            if ($this->filled('parent_id')) {
                $parentId = (int) $this->input('parent_id');

                // Prevenir auto-parenting
                if ($parentId === $category->id) {
                    $validator->errors()->add('parent_id', 'Uma categoria não pode ser pai de si mesma.');

                    return;
                }

                // Prevenir referência circular
                if ($category->wouldCreateCircularReference($parentId)) {
                    $validator->errors()->add('parent_id', 'Esta operação criaria uma hierarquia circular.');

                    return;
                }

                $parent = Category::find($parentId);
                if (! $parent) {
                    return;
                }

                // Parent deve pertencer ao mesmo tenant
                if ($parent->tenant_id !== $tenantId) {
                    $validator->errors()->add('parent_id', 'A categoria pai deve pertencer à sua empresa.');
                }
            }
        });
    }
}
