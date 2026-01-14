<?php

declare(strict_types=1);

namespace App\Services\Application;

use App\Models\BudgetTemplate;
use App\Support\ServiceResult;
use Illuminate\Support\Facades\DB;

class BudgetTemplateService
{
    /**
     * Cria um novo template.
     */
    public function createTemplate(array $data, int $tenantId, int $userId): ServiceResult
    {
        try {
            DB::beginTransaction();

            $template = BudgetTemplate::create(array_merge($data, [
                'tenant_id' => $tenantId,
                'user_id' => $userId,
            ]));

            DB::commit();

            return ServiceResult::success($template, 'Template criado com sucesso.');

        } catch (\Exception $e) {
            DB::rollBack();

            return ServiceResult::error(
                'Erro ao criar template: '.$e->getMessage()
            );
        }
    }

    /**
     * Atualiza um template.
     */
    public function updateTemplate(BudgetTemplate $template, array $data): ServiceResult
    {
        try {
            DB::beginTransaction();

            $template->update($data);

            DB::commit();

            return ServiceResult::success($template, 'Template atualizado com sucesso.');

        } catch (\Exception $e) {
            DB::rollBack();

            return ServiceResult::error(
                'Erro ao atualizar template: '.$e->getMessage()
            );
        }
    }

    /**
     * Exclui um template.
     */
    public function deleteTemplate(BudgetTemplate $template): ServiceResult
    {
        try {
            if (! $template->canBeDeleted()) {
                return ServiceResult::error(
                    'Template não pode ser excluído pois possui dependências.',
                );
            }

            DB::beginTransaction();

            $template->delete();

            DB::commit();

            return ServiceResult::success(null, 'Template excluído com sucesso.');

        } catch (\Exception $e) {
            DB::rollBack();

            return ServiceResult::error(
                'Erro ao excluir template: '.$e->getMessage()
            );
        }
    }

    /**
     * Cria orçamento a partir de template.
     */
    public function createBudgetFromTemplate(
        BudgetTemplate $template,
        array $overrides = [],
    ): ServiceResult {
        try {
            DB::beginTransaction();

            $budget = $template->createBudgetFromTemplate($overrides);

            DB::commit();

            return ServiceResult::success($budget, 'Orçamento criado a partir do template.');

        } catch (\Exception $e) {
            DB::rollBack();

            return ServiceResult::error(
                'Erro ao criar orçamento: '.$e->getMessage()
            );
        }
    }

    /**
     * Lista templates disponíveis.
     */
    public function listTemplates(int $tenantId, array $filters = []): ServiceResult
    {
        try {
            $query = BudgetTemplate::where('tenant_id', $tenantId)
                ->active();

            // Aplicar filtros
            if (isset($filters['category'])) {
                $query->byCategory($filters['category']);
            }

            if (isset($filters['is_public'])) {
                if ($filters['is_public']) {
                    $query->public();
                } else {
                    $query->private();
                }
            }

            if (isset($filters['search'])) {
                $query->where(function ($q) use ($filters) {
                    $q->where('name', 'like', '%'.$filters['search'].'%')
                        ->orWhere('description', 'like', '%'.$filters['search'].'%');
                });
            }

            // Ordenação
            $sortBy = $filters['sort_by'] ?? 'recent';
            switch ($sortBy) {
                case 'most_used':
                    $query->mostUsed();
                    break;
                case 'name':
                    $query->orderBy('name');
                    break;
                case 'recent':
                default:
                    $query->recent();
                    break;
            }

            $templates = $query->get();

            return ServiceResult::success($templates, 'Templates listados com sucesso.');

        } catch (\Exception $e) {
            return ServiceResult::error(
                'Erro ao listar templates: '.$e->getMessage()
            );
        }
    }

    /**
     * Obtém preview de um template.
     */
    public function getTemplatePreview(BudgetTemplate $template, array $variables = []): ServiceResult
    {
        try {
            $preview = $template->getPreview($variables);

            return ServiceResult::success($preview, 'Preview gerado com sucesso.');

        } catch (\Exception $e) {
            return ServiceResult::error(
                'Erro ao gerar preview: '.$e->getMessage()
            );
        }
    }

    /**
     * Duplica um template.
     */
    public function duplicateTemplate(BudgetTemplate $template, int $userId): ServiceResult
    {
        try {
            DB::beginTransaction();

            $newTemplate = $template->duplicate($userId);

            DB::commit();

            return ServiceResult::success($newTemplate, 'Template duplicado com sucesso.');

        } catch (\Exception $e) {
            DB::rollBack();

            return ServiceResult::error(
                'Erro ao duplicar template: '.$e->getMessage()
            );
        }
    }

    /**
     * Obtém estatísticas de uso de templates.
     */
    public function getTemplateStats(int $tenantId): ServiceResult
    {
        try {
            $stats = [
                'total_templates' => BudgetTemplate::where('tenant_id', $tenantId)->count(),
                'active_templates' => BudgetTemplate::where('tenant_id', $tenantId)->active()->count(),
                'public_templates' => BudgetTemplate::where('tenant_id', $tenantId)->public()->count(),
                'total_usage' => BudgetTemplate::where('tenant_id', $tenantId)->sum('usage_count'),
                'categories' => BudgetTemplate::where('tenant_id', $tenantId)
                    ->selectRaw('category, COUNT(*) as count')
                    ->groupBy('category')
                    ->pluck('count', 'category'),
            ];

            return ServiceResult::success($stats, 'Estatísticas obtidas com sucesso.');

        } catch (\Exception $e) {
            return ServiceResult::error(
                'Erro ao obter estatísticas: '.$e->getMessage()
            );
        }
    }

    /**
     * Cria templates padrão para um tenant.
     */
    public function createDefaultTemplates(int $tenantId, int $userId): ServiceResult
    {
        try {
            DB::beginTransaction();

            BudgetTemplate::createDefaultTemplates($tenantId, $userId);

            DB::commit();

            return ServiceResult::success(null, 'Templates padrão criados com sucesso.');

        } catch (\Exception $e) {
            DB::rollBack();

            return ServiceResult::error(
                'Erro ao criar templates padrão: '.$e->getMessage()
            );
        }
    }

    /**
     * Valida dados de um template.
     */
    public function validateTemplateData(array $data): ServiceResult
    {
        $rules = BudgetTemplate::businessRules();

        $validator = \Illuminate\Support\Facades\Validator::make($data, $rules);

        if ($validator->fails()) {
            return ServiceResult::error(
                'Dados inválidos: '.implode(', ', $validator->errors()->all())
            );
        }

        return ServiceResult::success($data, 'Dados válidos.');
    }

    /**
     * Obtém categorias disponíveis.
     */
    public function getAvailableCategories(): array
    {
        return BudgetTemplate::getAvailableCategories();
    }

    /**
     * Obtém variáveis sugeridas para um tipo de template.
     */
    public function getSuggestedVariables(string $category): array
    {
        $suggestions = [
            'produto' => [
                'produto_nome' => 'Nome do Produto',
                'produto_descricao' => 'Descrição do Produto',
                'produto_preco' => 'Preço do Produto',
                'produto_categoria' => 'Categoria do Produto',
            ],
            'servico' => [
                'servico_nome' => 'Nome do Serviço',
                'servico_descricao' => 'Descrição do Serviço',
                'servico_valor' => 'Valor do Serviço',
                'servico_horas' => 'Horas Estimadas',
            ],
            'projeto' => [
                'projeto_nome' => 'Nome do Projeto',
                'projeto_descricao' => 'Descrição do Projeto',
                'projeto_valor' => 'Valor do Projeto',
                'projeto_prazo' => 'Prazo de Entrega',
                'cliente_nome' => 'Nome do Cliente',
            ],
            'consultoria' => [
                'consultoria_tema' => 'Tema da Consultoria',
                'consultoria_objetivo' => 'Objetivo da Consultoria',
                'consultoria_valor' => 'Valor da Consultoria',
                'consultoria_horas' => 'Horas de Consultoria',
            ],
        ];

        return $suggestions[$category] ?? [];
    }
}
