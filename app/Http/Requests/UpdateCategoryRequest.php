<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Category;
use App\Models\Traits\TenantScoped;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-custom-categories');
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
            if (!$category instanceof Category) {
                $categoryId = $this->route('id') ?? $this->route('category');
                $category = Category::find($categoryId);
            }

            if (!$category) {
                return; // 404 handled by controller
            }

            $user = $this->user();
            $isAdmin = $user ? app(\App\Services\Core\PermissionService::class)->canManageGlobalCategories($user) : false;
            $tenantId = $isAdmin ? null : (TenantScoped::getCurrentTenantId() ?? ($user->tenant_id ?? null));
            $slug = Str::slug($this->input('name'));

            // 1. Validate Permissions
            if ($isAdmin && !$category->isGlobal()) {
                $validator->errors()->add('category', 'Admin só pode editar categorias globais.');
            }
            if (!$isAdmin && $category->isGlobal()) {
                $validator->errors()->add('category', 'Categorias globais só podem ser editadas por administradores.');
            }

            // 2. Validate Slug Uniqueness (Ignoring current category)
            $query = Category::query()->where('slug', $slug)->where('id', '!=', $category->id);

            if ($isAdmin) {
                if ($query->exists()) {
                    $validator->errors()->add('name', 'Este nome já está em uso.');
                }
            } else {
                $conflict = Category::where('slug', $slug)
                    ->where('id', '!=', $category->id)
                    ->whereHas('tenants', fn($t) => $t->where('tenant_id', $tenantId))
                    ->exists();

                if ($conflict) {
                    $validator->errors()->add('name', 'Este nome já está em uso.');
                }
            }

            // 3. Validate Parent
            if ($this->filled('parent_id')) {
                $parentId = (int) $this->input('parent_id');

                // Prevent self-parenting
                if ($parentId === $category->id) {
                    $validator->errors()->add('parent_id', 'Uma categoria não pode ser pai de si mesma.');
                    return;
                }

                // Prevent circular reference
                if ($category->wouldCreateCircularReference($parentId)) {
                    $validator->errors()->add('parent_id', 'Esta operação criaria uma hierarquia circular.');
                    return;
                }

                $parent = Category::find($parentId);
                if (!$parent) return;

                if ($isAdmin) {
                    if (!$parent->isGlobal()) {
                        $validator->errors()->add('parent_id', 'Admin só pode selecionar categoria pai base (global).');
                    }
                } else {
                    if ($tenantId === null) {
                        $validator->errors()->add('parent_id', 'Selecione uma categoria pai disponível.');
                    } else {
                        if (!$parent->isAvailableFor($tenantId)) {
                            $validator->errors()->add('parent_id', 'A categoria pai deve pertencer ao seu espaço ou ser global.');
                        }
                    }
                }
            }
        });
    }
}
