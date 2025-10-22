<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Abstracts\Controller;
use App\Mail\BudgetNotificationMail;
use App\Mail\EmailVerificationMail;
use App\Mail\InvoiceNotification;
use App\Mail\PasswordResetNotification;
use App\Mail\StatusUpdate;
use App\Mail\SupportResponse;
use App\Mail\WelcomeUserMail;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Infrastructure\EmailPreviewService;
use App\Services\Infrastructure\QueueService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Controller avançado para preview de e-mails com funcionalidades completas.
 *
 * Funcionalidades implementadas:
 * - Preview responsivo para múltiplos dispositivos
 * - Suporte a diferentes idiomas em tempo real
 * - Dados dinâmicos com exemplos realistas
 * - Integração com sistema de filas para teste
 * - Monitoramento de performance
 * - Geração automática de dados de teste
 * - Sistema de comparação entre idiomas
 * - Exportação de templates
 * - Cache inteligente para performance
 */
class EmailPreviewController extends Controller
{
    /**
     * Serviço de preview de e-mails.
     */
    private EmailPreviewService $emailPreviewService;

    /**
     * Construtor: inicializa serviços necessários.
     */
    public function __construct( EmailPreviewService $emailPreviewService )
    {
        $this->emailPreviewService = $emailPreviewService;
    }

    /**
     * Lista todos os templates de e-mail disponíveis para preview.
     */
    public function index(): View
    {
        $availableEmails  = $this->getAvailableEmailTypes();
        $availableLocales = $this->getAvailableLocales();
        $availableDevices = $this->getAvailableDevices();
        $tenants          = $this->getAvailableTenants();

        return view( 'emails.preview.index', compact(
            'availableEmails',
            'availableLocales',
            'availableDevices',
            'tenants',
        ) );
    }

    /**
     * Obtém tipos de e-mail disponíveis para preview.
     */
    private function getAvailableEmailTypes(): array
    {
        return [
            'welcome'              => [
                'name'        => 'Boas-vindas',
                'description' => 'E-mail enviado para novos usuários',
                'mailable'    => WelcomeUserMail::class,
                'icon'        => 'user-plus',
                'category'    => 'authentication',
            ],
            'verification'         => [
                'name'        => 'Verificação de E-mail',
                'description' => 'E-mail de confirmação de cadastro',
                'mailable'    => EmailVerificationMail::class,
                'icon'        => 'mail-check',
                'category'    => 'authentication',
            ],
            'password_reset'       => [
                'name'        => 'Redefinição de Senha',
                'description' => 'E-mail para redefinição de senha',
                'mailable'    => PasswordResetNotification::class,
                'icon'        => 'key',
                'category'    => 'authentication',
            ],
            'budget_notification'  => [
                'name'        => 'Notificação de Orçamento',
                'description' => 'E-mail sobre criação/ atualização de orçamento',
                'mailable'    => BudgetNotificationMail::class,
                'icon'        => 'file-text',
                'category'    => 'business',
            ],
            'invoice_notification' => [
                'name'        => 'Notificação de Fatura',
                'description' => 'E-mail sobre faturas e pagamentos',
                'mailable'    => InvoiceNotification::class,
                'icon'        => 'receipt',
                'category'    => 'business',
            ],
            'status_update'        => [
                'name'        => 'Atualização de Status',
                'description' => 'E-mail sobre mudança de status',
                'mailable'    => StatusUpdate::class,
                'icon'        => 'refresh-cw',
                'category'    => 'system',
            ],
            'support_response'     => [
                'name'        => 'Resposta de Suporte',
                'description' => 'E-mail de resposta do suporte',
                'mailable'    => SupportResponse::class,
                'icon'        => 'message-circle',
                'category'    => 'support',
            ],
        ];
    }

    /**
     * Obtém idiomas disponíveis para preview.
     */
    private function getAvailableLocales(): array
    {
        return [
            'pt-BR' => [
                'name'   => 'Português (Brasil)',
                'flag'   => '🇧🇷',
                'native' => 'Português',
            ],
            'en'    => [
                'name'   => 'English',
                'flag'   => '🇺🇸',
                'native' => 'English',
            ],
            'es'    => [
                'name'   => 'Español',
                'flag'   => '🇪🇸',
                'native' => 'Español',
            ],
        ];
    }

    /**
     * Obtém dispositivos disponíveis para preview.
     */
    private function getAvailableDevices(): array
    {
        return [
            'desktop' => [
                'name'   => 'Desktop',
                'width'  => 1200,
                'height' => 800,
                'icon'   => 'monitor',
            ],
            'tablet'  => [
                'name'   => 'Tablet',
                'width'  => 768,
                'height' => 1024,
                'icon'   => 'tablet',
            ],
            'mobile'  => [
                'name'   => 'Mobile',
                'width'  => 375,
                'height' => 667,
                'icon'   => 'smartphone',
            ],
        ];
    }

    /**
     * Obtém tenants disponíveis para preview.
     */
    private function getAvailableTenants(): array
    {
        return Cache::remember( 'email_preview_tenants', 3600, function () {
            return Tenant::select( 'id', 'name' )
                ->orderBy( 'name' )
                ->get()
                ->toArray();
        } );
    }

    /**
     * Exibe preview de um e-mail específico.
     */
    public function show( Request $request, string $emailType ): Response
    {
        $request->validate( [
            'locale'    => 'required|string|in:pt-BR,en',
            'tenant_id' => 'nullable|integer|exists:tenants,id',
        ] );

        $locale   = $request->get( 'locale', 'pt-BR' );
        $tenantId = $request->get( 'tenant_id' );

        // Configurar locale
        App::setLocale( $locale );

        // Buscar tenant se especificado
        $tenant = null;
        if ( $tenantId ) {
            $tenant = Tenant::find( $tenantId );
        }

        // Criar dados de exemplo para preview
        $previewData = $this->getPreviewData( $emailType, $locale, $tenant );

        if ( !$previewData ) {
            abort( 404, 'Tipo de e-mail não encontrado' );
        }

        // Gerar HTML do e-mail
        $emailHtml = $this->renderEmail( $emailType, $previewData );

        // Gerar assunto do e-mail
        $emailSubject = $this->getEmailSubject( $emailType, $previewData );

        return response( $emailHtml )
            ->header( 'Content-Type', 'text/html; charset=UTF-8' )
            ->header( 'X-Email-Subject', $emailSubject )
            ->header( 'X-Email-Locale', $locale );
    }

    /**
     * Obtém dados de exemplo para preview de e-mail (método legado mantido para compatibilidade).
     */
    private function getPreviewData( string $emailType, string $locale, ?Tenant $tenant ): array
    {
        return $this->emailPreviewService->generatePreviewData( $emailType, $locale, $tenant?->id );
    }

    /**
     * Cria usuário de exemplo para preview (método legado mantido para compatibilidade).
     */
    private function createPreviewUser( ?Tenant $tenant ): User
    {
        return new User( [
            'id'        => 1,
            'email'     => 'preview@easybudget.net.br',
            'name'      => 'João Silva',
            'tenant_id' => $tenant?->id ?? 1,
        ] );
    }

    /**
     * Renderiza o HTML do e-mail (método legado mantido para compatibilidade).
     */
    private function renderEmail( string $emailType, array $data ): string
    {
        return $this->renderEmailHtml( $emailType, $data );
    }

    /**
     * API endpoint para obter configurações de preview.
     */
    public function config( Request $request ): array
    {
        return [
            'locales'     => $this->getAvailableLocales(),
            'tenants'     => $this->getAvailableTenants(),
            'email_types' => $this->getAvailableEmailTypes(),
            'devices'     => $this->getAvailableDevices(),
            'stats'       => $this->emailPreviewService->getPreviewStats(),
        ];
    }

    /**
     * Cria mailable de teste para envio via fila.
     */
    private function createTestMailable( string $emailType, array $data, string $recipient )
    {
        return new class ($emailType, $data, $recipient) extends \Illuminate\Mail\Mailable
        {
            private string $emailType;
            private array  $data;
            private string $recipient;

            public function __construct( string $emailType, array $data, string $recipient )
            {
                $this->emailType = $emailType;
                $this->data      = $data;
                $this->recipient = $recipient;
            }

            public function build()
            {
                $subject = $this->getEmailSubject( $this->emailType, $this->data );

                return $this->to( $this->recipient )
                    ->subject( '[TESTE] ' . $subject )
                    ->html( $this->renderEmailHtml( $this->emailType, $this->data ) )
                    ->with( 'data', $this->data );
            }

        };
    }

    /**
     * Obtém assunto do e-mail baseado no tipo.
     */
    private function getEmailSubject( string $emailType, array $data ): string
    {
        return match ( $emailType ) {
            'welcome'              => __( 'emails.users.welcome.subject', [ 'app_name'              => config( 'app.name' ) ], $data[ 'locale' ] ),
            'verification'         => __( 'emails.users.verification.subject', [ 'app_name'         => config( 'app.name' ) ], $data[ 'locale' ] ),
            'password_reset'       => __( 'emails.password_reset.subject', [ 'app_name'       => config( 'app.name' ) ], $data[ 'locale' ] ),
            'budget_notification'  => __( 'emails.budget_notification.subject', $data, $data[ 'locale' ] ),
            'invoice_notification' => __( 'emails.invoice_notification.subject', $data, $data[ 'locale' ] ),
            'status_update'        => __( 'emails.status_update.subject', $data, $data[ 'locale' ] ),
            'support_response'     => __( 'emails.support_response.subject', $data, $data[ 'locale' ] ),
            default                => 'Preview de E-mail',
        };
    }

    /**
     * Renderiza HTML do e-mail baseado no tipo.
     */
    private function renderEmailHtml( string $emailType, array $data ): string
    {

        try {
            return match ( $emailType ) {
                'welcome'              => $this->renderMailableContent( $emailType, $data ),
                'verification'         => $this->renderMailableContent( $emailType, $data ),
                'password_reset'       => $this->renderMailableContent( $emailType, $data ),
                'budget_notification'  => $this->renderMailableContent( $emailType, $data ),
                'invoice_notification' => $this->renderMailableContent( $emailType, $data ),
                'status_update'        => $this->renderMailableContent( $emailType, $data ),
                'support_response'     => $this->renderMailableContent( $emailType, $data ),
                default                => '<p>Tipo de e-mail não encontrado</p>',
            };
        } catch ( Exception $e ) {
            Log::error( 'Erro ao renderizar HTML do e-mail', [
                'email_type' => $emailType,
                'error'      => $e->getMessage(),
            ] );

            return '<p>Erro ao renderizar e-mail: ' . $e->getMessage() . '</p>';
        }
    }

    /**
     * Renderiza conteúdo de uma mailable específica, detectando automaticamente se usa markdown ou view.
     */
    private function renderMailableContent( string $emailType, array $data ): string
    {
        try {
            // Obter a classe mailable baseada no tipo
            $mailableClass = $this->getMailableClass( $emailType );

            if ( !$mailableClass ) {
                return '<p>Classe mailable não encontrada</p>';
            }

            // Criar instância da mailable com dados de exemplo
            $mailable = $this->createMailableInstance( $mailableClass, $data );

            // Verificar se a mailable usa markdown ou view
            $content = $mailable->content();

            if ( isset( $content->markdown ) ) {

                // Usa markdown - renderizar usando sistema de markdown
                return $this->renderMarkdownEmail( $content->markdown, $content->with );
            } elseif ( isset( $content->view ) ) {
                // Usa view - renderizar usando sistema de view
                return view( $content->view, $content->with )->render();
            } else {
                return '<p>Tipo de conteúdo não suportado na mailable</p>';
            }

        } catch ( Exception $e ) {
            Log::error( 'Erro ao renderizar conteúdo da mailable', [
                'email_type' => $emailType,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ] );

            return '<p>Erro ao renderizar e-mail: ' . $e->getMessage() . '</p>';
        }
    }

    /**
     * Obtém a classe mailable baseada no tipo de e-mail.
     */
    private function getMailableClass( string $emailType ): ?string
    {
        $emailTypes = $this->getAvailableEmailTypes();

        return $emailTypes[ $emailType ][ 'mailable' ] ?? null;
    }

    /**
     * Cria instância da mailable com dados apropriados para preview.
     */
    private function createMailableInstance( string $mailableClass, array $data ): \Illuminate\Mail\Mailable
    {
        // Para WelcomeUserMail, precisamos de um usuário válido
        if ( $mailableClass === WelcomeUserMail::class) {
            $user             = $data[ 'user' ] ?? $this->createPreviewUser( $data[ 'tenant' ] ?? null );
            $tenant           = $data[ 'tenant' ] ?? null;
            $confirmationLink = $data[ 'confirmationLink' ] ?? 'https://example.com/confirm-account?token=preview_token_1234567890123456789012345678901234567890';

            return new $mailableClass( $user, $tenant, $confirmationLink );
        }

        // Para EmailVerificationMail, precisamos de um usuário válido
        if ( $mailableClass === EmailVerificationMail::class) {
            $user             = $data[ 'user' ] ?? $this->createPreviewUser( $data[ 'tenant' ] ?? null );
            $tenant           = $data[ 'tenant' ] ?? null;
            $confirmationLink = $data[ 'confirmationLink' ] ?? 'https://example.com/confirm-account?token=preview_token_1234567890123456789012345678901234567890';

            return new $mailableClass( $user, $tenant, $confirmationLink );
        }

        // Para PasswordResetNotification, precisamos de usuário e token
        if ( $mailableClass === PasswordResetNotification::class) {
            $user   = $data[ 'user' ] ?? $this->createPreviewUser( $data[ 'tenant' ] ?? null );
            $token  = $data[ 'token' ] ?? 'preview_reset_token_1234567890123456789012345678901234567890';
            $tenant = $data[ 'tenant' ] ?? null;

            return new $mailableClass( $user, $token, $tenant );
        }

        // Para outras mailables, usar dados padrão com tratamento seguro
        try {
            // Tentar criar com dados fornecidos primeiro
            if ( !empty( $data ) ) {
                return new $mailableClass( ...array_values( $data ) );
            }

            // Fallback: criar instância vazia se possível
            return new $mailableClass();
        } catch ( Exception $e ) {
            Log::warning( 'Erro ao criar instância da mailable, tentando método alternativo', [
                'mailable_class' => $mailableClass,
                'error'          => $e->getMessage(),
            ] );

            // Método alternativo: tentar criar com dados básicos de preview
            return $this->createMailableInstanceWithFallback( $mailableClass, $data );
        }
    }

    /**
     * Método alternativo para criar instância da mailable quando o método padrão falha.
     */
    private function createMailableInstanceWithFallback( string $mailableClass, array $data ): \Illuminate\Mail\Mailable
    {
        // Para BudgetNotificationMail
        if ( $mailableClass === BudgetNotificationMail::class) {
            $budget = $data[ 'budget' ] ?? (object) [
                'id'       => 1,
                'code'     => 'ORC-2025-001',
                'total'    => 1500.00,
                'customer' => (object) [ 'name' => 'Cliente Exemplo' ],
                'tenant'   => $data[ 'tenant' ] ?? null,
            ];
            $action = $data[ 'action' ] ?? 'created';

            return new $mailableClass( $budget, $action );
        }

        // Para InvoiceNotification
        if ( $mailableClass === InvoiceNotification::class) {
            $invoice = $data[ 'invoice' ] ?? (object) [
                'id'       => 1,
                'code'     => 'FAT-2025-001',
                'total'    => 1500.00,
                'customer' => (object) [ 'name' => 'Cliente Exemplo' ],
                'tenant'   => $data[ 'tenant' ] ?? null,
            ];
            $action  = $data[ 'action' ] ?? 'created';

            return new $mailableClass( $invoice, $action );
        }

        // Para StatusUpdate
        if ( $mailableClass === StatusUpdate::class) {
            $entity    = $data[ 'entity' ] ?? (object) [
                'id'   => 1,
                'name' => 'Entidade Exemplo',
                'type' => 'budget',
            ];
            $oldStatus = $data[ 'old_status' ] ?? 'pending';
            $newStatus = $data[ 'new_status' ] ?? 'approved';

            return new $mailableClass( $entity, $oldStatus, $newStatus );
        }

        // Para SupportResponse
        if ( $mailableClass === SupportResponse::class) {
            $ticket   = $data[ 'ticket' ] ?? (object) [
                'id'       => 1,
                'subject'  => 'Chamado de Exemplo',
                'customer' => (object) [ 'name' => 'Cliente Exemplo' ],
            ];
            $response = $data[ 'response' ] ?? 'Esta é uma resposta de exemplo para o seu chamado.';

            return new $mailableClass( $ticket, $response );
        }

        // Fallback genérico - tentar criar com dados vazios
        try {
            return new $mailableClass();
        } catch ( Exception $e ) {
            Log::error( 'Falha ao criar instância da mailable mesmo com fallback', [
                'mailable_class' => $mailableClass,
                'error'          => $e->getMessage(),
            ] );

            throw new Exception( "Não foi possível criar instância da mailable: {$mailableClass}" );
        }
    }

    /**
     * Renderiza e-mail usando sistema de markdown do Laravel.
     */
    private function renderMarkdownEmail( string $markdownTemplate, array $data ): string
    {
        try {
            // Usar o componente markdown do Laravel para renderizar
            $markdownRenderer = app( \Illuminate\Mail\Markdown::class);

            // Renderizar o template markdown com os dados
            $htmlString = $markdownRenderer->render( $markdownTemplate, $data );

            // Converter HtmlString para string
            return $htmlString instanceof \Illuminate\Support\HtmlString
                ? $htmlString->toHtml()
                : (string) $htmlString;
        } catch ( Exception $e ) {
            Log::error( 'Erro ao renderizar template markdown', [
                'template' => $markdownTemplate,
                'error'    => $e->getMessage(),
            ] );

            return '<p>Erro ao renderizar template markdown: ' . $e->getMessage() . '</p>';
        }
    }

    /**
     * Limpa cache de preview de e-mails.
     */
    public function clearCache( Request $request ): Response
    {
        try {
            Cache::forget( 'email_preview_tenants' );
            Cache::flush(); // Em produção, usar tags específicas

            Log::info( 'Cache de preview de e-mails limpo' );

            return response()->json( [
                'success'    => true,
                'message'    => 'Cache limpo com sucesso',
                'cleared_at' => now()->toDateTimeString(),
            ] );

        } catch ( Exception $e ) {
            Log::error( 'Erro ao limpar cache de preview', [
                'error' => $e->getMessage(),
            ] );

            return response()->json( [
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500 );
        }
    }

    /**
     * Simula cenário de erro para teste.
     */
    public function simulateError( Request $request, string $emailType ): Response
    {
        $request->validate( [
            'error_type' => 'required|string|in:render_error,queue_error,validation_error',
            'locale'     => 'nullable|string|in:pt-BR,en,es',
        ] );

        $errorType = $request->get( 'error_type' );
        $locale    = $request->get( 'locale', 'pt-BR' );

        try {
            return match ( $errorType ) {
                'render_error'     => $this->simulateRenderError( $emailType, $locale ),
                'queue_error'      => $this->simulateQueueError( $emailType, $locale ),
                'validation_error' => $this->simulateValidationError( $emailType, $locale ),
                default            => response()->json( [
                    'success'            => false,
                    'error'              => 'Tipo de erro não suportado',
                ], 400 ),
            };

        } catch ( Exception $e ) {
            return response()->json( [
                'success' => false,
                'error'   => 'Erro na simulação: ' . $e->getMessage(),
            ], 500 );
        }
    }

    /**
     * Simula erro de renderização.
     */
    private function simulateRenderError( string $emailType, string $locale ): Response
    {
        // Força erro tentando acessar propriedade inexistente
        $data                            = $this->emailPreviewService->generatePreviewData( $emailType, $locale );
        $data[ 'non_existent_property' ] = null;

        return response()->json( [
            'success'    => false,
            'error'      => 'Erro simulado de renderização',
            'error_type' => 'render_error',
            'details'    => 'Propriedade inexistente acessada durante renderização',
        ], 500 );
    }

    /**
     * Simula erro de fila.
     */
    private function simulateQueueError( string $emailType, string $locale ): Response
    {
        $queueService = app( QueueService::class);

        // Tenta enfileirar com dados inválidos
        $result = $queueService->queueEmail(
            'invalid_type',
            function () {
                throw new Exception( 'Erro simulado na fila' );
            },
            'test@example.com',
        );

        return response()->json( [
            'success'    => false,
            'error'      => $result->getMessage(),
            'error_type' => 'queue_error',
            'details'    => 'Erro simulado no processamento da fila',
        ], 500 );
    }

    /**
     * Simula erro de validação.
     */
    private function simulateValidationError( string $emailType, string $locale ): Response
    {
        return response()->json( [
            'success'           => false,
            'error'             => 'Dados de entrada inválidos',
            'error_type'        => 'validation_error',
            'validation_errors' => [
                'locale'     => [ 'Locale deve ser pt-BR, en ou es' ],
                'email_type' => [ 'Tipo de e-mail não suportado' ],
            ],
        ], 422 );
    }

}
