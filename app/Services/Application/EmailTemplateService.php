<?php

declare(strict_types=1);

namespace App\Services\Application;

use App\Models\EmailTemplate;
use App\Services\Infrastructure\VariableProcessor;
use App\Support\ServiceResult;
use App\Traits\SlugGenerator;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class EmailTemplateService
{
    use SlugGenerator;

    private VariableProcessor    $variableProcessor;
    private EmailTrackingService $trackingService;

    public function __construct(
        VariableProcessor $variableProcessor,
        EmailTrackingService $trackingService,
    ) {
        $this->variableProcessor = $variableProcessor;
        $this->trackingService   = $trackingService;
    }

    protected function findEntityByIdAndTenantId( int $id, int $tenantId ): ?Model
    {
        return EmailTemplate::where( 'id', $id )
            ->where( 'tenant_id', $tenantId )
            ->first();
    }

    protected function listEntitiesByTenantId( int $tenantId, array $filters = [], ?array $orderBy = null, ?int $limit = null, ?int $offset = null ): array
    {
        $query = EmailTemplate::where( 'tenant_id', $tenantId );

        // Aplicar filtros
        if ( isset( $filters[ 'category' ] ) ) {
            $query->byCategory( $filters[ 'category' ] );
        }

        if ( isset( $filters[ 'is_active' ] ) ) {
            $query->where( 'is_active', $filters[ 'is_active' ] );
        }

        if ( isset( $filters[ 'search' ] ) ) {
            $search = $filters[ 'search' ];
            $query->where( function ( $q ) use ( $search ) {
                $q->where( 'name', 'like', "%{$search}%" )
                    ->orWhere( 'subject', 'like', "%{$search}%" )
                    ->orWhere( 'slug', 'like', "%{$search}%" );
            } );
        }

        // Ordenação
        if ( $orderBy ) {
            $query->orderBy( $orderBy[ 0 ], $orderBy[ 1 ] );
        } else {
            $query->ordered();
        }

        if ( $limit ) {
            $query->limit( $limit );
        }

        if ( $offset ) {
            $query->offset( $offset );
        }

        return $query->get()->toArray();
    }

    protected function createEntity( array $data, int $tenantId ): Model
    {
        $template = new EmailTemplate();
        $template->fill( [
            'tenant_id'    => $tenantId,
            'name'         => $data[ 'name' ],
            'slug'         => $data[ 'slug' ] ?? $this->generateSlug( $data[ 'name' ] ),
            'category'     => $data[ 'category' ],
            'subject'      => $data[ 'subject' ],
            'html_content' => $data[ 'html_content' ],
            'text_content' => $data[ 'text_content' ] ?? null,
            'variables'    => $data[ 'variables' ] ?? [],
            'is_active'    => $data[ 'is_active' ] ?? true,
            'is_system'    => $data[ 'is_system' ] ?? false,
            'sort_order'   => $data[ 'sort_order' ] ?? 0,
            'metadata'     => $data[ 'metadata' ] ?? [],
        ] );

        return $template;
    }

    protected function updateEntity( Model $entity, array $data, int $tenantId ): void
    {
        $entity->fill( $data );
    }

    protected function saveEntity( Model $entity ): bool
    {
        return $entity->save();
    }

    protected function deleteEntity( Model $entity ): bool
    {
        return $entity->delete();
    }

    protected function belongsToTenant( Model $entity, int $tenantId ): bool
    {
        return (int) $entity->tenant_id === $tenantId;
    }

    protected function canDeleteEntity( Model $entity ): bool
    {
        return $entity->canBeDeleted();
    }

    /**
     * Implementação dos métodos abstratos da BaseTenantService.
     */

    public function getByIdAndTenantId( int $id, int $tenantId ): ServiceResult
    {
        $template = $this->findEntityByIdAndTenantId( $id, $tenantId );
        if ( !$template ) {
            return $this->error( 'NOT_FOUND', 'Template não encontrado.' );
        }
        return $this->success( $template, 'Template encontrado.' );
    }

    public function listByTenantId( int $tenantId, array $filters = [] ): ServiceResult
    {
        $templates = $this->listEntitiesByTenantId( $tenantId, $filters );
        return $this->success( $templates, 'Templates listados.' );
    }

    public function createByTenantId( array $data, int $tenantId ): ServiceResult
    {
        return $this->createTemplate( $data, $tenantId );
    }

    public function updateByIdAndTenantId( int $id, array $data, int $tenantId ): ServiceResult
    {
        return $this->updateTemplate( $id, $data, $tenantId );
    }

    public function deleteByIdAndTenantId( int $id, int $tenantId ): ServiceResult
    {
        $template = $this->findEntityByIdAndTenantId( $id, $tenantId );
        if ( !$template ) {
            return $this->error( 'NOT_FOUND', 'Template não encontrado.' );
        }

        if ( !$this->canDeleteEntity( $template ) ) {
            return $this->error( 'UNAUTHORIZED', 'Template não pode ser excluído.' );
        }

        if ( !$this->deleteEntity( $template ) ) {
            return $this->error( 'ERROR', 'Falha ao excluir template.' );
        }

        return $this->success( null, 'Template excluído com sucesso.' );
    }

    public function validate( array $data, bool $isUpdate = false ): ServiceResult
    {
        if ( !isset( $data[ 'tenant_id' ] ) ) {
            return $this->error( 'INVALID_DATA', 'tenant_id é obrigatório.' );
        }

        return $this->validateForTenant( $data, (int) $data[ 'tenant_id' ], $isUpdate );
    }

    /**
     * Validação específica para templates de email.
     */
    protected function validateForTenant( array $data, int $tenantId, bool $isUpdate = false ): ServiceResult
    {
        $rules = [
            'name'         => 'required|string|max:255',
            'slug'         => 'required|string|max:100|unique:email_templates,slug',
            'category'     => 'required|in:transactional,promotional,notification,system',
            'subject'      => 'required|string|max:500',
            'html_content' => 'required|string',
            'text_content' => 'nullable|string',
            'variables'    => 'nullable|array',
            'is_active'    => 'boolean',
            'is_system'    => 'boolean',
            'sort_order'   => 'integer|min:0',
            'metadata'     => 'nullable|array',
        ];

        // Ajustes para atualização
        if ( $isUpdate && isset( $data[ 'id' ] ) ) {
            $rules[ 'slug' ] = 'required|string|max:100|unique:email_templates,slug,' . $data[ 'id' ];
        }

        $validator = Validator::make( $data, $rules );

        if ( $validator->fails() ) {
            $messages = $validator->errors()->all();
            return $this->error( 'INVALID_DATA', implode( ', ', $messages ) );
        }

        // Validar variáveis utilizadas
        if ( isset( $data[ 'html_content' ] ) ) {
            $availableVariables = $this->variableProcessor->getAvailableVariables( $tenantId );
            $allAvailable       = [];
            foreach ( $availableVariables as $category ) {
                $allAvailable = array_merge( $allAvailable, array_keys( $category ) );
            }

            $validation = $this->variableProcessor->validateVariables( $data[ 'html_content' ], $allAvailable );

            if ( !$validation[ 'valid' ] ) {
                return $this->error(
                    'INVALID_DATA',
                    'Variáveis inválidas encontradas: ' . implode( ', ', $validation[ 'invalid' ] )
                );
            }
        }

        return $this->success();
    }

    /**
     * Cria template com validação completa.
     */
    public function createTemplate( array $data, int $tenantId ): ServiceResult
    {
        return DB::transaction( function () use ($data, $tenantId) {
            try {
                // Validação
                $validation = $this->validateForTenant( $data, $tenantId );
                if ( !$validation->isSuccess() ) {
                    return $validation;
                }

                // Criar entidade
                $template = $this->createEntity( $data, $tenantId );

                if ( !$this->saveEntity( $template ) ) {
                    return $this->error( 'ERROR', 'Falha ao salvar template.' );
                }

                // Extrair variáveis utilizadas
                $usedVariables = $this->variableProcessor->extractVariables( $template->html_content );
                $template->update( [ 'variables' => $usedVariables ] );

                return $this->success( $template, 'Template criado com sucesso.' );

            } catch ( Exception $e ) {
                Log::error( 'Erro ao criar template de email', [
                    'tenant_id' => $tenantId,
                    'data'      => $data,
                    'error'     => $e->getMessage()
                ] );

                return $this->error( 'ERROR', 'Erro interno ao criar template: ' . $e->getMessage() );
            }
        } );
    }

    /**
     * Atualiza template com validação completa.
     */
    public function updateTemplate( int $templateId, array $data, int $tenantId ): ServiceResult
    {
        return DB::transaction( function () use ($templateId, $data, $tenantId) {
            try {
                $template = $this->findEntityByIdAndTenantId( $templateId, $tenantId );

                if ( !$template ) {
                    return $this->error( 'NOT_FOUND', 'Template não encontrado.' );
                }

                if ( !$template->canBeEdited() ) {
                    return $this->error( 'UNAUTHORIZED', 'Template não pode ser editado.' );
                }

                // Validação
                $data[ 'id' ] = $templateId;
                $validation   = $this->validateForTenant( $data, $tenantId, true );
                if ( !$validation->isSuccess() ) {
                    return $validation;
                }

                // Atualizar entidade
                $this->updateEntity( $template, $data, $tenantId );

                if ( !$this->saveEntity( $template ) ) {
                    return $this->error( 'ERROR', 'Falha ao atualizar template.' );
                }

                // Atualizar variáveis utilizadas
                $usedVariables = $this->variableProcessor->extractVariables( $template->html_content );
                $template->update( [ 'variables' => $usedVariables ] );

                return $this->success( $template, 'Template atualizado com sucesso.' );

            } catch ( Exception $e ) {
                Log::error( 'Erro ao atualizar template de email', [
                    'template_id' => $templateId,
                    'tenant_id'   => $tenantId,
                    'error'       => $e->getMessage()
                ] );

                return $this->error( 'ERROR', 'Erro interno ao atualizar template: ' . $e->getMessage() );
            }
        } );
    }

    /**
     * Duplica template existente.
     */
    public function duplicateTemplate( int $templateId, int $tenantId ): ServiceResult
    {
        try {
            $originalTemplate = $this->findEntityByIdAndTenantId( $templateId, $tenantId );

            if ( !$originalTemplate ) {
                return $this->error( 'NOT_FOUND', 'Template original não encontrado.' );
            }

            $newTemplate            = $originalTemplate->replicate();
            $newTemplate->name      = 'Cópia de ' . $originalTemplate->name;
            $newTemplate->slug      = $originalTemplate->slug . '-copy-' . time();
            $newTemplate->is_system = false;
            $newTemplate->save();

            return $this->success( $newTemplate, 'Template duplicado com sucesso.' );

        } catch ( Exception $e ) {
            Log::error( 'Erro ao duplicar template', [
                'template_id' => $templateId,
                'tenant_id'   => $tenantId,
                'error'       => $e->getMessage()
            ] );

            return $this->error( 'ERROR', 'Erro ao duplicar template: ' . $e->getMessage() );
        }
    }

    /**
     * Processa template com dados variáveis.
     */
    public function processTemplate( int $templateId, array $data, int $tenantId ): ServiceResult
    {
        try {
            $template = $this->findEntityByIdAndTenantId( $templateId, $tenantId );

            if ( !$template ) {
                return $this->error( 'NOT_FOUND', 'Template não encontrado.' );
            }

            // Processar dados dinâmicos
            $processedData = $this->variableProcessor->processDynamicData( $data, $tenantId );

            // Processar conteúdo HTML
            $processedHtml = $this->variableProcessor->processText( $template->html_content, $processedData );

            // Processar conteúdo texto se existir
            $processedText = $template->text_content
                ? $this->variableProcessor->processText( $template->text_content, $processedData )
                : null;

            // Processar assunto
            $processedSubject = $this->variableProcessor->processText( $template->subject, $processedData );

            return $this->success( [
                'template'       => $template,
                'subject'        => $processedSubject,
                'html_content'   => $processedHtml,
                'text_content'   => $processedText,
                'variables_used' => $template->variables,
                'processed_data' => $processedData,
            ], 'Template processado com sucesso.' );

        } catch ( Exception $e ) {
            Log::error( 'Erro ao processar template', [
                'template_id' => $templateId,
                'tenant_id'   => $tenantId,
                'error'       => $e->getMessage()
            ] );

            return $this->error( 'ERROR', 'Erro ao processar template: ' . $e->getMessage() );
        }
    }

    /**
     * Obtém preview do template.
     */
    public function getTemplatePreview( int $templateId, array $data, int $tenantId ): ServiceResult
    {
        $processResult = $this->processTemplate( $templateId, $data, $tenantId );

        if ( !$processResult->isSuccess() ) {
            return $processResult;
        }

        $processed = $processResult->getData();

        return $this->success( [
            'id'           => $processed[ 'template' ]->id,
            'name'         => $processed[ 'template' ]->name,
            'subject'      => $processed[ 'subject' ],
            'html_content' => $processed[ 'html_content' ],
            'text_content' => $processed[ 'text_content' ],
            'category'     => $processed[ 'template' ]->category,
        ], 'Preview gerado com sucesso.' );
    }

    /**
     * Obtém estatísticas do template.
     */
    public function getTemplateStats( int $templateId, int $tenantId ): ServiceResult
    {
        try {
            $template = $this->findEntityByIdAndTenantId( $templateId, $tenantId );

            if ( !$template ) {
                return $this->error( 'NOT_FOUND', 'Template não encontrado.' );
            }

            $stats = $this->trackingService->getTemplateStats( $templateId, $tenantId );

            return $this->success( [
                'template'      => $template,
                'stats'         => $stats,
                'usage_summary' => $template->getUsageStats(),
            ], 'Estatísticas obtidas com sucesso.' );

        } catch ( Exception $e ) {
            Log::error( 'Erro ao obter estatísticas do template', [
                'template_id' => $templateId,
                'tenant_id'   => $tenantId,
                'error'       => $e->getMessage()
            ] );

            return $this->error( 'ERROR', 'Erro ao obter estatísticas: ' . $e->getMessage() );
        }
    }

    /**
     * Lista templates com filtros avançados.
     */
    public function listTemplatesWithFilters( int $tenantId, array $filters = [] ): ServiceResult
    {
        try {
            $templates = $this->listEntitiesByTenantId( $tenantId, $filters );

            // Adicionar estatísticas básicas para cada template
            foreach ( $templates as &$template ) {
                $templateModel = EmailTemplate::find( $template[ 'id' ] );
                if ( $templateModel ) {
                    $template[ 'stats' ] = $templateModel->getUsageStats();
                }
            }

            return $this->success( $templates, 'Templates listados com sucesso.' );

        } catch ( Exception $e ) {
            Log::error( 'Erro ao listar templates', [
                'tenant_id' => $tenantId,
                'filters'   => $filters,
                'error'     => $e->getMessage()
            ] );

            return $this->error( 'ERROR', 'Erro ao listar templates: ' . $e->getMessage() );
        }
    }

    /**
     * Cria templates predefinidos para o tenant.
     */
    public function createDefaultTemplates( int $tenantId ): ServiceResult
    {
        return DB::transaction( function () use ($tenantId) {
            try {
                $defaultTemplates = $this->getDefaultTemplateDefinitions();

                $createdCount = 0;
                foreach ( $defaultTemplates as $templateData ) {
                    $templateData[ 'tenant_id' ] = $tenantId;
                    $templateData[ 'is_system' ] = true;

                    $template = new EmailTemplate();
                    $template->fill( $templateData );

                    if ( $template->save() ) {
                        $createdCount++;
                    }
                }

                return $this->success(
                    [ 'created_count' => $createdCount ],
                    "{$createdCount} templates padrão criados com sucesso.",
                );

            } catch ( Exception $e ) {
                Log::error( 'Erro ao criar templates padrão', [
                    'tenant_id' => $tenantId,
                    'error'     => $e->getMessage()
                ] );

                return $this->error( 'ERROR', 'Erro ao criar templates padrão: ' . $e->getMessage() );
            }
        } );
    }

    /**
     * Obtém definições de templates padrão.
     */
    private function getDefaultTemplateDefinitions(): array
    {
        return [
            [
                'name'         => 'Confirmação de Orçamento',
                'slug'         => 'budget-confirmation',
                'category'     => 'transactional',
                'subject'      => 'Confirmação de Orçamento #{{budget_number}}',
                'html_content' => $this->getBudgetConfirmationTemplate(),
                'text_content' => $this->getBudgetConfirmationTextTemplate(),
                'is_active'    => true,
                'sort_order'   => 1,
            ],
            [
                'name'         => 'Aprovação de Orçamento',
                'slug'         => 'budget-approved',
                'category'     => 'notification',
                'subject'      => 'Orçamento #{{budget_number}} Aprovado',
                'html_content' => $this->getBudgetApprovedTemplate(),
                'text_content' => $this->getBudgetApprovedTextTemplate(),
                'is_active'    => true,
                'sort_order'   => 2,
            ],
            [
                'name'         => 'Rejeição de Orçamento',
                'slug'         => 'budget-rejected',
                'category'     => 'notification',
                'subject'      => 'Orçamento #{{budget_number}} Rejeitado',
                'html_content' => $this->getBudgetRejectedTemplate(),
                'text_content' => $this->getBudgetRejectedTextTemplate(),
                'is_active'    => true,
                'sort_order'   => 3,
            ],
            [
                'name'         => 'Fatura Gerada',
                'slug'         => 'invoice-generated',
                'category'     => 'transactional',
                'subject'      => 'Fatura #{{invoice_number}} Gerada',
                'html_content' => $this->getInvoiceGeneratedTemplate(),
                'text_content' => $this->getInvoiceGeneratedTextTemplate(),
                'is_active'    => true,
                'sort_order'   => 4,
            ],
            [
                'name'         => 'Pagamento Confirmado',
                'slug'         => 'payment-confirmed',
                'category'     => 'notification',
                'subject'      => 'Pagamento da Fatura #{{invoice_number}} Confirmado',
                'html_content' => $this->getPaymentConfirmedTemplate(),
                'text_content' => $this->getPaymentConfirmedTextTemplate(),
                'is_active'    => true,
                'sort_order'   => 5,
            ],
            [
                'name'         => 'Lembrete de Vencimento',
                'slug'         => 'payment-due-reminder',
                'category'     => 'notification',
                'subject'      => 'Lembrete: Fatura #{{invoice_number}} Vence em {{invoice_due_date}}',
                'html_content' => $this->getPaymentDueReminderTemplate(),
                'text_content' => $this->getPaymentDueReminderTextTemplate(),
                'is_active'    => true,
                'sort_order'   => 6,
            ],
            [
                'name'         => 'Boas-vindas',
                'slug'         => 'welcome',
                'category'     => 'promotional',
                'subject'      => 'Bem-vindo à {{company_name}}!',
                'html_content' => $this->getWelcomeTemplate(),
                'text_content' => $this->getWelcomeTextTemplate(),
                'is_active'    => true,
                'sort_order'   => 7,
            ],
            [
                'name'         => 'Recuperação de Senha',
                'slug'         => 'password-reset',
                'category'     => 'system',
                'subject'      => 'Recuperação de Senha - {{company_name}}',
                'html_content' => $this->getPasswordResetTemplate(),
                'text_content' => $this->getPasswordResetTextTemplate(),
                'is_active'    => true,
                'sort_order'   => 8,
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmação de Orçamento</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #f8f9fa; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
        .content { background: white; padding: 30px; border: 1px solid #dee2e6; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #6c757d; }
        .button { display: inline-block; padding: 12px 24px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        .highlight { background: #e9ecef; padding: 15px; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{company_name}}</h1>
            <p>Confirmação de Orçamento</p>
        </div>

        <div class="content">
            <h2>Olá {{customer_name}},</h2>

            <p>Recebemos seu orçamento e estamos analisando sua solicitação. Seguem os detalhes:</p>

            <div class="highlight">
                <strong>Orçamento:</strong> #{{budget_number}}<br>
                <strong>Valor:</strong> R$ {{budget_value}}<br>
                <strong>Prazo de Validade:</strong> {{budget_deadline}}
            </div>

            <p>{{budget_items}}</p>

            <p>Entraremos em contato em breve com mais informações ou ajustes necessários.</p>

            <p>Atenciosamente,<br>Equipe {{company_name}}</p>

            <div style="text-align: center;">
                <a href="{{budget_link}}" class="button">Visualizar Orçamento</a>
            </div>
        </div>

        <div class="footer">
            <p>{{company_name}} | {{company_email}} | {{company_phone}}</p>
            <p>Este é um email automático, por favor não responda diretamente.</p>
        </div>
    </div>
</body>
</html>';
    }

    /**
     * Outros métodos de template serão implementados...
     */
    private function getBudgetConfirmationTextTemplate(): string
    {
        return "Olá {{customer_name}},\n\nRecebemos seu orçamento #{{budget_number}} no valor de R$ {{budget_value}}.\n\n{{budget_items}}\n\nEntraremos em contato em breve.\n\nAtenciosamente,\nEquipe {{company_name}}\n\n{{company_email}} | {{company_phone}}";
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
    <p><strong>Próximos passos:</strong> Entraremos em contato para agendar o início dos trabalhos.</p>
</body>
</html>';
    }

    private function getBudgetApprovedTextTemplate(): string
    {
        return "Olá {{customer_name}},\n\nSeu orçamento #{{budget_number}} foi aprovado!\n\nValor: R$ {{budget_value}}\n\nPróximos passos: Entraremos em contato para agendar o início dos trabalhos.\n\nAtenciosamente,\nEquipe {{company_name}}";
    }

    private function getBudgetRejectedTemplate(): string
    {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Orçamento Rejeitado</title>
</head>
<body>
    <h1>Orçamento #{{budget_number}} Rejeitado</h1>
    <p>Olá {{customer_name}}, infelizmente seu orçamento foi rejeitado.</p>
    <p>Caso tenha dúvidas, entre em contato conosco.</p>
</body>
</html>';
    }

    private function getBudgetRejectedTextTemplate(): string
    {
        return "Olá {{customer_name}},\n\nInfelizmente seu orçamento #{{budget_number}} foi rejeitado.\n\nCaso tenha dúvidas, entre em contato conosco.\n\nAtenciosamente,\nEquipe {{company_name}}";
    }

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

    private function getInvoiceGeneratedTextTemplate(): string
    {
        return "Olá {{customer_name}},\n\nSua fatura #{{invoice_number}} foi gerada.\n\nValor: R$ {{invoice_amount}}\nVencimento: {{invoice_due_date}}\n\n{{invoice_link}}";
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
    <p>Obrigado pela preferência!</p>
</body>
</html>';
    }

    private function getPaymentConfirmedTextTemplate(): string
    {
        return "Olá {{customer_name}},\n\nConfirmamos o pagamento da fatura #{{invoice_number}} no valor de R$ {{invoice_amount}}.\n\nObrigado pela preferência!\n\nEquipe {{company_name}}";
    }

    private function getPaymentDueReminderTemplate(): string
    {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Lembrete de Vencimento</title>
</head>
<body>
    <h1>Lembrete de Vencimento</h1>
    <p>Olá {{customer_name}}, sua fatura #{{invoice_number}} vence em {{invoice_due_date}}.</p>
    <p><strong>Valor:</strong> R$ {{invoice_amount}}</p>
    <a href="{{invoice_link}}">Pagar Agora</a>
</body>
</html>';
    }

    private function getPaymentDueReminderTextTemplate(): string
    {
        return "Olá {{customer_name}},\n\nSua fatura #{{invoice_number}} no valor de R$ {{invoice_amount}} vence em {{invoice_due_date}}.\n\n{{invoice_link}}\n\nEquipe {{company_name}}";
    }

    private function getWelcomeTemplate(): string
    {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bem-vindo</title>
</head>
<body>
    <h1>Bem-vindo à {{company_name}}!</h1>
    <p>Olá {{customer_name}}, obrigado por se cadastrar!</p>
    <p>Estamos felizes em tê-lo conosco.</p>
</body>
</html>';
    }

    private function getWelcomeTextTemplate(): string
    {
        return "Olá {{customer_name}},\n\nBem-vindo à {{company_name}}!\n\nObrigado por se cadastrar. Estamos felizes em tê-lo conosco.\n\nEquipe {{company_name}}";
    }

    private function getPasswordResetTemplate(): string
    {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Recuperação de Senha</title>
</head>
<body>
    <h1>Recuperação de Senha</h1>
    <p>Olá {{user_name}}, você solicitou a recuperação de senha.</p>
    <p>Use o link abaixo para criar uma nova senha:</p>
    <a href="{{reset_link}}">Redefinir Senha</a>
    <p>Este link expira em 24 horas.</p>
</body>
</html>';
    }

    private function getPasswordResetTextTemplate(): string
    {
        return "Olá {{user_name}},\n\nVocê solicitou a recuperação de senha.\n\nUse o link abaixo para criar uma nova senha:\n{{reset_link}}\n\nEste link expira em 24 horas.\n\nEquipe {{company_name}}";
    }

}
