<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Category;
use App\Models\Traits\TenantScoped;
use App\Repositories\CategoryRepository;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can( 'manage-custom-categories' );
    }

    public function rules(): array
    {
        return [
            'name'      => [ 'required', 'string', 'max:255' ],
            'parent_id' => [ 'nullable', 'integer', 'exists:categories,id' ],
            'is_active' => [ 'nullable', 'boolean' ],
        ];
    }

    public function withValidator( Validator $validator ): void
    {
        $validator->after( function ( $validator ) {
            $user    = $this->user();
            $isAdmin = $user ? app( \App\Services\Core\PermissionService::class)->canManageGlobalCategories( $user ) : false;

            // Definir o tenant scope para validação:
            // - Admin (global): null (vai verificar apenas categorias globais)
            // - Provider: tenant específico (vai verificar apenas dentro do próprio tenant)
            $tenantId = $isAdmin ? null : ( $user->tenant_id ?? null );
            $slug     = Str::slug( $this->input( 'name' ) );

            // 1. Validate Slug Uniqueness usando CategoryRepository
            $categoryRepository = app( CategoryRepository::class);
            $slugExists         = $categoryRepository->existsBySlug( $slug, $tenantId, null );

            if ( $slugExists ) {
                $validator->errors()->add( 'name', 'Este nome já está em uso.' );
            }

            // 2. Validate Parent
            if ( $this->filled( 'parent_id' ) ) {
                $parentId = (int) $this->input( 'parent_id' );
                $parent   = Category::find( $parentId );

                if ( !$parent ) {
                    // Already handled by exists rule, but safe to check
                    return;
                }

                if ( $isAdmin ) {
                    // Admin can only pick global parents
                    if ( !$parent->isGlobal() ) {
                        $validator->errors()->add( 'parent_id', 'Admin só pode selecionar categoria pai base (global).' );
                    }
                } else {
                    // Tenant can pick global parent OR their own custom parent
                    if ( $tenantId === null ) {
                        $validator->errors()->add( 'parent_id', 'Selecione uma categoria pai disponível.' );
                    } else {
                        // Parent must be available to this tenant
                        if ( !$parent->isAvailableFor( $tenantId ) ) {
                            $validator->errors()->add( 'parent_id', 'A categoria pai deve pertencer ao seu espaço ou ser global.' );
                        }
                    }
                }

                // Validação adicional: Verificar se parent forma loop consigo mesmo
                // Para criação, verificamos se o parent tem ancestrais que formam loop
                // (Embora raro, pode acontecer se dados foram corrompidos)
                $tempCategory = new Category( [ 'id' => PHP_INT_MAX ] ); // ID temporário alto
                if ( $tempCategory->wouldCreateCircularReference( $parentId ) ) {
                    $validator->errors()->add( 'parent_id', 'A categoria pai selecionada possui hierarquia circular.' );
                }
            }
        } );
    }

}
