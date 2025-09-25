<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Validação para geração de relatórios de orçamentos.
 * Baseado no padrão do sistema antigo para relatórios.
 * Inclui validação de períodos e filtros específicos.
 *
 * @package App\Http\Requests
 * @author IA
 */
class BudgetReportFormRequest extends FormRequest
{
    /**
     * Determina se o usuário está autorizado a fazer este request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->tenant_id;
    }

    /**
     * Regras de validação para geração de relatórios.
     *
     * @return array<string, array|Rule|string>
     */
    public function rules(): array
    {
        return [
            'period' => [
                'required',
                Rule::in(['daily', 'weekly', 'monthly', 'quarterly', 'yearly', 'custom'])
            ],
            'date_from' => [
                'nullable',
                'date',
                'required_if:period,custom',
                'before_or_equal:date_to'
            ],
            'date_to' => [
                'nullable',
                'date',
                'required_if:period,custom',
                'after_or_equal:date_from',
                'before_or_equal:today'
            ],
            'status' => [
                'nullable',
                'array'
            ],
            'status.*' => [
                Rule::in(['pending', 'approved', 'rejected', 'completed', 'finalized'])
            ],
            'customer_id' => [
                'nullable',
                'integer',
                'exists:customers,id'
            ],
            'category_id' => [
                'nullable',
                'integer',
                'exists:categories,id'
            ],
            'user_id' => [
                'nullable',
                'integer',
                'exists:users,id'
            ],
            'amount_min' => [
                'nullable',
                'numeric',
                'min:0'
            ],
            'amount_max' => [
                'nullable',
                'numeric',
                'min:0',
                'gte:amount_min'
            ],
            'format' => [
                'nullable',
                Rule::in(['json', 'pdf', 'excel', 'csv'])
            ],
            'group_by' => [
                'nullable',
                Rule::in(['status', 'customer', 'category', 'user', 'month', 'week'])
            ],
            'include_items' => [
                'nullable',
                'boolean'
            ],
            'include_totals' => [
                'nullable',
                'boolean'
            ]
        ];
    }

    /**
     * Mensagens customizadas para erros de validação.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'period.required' => 'O período do relatório é obrigatório.',
            'period.in' => 'O período deve ser um dos valores permitidos.',
            
            'date_from.required_if' => 'A data inicial é obrigatória para período customizado.',
            'date_from.date' => 'A data inicial deve ser uma data válida.',
            'date_from.before_or_equal' => 'A data inicial deve ser anterior ou igual à data final.',
            
            'date_to.required_if' => 'A data final é obrigatória para período customizado.',
            'date_to.date' => 'A data final deve ser uma data válida.',
            'date_to.after_or_equal' => 'A data final deve ser posterior ou igual à data inicial.',
            'date_to.before_or_equal' => 'A data final não pode ser futura.',
            
            'status.array' => 'Os status devem ser fornecidos como array.',
            'status.*.in' => 'Status inválido selecionado.',
            
            'customer_id.integer' => 'O ID do cliente deve ser um número inteiro.',
            'customer_id.exists' => 'O cliente selecionado não existe.',
            
            'category_id.integer' => 'O ID da categoria deve ser um número inteiro.',
            'category_id.exists' => 'A categoria selecionada não existe.',
            
            'user_id.integer' => 'O ID do usuário deve ser um número inteiro.',
            'user_id.exists' => 'O usuário selecionado não existe.',
            
            'amount_min.numeric' => 'O valor mínimo deve ser numérico.',
            'amount_min.min' => 'O valor mínimo não pode ser negativo.',
            
            'amount_max.numeric' => 'O valor máximo deve ser numérico.',
            'amount_max.min' => 'O valor máximo não pode ser negativo.',
            'amount_max.gte' => 'O valor máximo deve ser maior ou igual ao valor mínimo.',
            
            'format.in' => 'O formato deve ser um dos valores permitidos: json, pdf, excel, csv.',
            
            'group_by.in' => 'O agrupamento deve ser um dos valores permitidos.',
            
            'include_items.boolean' => 'A opção incluir itens deve ser verdadeiro ou falso.',
            'include_totals.boolean' => 'A opção incluir totais deve ser verdadeiro ou falso.'
        ];
    }

    /**
     * Campos que devem ser retornados com erros de validação.
     *
     * @return array<int, string>
     */
    public function attributes(): array
    {
        return [
            'period' => 'período',
            'date_from' => 'data inicial',
            'date_to' => 'data final',
            'status' => 'status',
            'customer_id' => 'cliente',
            'category_id' => 'categoria',
            'user_id' => 'usuário',
            'amount_min' => 'valor mínimo',
            'amount_max' => 'valor máximo',
            'format' => 'formato',
            'group_by' => 'agrupar por',
            'include_items' => 'incluir itens',
            'include_totals' => 'incluir totais'
        ];
    }

    /**
     * Preparar os dados para validação.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        // Definir valores padrão
        $defaults = [
            'format' => 'json',
            'include_items' => false,
            'include_totals' => true
        ];

        foreach ($defaults as $key => $value) {
            if (!$this->has($key)) {
                $this->merge([$key => $value]);
            }
        }

        // Converter booleanos
        $this->merge([
            'include_items' => $this->boolean('include_items'),
            'include_totals' => $this->boolean('include_totals')
        ]);

        // Definir datas automáticas para períodos pré-definidos
        if ($this->period && $this->period !== 'custom') {
            $dates = $this->getDateRangeForPeriod($this->period);
            $this->merge($dates);
        }
    }

    /**
     * Obter intervalo de datas para período pré-definido.
     *
     * @param string $period
     * @return array
     */
    private function getDateRangeForPeriod(string $period): array
    {
        $now = now();
        
        return match($period) {
            'daily' => [
                'date_from' => $now->format('Y-m-d'),
                'date_to' => $now->format('Y-m-d')
            ],
            'weekly' => [
                'date_from' => $now->startOfWeek()->format('Y-m-d'),
                'date_to' => $now->endOfWeek()->format('Y-m-d')
            ],
            'monthly' => [
                'date_from' => $now->startOfMonth()->format('Y-m-d'),
                'date_to' => $now->endOfMonth()->format('Y-m-d')
            ],
            'quarterly' => [
                'date_from' => $now->startOfQuarter()->format('Y-m-d'),
                'date_to' => $now->endOfQuarter()->format('Y-m-d')
            ],
            'yearly' => [
                'date_from' => $now->startOfYear()->format('Y-m-d'),
                'date_to' => $now->endOfYear()->format('Y-m-d')
            ],
            default => []
        };
    }
}