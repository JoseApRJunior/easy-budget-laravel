<?php

namespace App\Http\Requests;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ProductUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Prepara os dados para validação.
     * Converte o preço de BRL (string) para float.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('price')) {
            $this->merge([
                'price' => \App\Helpers\CurrencyHelper::unformat($this->input('price')),
            ]);
        }
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
            'description' => 'sometimes|nullable|string|max:1000',
            'price' => 'sometimes|required|numeric|min:0',
            'category_id' => 'sometimes|nullable|integer|exists:categories,id',
            'sku' => 'sometimes|nullable|string|max:50',
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

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            if ($this->filled('category_id')) {
                $categoryId = (int) $this->input('category_id');
                $category = Category::withTrashed()->find($categoryId);

                if ($category && $category->trashed()) {
                    $validator->errors()->add('category_id', 'A categoria selecionada foi removida. Por favor, escolha outra categoria.');
                }
            }
        });
    }
}
