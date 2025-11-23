<?php

namespace App\Http\Requests;

use App\Models\Product;
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
        $tenantId = tenant()?->id ?? auth()->user()->tenant_id ?? null;
        $productId = $this->route('product')?->id ?? null;
        if (! $productId) {
            $sku = $this->route('sku') ?? $this->input('sku');
            if ($sku) {
                $query = Product::where('sku', $sku);
                if ($tenantId) {
                    $query->where('tenant_id', $tenantId);
                }
                $productId = $query->value('id');
            }
        }

        return [
            'name' => 'sometimes|required|string|max:255',
            'sku' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
                Rule::unique('products')->ignore($productId)->where(function ($query) use ($tenantId) {
                    if ($tenantId) {
                        $query->where('tenant_id', $tenantId);
                    }
                }),
            ],
            'price' => 'sometimes|required|numeric|min:0',
            'category_id' => 'sometimes|nullable|integer|exists:categories,id',
            'unit' => 'sometimes|nullable|string|max:20',
            'active' => 'sometimes|boolean',
            'image' => 'nullable|image|max:2048',
            'remove_image' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome do produto é obrigatório.',
            'sku.unique' => 'O SKU informado já está em uso por outro produto.',
            'price.required' => 'O preço é obrigatório.',
            'price.numeric' => 'O preço deve ser um valor numérico.',
            'price.min' => 'O preço deve ser no mínimo 0.',
            'category_id.exists' => 'A categoria selecionada é inválida.',
            'image.image' => 'O arquivo deve ser uma imagem.',
            'image.max' => 'A imagem não pode ter mais de 2MB.',
        ];
    }
}
