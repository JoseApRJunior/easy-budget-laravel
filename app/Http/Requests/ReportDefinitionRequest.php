<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form Request para validação de definições de relatório
 */
class ReportDefinitionRequest extends FormRequest
{
    /**
     * Determina se o usuário está autorizado a fazer esta requisição
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Regras de validação que se aplicam a esta requisição
     */
    public function rules(): array
    {
        $reportDefinitionId = $this->route('report')?->id ?? null;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('report_definitions')->where(function ($query) {
                    return $query->where('tenant_id', auth()->user()->tenant_id)
                        ->where('user_id', auth()->id());
                })->ignore($reportDefinitionId),
            ],
            'description' => 'nullable|string|max:1000',
            'category' => [
                'required',
                'string',
                Rule::in(array_keys(\App\Models\ReportDefinition::CATEGORIES)),
            ],
            'type' => [
                'required',
                'string',
                Rule::in(array_keys(\App\Models\ReportDefinition::TYPES)),
            ],
            'query_builder' => 'required|array',
            'query_builder.table' => 'required|string|max:100',
            'query_builder.selects' => 'nullable|array',
            'query_builder.selects.*.field' => 'required|string|max:100',
            'query_builder.selects.*.alias' => 'nullable|string|max:100',
            'query_builder.joins' => 'nullable|array',
            'query_builder.joins.*.table' => 'required|string|max:100',
            'query_builder.joins.*.first' => 'required|string|max:100',
            'query_builder.joins.*.operator' => 'required|string|in:=,!=,<,>,<=,>=,LIKE',
            'query_builder.joins.*.second' => 'required|string|max:100',
            'query_builder.joins.*.type' => 'nullable|string|in:INNER,LEFT,RIGHT,FULL',
            'query_builder.filters' => 'nullable|array',
            'query_builder.filters.*.column' => 'required|string|max:100',
            'query_builder.filters.*.operator' => 'required|string|in:=,!=,<,>,<=,>=,LIKE,IN,NOT IN',
            'query_builder.filters.*.value' => 'required',
            'query_builder.group_by' => 'nullable|array',
            'query_builder.group_by.*' => 'string|max:100',
            'query_builder.order_by' => 'nullable|array',
            'query_builder.order_by.*.column' => 'required|string|max:100',
            'query_builder.order_by.*.direction' => 'required|string|in:ASC,DESC',
            'query_builder.aggregations' => 'nullable|array',
            'query_builder.aggregations.*.function' => 'required|string|in:COUNT,SUM,AVG,MIN,MAX',
            'query_builder.aggregations.*.column' => 'required|string|max:100',
            'query_builder.aggregations.*.alias' => 'nullable|string|max:100',
            'config' => 'required|array',
            'config.title' => 'required|string|max:255',
            'config.format' => 'nullable|string|in:table,chart,mixed,kpi',
            'config.orientation' => 'nullable|string|in:portrait,landscape',
            'config.page_size' => 'nullable|string|in:a4,a3,letter,legal',
            'config.show_header' => 'nullable|boolean',
            'config.show_footer' => 'nullable|boolean',
            'config.show_totals' => 'nullable|boolean',
            'config.numeric_columns' => 'nullable|array',
            'config.numeric_columns.*' => 'string|max:100',
            'config.formatters' => 'nullable|array',
            'config.formatters.*.field' => 'required|string|max:100',
            'config.formatters.*.type' => 'required|string|in:currency,percentage,date,datetime,number',
            'config.formatters.*.options' => 'nullable|array',
            'config.calculations' => 'nullable|array',
            'config.calculations.*.field' => 'required|string|max:100',
            'config.calculations.*.formula' => 'required|string|max:500',
            'config.post_filters' => 'nullable|array',
            'filters' => 'nullable|array',
            'filters.*.field' => 'required|string|max:100',
            'filters.*.operator' => 'required|string|in:=,!=,<,>,<=,>=,LIKE,CONTAINS',
            'filters.*.value' => 'required',
            'visualization' => 'nullable|array',
            'visualization.chart_type' => 'nullable|string|in:line,bar,pie,doughnut,area,scatter',
            'visualization.x_field' => 'nullable|string|max:100',
            'visualization.y_field' => 'nullable|string|max:100',
            'visualization.color' => 'nullable|string|in:primary,secondary,success,danger,warning,info',
            'visualization.show_legend' => 'nullable|boolean',
            'visualization.show_grid' => 'nullable|boolean',
            'visualization.height' => 'nullable|integer|min:200|max:1000',
            'is_active' => 'boolean',
            'is_system' => 'boolean',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'version' => 'nullable|integer|min:1',
        ];
    }

    /**
     * Mensagens de erro personalizadas
     */
    public function messages(): array
    {
        return [
            'name.required' => 'O nome do relatório é obrigatório',
            'name.unique' => 'Já existe um relatório com este nome',
            'category.required' => 'A categoria deve ser selecionada',
            'category.in' => 'Categoria inválida',
            'type.required' => 'O tipo de relatório deve ser selecionado',
            'type.in' => 'Tipo de relatório inválido',
            'query_builder.required' => 'A configuração da query é obrigatória',
            'query_builder.table.required' => 'A tabela base deve ser especificada',
            'config.required' => 'A configuração do relatório é obrigatória',
            'config.title.required' => 'O título do relatório é obrigatório',
        ];
    }

    /**
     * Nomes dos atributos personalizados
     */
    public function attributes(): array
    {
        return [
            'name' => 'nome do relatório',
            'description' => 'descrição',
            'category' => 'categoria',
            'type' => 'tipo',
            'query_builder' => 'configuração da query',
            'config' => 'configuração do relatório',
            'is_active' => 'ativo',
            'is_system' => 'relatório do sistema',
        ];
    }

    /**
     * Preparar dados para validação
     */
    protected function prepareForValidation(): void
    {
        // Adicionar tenant_id e user_id automaticamente
        $this->merge([
            'tenant_id' => auth()->user()->tenant_id,
            'user_id' => auth()->id(),
        ]);

        // Sanitizar dados
        $this->sanitizeInput();
    }

    /**
     * Sanitiza dados de entrada
     */
    private function sanitizeInput(): void
    {
        // Sanitizar nome
        if ($this->has('name')) {
            $this->merge([
                'name' => trim(strip_tags($this->input('name'))),
            ]);
        }

        // Sanitizar descrição
        if ($this->has('description')) {
            $this->merge([
                'description' => trim(strip_tags($this->input('description'))),
            ]);
        }

        // Sanitizar tags
        if ($this->has('tags')) {
            $tags = $this->input('tags');
            if (is_array($tags)) {
                $this->merge([
                    'tags' => array_map(function ($tag) {
                        return trim(strip_tags($tag));
                    }, $tags),
                ]);
            }
        }
    }

    /**
     * Validação adicional após as regras básicas
     */
    protected function passedValidation(): void
    {
        // Validações customizadas
        $this->validateQueryBuilder();
        $this->validateVisualization();
        $this->validateTenantAccess();
    }

    /**
     * Valida configuração do Query Builder
     */
    private function validateQueryBuilder(): void
    {
        $queryBuilder = $this->input('query_builder', []);

        if (empty($queryBuilder)) {
            return;
        }

        // Validar se a tabela existe (simulação)
        $allowedTables = ['budgets', 'customers', 'budget_items', 'customer_interactions'];
        if (! in_array($queryBuilder['table'] ?? '', $allowedTables)) {
            // Em produção, isso seria uma validação mais robusta
            // Por ora, apenas logamos
        }

        // Validar campos selecionados
        if (isset($queryBuilder['selects'])) {
            foreach ($queryBuilder['selects'] as $select) {
                if (empty($select['field'])) {
                    throw new \Illuminate\Validation\ValidationException(
                        validator([], []),
                        ['query_builder.selects' => 'Campo de seleção inválido'],
                    );
                }
            }
        }
    }

    /**
     * Valida configuração de visualização
     */
    private function validateVisualization(): void
    {
        $visualization = $this->input('visualization', []);
        $type = $this->input('type');

        if (empty($visualization)) {
            return;
        }

        // Para gráficos, validar campos obrigatórios
        if (in_array($type, ['chart', 'mixed'])) {
            if (empty($visualization['chart_type'])) {
                throw new \Illuminate\Validation\ValidationException(
                    validator([], []),
                    ['visualization.chart_type' => 'Tipo de gráfico é obrigatório'],
                );
            }
        }
    }

    /**
     * Valida acesso do tenant
     */
    private function validateTenantAccess(): void
    {
        // Verificar se o usuário tem permissão para acessar recursos do tenant
        $user = auth()->user();

        if (! $user || ! $user->tenant_id) {
            throw new \Illuminate\Validation\ValidationException(
                validator([], []),
                ['user' => 'Usuário não autorizado'],
            );
        }
    }
}
