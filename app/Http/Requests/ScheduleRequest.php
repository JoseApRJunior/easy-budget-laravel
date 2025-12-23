<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request para criação de agendamentos
 */
class ScheduleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'provider_id' => 'required|exists:users,id',
            'service_date' => 'required|date|after:today',
            'service_time' => 'required|date_format:H:i',
            'service_duration' => 'required|integer|min:30|max:480', // 30 min a 8 horas
            'service_type' => 'required|string|max:100',
            'notes' => 'nullable|string|max:1000',
            'price' => 'nullable|numeric|min:0|max:999999.99',
        ];

        // Se o usuário for admin ou prestador, pode especificar o cliente
        if (auth()->user()->isAdmin() || auth()->user()->isProvider()) {
            $rules['customer_id'] = 'required|exists:users,id';
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'provider_id.required' => 'O prestador é obrigatório.',
            'provider_id.exists' => 'Prestador não encontrado.',

            'customer_id.required' => 'O cliente é obrigatório.',
            'customer_id.exists' => 'Cliente não encontrado.',

            'service_date.required' => 'A data do serviço é obrigatória.',
            'service_date.date' => 'A data do serviço deve ser uma data válida.',
            'service_date.after' => 'A data do serviço deve ser futura.',

            'service_time.required' => 'O horário do serviço é obrigatório.',
            'service_time.date_format' => 'O horário deve estar no formato HH:MM.',

            'service_duration.required' => 'A duração do serviço é obrigatória.',
            'service_duration.integer' => 'A duração deve ser um número inteiro (em minutos).',
            'service_duration.min' => 'A duração mínima é de 30 minutos.',
            'service_duration.max' => 'A duração máxima é de 8 horas (480 minutos).',

            'service_type.required' => 'O tipo de serviço é obrigatório.',
            'service_type.string' => 'O tipo de serviço deve ser um texto.',
            'service_type.max' => 'O tipo de serviço não pode ter mais de 100 caracteres.',

            'notes.string' => 'As observações devem ser um texto.',
            'notes.max' => 'As observações não podem ter mais de 1000 caracteres.',

            'price.numeric' => 'O preço deve ser um valor numérico.',
            'price.min' => 'O preço não pode ser negativo.',
            'price.max' => 'O preço não pode ser maior que R$ 999.999,99.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'provider_id' => 'prestador',
            'customer_id' => 'cliente',
            'service_date' => 'data do serviço',
            'service_time' => 'horário do serviço',
            'service_duration' => 'duração do serviço',
            'service_type' => 'tipo de serviço',
            'notes' => 'observações',
            'price' => 'preço',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Se o usuário for cliente, define o customer_id automaticamente
        if (auth()->user()->isCustomer() && ! $this->has('customer_id')) {
            $this->merge(['customer_id' => auth()->user()->id]);
        }

        // Converte valores vazios para null
        if ($this->notes === '') {
            $this->merge(['notes' => null]);
        }
        if ($this->price === '') {
            $this->merge(['price' => null]);
        }
    }
}
