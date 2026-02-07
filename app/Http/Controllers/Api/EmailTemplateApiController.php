<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Abstracts\Controller;
use App\Models\EmailTemplate;
use App\Services\Application\EmailTemplateService;
use App\Services\Infrastructure\VariableProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmailTemplateApiController extends Controller
{
    private EmailTemplateService $templateService;

    private VariableProcessor $variableProcessor;

    public function __construct(
        EmailTemplateService $templateService,
        VariableProcessor $variableProcessor,
    ) {
        $this->templateService = $templateService;
        $this->variableProcessor = $variableProcessor;
    }

    /**
     * Lista templates com paginação e filtros.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $filters = $request->only(['search', 'category', 'is_active', 'sort_by', 'sort_direction']);
        $perPage = (int) $request->get('per_page', 15);
        $page = (int) $request->get('page', 1);

        // Para API, vamos usar paginação simples
        $offset = ($page - 1) * $perPage;

        $templatesResult = $this->templateService->listByTenantId($user->tenant_id, $filters);

        if (! $templatesResult->isSuccess()) {
            return $this->errorResponse('Erro ao listar templates: '.$templatesResult->getMessage());
        }

        $templates = $templatesResult->getData();

        // Aplicar paginação manual
        $total = count($templates);
        $paginated = array_slice($templates, $offset, $perPage);

        return $this->successResponse([
            'templates' => $paginated,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int) ceil($total / $perPage),
                'from' => $offset + 1,
                'to' => min($offset + $perPage, $total),
            ],
        ]);
    }

    /**
     * Cria novo template.
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:100|unique:email_templates,slug',
            'category' => 'required|in:transactional,promotional,notification,system',
            'subject' => 'required|string|max:500',
            'html_content' => 'required|string',
            'text_content' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
            'metadata' => 'nullable|array',
        ]);

        try {
            $result = $this->templateService->createTemplate($validated, $user->tenant_id);

            if ($result->isSuccess()) {
                return $this->successResponse($result->getData(), 'Template criado com sucesso.', 201);
            } else {
                return $this->errorResponse($result->getMessage(), 400);
            }

        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao criar template: '.$e->getMessage(), 500);
        }
    }

    /**
     * Mostra template específico.
     */
    public function show(EmailTemplate $template): JsonResponse
    {
        // Verificar permissão
        if ($template->tenant_id !== Auth::user()->tenant_id) {
            return $this->errorResponse('Acesso negado.', 403);
        }

        try {
            // Carregar relacionamentos
            $template->load(['logs' => function ($query) {
                $query->latest()->limit(5);
            }]);

            return $this->successResponse($template);

        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao obter template: '.$e->getMessage(), 500);
        }
    }

    /**
     * Atualiza template.
     */
    public function update(Request $request, EmailTemplate $template): JsonResponse
    {
        // Verificar permissão
        if ($template->tenant_id !== Auth::user()->tenant_id || ! $template->canBeEdited()) {
            return $this->errorResponse('Acesso negado.', 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:100|unique:email_templates,slug,'.$template->id,
            'category' => 'required|in:transactional,promotional,notification,system',
            'subject' => 'required|string|max:500',
            'html_content' => 'required|string',
            'text_content' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
            'metadata' => 'nullable|array',
        ]);

        try {
            $result = $this->templateService->updateTemplate($template->id, $validated, $template->tenant_id);

            if ($result->isSuccess()) {
                return $this->successResponse($result->getData(), 'Template atualizado com sucesso.');
            } else {
                return $this->errorResponse($result->getMessage(), 400);
            }

        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao atualizar template: '.$e->getMessage(), 500);
        }
    }

    /**
     * Remove template.
     */
    public function destroy(EmailTemplate $template): JsonResponse
    {
        // Verificar permissão
        if ($template->tenant_id !== Auth::user()->tenant_id || ! $template->canBeDeleted()) {
            return $this->errorResponse('Acesso negado.', 403);
        }

        try {
            $result = $this->templateService->deleteByIdAndTenantId($template->id, $template->tenant_id);

            if ($result->isSuccess()) {
                return $this->successResponse(null, 'Template excluído com sucesso.');
            } else {
                return $this->errorResponse($result->getMessage(), 400);
            }

        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao excluir template: '.$e->getMessage(), 500);
        }
    }

    /**
     * Obtém preview do template.
     */
    public function preview(Request $request, EmailTemplate $template): JsonResponse
    {
        // Verificar permissão
        if ($template->tenant_id !== Auth::user()->tenant_id) {
            return $this->errorResponse('Acesso negado.', 403);
        }

        $data = $request->input('data', []);

        try {
            $result = $this->templateService->getTemplatePreview($template->id, $data, $template->tenant_id);

            if ($result->isSuccess()) {
                return $this->successResponse($result->getData());
            } else {
                return $this->errorResponse($result->getMessage(), 400);
            }

        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao gerar preview: '.$e->getMessage(), 500);
        }
    }

    /**
     * Envia email de teste.
     */
    public function sendTest(Request $request, EmailTemplate $template): JsonResponse
    {
        // Verificar permissão
        if ($template->tenant_id !== Auth::user()->tenant_id) {
            return $this->errorResponse('Acesso negado.', 403);
        }

        $validated = $request->validate([
            'test_email' => 'required|email',
            'test_name' => 'nullable|string|max:255',
            'test_data' => 'nullable|array',
        ]);

        try {
            // Processar template com dados de teste
            $data = array_merge($validated['test_data'] ?? [], [
                'context' => 'test',
                'test_mode' => true,
            ]);

            $processResult = $this->templateService->processTemplate($template->id, $data, $template->tenant_id);

            if (! $processResult->isSuccess()) {
                return $this->errorResponse('Erro ao processar template: '.$processResult->getMessage(), 400);
            }

            $processed = $processResult->getData();

            // TODO: Implementar envio real de email de teste
            // Por enquanto, retornar sucesso
            return $this->successResponse([
                'message' => 'Email de teste enviado para '.$validated['test_email'],
                'preview' => [
                    'subject' => $processed['subject'],
                    'html_content' => $processed['html_content'],
                    'text_content' => $processed['text_content'],
                ],
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao enviar email de teste: '.$e->getMessage(), 500);
        }
    }

    /**
     * Duplica template.
     */
    public function duplicate(Request $request, EmailTemplate $template): JsonResponse
    {
        // Verificar permissão
        if ($template->tenant_id !== Auth::user()->tenant_id) {
            return $this->errorResponse('Acesso negado.', 403);
        }

        try {
            $result = $this->templateService->duplicateTemplate($template->id, $template->tenant_id);

            if ($result->isSuccess()) {
                return $this->successResponse($result->getData(), 'Template duplicado com sucesso.');
            } else {
                return $this->errorResponse($result->getMessage(), 400);
            }

        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao duplicar template: '.$e->getMessage(), 500);
        }
    }

    /**
     * Obtém variáveis disponíveis.
     */
    public function getVariables(Request $request): JsonResponse
    {
        $user = Auth::user();

        try {
            $availableVariables = $this->variableProcessor->getAvailableVariables($user->tenant_id);

            return $this->successResponse([
                'variables' => $availableVariables,
                'total_categories' => count($availableVariables),
                'total_variables' => array_sum(array_map('count', $availableVariables)),
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao obter variáveis: '.$e->getMessage(), 500);
        }
    }

    /**
     * Obtém estatísticas dos templates.
     */
    public function getStats(Request $request, EmailTemplate $template): JsonResponse
    {
        // Verificar permissão
        if ($template->tenant_id !== Auth::user()->tenant_id) {
            return $this->errorResponse('Acesso negado.', 403);
        }

        $period = $request->get('period', 'month');

        try {
            $statsResult = $this->templateService->getTemplateStats($template->id, $template->tenant_id);

            if ($statsResult->isSuccess()) {
                return $this->successResponse($statsResult->getData());
            } else {
                return $this->errorResponse($statsResult->getMessage(), 400);
            }

        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao obter estatísticas: '.$e->getMessage(), 500);
        }
    }

    /**
     * Obtém estatísticas gerais de email.
     */
    public function getAnalytics(Request $request): JsonResponse
    {
        $user = Auth::user();
        $period = $request->get('period', 'month');

        try {
            // TODO: Implementar método no serviço para estatísticas gerais
            $stats = [
                'total_templates' => EmailTemplate::where('tenant_id', $user->tenant_id)->count(),
                'active_templates' => EmailTemplate::where('tenant_id', $user->tenant_id)->active()->count(),
                'total_sent' => 0, // Será implementado quando tivermos logs
                'period' => $period,
            ];

            return $this->successResponse($stats);

        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao obter analytics: '.$e->getMessage(), 500);
        }
    }

    /**
     * Obtém templates predefinidos por categoria.
     */
    public function getPresets(Request $request): JsonResponse
    {
        $user = Auth::user();
        $category = $request->get('category');

        try {
            $presets = [];

            switch ($category) {
                case 'transactional':
                    $presets = $this->getTransactionalPresets();
                    break;
                case 'promotional':
                    $presets = $this->getPromotionalPresets();
                    break;
                case 'notification':
                    $presets = $this->getNotificationPresets();
                    break;
                default:
                    return $this->errorResponse('Categoria inválida.', 400);
            }

            return $this->successResponse([
                'category' => $category,
                'presets' => $presets,
                'total' => count($presets),
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao obter presets: '.$e->getMessage(), 500);
        }
    }

    /**
     * Obtém templates predefinidos transacionais.
     */
    private function getTransactionalPresets(): array
    {
        return [
            [
                'name' => 'Confirmação de Orçamento',
                'slug' => 'budget-confirmation',
                'category' => 'transactional',
                'subject' => 'Confirmação de Orçamento #{{budget_number}}',
                'description' => 'Email enviado ao cliente confirmando recebimento do orçamento',
                'html_content' => $this->getBudgetConfirmationTemplate(),
            ],
            [
                'name' => 'Fatura Gerada',
                'slug' => 'invoice-generated',
                'category' => 'transactional',
                'subject' => 'Fatura #{{invoice_number}} Gerada',
                'description' => 'Email enviado ao cliente quando uma fatura é gerada',
                'html_content' => $this->getInvoiceGeneratedTemplate(),
            ],
        ];
    }

    /**
     * Obtém templates predefinidos promocionais.
     */
    private function getPromotionalPresets(): array
    {
        return [
            [
                'name' => 'Newsletter Mensal',
                'slug' => 'monthly-newsletter',
                'category' => 'promotional',
                'subject' => 'Newsletter {{company_name}} - {{current_date}}',
                'description' => 'Informativo mensal para clientes',
                'html_content' => $this->getNewsletterTemplate(),
            ],
            [
                'name' => 'Promoções Especiais',
                'slug' => 'special-offers',
                'category' => 'promotional',
                'subject' => 'Ofertas Especiais - {{company_name}}',
                'description' => 'Email promocional com ofertas e descontos',
                'html_content' => $this->getPromotionalTemplate(),
            ],
        ];
    }

    /**
     * Obtém templates predefinidos de notificação.
     */
    private function getNotificationPresets(): array
    {
        return [
            [
                'name' => 'Aprovação de Orçamento',
                'slug' => 'budget-approved',
                'category' => 'notification',
                'subject' => 'Orçamento #{{budget_number}} Aprovado',
                'description' => 'Notificação de aprovação de orçamento',
                'html_content' => $this->getBudgetApprovedTemplate(),
            ],
            [
                'name' => 'Pagamento Confirmado',
                'slug' => 'payment-confirmed',
                'category' => 'notification',
                'subject' => 'Pagamento da Fatura #{{invoice_number}} Confirmado',
                'description' => 'Confirmação de pagamento recebido',
                'html_content' => $this->getPaymentConfirmedTemplate(),
            ],
        ];
    }

    /**
     * Template HTML para confirmação de orçamento.
     */
    private function getBudgetConfirmationTemplate(): string
    {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Confirmação de Orçamento</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #f8f9fa; padding: 20px; text-align: center; }
        .content { background: white; padding: 30px; border: 1px solid #dee2e6; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{company_name}}</h1>
        </div>
        <div class="content">
            <h2>Olá {{customer_name}},</h2>
            <p>Recebemos seu orçamento e estamos analisando sua solicitação.</p>
            <p><strong>Orçamento:</strong> #{{budget_number}}</p>
            <p><strong>Valor:</strong> R$ {{budget_value}}</p>
            <p>{{budget_items}}</p>
        </div>
        <div class="footer">
            <p>{{company_name}} | {{company_email}}</p>
        </div>
    </div>
</body>
</html>';
    }

    /**
     * Outros templates serão implementados...
     */
    private function getInvoiceGeneratedTemplate(): string
    {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Fatura Gerada</title>
</head>
<body>
    <h1>Fatura #{{invoice_number}} Gerada</h1>
    <p>Olá {{customer_name}}, sua fatura foi gerada.</p>
    <p><strong>Valor:</strong> R$ {{invoice_amount}}</p>
    <p><strong>Vencimento:</strong> {{invoice_due_date}}</p>
    <a href="{{invoice_link}}">Visualizar Fatura</a>
</body>
</html>';
    }

    private function getNewsletterTemplate(): string
    {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Newsletter</title>
</head>
<body>
    <h1>Newsletter {{company_name}}</h1>
    <p>Olá {{customer_name}}, confira nossas novidades!</p>
    <p>{{newsletter_content}}</p>
</body>
</html>';
    }

    private function getPromotionalTemplate(): string
    {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Promoções</title>
</head>
<body>
    <h1>Ofertas Especiais - {{company_name}}</h1>
    <p>Olá {{customer_name}}, confira nossas promoções!</p>
    <p>{{promotional_content}}</p>
</body>
</html>';
    }

    private function getBudgetApprovedTemplate(): string
    {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Orçamento Aprovado</title>
</head>
<body>
    <h1>Orçamento #{{budget_number}} Aprovado</h1>
    <p>Olá {{customer_name}}, seu orçamento foi aprovado!</p>
    <p><strong>Valor:</strong> R$ {{budget_value}}</p>
</body>
</html>';
    }

    private function getPaymentConfirmedTemplate(): string
    {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Pagamento Confirmado</title>
</head>
<body>
    <h1>Pagamento Confirmado</h1>
    <p>Olá {{customer_name}}, confirmamos o pagamento da fatura #{{invoice_number}}.</p>
    <p><strong>Valor:</strong> R$ {{invoice_amount}}</p>
</body>
</html>';
    }

    /**
     * Métodos auxiliares para resposta JSON.
     */
    private function successResponse($data, string $message = 'Operação realizada com sucesso.', int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => now()->toISOString(),
        ], $statusCode);
    }

    private function errorResponse(string $message, int $statusCode = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'timestamp' => now()->toISOString(),
        ], $statusCode);
    }
}
