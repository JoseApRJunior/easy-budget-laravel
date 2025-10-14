<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\EmailSenderConfigurationRequest;
use App\Models\Tenant;
use App\Services\Infrastructure\EmailSenderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Controller para gerenciamento de configurações de remetente de e-mail.
 *
 * Funcionalidades principais:
 * - Configuração de remetentes personalizados por tenant
 * - Validação de remetentes e domínios
 * - Gerenciamento de configurações de segurança
 * - Sanitização de conteúdo de e-mail
 * - Monitoramento de uso do sistema
 *
 * Este controller integra com o sistema multi-tenant existente
 * e fornece interface web para configuração de e-mails.
 */
class EmailSenderController extends Controller
{
    /**
     * Serviço de remetentes de e-mail.
     */
    private EmailSenderService $emailSenderService;

    /**
     * Construtor: inicializa serviços.
     */
    public function __construct( EmailSenderService $emailSenderService )
    {
        $this->emailSenderService = $emailSenderService;
    }

    /**
     * Exibe configurações atuais de remetente.
     */
    public function index( Request $request )
    {
        try {
            $user     = Auth::user();
            $tenantId = $user?->tenant_id;

            // Obter configuração atual
            $configResult = $this->emailSenderService->getSenderConfiguration( $tenantId );

            if ( !$configResult->isSuccess() ) {
                return back()->withErrors( [
                    'config_error' => $configResult->getMessage()
                ] );
            }

            $config = $configResult->getData();

            // Obter estatísticas de uso
            $stats = $this->emailSenderService->getUsageStatistics();

            return view( 'emails.senders.index', [
                'config'        => $config,
                'stats'         => $stats,
                'can_customize' => config( 'email-senders.tenants.customizable' ),
            ] );

        } catch ( \Exception $e ) {
            Log::error( 'Erro ao exibir configurações de remetente', [
                'user_id'   => Auth::id(),
                'tenant_id' => $tenantId ?? null,
                'error'     => $e->getMessage(),
            ] );

            return back()->withErrors( [
                'system_error' => 'Erro interno ao carregar configurações.'
            ] );
        }
    }

    /**
     * Exibe formulário para configuração personalizada de remetente.
     */
    public function create( Request $request )
    {
        try {
            // Verificar se personalização está habilitada
            if ( !config( 'email-senders.tenants.customizable' ) ) {
                return back()->withErrors( [
                    'customization_disabled' => 'Personalização de remetentes desabilitada.'
                ] );
            }

            $user     = Auth::user();
            $tenantId = $user?->tenant_id;

            // Obter configuração atual para referência
            $currentConfig = $this->emailSenderService->getSenderConfiguration( $tenantId );

            return view( 'emails.senders.create', [
                'current_config'   => $currentConfig->getData(),
                'validation_rules' => config( 'email-senders.global.validation' ),
            ] );

        } catch ( \Exception $e ) {
            Log::error( 'Erro ao exibir formulário de configuração', [
                'user_id' => Auth::id(),
                'error'   => $e->getMessage(),
            ] );

            return back()->withErrors( [
                'system_error' => 'Erro interno ao carregar formulário.'
            ] );
        }
    }

    /**
     * Salva configuração personalizada de remetente.
     */
    public function store( EmailSenderConfigurationRequest $request )
    {
        try {
            $user     = Auth::user();
            $tenantId = $user?->tenant_id;

            if ( !$tenantId ) {
                return back()->withErrors( [
                    'tenant_error' => 'Usuário não associado a tenant válido.'
                ] );
            }

            // Extrair dados validados
            $data = $request->validated();

            // Configurar remetente personalizado
            $result = $this->emailSenderService->setTenantSenderConfiguration(
                $tenantId,
                $data[ 'email' ],
                $data[ 'name' ] ?? null,
                $data[ 'reply_to' ] ?? null
            );

            if ( !$result->isSuccess() ) {
                return back()->withErrors( [
                    'configuration_error' => $result->getMessage()
                ] )->withInput();
            }

            Log::info( 'Configuração de remetente personalizada salva', [
                'user_id'   => $user->id,
                'tenant_id' => $tenantId,
                'email'     => $data[ 'email' ],
            ] );

            return redirect()->route( 'email.senders.index' )
                ->with( 'success', 'Configuração de remetente salva com sucesso.' );

        } catch ( \Exception $e ) {
            Log::error( 'Erro ao salvar configuração de remetente', [
                'user_id'   => Auth::id(),
                'tenant_id' => $tenantId ?? null,
                'error'     => $e->getMessage(),
            ] );

            return back()->withErrors( [
                'system_error' => 'Erro interno ao salvar configuração.'
            ] )->withInput();
        }
    }

    /**
     * Remove configuração personalizada de remetente.
     */
    public function destroy( Request $request )
    {
        try {
            $user     = Auth::user();
            $tenantId = $user?->tenant_id;

            if ( !$tenantId ) {
                return response()->json( [
                    'success' => false,
                    'error'   => 'Usuário não associado a tenant válido.'
                ], 400 );
            }

            // Remover configuração personalizada
            $result = $this->emailSenderService->removeTenantSenderConfiguration( $tenantId );

            if ( !$result->isSuccess() ) {
                return response()->json( [
                    'success' => false,
                    'error'   => $result->getMessage()
                ], 400 );
            }

            Log::info( 'Configuração de remetente personalizada removida', [
                'user_id'   => $user->id,
                'tenant_id' => $tenantId,
            ] );

            return response()->json( [
                'success' => true,
                'message' => 'Configuração personalizada removida com sucesso.'
            ] );

        } catch ( \Exception $e ) {
            Log::error( 'Erro ao remover configuração de remetente', [
                'user_id'   => Auth::id(),
                'tenant_id' => $tenantId ?? null,
                'error'     => $e->getMessage(),
            ] );

            return response()->json( [
                'success' => false,
                'error'   => 'Erro interno ao remover configuração.'
            ], 500 );
        }
    }

    /**
     * Testa configuração de remetente atual.
     */
    public function test( Request $request )
    {
        try {
            $user     = Auth::user();
            $tenantId = $user?->tenant_id;

            // Obter configuração atual
            $configResult = $this->emailSenderService->getSenderConfiguration( $tenantId );

            if ( !$configResult->isSuccess() ) {
                return response()->json( [
                    'success' => false,
                    'error'   => 'Erro na configuração: ' . $configResult->getMessage()
                ], 400 );
            }

            $config    = $configResult->getData();
            $testEmail = $request->input( 'test_email', $user?->email );

            // Usar o serviço de e-mail existente para teste
            $mailerService = app( \App\Services\Infrastructure\MailerService::class);
            $testResult    = $mailerService->sendTestEmail( $testEmail );

            if ( $testResult->isSuccess() ) {
                return response()->json( [
                    'success'     => true,
                    'message'     => 'E-mail de teste enviado com sucesso.',
                    'config_used' => $config,
                ] );
            } else {
                return response()->json( [
                    'success'     => false,
                    'error'       => 'Erro no envio: ' . $testResult->getMessage(),
                    'config_used' => $config,
                ], 500 );
            }

        } catch ( \Exception $e ) {
            Log::error( 'Erro ao testar configuração de remetente', [
                'user_id'   => Auth::id(),
                'tenant_id' => $tenantId ?? null,
                'error'     => $e->getMessage(),
            ] );

            return response()->json( [
                'success' => false,
                'error'   => 'Erro interno no teste.'
            ], 500 );
        }
    }

    /**
     * Valida endereço de e-mail em tempo real.
     */
    public function validateEmail( Request $request )
    {
        try {
            $email    = $request->input( 'email' );
            $name     = $request->input( 'name' );
            $tenantId = Auth::user()?->tenant_id;

            if ( !$email ) {
                return response()->json( [
                    'success' => false,
                    'error'   => 'E-mail é obrigatório.'
                ], 400 );
            }

            $validation = $this->emailSenderService->validateSender( $email, $name, $tenantId );

            return response()->json( [
                'success' => $validation->isSuccess(),
                'message' => $validation->getMessage(),
                'data'    => $validation->getData(),
            ] );

        } catch ( \Exception $e ) {
            return response()->json( [
                'success' => false,
                'error'   => 'Erro na validação.'
            ], 500 );
        }
    }

    /**
     * Sanitiza conteúdo de e-mail.
     */
    public function sanitizeContent( Request $request )
    {
        try {
            $content     = $request->input( 'content' );
            $contentType = $request->input( 'content_type', 'html' );

            if ( !$content ) {
                return response()->json( [
                    'success' => false,
                    'error'   => 'Conteúdo é obrigatório.'
                ], 400 );
            }

            $sanitized = $this->emailSenderService->sanitizeEmailContent( $content, $contentType );

            return response()->json( [
                'success' => $sanitized->isSuccess(),
                'message' => $sanitized->getMessage(),
                'data'    => $sanitized->getData(),
            ] );

        } catch ( \Exception $e ) {
            return response()->json( [
                'success' => false,
                'error'   => 'Erro na sanitização.'
            ], 500 );
        }
    }

    /**
     * Exibe estatísticas detalhadas do sistema.
     */
    public function stats( Request $request )
    {
        try {
            $stats = $this->emailSenderService->getUsageStatistics();

            return view( 'emails.senders.stats', [
                'stats' => $stats,
            ] );

        } catch ( \Exception $e ) {
            Log::error( 'Erro ao exibir estatísticas', [
                'user_id' => Auth::id(),
                'error'   => $e->getMessage(),
            ] );

            return back()->withErrors( [
                'system_error' => 'Erro interno ao carregar estatísticas.'
            ] );
        }
    }

}
