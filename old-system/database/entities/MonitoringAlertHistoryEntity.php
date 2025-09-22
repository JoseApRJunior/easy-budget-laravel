<?php

namespace app\database\entitiesORM;

use app\database\entitiesORM\AbstractEntityORM;
use app\database\repositories\MonitoringAlertHistoryRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entidade ORM para histórico de alertas de monitoramento.
 *
 * Esta entidade representa o histórico de alertas gerados pelo sistema
 * de monitoramento, incluindo alertas de performance, erros, limites
 * excedidos e outras condições que requerem atenção.
 *
 * @package app\database\entitiesORM
 * @psalm-suppress PropertyNotSetInConstructor
 */
#[ORM\Entity(repositoryClass: MonitoringAlertHistoryRepository::class) ]
#[ORM\Table(name: 'monitoring_alerts_history') ]
#[ORM\HasLifecycleCallbacks ]
class MonitoringAlertHistoryEntity extends AbstractEntityORM
{
    /**
     * Identificador único do alerta
     */
    #[ORM\Id ]
    #[ORM\Column(type: Types::BIGINT) ]
    #[ORM\GeneratedValue ]
    private ?int $id = null;

    /**
     * Tenant proprietário do alerta (multi-tenancy)
     */
    #[ORM\ManyToOne(targetEntity: TenantEntity::class) ]
    #[ORM\JoinColumn(name: 'tenant_id', referencedColumnName: 'id', nullable: false) ]
    private TenantEntity $tenant;

    /**
     * Tipo do alerta (performance, error, threshold, security, etc.)
     */
    #[ORM\Column(name: 'alert_type', type: Types::STRING, length: 50) ]
    private string $alertType;

    /**
     * Nível de severidade do alerta (low, medium, high, critical)
     */
    #[ORM\Column(name: 'severity', type: Types::STRING, length: 20) ]
    private string $severity;

    /**
     * Título descritivo do alerta
     */
    #[ORM\Column(name: 'title', type: Types::STRING, length: 255) ]
    private string $title;

    /**
     * Descrição detalhada do alerta
     */
    #[ORM\Column(name: 'description', type: Types::TEXT) ]
    private string $description;

    /**
     * Componente ou módulo que gerou o alerta
     */
    #[ORM\Column(name: 'component', type: Types::STRING, length: 100) ]
    private string $component;

    /**
     * Endpoint relacionado ao alerta (se aplicável)
     */
    #[ORM\Column(name: 'endpoint', type: Types::STRING, length: 255, nullable: true) ]
    private ?string $endpoint = null;

    /**
     * Método HTTP relacionado ao alerta (se aplicável)
     */
    #[ORM\Column(name: 'method', type: Types::STRING, length: 10, nullable: true) ]
    private ?string $method = null;

    /**
     * Valor atual da métrica que disparou o alerta
     */
    #[ORM\Column(name: 'current_value', type: Types::DECIMAL, precision: 15, scale: 3, nullable: true) ]
    private ?float $currentValue = null;

    /**
     * Valor limite que foi excedido
     */
    #[ORM\Column(name: 'threshold_value', type: Types::DECIMAL, precision: 15, scale: 3, nullable: true) ]
    private ?float $thresholdValue = null;

    /**
     * Unidade de medida da métrica (ms, MB, %, count, etc.)
     */
    #[ORM\Column(name: 'unit', type: Types::STRING, length: 20, nullable: true) ]
    private ?string $unit = null;

    /**
     * Dados adicionais em formato JSON
     */
    #[ORM\Column(name: 'metadata', type: Types::JSON, nullable: true) ]
    private ?array $metadata = null;

    /**
     * Status do alerta (active, acknowledged, resolved, ignored)
     */
    #[ORM\Column(name: 'status', type: Types::STRING, length: 20) ]
    private string $status;

    /**
     * Usuário que reconheceu o alerta (se aplicável)
     */
    #[ORM\ManyToOne(targetEntity: UserEntity::class) ]
    #[ORM\JoinColumn(name: 'acknowledged_by', referencedColumnName: 'id', nullable: true) ]
    private ?UserEntity $acknowledgedBy = null;

    /**
     * Data e hora em que o alerta foi reconhecido
     */
    #[ORM\Column(name: 'acknowledged_at', type: Types::DATETIME_IMMUTABLE, nullable: true) ]
    private ?DateTimeImmutable $acknowledgedAt = null;

    /**
     * Usuário que resolveu o alerta (se aplicável)
     */
    #[ORM\ManyToOne(targetEntity: UserEntity::class) ]
    #[ORM\JoinColumn(name: 'resolved_by', referencedColumnName: 'id', nullable: true) ]
    private ?UserEntity $resolvedBy = null;

    /**
     * Data e hora em que o alerta foi resolvido
     */
    #[ORM\Column(name: 'resolved_at', type: Types::DATETIME_IMMUTABLE, nullable: true) ]
    private ?DateTimeImmutable $resolvedAt = null;

    /**
     * Comentários sobre a resolução do alerta
     */
    #[ORM\Column(name: 'resolution_notes', type: Types::TEXT, nullable: true) ]
    private ?string $resolutionNotes = null;

    /**
     * Número de ocorrências do mesmo tipo de alerta
     */
    #[ORM\Column(name: 'occurrence_count', type: Types::INTEGER) ]
    private int $occurrenceCount;

    /**
     * Data da primeira ocorrência deste tipo de alerta
     */
    #[ORM\Column(name: 'first_occurrence', type: Types::DATETIME_IMMUTABLE) ]
    private DateTimeImmutable $firstOccurrence;

    /**
     * Data da última ocorrência deste tipo de alerta
     */
    #[ORM\Column(name: 'last_occurrence', type: Types::DATETIME_IMMUTABLE) ]
    private DateTimeImmutable $lastOccurrence;

    /**
     * Data e hora de criação do registro
     */
    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE) ]
    private DateTimeImmutable $createdAt;

    /**
     * Data e hora da última atualização
     */
    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE) ]
    private DateTimeImmutable $updatedAt;

    /**
     * Construtor da entidade MonitoringAlertHistoryEntity.
     *
     * @param TenantEntity $tenant Tenant proprietário do alerta
     * @param string $alertType Tipo do alerta
     * @param string $severity Nível de severidade
     * @param string $title Título do alerta
     * @param string $description Descrição do alerta
     * @param string $component Componente que gerou o alerta
     */
    public function __construct(
        TenantEntity $tenant,
        string $alertType,
        string $severity,
        string $title,
        string $description,
        string $component,
    ) {
        $this->tenant          = $tenant;
        $this->alertType       = $alertType;
        $this->severity        = strtolower( $severity );
        $this->title           = $title;
        $this->description     = $description;
        $this->component       = $component;
        $this->status          = 'active';
        $this->occurrenceCount = 1;
        $this->createdAt       = new DateTimeImmutable();
        $this->updatedAt       = new DateTimeImmutable();
        $this->firstOccurrence = new DateTimeImmutable();
        $this->lastOccurrence  = new DateTimeImmutable();
    }

    /**
     * Callback executado antes de atualizar a entidade.
     */
    #[ORM\PreUpdate ]
    public function preUpdate(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Obtém o ID do alerta.
     *
     * @return int|null ID do alerta ou null se ainda não persistido
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Obtém o tenant proprietário do alerta.
     *
     * @return TenantEntity Tenant proprietário
     */
    public function getTenant(): TenantEntity
    {
        return $this->tenant;
    }

    /**
     * Define o tenant proprietário do alerta.
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
     * Obtém o tipo do alerta.
     *
     * @return string Tipo do alerta
     */
    public function getAlertType(): string
    {
        return $this->alertType;
    }

    /**
     * Define o tipo do alerta.
     *
     * @param string $alertType Tipo do alerta
     * @return self
     */
    public function setAlertType( string $alertType ): self
    {
        $this->alertType = $alertType;
        return $this;
    }

    /**
     * Obtém o nível de severidade.
     *
     * @return string Nível de severidade
     */
    public function getSeverity(): string
    {
        return $this->severity;
    }

    /**
     * Define o nível de severidade.
     *
     * @param string $severity Nível de severidade
     * @return self
     */
    public function setSeverity( string $severity ): self
    {
        $this->severity = strtolower( $severity );
        return $this;
    }

    /**
     * Obtém o título do alerta.
     *
     * @return string Título do alerta
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Define o título do alerta.
     *
     * @param string $title Título do alerta
     * @return self
     */
    public function setTitle( string $title ): self
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Obtém a descrição do alerta.
     *
     * @return string Descrição do alerta
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Define a descrição do alerta.
     *
     * @param string $description Descrição do alerta
     * @return self
     */
    public function setDescription( string $description ): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Obtém o componente que gerou o alerta.
     *
     * @return string Componente
     */
    public function getComponent(): string
    {
        return $this->component;
    }

    /**
     * Define o componente que gerou o alerta.
     *
     * @param string $component Componente
     * @return self
     */
    public function setComponent( string $component ): self
    {
        $this->component = $component;
        return $this;
    }

    /**
     * Obtém o endpoint relacionado ao alerta.
     *
     * @return string|null Endpoint ou null se não aplicável
     */
    public function getEndpoint(): ?string
    {
        return $this->endpoint;
    }

    /**
     * Define o endpoint relacionado ao alerta.
     *
     * @param string|null $endpoint Endpoint
     * @return self
     */
    public function setEndpoint( ?string $endpoint ): self
    {
        $this->endpoint = $endpoint;
        return $this;
    }

    /**
     * Obtém o método HTTP relacionado ao alerta.
     *
     * @return string|null Método HTTP ou null se não aplicável
     */
    public function getMethod(): ?string
    {
        return $this->method;
    }

    /**
     * Define o método HTTP relacionado ao alerta.
     *
     * @param string|null $method Método HTTP
     * @return self
     */
    public function setMethod( ?string $method ): self
    {
        $this->method = $method ? strtoupper( $method ) : null;
        return $this;
    }

    /**
     * Obtém o valor atual da métrica.
     *
     * @return float|null Valor atual ou null se não aplicável
     */
    public function getCurrentValue(): ?float
    {
        return $this->currentValue;
    }

    /**
     * Define o valor atual da métrica.
     *
     * @param float|null $currentValue Valor atual
     * @return self
     */
    public function setCurrentValue( ?float $currentValue ): self
    {
        $this->currentValue = $currentValue;
        return $this;
    }

    /**
     * Obtém o valor limite que foi excedido.
     *
     * @return float|null Valor limite ou null se não aplicável
     */
    public function getThresholdValue(): ?float
    {
        return $this->thresholdValue;
    }

    /**
     * Define o valor limite que foi excedido.
     *
     * @param float|null $thresholdValue Valor limite
     * @return self
     */
    public function setThresholdValue( ?float $thresholdValue ): self
    {
        $this->thresholdValue = $thresholdValue;
        return $this;
    }

    /**
     * Obtém a unidade de medida da métrica.
     *
     * @return string|null Unidade de medida ou null se não aplicável
     */
    public function getUnit(): ?string
    {
        return $this->unit;
    }

    /**
     * Define a unidade de medida da métrica.
     *
     * @param string|null $unit Unidade de medida
     * @return self
     */
    public function setUnit( ?string $unit ): self
    {
        $this->unit = $unit;
        return $this;
    }

    /**
     * Obtém os dados adicionais em formato array.
     *
     * @return array|null Dados adicionais ou null se não disponíveis
     */
    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    /**
     * Define os dados adicionais.
     *
     * @param array|null $metadata Dados adicionais
     * @return self
     */
    public function setMetadata( ?array $metadata ): self
    {
        $this->metadata = $metadata;
        return $this;
    }

    /**
     * Adiciona um item aos dados adicionais.
     *
     * @param string $key Chave do item
     * @param mixed $value Valor do item
     * @return self
     */
    public function addMetadata( string $key, mixed $value ): self
    {
        if ( $this->metadata === null ) {
            $this->metadata = [];
        }
        $this->metadata[ $key ] = $value;
        return $this;
    }

    /**
     * Obtém o status do alerta.
     *
     * @return string Status do alerta
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Define o status do alerta.
     *
     * @param string $status Status do alerta
     * @return self
     */
    public function setStatus( string $status ): self
    {
        $this->status = strtolower( $status );
        return $this;
    }

    /**
     * Obtém o usuário que reconheceu o alerta.
     *
     * @return UserEntity|null Usuário ou null se não reconhecido
     */
    public function getAcknowledgedBy(): ?UserEntity
    {
        return $this->acknowledgedBy;
    }

    /**
     * Obtém a data de reconhecimento do alerta.
     *
     * @return DateTimeImmutable|null Data de reconhecimento ou null se não reconhecido
     */
    public function getAcknowledgedAt(): ?DateTimeImmutable
    {
        return $this->acknowledgedAt;
    }

    /**
     * Reconhece o alerta.
     *
     * @param UserEntity $user Usuário que reconhece o alerta
     * @return self
     */
    public function acknowledge( UserEntity $user ): self
    {
        $this->acknowledgedBy = $user;
        $this->acknowledgedAt = new DateTimeImmutable();
        $this->status         = 'acknowledged';
        return $this;
    }

    /**
     * Obtém o usuário que resolveu o alerta.
     *
     * @return UserEntity|null Usuário ou null se não resolvido
     */
    public function getResolvedBy(): ?UserEntity
    {
        return $this->resolvedBy;
    }

    /**
     * Obtém a data de resolução do alerta.
     *
     * @return DateTimeImmutable|null Data de resolução ou null se não resolvido
     */
    public function getResolvedAt(): ?DateTimeImmutable
    {
        return $this->resolvedAt;
    }

    /**
     * Obtém as notas de resolução.
     *
     * @return string|null Notas de resolução ou null se não disponíveis
     */
    public function getResolutionNotes(): ?string
    {
        return $this->resolutionNotes;
    }

    /**
     * Resolve o alerta.
     *
     * @param UserEntity $user Usuário que resolve o alerta
     * @param string|null $notes Notas sobre a resolução
     * @return self
     */
    public function resolve( UserEntity $user, ?string $notes = null ): self
    {
        $this->resolvedBy      = $user;
        $this->resolvedAt      = new DateTimeImmutable();
        $this->resolutionNotes = $notes;
        $this->status          = 'resolved';
        return $this;
    }

    /**
     * Obtém o número de ocorrências.
     *
     * @return int Número de ocorrências
     */
    public function getOccurrenceCount(): int
    {
        return $this->occurrenceCount;
    }

    /**
     * Incrementa o contador de ocorrências.
     *
     * @return self
     */
    public function incrementOccurrence(): self
    {
        $this->occurrenceCount++;
        $this->lastOccurrence = new DateTimeImmutable();
        return $this;
    }

    /**
     * Obtém a data da primeira ocorrência.
     *
     * @return DateTimeImmutable Data da primeira ocorrência
     */
    public function getFirstOccurrence(): DateTimeImmutable
    {
        return $this->firstOccurrence;
    }

    /**
     * Obtém a data da última ocorrência.
     *
     * @return DateTimeImmutable Data da última ocorrência
     */
    public function getLastOccurrence(): DateTimeImmutable
    {
        return $this->lastOccurrence;
    }

    /**
     * Obtém a data de criação.
     *
     * @return DateTimeImmutable Data de criação
     */
    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Obtém a data da última atualização.
     *
     * @return DateTimeImmutable Data da última atualização
     */
    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Verifica se o alerta está ativo.
     *
     * @return bool True se ativo, false caso contrário
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Verifica se o alerta foi reconhecido.
     *
     * @return bool True se reconhecido, false caso contrário
     */
    public function isAcknowledged(): bool
    {
        return $this->status === 'acknowledged';
    }

    /**
     * Verifica se o alerta foi resolvido.
     *
     * @return bool True se resolvido, false caso contrário
     */
    public function isResolved(): bool
    {
        return $this->status === 'resolved';
    }

    /**
     * Verifica se o alerta foi ignorado.
     *
     * @return bool True se ignorado, false caso contrário
     */
    public function isIgnored(): bool
    {
        return $this->status === 'ignored';
    }

    /**
     * Verifica se é um alerta crítico.
     *
     * @return bool True se crítico, false caso contrário
     */
    public function isCritical(): bool
    {
        return $this->severity === 'critical';
    }

    /**
     * Verifica se é um alerta de alta prioridade.
     *
     * @return bool True se alta prioridade, false caso contrário
     */
    public function isHighPriority(): bool
    {
        return in_array( $this->severity, [ 'high', 'critical' ] );
    }

    /**
     * Calcula a duração desde a criação até a resolução.
     *
     * @return \DateInterval|null Duração ou null se não resolvido
     */
    public function getResolutionDuration(): ?\DateInterval
    {
        if ( $this->resolvedAt === null ) {
            return null;
        }

        return $this->createdAt->diff( $this->resolvedAt );
    }

    /**
     * Calcula a duração desde a criação até o reconhecimento.
     *
     * @return \DateInterval|null Duração ou null se não reconhecido
     */
    public function getAcknowledgmentDuration(): ?\DateInterval
    {
        if ( $this->acknowledgedAt === null ) {
            return null;
        }

        return $this->createdAt->diff( $this->acknowledgedAt );
    }

    /**
     * Obtém a porcentagem de excesso do limite (se aplicável).
     *
     * @return float|null Porcentagem de excesso ou null se não aplicável
     */
    public function getThresholdExcessPercentage(): ?float
    {
        if ( $this->currentValue === null || $this->thresholdValue === null || $this->thresholdValue == 0 ) {
            return null;
        }

        return ( ( $this->currentValue - $this->thresholdValue ) / $this->thresholdValue ) * 100;
    }

    /**
     * Converte a entidade para array para serialização.
     *
     * @return array Representação em array da entidade
     */
    public function toArray(): array
    {
        return [ 
            'id'                          => $this->id,
            'tenant_id'                   => $this->tenant->getId(),
            'alert_type'                  => $this->alertType,
            'severity'                    => $this->severity,
            'title'                       => $this->title,
            'description'                 => $this->description,
            'component'                   => $this->component,
            'endpoint'                    => $this->endpoint,
            'method'                      => $this->method,
            'current_value'               => $this->currentValue,
            'threshold_value'             => $this->thresholdValue,
            'unit'                        => $this->unit,
            'metadata'                    => $this->metadata,
            'status'                      => $this->status,
            'acknowledged_by'             => $this->acknowledgedBy?->getId(),
            'acknowledged_at'             => $this->acknowledgedAt?->format( 'Y-m-d H:i:s' ),
            'resolved_by'                 => $this->resolvedBy?->getId(),
            'resolved_at'                 => $this->resolvedAt?->format( 'Y-m-d H:i:s' ),
            'resolution_notes'            => $this->resolutionNotes,
            'occurrence_count'            => $this->occurrenceCount,
            'first_occurrence'            => $this->firstOccurrence->format( 'Y-m-d H:i:s' ),
            'last_occurrence'             => $this->lastOccurrence->format( 'Y-m-d H:i:s' ),
            'created_at'                  => $this->createdAt->format( 'Y-m-d H:i:s' ),
            'updated_at'                  => $this->updatedAt->format( 'Y-m-d H:i:s' ),
            'is_active'                   => $this->isActive(),
            'is_acknowledged'             => $this->isAcknowledged(),
            'is_resolved'                 => $this->isResolved(),
            'is_critical'                 => $this->isCritical(),
            'is_high_priority'            => $this->isHighPriority(),
            'threshold_excess_percentage' => $this->getThresholdExcessPercentage()
        ];
    }

}
