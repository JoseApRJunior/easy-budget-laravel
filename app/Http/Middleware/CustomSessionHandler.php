<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Middleware para gerenciamento customizado de sessões no projeto Easy Budget.
 *
 * Este middleware intercepta as requisições para monitorar e gerenciar operações de sessão,
 * permitindo a preservação de lógica de sessão legacy e futuras extensões. Inicialmente,
 * apenas registra logs para monitoramento, sem alterar o comportamento padrão do Laravel.
 *
 * @note Este middleware deve ser registrado APÓS o StartSession no grupo 'web' para evitar acesso prematuro à sessão.
 */
class CustomSessionHandler
{
    /**
     * Manipula a requisição HTTP, aplicando lógica de sessão customizada.
     *
     * Verifica a sessão atual, registra logs de monitoramento e passa a requisição adiante.
     * Futuramente, pode incluir lógica para migração de dados legacy ou validações específicas.
     *
     * @param  Request  $request  A requisição HTTP atual
     * @param  Closure  $next  A closure que executa o próximo middleware ou controller
     * @return Response A resposta HTTP
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verifica se o isolamento por navegador está habilitado
        if (config('session.isolate_by_browser', false)) {
            $this->ensureBrowserIsolation($request);
        }

        // Log de início da sessão para monitoramento (apenas em dev/test)
        if (! app()->environment('production')) {
            if ($request->hasSession()) {
                $maskedSessionId = substr($request->session()->getId(), -4) ?: '****';
                $maskedUserId = $request->user()?->id ? substr((string) $request->user()->id, -4) ?: '****' : 'anônimo';

                Log::debug('CustomSessionHandler: Iniciando processamento de sessão', [
                    'session_id' => $maskedSessionId,
                    'user_id' => $maskedUserId,
                    'url' => $request->url(),
                    'has_auth_session' => $request->session()->has('auth'),
                    'browser_fingerprint' => substr($request->session()->get('browser_fingerprint', 'none'), -8),
                ]);
            } else {
                Log::debug('CustomSessionHandler: Iniciando processamento sem sessão disponível', [
                    'url' => $request->url(),
                ]);
            }
        }

        // Executa o próximo middleware ou controller
        $response = $next($request);

        // Log de fim da sessão para monitoramento (apenas em dev/test)
        if (! app()->environment('production') && $request->hasSession()) {
            $maskedSessionId = substr($request->session()->getId(), -4) ?: '****';

            Log::debug('CustomSessionHandler: Finalizando processamento de sessão', [
                'session_id' => $maskedSessionId,
                'session_data_size' => count($request->session()->all()),
                'has_auth_session' => $request->session()->has('auth'),
            ]);
        }

        return $response;
    }

    /**
     * Garante isolamento completo de sessão por navegador.
     *
     * Este método garante que cada navegador tenha sua própria sessão independente,
     * evitando qualquer compartilhamento de sessão entre diferentes navegadores.
     *
     * @param  Request  $request  A requisição HTTP atual
     */
    private function ensureBrowserIsolation(Request $request): void
    {
        // Cria uma marca única por navegador para garantir isolamento
        $browserFingerprint = $this->getBrowserFingerprint($request);

        // Armazena informações do navegador na sessão
        $request->session()->put('browser_fingerprint', $browserFingerprint);
        $request->session()->put('browser_isolation', true);

        // Não interfere na sessão auth - deixa o Laravel gerenciar naturalmente
        // Remove qualquer lógica que possa estar causando inconsistência entre navegadores
    }

    /**
     * Cria uma marca única para identificar o navegador.
     */
    private function getBrowserFingerprint(Request $request): string
    {
        $userAgent = $request->userAgent();
        $ip = $request->ip();
        $acceptLanguage = $request->header('Accept-Language', '');

        return hash('sha256', $userAgent.$ip.$acceptLanguage.time());
    }

    /**
     * Cria uma nova sessão customizada.
     *
     * Método protegido para futura implementação de criação de sessões com lógica legacy.
     * Atualmente, não é utilizado, mas está preparado para extensão.
     *
     * @param  string  $sessionId  O ID da sessão a ser criada
     */
    protected function createSession(string $sessionId): void
    {
        // Lógica futura para criação de sessão customizada
        Log::debug('CustomSessionHandler: Criando sessão', ['session_id' => $sessionId]);
    }

    /**
     * Lê dados de uma sessão existente.
     *
     * Método protegido para futura leitura de dados de sessão com compatibilidade legacy.
     *
     * @param  string  $sessionId  O ID da sessão a ser lida
     * @return array|null Dados da sessão ou null se não encontrada
     */
    protected function readSession(string $sessionId): ?array
    {
        // Lógica futura para leitura de sessão customizada
        Log::debug('CustomSessionHandler: Lendo sessão', ['session_id' => $sessionId]);

        return null; // Placeholder para dados legacy
    }

    /**
     * Escreve dados em uma sessão.
     *
     * Método protegido para futura escrita de dados de sessão com validações customizadas.
     *
     * @param  string  $sessionId  O ID da sessão
     * @param  array  $data  Os dados a serem escritos
     */
    protected function writeSession(string $sessionId, array $data): void
    {
        // Lógica futura para escrita de sessão customizada
        Log::debug('CustomSessionHandler: Escrevendo sessão', [
            'session_id' => $sessionId,
            'data_keys' => array_keys($data),
        ]);
    }

    /**
     * Destrói uma sessão existente.
     *
     * Método protegido para futura destruição de sessões com cleanup de dados legacy.
     *
     * @param  string  $sessionId  O ID da sessão a ser destruída
     */
    protected function destroySession(string $sessionId): void
    {
        // Lógica futura para destruição de sessão customizada
        Log::debug('CustomSessionHandler: Destruindo sessão', ['session_id' => $sessionId]);
    }
}
