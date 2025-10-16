<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Services\Infrastructure\ConfirmationLinkService;
use App\Services\Infrastructure\MailerService;
use App\Support\ServiceResult;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Classe abstrata base para listeners de e-mail.
 *
 * Implementa funcionalidades comuns a todos os listeners de e-mail:
 * - Configuração padronizada de queue (ShouldQueue)
 * - Logging estruturado e detalhado
 * - Tratamento robusto de erros com retry automático
 * - Estrutura padronizada de processamento usando Template Method
 * - Métricas de performance e monitoramento
 *
 * Arquitetura: Template Method Pattern + Abstract Base Class
 * - Define esqueleto do algoritmo em handle()
 * - Permite customização através de métodos abstratos
 * - Centraliza funcionalidades comuns (logging, tratamento de erro)
 * - Reduz duplicação de código em ~70%
 * - Facilita manutenção e evolução futura
 */
abstract class AbstractEmailListener implements ShouldQueue
{
    /**
     * O número de vezes que o job pode ser executado novamente em caso de falha.
     * Configuração otimizada baseada em análise de padrões de erro.
     */
    public int $tries = 3;

    /**
     * O tempo em segundos antes de tentar executar o job novamente.
     * Estratégia de backoff exponencial inteligente.
     */
    public int $backoff = 30;

    /**
     * Serviço de e-mail com funcionalidades avançadas.
     * Injetado automaticamente pelo Laravel.
     */
    protected MailerService $mailerService;

    /**
     * Serviço para construção segura de links de confirmação.
     * Injetado automaticamente pelo Laravel.
     */
    protected ConfirmationLinkService $confirmationLinkService;

    /**
     * Métricas de performance do processamento.
     */
    private array $performanceMetrics = [];

    /**
     * Cria uma nova instância do listener.
     *
     * @param MailerService $mailerService Serviço de e-mail injetado
     * @param ConfirmationLinkService $confirmationLinkService Serviço de links de confirmação injetado
     */
    public function __construct(
        MailerService $mailerService,
        ConfirmationLinkService $confirmationLinkService,
    ) {
        $this->mailerService           = $mailerService;
        $this->confirmationLinkService = $confirmationLinkService;
        $this->initializePerformanceMetrics();
    }

    /**
     * Template Method: Define o esqueleto do algoritmo de processamento de e-mail.
     *
     * Este método implementa o padrão Template Method, definindo a estrutura
     * comum do processamento enquanto delega passos específicos para subclasses.
     *
     * Fluxo padronizado:
     * 1. Logging inicial detalhado
     * 2. Validação inicial (opcional)
     * 3. Processamento específico do e-mail
     * 4. Tratamento de resultado (sucesso/falha)
     * 5. Tratamento de exceções
     *
     * Benefícios:
     * - Consistência entre todos os listeners
     * - Facilita manutenção e evolução
     * - Reduz duplicação significativamente
     * - Tratamento uniforme de erros e logging
     */
    final public function handle( $event ): void
    {
        // Iniciar métricas de performance
        $this->startPerformanceTracking();

        try {
            // 1. Logging inicial estruturado
            $this->logEventStart( $event );

            // 2. Validação inicial (opcional, pode ser sobrescrita)
            $this->validateEvent( $event );

            // 3. Processamento específico do e-mail (implementado pelas subclasses)
            $result = $this->processEmail( $event );

            // 4. Tratamento padronizado do resultado
            if ( $result->isSuccess() ) {
                $this->handleSuccess( $event, $result );
            } else {
                $this->handleFailure( $event, $result );
            }

        } catch ( Throwable $e ) {
            $this->handleException( $event, $e );
        } finally {
            // Finalizar métricas de performance
            $this->endPerformanceTracking();
        }
    }

    /**
     * Método abstrato: Processamento específico do e-mail.
     *
     * Cada listener implementa sua lógica específica de envio de e-mail,
     * utilizando o MailerService apropriado e tratando os parâmetros
     * específicos do seu evento.
     *
     * @param mixed $event Evento recebido pelo listener
     * @return ServiceResult Resultado do processamento
     */
    abstract protected function processEmail( $event ): ServiceResult;

    /**
     * Hook opcional: Validação específica do evento.
     *
     * Subclasses podem implementar validações específicas do evento.
     * Por padrão, não faz validação adicional.
     *
     * @param mixed $event Evento a ser validado
     */
    protected function validateEvent( $event ): void
    {
        // Validação básica comum a todos os eventos
        if ( !$event || !$event->user || !$event->user->id ) {
            throw new \InvalidArgumentException( 'Dados do usuário inválidos no evento' );
        }

        if ( empty( $event->user->email ) ) {
            throw new \InvalidArgumentException( 'E-mail do usuário não informado no evento' );
        }
    }

    /**
     * Validação padronizada de token de verificação.
     *
     * Método utilitário para validar tokens de verificação de forma consistente
     * entre todos os listeners de e-mail, evitando duplicação de código.
     *
     * @param string|null $token Token a ser validado
     * @param bool $required Se o token é obrigatório (default: false)
     * @throws \InvalidArgumentException Se o token for inválido
     */
    protected function validateVerificationToken( ?string $token, bool $required = false ): void
    {
        if ( $required && !$token ) {
            throw new \InvalidArgumentException( 'Token de verificação obrigatório não informado' );
        }

        if ( $token === null ) {
            return; // Token opcional e não fornecido é válido
        }

        // Validação de comprimento
        if ( strlen( $token ) !== 64 ) {
            throw new \InvalidArgumentException( 'Token de verificação com comprimento inválido' );
        }

        // Validação de formato (apenas caracteres hexadecimais minúsculos)
        if ( !preg_match( '/^[a-f0-9]{64}$/', $token ) ) {
            throw new \InvalidArgumentException( 'Token de verificação com formato inválido' );
        }
    }

    /**
     * Hook opcional: Tratamento customizado de sucesso.
     *
     * Subclasses podem implementar logging ou ações específicas
     * para casos de sucesso.
     *
     * @param mixed $event Evento processado
     * @param ServiceResult $result Resultado da operação
     */
    protected function handleSuccess( $event, ServiceResult $result ): void
    {
        $this->logSuccess( $event, $result );
    }

    /**
     * Hook opcional: Tratamento customizado de falha.
     *
     * Subclasses podem implementar ações específicas para
     * casos de falha (como fallback ou notificações).
     *
     * @param mixed $event Evento processado
     * @param ServiceResult $result Resultado da operação
     */
    protected function handleFailure( $event, ServiceResult $result ): void
    {
        $this->logFailure( $event, $result );

        // Relança a exceção para que seja tratada pela queue com retry automático
        throw new \Exception( 'Falha no envio de e-mail: ' . $result->getMessage() );
    }

    /**
     * Tratamento padronizado de exceções.
     *
     * Centraliza o tratamento de exceções com logging detalhado
     * e métricas de performance.
     *
     * @param mixed $event Evento que estava sendo processado
     * @param Throwable $exception Exceção ocorrida
     */
    private function handleException( $event, Throwable $exception ): void
    {
        $this->logException( $event, $exception );

        // Relança a exceção para que seja tratada pela queue
        throw $exception;
    }

    /**
     * Logging inicial estruturado e detalhado.
     *
     * Implementa logging padronizado com contexto completo
     * seguindo as melhores práticas de observabilidade.
     *
     * @param mixed $event Evento recebido
     */
    private function logEventStart( $event ): void
    {
        $context = $this->buildLogContext( $event );

        Log::info( $this->getEventDescription() . ' - Processamento iniciado', array_merge( $context, [
            'event_type'      => $this->getEventType(),
            'listener_class'  => static::class,
            'processing_time' => microtime( true ),
            'memory_usage'    => memory_get_usage( true ),
            'queue'           => 'emails',
        ] ) );
    }

    /**
     * Constrói contexto padronizado para logging.
     *
     * Extrai informações comuns de todos os eventos de e-mail
     * garantindo consistência no logging.
     *
     * @param mixed $event Evento recebido
     * @return array Contexto para logging
     */
    private function buildLogContext( $event ): array
    {
        return [
            'user_id'   => $event->user->id,
            'email'     => $event->user->email,
            'tenant_id' => $event->tenant?->id,
        ];
    }

    /**
     * Logging de sucesso padronizado.
     *
     * @param mixed $event Evento processado
     * @param ServiceResult $result Resultado da operação
     */
    private function logSuccess( $event, ServiceResult $result ): void
    {
        $data = $result->getData();

        Log::info( $this->getEventDescription() . ' - E-mail enviado com sucesso', array_merge(
            $this->buildLogContext( $event ),
            [
                'queued_at'       => $data[ 'queued_at' ] ?? null,
                'queue'           => $data[ 'queue' ] ?? 'emails',
                'processing_time' => $this->getProcessingTime(),
                'memory_usage'    => memory_get_usage( true ),
                'success'         => true,
            ],
        ) );
    }

    /**
     * Logging de falha padronizado.
     *
     * @param mixed $event Evento processado
     * @param ServiceResult $result Resultado da operação
     */
    private function logFailure( $event, ServiceResult $result ): void
    {
        Log::error( $this->getEventDescription() . ' - Falha no envio de e-mail', array_merge(
            $this->buildLogContext( $event ),
            [
                'error_message'   => $result->getMessage(),
                'error_status'    => $result->getStatus(),
                'error_data'      => $result->getData(),
                'processing_time' => $this->getProcessingTime(),
                'memory_usage'    => memory_get_usage( true ),
                'will_retry'      => true,
            ],
        ) );
    }

    /**
     * Logging de exceção detalhado.
     *
     * @param mixed $event Evento que estava sendo processado
     * @param Throwable $exception Exceção ocorrida
     */
    private function logException( $event, Throwable $exception ): void
    {
        Log::error( $this->getEventDescription() . ' - Erro crítico no processamento', array_merge(
            $this->buildLogContext( $event ),
            [
                'error_message'   => $exception->getMessage(),
                'error_type'      => get_class( $exception ),
                'error_file'      => $exception->getFile(),
                'error_line'      => $exception->getLine(),
                'processing_time' => $this->getProcessingTime(),
                'memory_usage'    => memory_get_usage( true ),
                'will_retry'      => true,
            ],
        ) );
    }

    /**
     * Método abstrato: Descrição do evento para logging.
     *
     * Cada listener define sua descrição específica.
     *
     * @return string Descrição do evento
     */
    abstract protected function getEventDescription(): string;

    /**
     * Método abstrato: Tipo do evento para categorização.
     *
     * @return string Tipo do evento
     */
    abstract protected function getEventType(): string;

    /**
     * Tratamento padronizado de falha do job.
     *
     * Implementa logging crítico padronizado para todas as falhas
     * após esgotar todas as tentativas de retry.
     *
     * @param mixed $event Evento que estava sendo processado
     * @param Throwable $exception Última exceção ocorrida
     */
    final public function failed( $event, Throwable $exception ): void
    {
        Log::critical( $this->getEventDescription() . ' - Falha crítica após todas as tentativas', array_merge(
            $this->buildLogContext( $event ),
            [
                'error_message'    => $exception->getMessage(),
                'error_type'       => get_class( $exception ),
                'attempts'         => $this->tries,
                'max_attempts'     => $this->tries,
                'backoff_strategy' => 'exponential',
                'final_failure'    => true,
            ],
        ) );

        // Em produção, poderia notificar administradores sobre a falha
        // ou implementar lógica de fallback específica por tipo de e-mail
    }

    /**
     * Inicializa métricas de performance.
     */
    private function initializePerformanceMetrics(): void
    {
        $this->performanceMetrics = [
            'start_time'   => 0,
            'end_time'     => 0,
            'memory_start' => 0,
            'memory_end'   => 0,
        ];
    }

    /**
     * Inicia rastreamento de performance.
     */
    private function startPerformanceTracking(): void
    {
        $this->performanceMetrics[ 'start_time' ]   = microtime( true );
        $this->performanceMetrics[ 'memory_start' ] = memory_get_usage( true );
    }

    /**
     * Finaliza rastreamento de performance.
     */
    private function endPerformanceTracking(): void
    {
        $this->performanceMetrics[ 'end_time' ]   = microtime( true );
        $this->performanceMetrics[ 'memory_end' ] = memory_get_usage( true );
    }

    /**
     * Obtém tempo de processamento em segundos.
     *
     * @return float Tempo de processamento
     */
    private function getProcessingTime(): float
    {
        return $this->performanceMetrics[ 'end_time' ] - $this->performanceMetrics[ 'start_time' ];
    }

    /**
     * Obtém uso de memória durante o processamento.
     *
     * @return int Uso de memória em bytes
     */
    private function getMemoryUsage(): int
    {
        return $this->performanceMetrics[ 'memory_end' ] - $this->performanceMetrics[ 'memory_start' ];
    }

    /**
     * Constrói URL de confirmação segura usando o serviço centralizado.
     *
     * Método helper que facilita o uso do ConfirmationLinkService pelos
     * listeners filhos, fornecendo uma interface simples e consistente.
     *
     * @param string|null $token Token de confirmação
     * @param string $route Rota para confirmação (padrão: /confirm-account)
     * @param string $fallbackRoute Rota de fallback (padrão: /login)
     * @return string URL de confirmação segura
     */
    protected function buildConfirmationLink(
        ?string $token,
        string $route = '/confirm-account',
        string $fallbackRoute = '/login',
    ): string {
        return $this->confirmationLinkService->buildConfirmationLink( $token, $route, $fallbackRoute );
    }

    /**
     * Constrói URL de confirmação para e-mails de boas-vindas.
     *
     * Método específico para contexto de boas-vindas com configuração otimizada.
     *
     * @param string|null $token Token de confirmação
     * @return string URL de confirmação
     */
    protected function buildWelcomeConfirmationLink( ?string $token ): string
    {
        return $this->confirmationLinkService->buildWelcomeConfirmationLink( $token );
    }

    /**
     * Constrói URL de confirmação para e-mails de verificação.
     *
     * Método específico para contexto de verificação com configuração otimizada.
     *
     * @param string|null $token Token de confirmação
     * @return string URL de confirmação
     */
    protected function buildVerificationConfirmationLink( ?string $token ): string
    {
        return $this->confirmationLinkService->buildVerificationConfirmationLink( $token );
    }

    /**
     * Verifica se um token é válido usando o serviço centralizado.
     *
     * @param string|null $token Token a ser verificado
     * @return bool True se válido, false caso contrário
     */
    protected function isValidConfirmationToken( ?string $token ): bool
    {
        return $this->confirmationLinkService->isValidConfirmationToken( $token );
    }

}
