<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Models\User;
use App\Services\Infrastructure\EmailRateLimitService;
use App\Services\Infrastructure\EmailSenderService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware avançado para segurança de envio de e-mails.
 *
 * Funcionalidades principais:
 * - Validação de remetentes antes do envio
 * - Controle de acesso para operações de e-mail
 * - Rate limiting integrado
 * - Logging de segurança detalhado
 * - Sanitização automática de conteúdo
 * - Monitoramento de tentativas suspeitas
 *
 * Este middleware deve ser aplicado em rotas relacionadas
 * ao envio de e-mails para garantir segurança completa.
 */
class EmailSecurityMiddleware
{
    /**
     * Serviço de remetentes de e-mail.
     */
    private EmailSenderService $emailSenderService;

    /**
     * Serviço de rate limiting.
     */
    private EmailRateLimitService $rateLimitService;

    /**
     * Construtor: inicializa serviços.
     */
    public function __construct(
        EmailSenderService $emailSenderService,
        EmailRateLimitService $rateLimitService,
    ) {
        $this->emailSenderService = $emailSenderService;
        $this->rateLimitService = $rateLimitService;
    }

    /**
     * Trata requisição HTTP aplicando validações de segurança.
     *
     * @param  Request  $request  Requisição HTTP
     * @param  Closure  $next  Próximo middleware
     * @return Response Resposta HTTP
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // 1. Verificar autenticação
            $authCheck = $this->checkAuthentication($request);
            if (! $authCheck->isSuccess()) {
                return $this->createErrorResponse($authCheck->getMessage(), 401);
            }

            // 2. Verificar autorização
            $authzCheck = $this->checkAuthorization($request);
            if (! $authzCheck->isSuccess()) {
                return $this->createErrorResponse($authzCheck->getMessage(), 403);
            }

            // 3. Validar dados de e-mail na requisição
            $emailValidation = $this->validateEmailRequest($request);
            if (! $emailValidation->isSuccess()) {
                return $this->createErrorResponse($emailValidation->getMessage(), 400);
            }

            // 4. Verificar rate limiting
            $rateLimitCheck = $this->checkRateLimiting($request);
            if (! $rateLimitCheck->isSuccess()) {
                return $this->createErrorResponse($rateLimitCheck->getMessage(), 429);
            }

            // 5. Sanitizar conteúdo se necessário
            $sanitizedRequest = $this->sanitizeRequestContent($request);

            // 6. Log de segurança
            $this->logSecurityEvent('email_request_authorized', [
                'user_id' => Auth::id(),
                'tenant_id' => Auth::user()?->tenant_id,
                'request_method' => $request->method(),
                'request_path' => $request->path(),
                'ip_address' => $request->ip(),
            ]);

            // Continuar com a requisição
            return $next($sanitizedRequest);

        } catch (\Exception $e) {
            $this->logSecurityEvent('email_security_middleware_error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_path' => $request->path(),
                'user_id' => Auth::id(),
            ]);

            return $this->createErrorResponse('Erro interno de segurança', 500);
        }
    }

    /**
     * Verifica autenticação do usuário.
     */
    private function checkAuthentication(Request $request): \App\Support\ServiceResult
    {
        if (! Auth::check()) {
            return \App\Support\ServiceResult::error(
                \App\Enums\OperationStatus::UNAUTHORIZED,
                'Autenticação obrigatória para operações de e-mail.',
            );
        }

        return \App\Support\ServiceResult::success(true, 'Usuário autenticado.');
    }

    /**
     * Verifica autorização para operação de e-mail.
     */
    private function checkAuthorization(Request $request): \App\Support\ServiceResult
    {
        $user = Auth::user();

        // Verificar se usuário tem permissão para enviar e-mails
        if (! $this->userCanSendEmails($user)) {
            $this->logSecurityEvent('unauthorized_email_access', [
                'user_id' => $user->id,
                'email' => $user->email,
                'request_path' => $request->path(),
            ]);

            return \App\Support\ServiceResult::error(
                \App\Enums\OperationStatus::UNAUTHORIZED,
                'Usuário não autorizado a enviar e-mails.',
            );
        }

        return \App\Support\ServiceResult::success(true, 'Usuário autorizado.');
    }

    /**
     * Valida dados de e-mail na requisição.
     */
    private function validateEmailRequest(Request $request): \App\Support\ServiceResult
    {
        $errors = [];

        // Validar destinatário
        if ($request->has('to')) {
            $to = $request->input('to');
            if (is_string($to)) {
                $validation = $this->emailSenderService->validateSender($to);
                if (! $validation->isSuccess()) {
                    $errors[] = 'Destinatário inválido: '.$validation->getMessage();
                }
            }
        }

        // Validar remetente (se fornecido)
        if ($request->has('from_email')) {
            $fromEmail = $request->input('from_email');
            $fromName = $request->input('from_name');

            $validation = $this->emailSenderService->validateSender(
                $fromEmail,
                $fromName,
                Auth::user()?->tenant_id,
            );

            if (! $validation->isSuccess()) {
                $errors[] = 'Remetente inválido: '.$validation->getMessage();
            }
        }

        // Validar conteúdo
        if ($request->has('content') || $request->has('body')) {
            $content = $request->input('content') ?? $request->input('body');

            if (strlen($content) > 50000) { // 50KB máximo
                $errors[] = 'Conteúdo muito grande (máximo 50KB).';
            }
        }

        if (! empty($errors)) {
            return \App\Support\ServiceResult::error(
                \App\Enums\OperationStatus::INVALID_DATA,
                implode(' ', $errors),
            );
        }

        return \App\Support\ServiceResult::success(true, 'Dados de e-mail válidos.');
    }

    /**
     * Verifica rate limiting para a requisição.
     */
    private function checkRateLimiting(Request $request): \App\Support\ServiceResult
    {
        $user = Auth::user();
        $tenant = $user?->tenant;

        // Determinar tipo de e-mail baseado na rota/ação
        $emailType = $this->determineEmailType($request);

        return $this->rateLimitService->checkRateLimit($user, $tenant, $emailType);
    }

    /**
     * Sanitiza conteúdo da requisição se necessário.
     */
    private function sanitizeRequestContent(Request $request): Request
    {
        // Se há conteúdo para sanitizar
        if ($request->has('content') || $request->has('body')) {
            $content = $request->input('content') ?? $request->input('body');
            $contentType = $request->input('content_type', 'html');

            $sanitized = $this->emailSenderService->sanitizeEmailContent($content, $contentType);

            if ($sanitized->isSuccess()) {
                $data = $sanitized->getData();

                // Substituir conteúdo sanitizado
                if (isset($data['sanitized_content'])) {
                    $request->merge([
                        'original_content' => $content,
                        'sanitized_content' => $data['sanitized_content'],
                        'content_changed' => $data['content_changed'],
                    ]);
                }
            }
        }

        return $request;
    }

    /**
     * Verifica se usuário pode enviar e-mails.
     */
    private function userCanSendEmails(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        // Verificar se usuário está ativo
        if (! $user->is_active) {
            return false;
        }

        // Verificar se tenant está ativo (se aplicável)
        if ($user->tenant && ! $user->tenant->is_active) {
            return false;
        }

        // Em produção, verificar permissões específicas
        // Por ora, qualquer usuário ativo pode enviar e-mails
        return true;
    }

    /**
     * Determina tipo de e-mail baseado na requisição.
     */
    private function determineEmailType(Request $request): string
    {
        $path = $request->path();
        $method = $request->method();

        // Mapeamento de rotas para tipos de e-mail
        $typeMapping = [
            'email/verification' => 'critical',
            'email/budget' => 'high',
            'email/invoice' => 'high',
            'email/support' => 'normal',
            'email/notification' => 'normal',
        ];

        foreach ($typeMapping as $route => $type) {
            if (str_contains($path, $route)) {
                return $type;
            }
        }

        // Tipo padrão baseado no método HTTP
        return match ($method) {
            'POST' => 'normal',
            'PUT', 'PATCH' => 'high',
            default => 'low',
        };
    }

    /**
     * Cria resposta de erro padronizada.
     */
    private function createErrorResponse(string $message, int $statusCode): Response
    {
        return response()->json([
            'success' => false,
            'error' => 'Erro de segurança',
            'message' => $message,
            'timestamp' => now()->toDateTimeString(),
        ], $statusCode);
    }

    /**
     * Loga evento de segurança.
     */
    private function logSecurityEvent(string $event, array $context = []): void
    {
        $logData = [
            'event' => $event,
            'timestamp' => now()->toDateTimeString(),
            'middleware' => 'EmailSecurityMiddleware',
        ];

        $logData = array_merge($logData, $context);

        Log::warning('Evento de segurança de e-mail', $logData);
    }
}
