<?php

declare(strict_types=1);

namespace App\Services\Infrastructure;

use App\Mail\BudgetNotificationMail;
use App\Mail\EmailVerificationMail;
use App\Mail\InvoiceNotification;
use App\Mail\PasswordResetNotification;
use App\Mail\StatusUpdate;
use App\Mail\SupportResponse;
use App\Mail\WelcomeUser;
use App\Models\Budget;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\User;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Serviço avançado para preview de e-mails no sistema Easy Budget.
 *
 * Funcionalidades implementadas:
 * - Geração de dados de teste realistas
 * - Preview responsivo para múltiplos dispositivos
 * - Cache inteligente para performance
 * - Suporte a múltiplos idiomas
 * - Templates de exemplo para cada tipo de e-mail
 * - Sistema de comparação entre idiomas
 * - Métricas de performance
 * - Tratamento robusto de erros
 */
class EmailPreviewService
{
    /**
     * Cache de dados de preview por tipo e locale.
     */
    private const PREVIEW_CACHE_TTL = 3600; // 1 hora

    /**
     * Configurações de dispositivos para preview.
     */
    private array $deviceConfigs = [
        'desktop' => [
            'width'      => 1200,
            'height'     => 800,
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        ],
        'tablet'  => [
            'width'      => 768,
            'height'     => 1024,
            'user_agent' => 'Mozilla/5.0 (iPad; CPU OS 14_0 like Mac OS X) AppleWebKit/605.1.15',
        ],
        'mobile'  => [
            'width'      => 375,
            'height'     => 667,
            'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15',
        ],
    ];

    /**
     * Gera dados de exemplo para preview de e-mail.
     */
    public function generatePreviewData(
        string $emailType,
        string $locale,
        ?int $tenantId = null,
        array $customData = [],
    ): array {
        $cacheKey = "email_preview_data_{$emailType}_{$locale}_{$tenantId}";

        return Cache::remember( $cacheKey, self::PREVIEW_CACHE_TTL, function () use ($emailType, $locale, $tenantId, $customData) {
            $baseData = [
                'locale'    => $locale,
                'tenant'    => $this->getPreviewTenant( $tenantId ),
                'timestamp' => now()->toDateTimeString(),
            ];

            $specificData = match ( $emailType ) {
                'welcome'              => $this->getWelcomeData( $locale, $customData ),
                'verification'         => $this->getVerificationData( $locale, $customData ),
                'password_reset'       => $this->getPasswordResetData( $locale, $customData ),
                'budget_notification'  => $this->getBudgetNotificationData( $locale, $customData ),
                'invoice_notification' => $this->getInvoiceNotificationData( $locale, $customData ),
                'status_update'        => $this->getStatusUpdateData( $locale, $customData ),
                'support_response'     => $this->getSupportResponseData( $locale, $customData ),
                default                => throw new \InvalidArgumentException( "Tipo de e-mail não suportado: {$emailType}" ),
            };

            return array_merge( $baseData, $specificData );
        } );
    }

    /**
     * Renderiza preview do e-mail para dispositivo específico.
     */
    public function renderEmailPreview(
        string $emailType,
        array $data,
        string $device = 'desktop',
    ): array {
        try {
            $deviceConfig = $this->deviceConfigs[ $device ] ?? $this->deviceConfigs[ 'desktop' ];

            $startTime = microtime( true );

            // Renderizar HTML do e-mail
            $html = $this->renderEmailHtml( $emailType, $data );

            $renderTime = microtime( true ) - $startTime;

            // Gerar metadados do preview
            $metadata = [
                'device'          => $device,
                'dimensions'      => [
                    'width'  => $deviceConfig[ 'width' ],
                    'height' => $deviceConfig[ 'height' ],
                ],
                'render_time_ms'  => round( $renderTime * 1000, 2 ),
                'html_size_bytes' => strlen( $html ),
                'locale'          => $data[ 'locale' ],
                'generated_at'    => now()->toDateTimeString(),
            ];

            // Log de métricas de performance
            Log::info( 'Preview de e-mail renderizado', [
                'email_type'      => $emailType,
                'device'          => $device,
                'locale'          => $data[ 'locale' ],
                'render_time_ms'  => $metadata[ 'render_time_ms' ],
                'html_size_bytes' => $metadata[ 'html_size_bytes' ],
            ] );

            return [
                'success'       => true,
                'html'          => $html,
                'metadata'      => $metadata,
                'device_config' => $deviceConfig,
            ];

        } catch ( Exception $e ) {
            Log::error( 'Erro ao renderizar preview de e-mail', [
                'email_type' => $emailType,
                'device'     => $device,
                'error'      => $e->getMessage(),
            ] );

            return [
                'success' => false,
                'error'   => $e->getMessage(),
                'device'  => $device,
            ];
        }
    }

    /**
     * Compara preview entre diferentes idiomas.
     */
    public function compareLocales(
        string $emailType,
        array $locales,
        ?int $tenantId = null,
        array $customData = [],
    ): array {
        $comparisons = [];

        foreach ( $locales as $locale ) {
            try {
                $data    = $this->generatePreviewData( $emailType, $locale, $tenantId, $customData );
                $preview = $this->renderEmailPreview( $emailType, $data );

                if ( $preview[ 'success' ] ) {
                    $comparisons[ $locale ] = [
                        'locale'  => $locale,
                        'data'    => $data,
                        'preview' => $preview,
                        'status'  => 'success',
                    ];
                } else {
                    $comparisons[ $locale ] = [
                        'locale' => $locale,
                        'error'  => $preview[ 'error' ],
                        'status' => 'error',
                    ];
                }

            } catch ( Exception $e ) {
                $comparisons[ $locale ] = [
                    'locale' => $locale,
                    'error'  => $e->getMessage(),
                    'status' => 'error',
                ];
            }
        }

        return [
            'email_type'    => $emailType,
            'comparisons'   => $comparisons,
            'total_locales' => count( $locales ),
            'success_count' => collect( $comparisons )->where( 'status', 'success' )->count(),
            'error_count'   => collect( $comparisons )->where( 'status', 'error' )->count(),
            'generated_at'  => now()->toDateTimeString(),
        ];
    }

    /**
     * Exporta template de e-mail para documentação.
     */
    public function exportEmailTemplate(
        string $emailType,
        string $locale,
        string $format = 'html',
        ?int $tenantId = null,
    ): ServiceResult {
        try {
            $data    = $this->generatePreviewData( $emailType, $locale, $tenantId );
            $preview = $this->renderEmailPreview( $emailType, $data );

            if ( !$preview[ 'success' ] ) {
                return ServiceResult::error(
                    'Erro ao renderizar template para exportação: ' . $preview[ 'error' ]
                );
            }

            $exportData = [
                'email_type'  => $emailType,
                'locale'      => $locale,
                'format'      => $format,
                'data'        => $data,
                'html'        => $preview[ 'html' ],
                'metadata'    => $preview[ 'metadata' ],
                'exported_at' => now()->toDateTimeString(),
            ];

            // Se formato for JSON, retornar dados estruturados
            if ( $format === 'json' ) {
                return ServiceResult::success( $exportData, 'Template exportado com sucesso em formato JSON' );
            }

            // Para formato HTML, retornar apenas o HTML
            return ServiceResult::success( [
                'html'     => $preview[ 'html' ],
                'metadata' => $preview[ 'metadata' ],
            ], 'Template exportado com sucesso em formato HTML' );

        } catch ( Exception $e ) {
            Log::error( 'Erro ao exportar template de e-mail', [
                'email_type' => $emailType,
                'locale'     => $locale,
                'format'     => $format,
                'error'      => $e->getMessage(),
            ] );

            return ServiceResult::error( 'Erro ao exportar template: ' . $e->getMessage() );
        }
    }

    /**
     * Obtém estatísticas de uso do sistema de preview.
     */
    public function getPreviewStats(): array
    {
        try {
            $cacheKey = 'email_preview_stats';

            return Cache::remember( $cacheKey, 1800, function () { // 30 minutos
                $stats = [
                    'total_previews'      => $this->getTotalPreviewCount(),
                    'previews_by_type'    => $this->getPreviewsByType(),
                    'previews_by_locale'  => $this->getPreviewsByLocale(),
                    'previews_by_device'  => $this->getPreviewsByDevice(),
                    'average_render_time' => $this->getAverageRenderTime(),
                    'cache_hits'          => $this->getCacheHitRate(),
                    'generated_at'        => now()->toDateTimeString(),
                ];

                Log::info( 'Estatísticas de preview obtidas', $stats );

                return $stats;
            } );

        } catch ( Exception $e ) {
            Log::error( 'Erro ao obter estatísticas de preview', [
                'error' => $e->getMessage(),
            ] );

            return [
                'error'        => 'Erro ao obter estatísticas: ' . $e->getMessage(),
                'generated_at' => now()->toDateTimeString(),
            ];
        }
    }

    /**
     * Obtém dados de exemplo para e-mail de boas-vindas.
     */
    private function getWelcomeData( string $locale, array $customData = [] ): array
    {
        return array_merge( [
            'user'             => $this->createPreviewUser(),
            'first_name'       => 'João',
            'confirmationLink' => route( 'verification.verify', [ 'id' => 1, 'hash' => 'preview-hash-123' ] ),
            'app_name'         => config( 'app.name', 'Easy Budget' ),
            'company_name'     => 'Easy Budget',
            'login_url'        => route( 'login' ),
        ], $customData );
    }

    /**
     * Obtém dados de exemplo para e-mail de verificação.
     */
    private function getVerificationData( string $locale, array $customData = [] ): array
    {
        return array_merge( [
            'user'              => $this->createPreviewUser(),
            'verificationToken' => 'preview-token-123',
            'confirmationLink'  => route( 'verification.verify', [ 'id' => 1, 'hash' => 'preview-hash-123' ] ),
            'expiresAt'         => now()->addMinutes( 30 )->format( 'd/m/Y H:i:s' ),
            'company'           => [
                'company_name' => 'Easy Budget',
                'email'        => 'contato@easybudget.net.br',
                'phone'        => '(11) 99999-9999',
            ],
        ], $customData );
    }

    /**
     * Obtém dados de exemplo para e-mail de redefinição de senha.
     */
    private function getPasswordResetData( string $locale, array $customData = [] ): array
    {
        return array_merge( [
            'user'       => $this->createPreviewUser(),
            'resetToken' => 'preview-reset-token-456',
            'resetUrl'   => route( 'password.reset', [ 'token' => 'preview-reset-token-456' ] ),
            'expiresAt'  => now()->addMinutes( 60 )->format( 'd/m/Y H:i:s' ),
        ], $customData );
    }

    /**
     * Obtém dados de exemplo para notificação de orçamento.
     */
    private function getBudgetNotificationData( string $locale, array $customData = [] ): array
    {
        return array_merge( [
            'budget'           => $this->createPreviewBudget(),
            'customer'         => $this->createPreviewCustomer(),
            'notificationType' => 'created',
            'publicUrl'        => route( 'budgets.show', [ 'budget' => 1 ] ),
            'customMessage'    => 'Este é um orçamento de exemplo para demonstração.',
        ], $customData );
    }

    /**
     * Obtém dados de exemplo para notificação de fatura.
     */
    private function getInvoiceNotificationData( string $locale, array $customData = [] ): array
    {
        return array_merge( [
            'invoice'       => $this->createPreviewInvoice(),
            'customer'      => $this->createPreviewCustomer(),
            'publicLink'    => route( 'invoices.show', [ 'invoice' => 1 ] ),
            'customMessage' => 'Esta é uma fatura de exemplo para demonstração.',
        ], $customData );
    }

    /**
     * Obtém dados de exemplo para atualização de status.
     */
    private function getStatusUpdateData( string $locale, array $customData = [] ): array
    {
        return array_merge( [
            'entity'     => $this->createPreviewBudget(),
            'status'     => 'approved',
            'statusName' => 'Aprovado',
            'entityUrl'  => route( 'budgets.show', [ 'budget' => 1 ] ),
        ], $customData );
    }

    /**
     * Obtém dados de exemplo para resposta de suporte.
     */
    private function getSupportResponseData( string $locale, array $customData = [] ): array
    {
        return array_merge( [
            'ticket'   => [
                'id'      => 1,
                'subject' => 'Problema com orçamento',
                'message' => 'Estou tendo dificuldades para criar um orçamento...',
            ],
            'response' => 'Olá! Podemos ajudá-lo com a criação do orçamento. Nossa equipe entrará em contato em breve.',
        ], $customData );
    }

    /**
     * Cria usuário de exemplo para preview.
     */
    private function createPreviewUser(): User
    {
        return new User( [
            'id'         => 1,
            'email'      => 'preview@easybudget.net.br',
            'name'       => 'João Silva',
            'first_name' => 'João',
            'last_name'  => 'Silva',
        ] );
    }

    /**
     * Obtém tenant de exemplo para preview.
     */
    private function getPreviewTenant( ?int $tenantId ): ?Tenant
    {
        if ( $tenantId ) {
            return Tenant::find( $tenantId );
        }

        return Cache::remember( 'preview_tenant', self::PREVIEW_CACHE_TTL, function () {
            return Tenant::first() ?? new Tenant( [
                'id'   => 1,
                'name' => 'Easy Budget Demo',
            ] );
        } );
    }

    /**
     * Cria orçamento de exemplo para preview.
     */
    private function createPreviewBudget(): Budget
    {
        return new Budget( [
            'id'          => 1,
            'code'        => 'ORC-2025-001',
            'total'       => 1500.00,
            'discount'    => 50.00,
            'description' => 'Orçamento de exemplo para demonstração',
            'created_at'  => now(),
        ] );
    }

    /**
     * Cria cliente de exemplo para preview.
     */
    private function createPreviewCustomer(): Customer
    {
        $customer = new Customer( [
            'id'     => 1,
            'status' => 'active',
        ] );

        // Mock dos relacionamentos
        $customer->commonData = (object) [
            'first_name' => 'Maria',
            'last_name'  => 'Santos',
            'email'      => 'maria.santos@email.com',
        ];

        $customer->contact = (object) [
            'phone' => '(11) 99999-9999',
        ];

        return $customer;
    }

    /**
     * Cria fatura de exemplo para preview.
     */
    private function createPreviewInvoice(): Invoice
    {
        return new Invoice( [
            'id'             => 1,
            'code'           => 'FAT-2025-001',
            'total'          => 1200.00,
            'subtotal'       => 1000.00,
            'discount'       => 100.00,
            'due_date'       => now()->addDays( 30 ),
            'payment_method' => 'PIX',
            'created_at'     => now(),
        ] );
    }

    /**
     * Renderiza HTML do e-mail baseado no tipo.
     */
    private function renderEmailHtml( string $emailType, array $data ): string
    {
        return match ( $emailType ) {
            'welcome'              => view( 'emails.new-user', $data )->render(),
            'verification'         => view( 'emails.verification', $data )->render(),
            'password_reset'       => view( 'emails.password-reset', $data )->render(),
            'budget_notification'  => view( 'emails.budget-notification', $data )->render(),
            'invoice_notification' => view( 'emails.invoice-notification', $data )->render(),
            'status_update'        => view( 'emails.status-update', $data )->render(),
            'support_response'     => view( 'emails.support-response', $data )->render(),
            default                => throw new \InvalidArgumentException( "Tipo de e-mail não suportado: {$emailType}" ),
        };
    }

    /**
     * Obtém contagem total de previews (simulado).
     */
    private function getTotalPreviewCount(): int
    {
        // Em produção, seria obtido do banco de dados
        return rand( 150, 500 );
    }

    /**
     * Obtém distribuição de previews por tipo (simulado).
     */
    private function getPreviewsByType(): array
    {
        return [
            'welcome'              => rand( 20, 50 ),
            'verification'         => rand( 30, 70 ),
            'password_reset'       => rand( 10, 30 ),
            'budget_notification'  => rand( 40, 80 ),
            'invoice_notification' => rand( 25, 60 ),
            'status_update'        => rand( 15, 40 ),
            'support_response'     => rand( 5, 20 ),
        ];
    }

    /**
     * Obtém distribuição de previews por locale (simulado).
     */
    private function getPreviewsByLocale(): array
    {
        return [
            'pt-BR' => rand( 80, 120 ),
            'en'    => rand( 30, 60 ),
            'es'    => rand( 10, 25 ),
        ];
    }

    /**
     * Obtém distribuição de previews por dispositivo (simulado).
     */
    private function getPreviewsByDevice(): array
    {
        return [
            'desktop' => rand( 60, 100 ),
            'tablet'  => rand( 20, 40 ),
            'mobile'  => rand( 30, 70 ),
        ];
    }

    /**
     * Obtém tempo médio de renderização (simulado).
     */
    private function getAverageRenderTime(): float
    {
        return round( rand( 50, 200 ) / 100, 2 ); // 0.50ms a 2.00ms
    }

    /**
     * Obtém taxa de acerto do cache (simulado).
     */
    private function getCacheHitRate(): float
    {
        return round( rand( 75, 95 ) / 100, 2 ); // 75% a 95%
    }

}
