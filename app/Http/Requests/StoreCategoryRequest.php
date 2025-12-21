<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name'      => [ 'required', 'string', 'max:255' ],
            'slug'      => [ 'nullable', 'string', 'max:255', 'regex:/^[a-z0-9-]+$/' ],
            'parent_id' => [ 'nullable', 'integer', 'exists:categories,id' ],
            'is_active' => [ 'nullable', 'boolean' ],
        ];
    }

    public function withValidator( Validator $validator ): void
    {
        $validator->after( function ( $validator ) {
            $user     = $this->user();
            $tenantId = $user->tenant_id ?? null;

            // Na nova lógica simplificada, nomes duplicados são permitidos
            // O sistema gera slugs únicos automaticamente (servicos-gerais, servicos-gerais-2, etc.)

            // Validar parent (se fornecido)
            if ( $this->filled( 'parent_id' ) ) {
                $parentId = (int) $this->input( 'parent_id' );
                $parent   = Category::find( $parentId );

                if ( !$parent ) {
                    return;
                }

                // Parent deve pertencer ao mesmo tenant
                if ( $parent->tenant_id !== $tenantId ) {
                    $validator->errors()->add( 'parent_id', 'A categoria pai deve pertencer à sua empresa.' );
                }

                // REMOVIDO: Validação de referência circular
                // Esta validação é feita no CategoryService::createCategory() de forma mais precisa
                // A validação aqui estava incorreta (verificava se o parent formaria loop consigo mesmo)
            }
        } );
    }

}
