<?php

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ProductStoreRequest extends FormRequest
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
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'sku' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('products')->where(function ($query) {
                    return $query->where('tenant_id', \auth()->user()->tenant_id);
                }),
            ],
            'price' => 'required|numeric|min:0',
            'category_id' => 'nullable|integer|exists:categories,id',
            'unit' => 'nullable|string|max:20',
            'active' => 'boolean',
            'image' => 'nullable|image|max:2048', // 2MB max
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
