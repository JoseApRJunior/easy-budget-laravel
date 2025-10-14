<?php

declare(strict_types=1);

namespace App\Services\Infrastructure;

use App\Enums\OperationStatus;
use App\Mail\BudgetNotificationMail;
use App\Mail\EmailVerificationMail;
use App\Mail\InvoiceNotification;
use App\Models\Budget;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\User;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Serviço avançado para testes automatizados de e-mail no sistema Easy Budget.
 *
 * Funcionalidades principais:
 * - Scripts de teste para todos os tipos de e-mail
 * - Validação automática de conteúdo e estrutura
 * - Testes de integração com filas
 * - Relatórios de teste automatizados
 * - Monitoramento de e-mails enviados
 * - Logs detalhados de teste
 *
 * Este service integra com Mailtrap para desenvolvimento e permite
 * testes abrangentes de todas as funcionalidades de e-mail.
 */
class EmailTestService
{
    /**
     * Serviço de provedores de e-mail.
     */
    private EmailProviderService $providerService;

    /**
     * Cache key para resultados de teste.
     */
    private string $cacheKey = 'email_test_results';

    /**
     * TTL do cache em minutos.
     */
    private int $cacheTtl = 30;

    /**
     * Tipos de teste disponíveis.
     */
    private array $testTypes = [
        'connectivity'         => [
            'name'        => 'Teste de Conectividade',
            'description' => 'Testa conexão com o provedor de e-mail',
        ],
        'verification'         => [
            'name'        => 'E-mail de Verificação',
            'description' => 'Testa envio de e-mail de verificação de conta',
        ],
        'budget_notification'  => [
            'name'        => 'Notificação de Orçamento',
            'description' => 'Testa envio de notificação de orçamento',
        ],
        'invoice_notification' => [
            'name'        => 'Notificação de Fatura',
            'description' => 'Testa envio de notificação de fatura',
        ],
        'template_rendering'   => [
            'name'        => 'Renderização de Templates',
            'description' => 'Testa renderização correta de templates de e-mail',
        ],
        'queue_integration'    => [
            'name'        => 'Integração com Filas',
            'description' => 'Testa processamento assíncrono de e-mails',
        ],
        'full_workflow'        => [
            'name'        => 'Workflow Completo',
            'description' => 'Executa todos os testes em sequência',
        ],
    ];

    /**
     * Construtor: inicializa serviços necessários.
     */
    public function __construct( EmailProviderService $providerService )
    {
        $this->providerService = $providerService;
    }

    /**
     * Executa teste específico de e-mail.
     */
    public function runTest( string $testType, array $options = [] ): ServiceResult
    {
        try {
            if ( !isset( $this->testTypes[ $testType ] ) ) {
                return ServiceResult::error(
                    OperationStatus::INVALID_DATA,
                    "Tipo de teste '{$testType}' não é suportado.",
                );
            }

            Log::info( 'Iniciando teste de e-mail', [
                'test_type' => $testType,
                'options'   => $options,
            ] );

            $result = match ( $testType ) {
                'connectivity'         => $this->testConnectivity( $options ),
                'verification'         => $this->testEmailVerification( $options ),
                'budget_notification'  => $this->testBudgetNotification( $options ),
                'invoice_notification' => $this->testInvoiceNotification( $options ),
                'template_rendering'   => $this->testTemplateRendering( $options ),
                'queue_integration'    => $this->testQueueIntegration( $options ),
                'full_workflow'        => $this->runFullWorkflow( $options ),
                default                => ServiceResult::error(
                    OperationStatus::ERROR,
                    "Tipo de teste '{$testType}' não implementado.",
                ),
            };

            // Cache resultado do teste
            $this->cacheTestResult( $testType, $result );

            Log::info( 'Teste de e-mail concluído', [
                'test_type'  => $testType,
                'is_success' => $result->isSuccess(),
                'message'    => $result->getMessage(),
            ] );

            return $result;

        } catch ( Exception $e ) {
            Log::error( 'Erro ao executar teste de e-mail', [
                'test_type' => $testType,
                'error'     => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ] );

            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao executar teste: ' . $e->getMessage()
            );
        }
    }

    /**
     * Testa conectividade com o provedor de e-mail.
     */
    private function testConnectivity( array $options = [] ): ServiceResult
    {
        $provider = $options[ 'provider' ] ?? null;

        if ( $provider ) {
            return $this->providerService->testProvider( $provider );
        }

        // Testa provedor atual se nenhum especificado
        $currentProvider = $this->providerService->getCurrentProvider();

        if ( isset( $currentProvider[ 'error' ] ) ) {
            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro no provedor atual: ' . $currentProvider[ 'error' ]
            );
        }

        return $this->providerService->testProvider( $currentProvider[ 'provider' ] );
    }

    /**
     * Testa envio de e-mail de verificação.
     */
    private function testEmailVerification( array $options = [] ): ServiceResult
    {
        try {
            // Criar usuário de teste se necessário
            $user = $this->getOrCreateTestUser( $options );

            // Dados de teste
            $verificationToken = 'test_verification_token_' . time();
            $verificationUrl   = $options[ 'verification_url' ] ?? config( 'app.url' ) . '/confirm-account?token=' . $verificationToken;

            // Criar mailable para teste
            $mailable = new EmailVerificationMail(
                $user,
                $verificationToken,
                $verificationUrl,
                $user->tenant,
                [ 'company_name' => $user->tenant?->name ?? 'Easy Budget' ],
                'pt-BR',
            );

            // Validar estrutura do e-mail
            $validation = $this->validateEmailStructure( $mailable );
            if ( !$validation->isSuccess() ) {
                return $validation;
            }

            // Enviar e-mail de teste
            $recipientEmail = $options[ 'recipient_email' ] ?? 'test@example.com';

            Mail::to( $recipientEmail )->send( $mailable );

            return ServiceResult::success( [
                'test_type'          => 'verification',
                'recipient'          => $recipientEmail,
                'user_id'            => $user->id,
                'verification_token' => $verificationToken,
                'sent_at'            => now()->toDateTimeString(),
            ], 'E-mail de verificação enviado com sucesso' );

        } catch ( Exception $e ) {
            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao testar e-mail de verificação: ' . $e->getMessage()
            );
        }
    }

    /**
     * Testa envio de notificação de orçamento.
     */
    private function testBudgetNotification( array $options = [] ): ServiceResult
    {
        try {
            // Criar dados de teste
            $budget   = $this->getOrCreateTestBudget( $options );
            $customer = $budget->customer ?? $this->getOrCreateTestCustomer( $options );

            // Criar mailable para teste
            $mailable = new BudgetNotificationMail(
                $budget,
                $customer,
                'created',
                $budget->tenant ?? Tenant::first(),
                [ 'company_name' => $budget->tenant?->name ?? 'Easy Budget' ],
                config( 'app.url' ) . '/budgets/' . $budget->id,
                'Este é um orçamento de teste das funcionalidades de e-mail.',
                'pt-BR',
            );

            // Validar estrutura do e-mail
            $validation = $this->validateEmailStructure( $mailable );
            if ( !$validation->isSuccess() ) {
                return $validation;
            }

            // Enviar e-mail de teste
            $recipientEmail = $options[ 'recipient_email' ] ?? 'test@example.com';

            Mail::to( $recipientEmail )->send( $mailable );

            return ServiceResult::success( [
                'test_type'   => 'budget_notification',
                'recipient'   => $recipientEmail,
                'budget_id'   => $budget->id,
                'budget_code' => $budget->code,
                'customer_id' => $customer->id,
                'sent_at'     => now()->toDateTimeString(),
            ], 'Notificação de orçamento enviada com sucesso' );

        } catch ( Exception $e ) {
            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao testar notificação de orçamento: ' . $e->getMessage()
            );
        }
    }

    /**
     * Testa envio de notificação de fatura.
     */
    private function testInvoiceNotification( array $options = [] ): ServiceResult
    {
        try {
            // Criar dados de teste
            $invoice  = $this->getOrCreateTestInvoice( $options );
            $customer = $invoice->customer ?? $this->getOrCreateTestCustomer( $options );

            // Criar mailable para teste
            $mailable = new InvoiceNotification(
                $invoice,
                $customer,
                $invoice->tenant ?? Tenant::first(),
                [ 'company_name' => $invoice->tenant?->name ?? 'Easy Budget' ],
                config( 'app.url' ) . '/invoice/' . ( $invoice->public_hash ?? 'test-hash' ),
                'Esta é uma fatura de teste das funcionalidades de e-mail.',
                'pt-BR',
            );

            // Validar estrutura do e-mail
            $validation = $this->validateEmailStructure( $mailable );
            if ( !$validation->isSuccess() ) {
                return $validation;
            }

            // Enviar e-mail de teste
            $recipientEmail = $options[ 'recipient_email' ] ?? 'test@example.com';

            Mail::to( $recipientEmail )->send( $mailable );

            return ServiceResult::success( [
                'test_type'    => 'invoice_notification',
                'recipient'    => $recipientEmail,
                'invoice_id'   => $invoice->id,
                'invoice_code' => $invoice->code,
                'customer_id'  => $customer->id,
                'sent_at'      => now()->toDateTimeString(),
            ], 'Notificação de fatura enviada com sucesso' );

        } catch ( Exception $e ) {
            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao testar notificação de fatura: ' . $e->getMessage()
            );
        }
    }

    /**
     * Testa renderização de templates de e-mail.
     */
    private function testTemplateRendering( array $options = [] ): ServiceResult
    {
        try {
            $templates = [
                'emails.email-verification',
                'emails.budget-notification',
                'emails.invoice-notification',
                'emails.welcome-user',
                'emails.password-reset',
            ];

            $results = [];
            $errors  = [];

            foreach ( $templates as $template ) {
                try {
                    // Tentar renderizar template com dados básicos
                    $testData = [
                        'app_name' => 'Easy Budget',
                        'user'     => (object) [ 'name' => 'Teste User' ],
                        'subject'  => 'Template de Teste',
                    ];

                    view( $template, $testData )->render();

                    $results[] = [
                        'template' => $template,
                        'status'   => 'success',
                        'message'  => 'Template renderizado com sucesso',
                    ];

                } catch ( Exception $e ) {
                    $errors[] = [
                        'template' => $template,
                        'error'    => $e->getMessage(),
                    ];
                }
            }

            $successCount = count( $results );
            $errorCount   = count( $errors );

            return ServiceResult::success( [
                'test_type'            => 'template_rendering',
                'total_templates'      => count( $templates ),
                'successful_templates' => $successCount,
                'failed_templates'     => $errorCount,
                'results'              => $results,
                'errors'               => $errors,
                'tested_at'            => now()->toDateTimeString(),
            ], "Teste de renderização concluído: {$successCount} sucessos, {$errorCount} erros" );

        } catch ( Exception $e ) {
            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao testar renderização de templates: ' . $e->getMessage()
            );
        }
    }

    /**
     * Testa integração com filas de e-mail.
     */
    private function testQueueIntegration( array $options = [] ): ServiceResult
    {
        try {
            // Criar usuário de teste
            $user = $this->getOrCreateTestUser( $options );

            // Dados de teste
            $verificationToken = 'test_queue_token_' . time();
            $verificationUrl   = config( 'app.url' ) . '/confirm-account?token=' . $verificationToken;

            // Criar mailable para teste
            $mailable = new EmailVerificationMail(
                $user,
                $verificationToken,
                $verificationUrl,
                $user->tenant,
                [ 'company_name' => $user->tenant?->name ?? 'Easy Budget' ],
                'pt-BR',
            );

            // Enfileirar e-mail
            $recipientEmail = $options[ 'recipient_email' ] ?? 'test@example.com';

            Mail::to( $recipientEmail )->queue( $mailable );

            // Verificar se foi enfileirado corretamente
            $queueStats = app( QueueService::class)->getAdvancedQueueStats();

            return ServiceResult::success( [
                'test_type'          => 'queue_integration',
                'recipient'          => $recipientEmail,
                'user_id'            => $user->id,
                'verification_token' => $verificationToken,
                'queued_at'          => now()->toDateTimeString(),
                'queue_stats'        => $queueStats,
            ], 'E-mail enfileirado com sucesso para teste de integração' );

        } catch ( Exception $e ) {
            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao testar integração com filas: ' . $e->getMessage()
            );
        }
    }

    /**
     * Executa workflow completo de testes.
     */
    private function runFullWorkflow( array $options = [] ): ServiceResult
    {
        try {
            $results = [];
            $errors  = [];

            // Executar todos os testes em sequência
            foreach ( array_keys( $this->testTypes ) as $testType ) {
                if ( $testType === 'full_workflow' ) {
                    continue; // Evitar recursão
                }

                try {
                    $result = $this->runTest( $testType, $options );

                    if ( $result->isSuccess() ) {
                        $results[] = [
                            'test_type' => $testType,
                            'status'    => 'success',
                            'message'   => $result->getMessage(),
                            'data'      => $result->getData(),
                        ];
                    } else {
                        $errors[] = [
                            'test_type' => $testType,
                            'status'    => 'error',
                            'message'   => $result->getMessage(),
                        ];
                    }

                } catch ( Exception $e ) {
                    $errors[] = [
                        'test_type' => $testType,
                        'status'    => 'exception',
                        'message'   => $e->getMessage(),
                    ];
                }
            }

            $successCount = count( $results );
            $errorCount   = count( $errors );

            return ServiceResult::success( [
                'test_type'        => 'full_workflow',
                'total_tests'      => count( $this->testTypes ) - 1, // Excluindo full_workflow
                'successful_tests' => $successCount,
                'failed_tests'     => $errorCount,
                'results'          => $results,
                'errors'           => $errors,
                'executed_at'      => now()->toDateTimeString(),
            ], "Workflow completo executado: {$successCount} sucessos, {$errorCount} erros" );

        } catch ( Exception $e ) {
            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao executar workflow completo: ' . $e->getMessage()
            );
        }
    }

    /**
     * Valida estrutura de um e-mail.
     */
    private function validateEmailStructure( $mailable ): ServiceResult
    {
        try {
            // Verificar se mailable tem os métodos necessários
            if ( !method_exists( $mailable, 'build' ) ) {
                return ServiceResult::error(
                    OperationStatus::INVALID_DATA,
                    'Mailable não possui método build',
                );
            }

            // Tentar construir o e-mail para validar estrutura
            $builtEmail = $mailable->build();

            return ServiceResult::success( [
                'mailable_class'   => get_class( $mailable ),
                'has_build_method' => true,
                'build_successful' => true,
                'validated_at'     => now()->toDateTimeString(),
            ], 'Estrutura do e-mail validada com sucesso' );

        } catch ( Exception $e ) {
            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro na validação da estrutura do e-mail: ' . $e->getMessage()
            );
        }
    }

    /**
     * Obtém ou cria usuário de teste.
     */
    private function getOrCreateTestUser( array $options = [] ): User
    {
        $tenantId = $options[ 'tenant_id' ] ?? 1;

        $user = User::where( 'email', 'test@example.com' )
            ->where( 'tenant_id', $tenantId )
            ->first();

        if ( !$user ) {
            $tenant = Tenant::find( $tenantId ) ?? Tenant::first();

            $user = User::factory()->create( [
                'email'     => 'test@example.com',
                'tenant_id' => $tenant?->id ?? 1,
                'name'      => 'Test User',
            ] );
        }

        return $user;
    }

    /**
     * Obtém ou cria cliente de teste.
     */
    private function getOrCreateTestCustomer( array $options = [] ): Customer
    {
        $tenantId = $options[ 'tenant_id' ] ?? 1;

        $customer = Customer::where( 'tenant_id', $tenantId )->first();

        if ( !$customer ) {
            $customer = Customer::factory()->create( [
                'tenant_id' => $tenantId,
            ] );
        }

        return $customer;
    }

    /**
     * Obtém ou cria orçamento de teste.
     */
    private function getOrCreateTestBudget( array $options = [] ): Budget
    {
        $tenantId = $options[ 'tenant_id' ] ?? 1;

        $budget = Budget::where( 'code', 'TEST-' . date( 'Ymd' ) )
            ->where( 'tenant_id', $tenantId )
            ->first();

        if ( !$budget ) {
            $customer = $this->getOrCreateTestCustomer( $options );

            $budget = Budget::factory()->create( [
                'tenant_id'   => $tenantId,
                'customer_id' => $customer->id,
                'code'        => 'TEST-' . time(),
                'total'       => 1500.00,
                'discount'    => 50.00,
            ] );
        }

        return $budget;
    }

    /**
     * Obtém ou cria fatura de teste.
     */
    private function getOrCreateTestInvoice( array $options = [] ): Invoice
    {
        $tenantId = $options[ 'tenant_id' ] ?? 1;

        $invoice = Invoice::where( 'code', 'TEST-INV-' . date( 'Ymd' ) )
            ->where( 'tenant_id', $tenantId )
            ->first();

        if ( !$invoice ) {
            $customer = $this->getOrCreateTestCustomer( $options );

            $invoice = Invoice::factory()->create( [
                'tenant_id'   => $tenantId,
                'customer_id' => $customer->id,
                'code'        => 'TEST-INV-' . time(),
                'total'       => 1200.00,
                'subtotal'    => 1000.00,
                'discount'    => 100.00,
            ] );
        }

        return $invoice;
    }

    /**
     * Cache resultado de teste.
     */
    private function cacheTestResult( string $testType, ServiceResult $result ): void
    {
        $cacheData = [
            'test_type'  => $testType,
            'is_success' => $result->isSuccess(),
            'message'    => $result->getMessage(),
            'data'       => $result->getData(),
            'cached_at'  => now()->toDateTimeString(),
        ];

        Cache::put( $this->cacheKey . '_' . $testType, $cacheData, $this->cacheTtl );
    }

    /**
     * Obtém tipos de teste disponíveis.
     */
    public function getAvailableTestTypes(): array
    {
        return $this->testTypes;
    }

    /**
     * Obtém resultado em cache de um teste específico.
     */
    public function getCachedTestResult( string $testType ): ?array
    {
        return Cache::get( $this->cacheKey . '_' . $testType );
    }

    /**
     * Limpa cache de resultados de teste.
     */
    public function clearTestCache(): ServiceResult
    {
        try {
            $cacheKeys = [
                $this->cacheKey . '_connectivity',
                $this->cacheKey . '_verification',
                $this->cacheKey . '_budget_notification',
                $this->cacheKey . '_invoice_notification',
                $this->cacheKey . '_template_rendering',
                $this->cacheKey . '_queue_integration',
                $this->cacheKey . '_full_workflow',
            ];

            foreach ( $cacheKeys as $key ) {
                Cache::forget( $key );
            }

            Log::info( 'Cache de testes de e-mail limpo' );

            return ServiceResult::success(
                null,
                'Cache de testes limpo com sucesso',
            );

        } catch ( Exception $e ) {
            Log::error( 'Erro ao limpar cache de testes', [
                'error' => $e->getMessage(),
            ] );

            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao limpar cache: ' . $e->getMessage()
            );
        }
    }

    /**
     * Gera relatório completo de testes.
     */
    public function generateTestReport(): ServiceResult
    {
        try {
            $report = [
                'generated_at'  => now()->toDateTimeString(),
                'environment'   => app()->environment(),
                'provider_info' => $this->providerService->getCurrentProvider(),
                'test_results'  => [],
                'summary'       => [
                    'total_tests'      => 0,
                    'successful_tests' => 0,
                    'failed_tests'     => 0,
                ],
            ];

            foreach ( array_keys( $this->testTypes ) as $testType ) {
                if ( $testType === 'full_workflow' ) {
                    continue; // Pular teste composto
                }

                $cachedResult = $this->getCachedTestResult( $testType );

                if ( $cachedResult ) {
                    $report[ 'test_results' ][] = [
                        'test_type'   => $testType,
                        'name'        => $this->testTypes[ $testType ][ 'name' ],
                        'description' => $this->testTypes[ $testType ][ 'description' ],
                        'is_success'  => $cachedResult[ 'is_success' ],
                        'message'     => $cachedResult[ 'message' ],
                        'cached_at'   => $cachedResult[ 'cached_at' ],
                    ];

                    $report[ 'summary' ][ 'total_tests' ]++;
                    if ( $cachedResult[ 'is_success' ] ) {
                        $report[ 'summary' ][ 'successful_tests' ]++;
                    } else {
                        $report[ 'summary' ][ 'failed_tests' ]++;
                    }
                }
            }

            return ServiceResult::success( $report, 'Relatório de testes gerado com sucesso' );

        } catch ( Exception $e ) {
            Log::error( 'Erro ao gerar relatório de testes', [
                'error' => $e->getMessage(),
            ] );

            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao gerar relatório: ' . $e->getMessage()
            );
        }
    }

}
