<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Category;
use App\Models\Traits\TenantScoped;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;

class StoreCategoryRequest extends FormRequest
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
            $user = $this->user();
            $isAdmin = $user ? app(\App\Services\Core\PermissionService::class)->canManageGlobalCategories($user) : false;
            $tenantId = $isAdmin ? null : ($this->integer('tenant_id') ?: (TenantScoped::getCurrentTenantId() ?? ($user->tenant_id ?? null)));
            $slug = Str::slug($this->input('name'));

            // 1. Validate Slug Uniqueness
            $query = Category::query()->where('slug', $slug);

            if ($isAdmin) {
                // Admin creating global category: slug must be unique globally among global categories?
                // Or unique among ALL categories?
                // Controller logic was: Category::query()->where('slug', $slug)->exists();
                // This implies unique across the entire table.
                if ($query->exists()) {
                    $validator->errors()->add('name', 'Este nome já está em uso.');
                }
            } else {
                // Tenant creating custom category
                $existingInTenant = Category::query()
                    ->where('slug', $slug)
                    ->where(function ($q) use ($tenantId) {
                        if ($tenantId !== null) {
                            $q->whereHas('tenants', function ($t) use ($tenantId) {
                                $t->where('tenant_id', $tenantId);
                            })
                            ->orWhereDoesntHave('tenants'); // Conflict with global categories too?
                            // Controller logic was complex:
                            /*
                            $q->where('tenant_id', $tenantId) // Legacy check
                                ->orWhereExists(...) // Pivot check
                            */
                            // New logic should be:
                            // Conflict if exists a category with same slug that is EITHER global OR linked to this tenant
                        } else {
                            $q->whereRaw('1=0');
                        }
                    })
                    ->exists();

                // Wait, the controller logic for tenant was:
                /*
                 $existingInTenant = Category::query()
                    ->where('slug', $slug)
                    ->where(function ($q) use ($tenantId) {
                        if ($tenantId !== null) {
                            $q->where('tenant_id', $tenantId) // Legacy
                                ->orWhereExists(...) // Pivot
                        }
                    })
                */
                // It seems it checks if *I* already have this category (global or custom).
                // If I have it, I can't create another with same name.

                // Using the new helper: isAvailableFor($tenantId)
                // But we need to check by slug.

                $conflict = Category::where('slug', $slug)
                    ->where(function($q) use ($tenantId) {
                        $q->globalOnly()
                          ->orWhereHas('tenants', fn($t) => $t->where('tenant_id', $tenantId));
                    })
                    ->exists();

                if ($conflict) {
                    $validator->errors()->add('name', 'Este nome já está em uso.');
                }
            }

            // 2. Validate Parent
            if ($this->filled('parent_id')) {
                $parentId = (int) $this->input('parent_id');
                $parent = Category::find($parentId);

                if (!$parent) {
                     // Already handled by exists rule, but safe to check
                     return;
                }

                if ($isAdmin) {
                    // Admin can only pick global parents
                    if (!$parent->isGlobal()) {
                        $validator->errors()->add('parent_id', 'Admin só pode selecionar categoria pai base (global).');
                    }
                } else {
                    // Tenant can pick global parent OR their own custom parent
                    if ($tenantId === null) {
                         $validator->errors()->add('parent_id', 'Selecione uma categoria pai disponível.');
                    } else {
                        // Parent must be available to this tenant
                        if (!$parent->isAvailableFor($tenantId)) {
                            $validator->errors()->add('parent_id', 'A categoria pai deve pertencer ao seu espaço ou ser global.');
                        }
                    }
                }
            }
        });
    }
}
