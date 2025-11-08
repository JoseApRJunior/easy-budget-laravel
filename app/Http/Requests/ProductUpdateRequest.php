<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $productId = $this->route( 'product' ); // Assume que a rota tem um parâmetro 'product' com o ID do produto

        return [
            'name'         => 'sometimes|required|string|max:255',
            'sku'          => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
                Rule::unique( 'products' )->ignore( $productId )->where( fn( $query ) => $query->where( 'tenant_id', tenant()->id ) )
            ],
            'price'        => 'sometimes|required|numeric|min:0',
            'category_id'  => 'sometimes|nullable|integer|exists:categories,id',
            'unit'         => 'sometimes|nullable|string|max:20',
            'active'       => 'sometimes|boolean',
            'image'        => 'nullable|image|max:2048', // 2MB max
            'remove_image' => 'sometimes|boolean' // Campo para indicar remoção de imagem existente
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'      => 'O nome do produto é obrigatório.',
            'sku.unique'         => 'O SKU informado já está em uso por outro produto.',
            'price.required'     => 'O preço é obrigatório.',
            'price.numeric'      => 'O preço deve ser um valor numérico.',
            'price.min'          => 'O preço deve ser no mínimo 0.',
            'category_id.exists' => 'A categoria selecionada é inválida.',
            'image.image'        => 'O arquivo deve ser uma imagem.',
            'image.max'          => 'A imagem não pode ter mais de 2MB.'
        ];
    }

}
