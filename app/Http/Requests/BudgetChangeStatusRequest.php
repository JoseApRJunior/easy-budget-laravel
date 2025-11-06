<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\BudgetStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form Request para alteração de status de orçamentos
 */
class BudgetChangeStatusRequest extends FormRequest
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
        $budget        = $this->route( 'budget' );
        $currentStatus = $budget?->status;

        return [
            'new_status' => [
                'required',
                Rule::in( array_column( BudgetStatus::cases(), 'value' ) ),
                function ( $attribute, $value, $fail ) use ( $currentStatus ) {
                    if ( $currentStatus ) {
                        try {
                            $targetStatus = BudgetStatus::from( $value );
                            if ( !$currentStatus->canTransitionTo( $targetStatus ) ) {
                                $fail( 'Transição de status não permitida de ' . $currentStatus->getDescription() . ' para ' . $targetStatus->getDescription() );
                            }
                        } catch ( \ValueError $e ) {
                            $fail( 'Status inválido' );
                        }
                    }
                }
            ],
            'notes'      => 'nullable|string|max:500',
            'reason'     => 'nullable|string|max:200',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'new_status.required' => 'O novo status é obrigatório.',
            'new_status.in'       => 'Status inválido.',
            'notes.max'           => 'As observações não podem ter mais de 500 caracteres.',
            'reason.max'          => 'O motivo não pode ter mais de 200 caracteres.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'new_status' => 'novo status',
            'notes'      => 'observações',
            'reason'     => 'motivo',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Converte valores vazios para null
        if ( $this->notes === '' ) {
            $this->merge( [ 'notes' => null ] );
        }
        if ( $this->reason === '' ) {
            $this->merge( [ 'reason' => null ] );
        }
    }

}
