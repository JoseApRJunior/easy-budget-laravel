<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Abstracts\Controller;
use App\Services\Application\EmailVerificationService;
use App\Support\ServiceResult;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Controller personalizado para reenvio de e-mail de verificação.
 *
 * Este controller substitui o padrão do Laravel para usar nosso sistema personalizado
 * de verificação de e-mail, que oferece:
 * - Controle avançado de tokens com expiração de 30 minutos
 * - Remoção automática de tokens antigos
 * - Integração com sistema multi-tenant
 * - Tratamento robusto de erros com logging detalhado
 * - Uso de eventos para envio de e-mails
 * - Arquitetura Controller → Service → Repository → Model
 */
class EmailVerificationNotificationController extends Controller
{
    /**
     * Serviço de verificação de e-mail personalizado.
     */
    private EmailVerificationService $emailVerificationService;

    /**
     * Construtor: inicializa serviços necessários.
     */
    public function __construct( EmailVerificationService $emailVerificationService )
    {
        $this->emailVerificationService = $emailVerificationService;
    }

    /**
     * Envia nova notificação de verificação de e-mail usando nosso sistema personalizado.
     *
     * Este método substitui completamente o comportamento padrão do Laravel:
     * 1. Usa nosso EmailVerificationService para lógica de negócio
     * 2. Implementa validações específicas do nosso sistema
     * 3. Mantém integração com sistema multi-tenant
     * 4. Oferece tratamento robusto de erros
     * 5. Segue padrão de resposta consistente com o sistema
     */
    public function store( Request $request ): RedirectResponse
    {
        try {
            $user = $request->user();

            // Log detalhado da operação
            Log::info( 'Solicitação de reenvio de e-mail de verificação recebida', [
                'user_id'    => $user->id,
                'tenant_id'  => $user->tenant_id,
                'email'      => $user->email,
                'ip'         => $request->ip(),
                'user_agent' => $request->userAgent(),
            ] );

            // Verificar se usuário já está verificado
            if ( $user->hasVerifiedEmail() ) {
                Log::info( 'Tentativa de reenvio para usuário já verificado', [
                    'user_id'           => $user->id,
                    'tenant_id'         => $user->tenant_id,
                    'email'             => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                ] );

                return redirect()->intended( route( 'dashboard', absolute: false ) )
                    ->with( 'warning', 'Seu e-mail já foi verificado anteriormente.' );
            }

            // Verificar se usuário está ativo
            if ( $user->is_active ) {
                Log::warning( 'Tentativa de reenvio para usuário ativo', [
                    'user_id'   => $user->id,
                    'tenant_id' => $user->tenant_id,
                    'email'     => $user->email,
                ] );

                return back()->with( 'error', 'Usuário ativo. Entre em contato com o suporte.' );
            }

            // Usar nosso serviço personalizado para reenvio
            $result = $this->emailVerificationService->resendConfirmationEmail( $user );

            if ( $result->isSuccess() ) {
                Log::info( 'E-mail de verificação reenviado com sucesso', [
                    'user_id'   => $user->id,
                    'tenant_id' => $user->tenant_id,
                    'email'     => $user->email,
                ] );

                return back()->with( 'success', 'E-mail de verificação enviado com sucesso! Verifique sua caixa de entrada.' );
            }

            // Tratamento específico de diferentes tipos de erro
            $errorMessage = match ( $result->getStatus() ) {
                'CONFLICT'  => 'Este e-mail já foi verificado anteriormente.',
                'NOT_FOUND' => 'Usuário não encontrado. Entre em contato com o suporte.',
                default     => 'Erro interno ao enviar e-mail de verificação. Tente novamente.',
            };

            Log::warning( 'Falha no reenvio de e-mail de verificação', [
                'user_id'   => $user->id,
                'tenant_id' => $user->tenant_id,
                'email'     => $user->email,
                'status'    => $result->getStatus(),
                'message'   => $result->getMessage(),
            ] );

            return back()->with( 'error', $errorMessage );

        } catch ( \Exception $e ) {
            Log::error( 'Erro crítico no reenvio de e-mail de verificação', [
                'user_id'   => $request->user()->id ?? null,
                'tenant_id' => $request->user()->tenant_id ?? null,
                'email'     => $request->user()->email ?? null,
                'error'     => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ] );

            return back()->with( 'error', 'Erro interno do sistema. Tente novamente em alguns minutos.' );
        }
    }

}
