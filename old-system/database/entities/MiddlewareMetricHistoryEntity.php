<?php

namespace app\database\entities;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entidade ORM para histórico de métricas de middlewares.
 *
 * Esta entidade representa o histórico detalhado de métricas de performance
 * coletadas pelos middlewares do sistema, incluindo tempo de resposta,
 * uso de memória, CPU e outras métricas relevantes para monitoramento.
 *
 * @package app\database\entitiesORM
 * @psalm-suppress PropertyNotSetInConstructoa
 */
#[ORM\Entity ]
#[ORM\Table(name: 'middleware_metrics_history') ]
#[ORM\HasLifecycleCallbacks ]
class MiddlewareMetricHistoryEntity extends AbstractEntityORM
{
    /**
     * Identificador único da métrica
     */
    #[ORM\Id ]
    #[ORM\Column(type: Types::BIGINT) ]
    #[ORM\GeneratedValue ]
    private ?int $id = null;

    /**
     * Tenant proprietário da métrica (multi-tenancy)
     */
    #[ORM\ManyToOne(targetEntity: TenantEntity::class) ]
    #[ORM\JoinColumn(name: 'tenant_id', referencedColumnName: 'id', nullable: false) ]
    private TenantEntity $tenant;

    /**
     * Nome do middleware que gerou a métrica
     */
    #[ORM\Column(name: 'middleware_name', type: Types::STRING, length: 100) ]
    private string $middlewareName;

    /**
     * Endpoint da requisição monitorada
     */
    #[ORM\Column(name: 'endpoint', type: Types::STRING, length: 255) ]
    private string $endpoint;

    /**
     * Método HTTP da requisição (GET, POST, PUT, DELETE, etc.)
     */
    #[ORM\Column(name: 'method', type: Types::STRING, length: 10) ]
    private string $method;

    /**
     * Tempo de resposta em milissegundos
     */
    #[ORM\Column(name: 'response_time', type: Types::DECIMAL, precision: 10, scale: 3) ]
    private float $responseTime;

    /**
     * Uso de memória em bytes
     */
    #[ORM\Column(name: 'memory_usage', type: Types::BIGINT) ]
    private int $memoryUsage;

    /**
     * Uso de CPU em porcentagem (opcional)
     */
    #[ORM\Column(name: 'cpu_usage', type: Types::DECIMAL, precision: 5, scale: 2, nullable: true) ]
    private ?float $cpuUsage = null;

    /**
     * Código de status HTTP da resposta
     */
    #[ORM\Column(name: 'status_code', type: Types::INTEGER) ]
    private int $statusCode;

    /**
     * Mensagem de erro (se houver)
     */
    #[ORM\Column(name: 'error_message', type: Types::TEXT, nullable: true) ]
    private ?string $errorMessage = null;

    /**
     * Usuário que fez a requisição (opcional)
     */
    #[ORM\ManyToOne(targetEntity: UserEntity::class) ]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true) ]
    private ?UserEntity $user = null;

    /**
     * Endereço IP da requisição
     */
    #[ORM\Column(name: 'ip_address', type: Types::STRING, length: 45, nullable: true) ]
    private ?string $ipAddress = null;

    /**
     * User Agent do navegador
     */
    #[ORM\Column(name: 'user_agent', type: Types::TEXT, nullable: true) ]
    private ?string $userAgent = null;

    /**
     * Tamanho da requisição em bytes
     */
    #[ORM\Column(name: 'request_size', type: Types::BIGINT, nullable: true) ]
    private ?int $requestSize = null;

    /**
     * Tamanho da resposta em bytes
     */
    #[ORM\Column(name: 'response_size', type: Types::BIGINT, nullable: true) ]
    private ?int $responseSize = null;

    /**
     * Número de queries de banco de dados executadas
     */
    #[ORM\Column(name: 'database_queries', type: Types::INTEGER, nullable: true) ]
    private ?int $databaseQueries = null;

    /**
     * Número de cache hits
     */
    #[ORM\Column(name: 'cache_hits', type: Types::INTEGER, nullable: true) ]
    private ?int $cacheHits = null;

    /**
     * Número de cache misses
     */
    #[ORM\Column(name: 'cache_misses', type: Types::INTEGER, nullable: true) ]
    private ?int $cacheMisses = null;

    /**
     * Data e hora de criação da métrica
     */
    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE) ]
    private DateTimeImmutable $createdAt;

    /**
     * Construtor da entidade MiddlewareMetricHistoryEntity.
     *
     * @param TenantEntity $tenant Tenant proprietário da métrica
     * @param string $middlewareName Nome do middleware
     * @param string $endpoint Endpoint da requisição
     * @param string $method Método HTTP
     * @param float $responseTime Tempo de resposta em milissegundos
     * @param int $memoryUsage Uso de memória em bytes
     * @param int $statusCode Código de status HTTP
     */
    public function __construct(
        TenantEntity $tenant,
        string $middlewareName,
        string $endpoint,
        string $method,
        float $responseTime,
        int $memoryUsage,
        int $statusCode,
    ) {
        $this->tenant         = $tenant;
        $this->middlewareName = $middlewareName;
        $this->endpoint       = $endpoint;
        $this->method         = strtoupper( $method );
        $this->responseTime   = $responseTime;
        $this->memoryUsage    = $memoryUsage;
        $this->statusCode     = $statusCode;
        $this->createdAt      = new DateTimeImmutable();
    }

    /**
     * Obtém o ID da métrica.
     *
     * @return int|null ID da métrica ou null se ainda não persistida
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Obtém o tenant proprietário da métrica.
     *
     * @return TenantEntity Tenant proprietário
     */
    public function getTenant(): TenantEntity
    {
        return $this->tenant;
    }

    /**
     * Define o tenant proprietário da métrica.
     *
     * @param TenantEntity $tenant Tenant proprietário
     * @return self
     */
    public function setTenant( TenantEntity $tenant ): self
    {
        $this->tenant = $tenant;
        return $this;
    }

    /**
     * Obtém o nome do middleware.
     *
     * @return string Nome do middleware
     */
    public function getMiddlewareName(): string
    {
        return $this->middlewareName;
    }

    /**
     * Define o nome do middleware.
     *
     * @param string $middlewareName Nome do middleware
     * @return self
     */
    public function setMiddlewareName( string $middlewareName ): self
    {
        $this->middlewareName = $middlewareName;
        return $this;
    }

    /**
     * Obtém o endpoint da requisição.
     *
     * @return string Endpoint da requisição
     */
    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    /**
     * Define o endpoint da requisição.
     *
     * @param string $endpoint Endpoint da requisição
     * @return self
     */
    public function setEndpoint( string $endpoint ): self
    {
        $this->endpoint = $endpoint;
        return $this;
    }

    /**
     * Obtém o método HTTP.
     *
     * @return string Método HTTP
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Define o método HTTP.
     *
     * @param string $method Método HTTP
     * @return self
     */
    public function setMethod( string $method ): self
    {
        $this->method = strtoupper( $method );
        return $this;
    }

    /**
     * Obtém o tempo de resposta em milissegundos.
     *
     * @return float Tempo de resposta
     */
    public function getResponseTime(): float
    {
        return $this->responseTime;
    }

    /**
     * Define o tempo de resposta em milissegundos.
     *
     * @param float $responseTime Tempo de resposta
     * @return self
     */
    public function setResponseTime( float $responseTime ): self
    {
        $this->responseTime = $responseTime;
        return $this;
    }

    /**
     * Obtém o uso de memória em bytes.
     *
     * @return int Uso de memória
     */
    public function getMemoryUsage(): int
    {
        return $this->memoryUsage;
    }

    /**
     * Define o uso de memória em bytes.
     *
     * @param int $memoryUsage Uso de memória
     * @return self
     */
    public function setMemoryUsage( int $memoryUsage ): self
    {
        $this->memoryUsage = $memoryUsage;
        return $this;
    }

    /**
     * Obtém o uso de CPU em porcentagem.
     *
     * @return float|null Uso de CPU ou null se não disponível
     */
    public function getCpuUsage(): ?float
    {
        return $this->cpuUsage;
    }

    /**
     * Define o uso de CPU em porcentagem.
     *
     * @param float|null $cpuUsage Uso de CPU
     * @return self
     */
    public function setCpuUsage( ?float $cpuUsage ): self
    {
        $this->cpuUsage = $cpuUsage;
        return $this;
    }

    /**
     * Obtém o código de status HTTP.
     *
     * @return int Código de status
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Define o código de status HTTP.
     *
     * @param int $statusCode Código de status
     * @return self
     */
    public function setStatusCode( int $statusCode ): self
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    /**
     * Obtém a mensagem de erro.
     *
     * @return string|null Mensagem de erro ou null se não houver
     */
    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    /**
     * Define a mensagem de erro.
     *
     * @param string|null $errorMessage Mensagem de erro
     * @return self
     */
    public function setErrorMessage( ?string $errorMessage ): self
    {
        $this->errorMessage = $errorMessage;
        return $this;
    }

    /**
     * Obtém o usuário que fez a requisição.
     *
     * @return UserEntity|null Usuário ou null se não identificado
     */
    public function getUser(): ?UserEntity
    {
        return $this->user;
    }

    /**
     * Define o usuário que fez a requisição.
     *
     * @param UserEntity|null $user Usuário
     * @return self
     */
    public function setUser( ?UserEntity $user ): self
    {
        $this->user = $user;
        return $this;
    }

    /**
     * Obtém o endereço IP da requisição.
     *
     * @return string|null Endereço IP ou null se não disponível
     */
    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    /**
     * Define o endereço IP da requisição.
     *
     * @param string|null $ipAddress Endereço IP
     * @return self
     */
    public function setIpAddress( ?string $ipAddress ): self
    {
        $this->ipAddress = $ipAddress;
        return $this;
    }

    /**
     * Obtém o User Agent do navegador.
     *
     * @return string|null User Agent ou null se não disponível
     */
    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    /**
     * Define o User Agent do navegador.
     *
     * @param string|null $userAgent User Agent
     * @return self
     */
    public function setUserAgent( ?string $userAgent ): self
    {
        $this->userAgent = $userAgent;
        return $this;
    }

    /**
     * Obtém o tamanho da requisição em bytes.
     *
     * @return int|null Tamanho da requisição ou null se não disponível
     */
    public function getRequestSize(): ?int
    {
        return $this->requestSize;
    }

    /**
     * Define o tamanho da requisição em bytes.
     *
     * @param int|null $requestSize Tamanho da requisição
     * @return self
     */
    public function setRequestSize( ?int $requestSize ): self
    {
        $this->requestSize = $requestSize;
        return $this;
    }

    /**
     * Obtém o tamanho da resposta em bytes.
     *
     * @return int|null Tamanho da resposta ou null se não disponível
     */
    public function getResponseSize(): ?int
    {
        return $this->responseSize;
    }

    /**
     * Define o tamanho da resposta em bytes.
     *
     * @param int|null $responseSize Tamanho da resposta
     * @return self
     */
    public function setResponseSize( ?int $responseSize ): self
    {
        $this->responseSize = $responseSize;
        return $this;
    }

    /**
     * Obtém o número de queries de banco de dados executadas.
     *
     * @return int|null Número de queries ou null se não disponível
     */
    public function getDatabaseQueries(): ?int
    {
        return $this->databaseQueries;
    }

    /**
     * Define o número de queries de banco de dados executadas.
     *
     * @param int|null $databaseQueries Número de queries
     * @return self
     */
    public function setDatabaseQueries( ?int $databaseQueries ): self
    {
        $this->databaseQueries = $databaseQueries;
        return $this;
    }

    /**
     * Obtém o número de cache hits.
     *
     * @return int|null Número de cache hits ou null se não disponível
     */
    public function getCacheHits(): ?int
    {
        return $this->cacheHits;
    }

    /**
     * Define o número de cache hits.
     *
     * @param int|null $cacheHits Número de cache hits
     * @return self
     */
    public function setCacheHits( ?int $cacheHits ): self
    {
        $this->cacheHits = $cacheHits;
        return $this;
    }

    /**
     * Obtém o número de cache misses.
     *
     * @return int|null Número de cache misses ou null se não disponível
     */
    public function getCacheMisses(): ?int
    {
        return $this->cacheMisses;
    }

    /**
     * Define o número de cache misses.
     *
     * @param int|null $cacheMisses Número de cache misses
     * @return self
     */
    public function setCacheMisses( ?int $cacheMisses ): self
    {
        $this->cacheMisses = $cacheMisses;
        return $this;
    }

    /**
     * Obtém a data e hora de criação da métrica.
     *
     * @return DateTimeImmutable Data de criação
     */
    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Verifica se a requisição foi bem-sucedida (status 2xx).
     *
     * @return bool True se bem-sucedida, false caso contrário
     */
    public function isSuccessful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    /**
     * Verifica se houve erro na requisição (status 4xx ou 5xx).
     *
     * @return bool True se houve erro, false caso contrário
     */
    public function hasError(): bool
    {
        return $this->statusCode >= 400;
    }

    /**
     * Verifica se houve erro do servidor (status 5xx).
     *
     * @return bool True se erro do servidor, false caso contrário
     */
    public function isServerError(): bool
    {
        return $this->statusCode >= 500;
    }

    /**
     * Verifica se houve erro do cliente (status 4xx).
     *
     * @return bool True se erro do cliente, false caso contrário
     */
    public function isClientError(): bool
    {
        return $this->statusCode >= 400 && $this->statusCode < 500;
    }

    /**
     * Calcula a eficiência do cache (hits / (hits + misses)).
     *
     * @return float|null Eficiência do cache (0-1) ou null se dados não disponíveis
     */
    public function getCacheEfficiency(): ?float
    {
        if ( $this->cacheHits === null || $this->cacheMisses === null ) {
            return null;
        }

        $total = $this->cacheHits + $this->cacheMisses;
        if ( $total === 0 ) {
            return null;
        }

        return $this->cacheHits / $total;
    }

    /**
     * Obtém o uso de memória formatado em formato legível.
     *
     * @return string Uso de memória formatado (ex: "2.5 MB")
     */
    public function getFormattedMemoryUsage(): string
    {
        $bytes = $this->memoryUsage;
        $units = [ 'B', 'KB', 'MB', 'GB', 'TB' ];

        for ( $i = 0; $bytes > 1024 && $i < count( $units ) - 1; $i++ ) {
            $bytes /= 1024;
        }

        return round( $bytes, 2 ) . ' ' . $units[ $i ];
    }

    /**
     * Converte a entidade para array para serialização.
     *
     * @return array Representação em array da entidade
     */
    public function toArray(): array
    {
        return [
            'id'                     => $this->id,
            'tenant_id'              => $this->tenant->getId(),
            'middleware_name'        => $this->middlewareName,
            'endpoint'               => $this->endpoint,
            'method'                 => $this->method,
            'response_time'          => $this->responseTime,
            'memory_usage'           => $this->memoryUsage,
            'cpu_usage'              => $this->cpuUsage,
            'status_code'            => $this->statusCode,
            'error_message'          => $this->errorMessage,
            'user_id'                => $this->user?->getId(),
            'ip_address'             => $this->ipAddress,
            'user_agent'             => $this->userAgent,
            'request_size'           => $this->requestSize,
            'response_size'          => $this->responseSize,
            'database_queries'       => $this->databaseQueries,
            'cache_hits'             => $this->cacheHits,
            'cache_misses'           => $this->cacheMisses,
            'created_at'             => $this->createdAt->format( 'Y-m-d H:i:s' ),
            'is_successful'          => $this->isSuccessful(),
            'has_error'              => $this->hasError(),
            'cache_efficiency'       => $this->getCacheEfficiency(),
            'formatted_memory_usage' => $this->getFormattedMemoryUsage()
        ];
    }

}