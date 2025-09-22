<?php

namespace design_patern\design_pattern_with_tenant\entities;

use app\interfaces\EntityORMInterface;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

/**
 * Padrão de Entity ORM WithTenant - Easy Budget
 *
 * PADRÕES IMPLEMENTADOS:
 * ✅ Implementa EntityORMInterface - Contrato padrão do projeto
 * ✅ Implementa JsonSerializable - Para logs e APIs
 * ✅ Anotações Doctrine ORM - Mapeamento objeto-relacional
 * ✅ Campo tenant_id obrigatório - Controle multi-tenant
 * ✅ Timestamps automáticos - created_at e updated_at
 * ✅ Métodos getter/setter padronizados - Encapsulamento
 * ✅ Validação de dados com tenant - Regras de negócio multi-tenant
 * ✅ Comentários em português brasileiro - Padrão do projeto
 * ✅ Índices compostos incluindo tenant_id - Performance otimizada
 *
 * BENEFÍCIOS:
 * - Isolamento completo de dados entre tenants
 * - Mapeamento automático com banco de dados
 * - Serialização para logs de auditoria
 * - Timestamps controlados pelo Doctrine
 * - Validação de integridade dos dados por tenant
 * - Performance otimizada com índices compostos
 *
 * @ORM\Entity(repositoryClass="design_patern\design_pattern_with_tenant\repositories\DesignPatternWithTenantRepository")
 * @ORM\Table(
 *     name="design_patterns_with_tenant",
 *     indexes={
 *         @ORM\Index(name="idx_tenant_name", columns={"tenant_id", "name"}),
 *         @ORM\Index(name="idx_tenant_slug", columns={"tenant_id", "slug"}),
 *         @ORM\Index(name="idx_tenant_active", columns={"tenant_id", "active"}),
 *         @ORM\Index(name="idx_tenant_created", columns={"tenant_id", "created_at"})
 *     },
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="unique_tenant_slug", columns={"tenant_id", "slug"})
 *     }
 * )
 * @ORM\HasLifecycleCallbacks
 */
class DesignPatternWithTenantEntity implements EntityORMInterface
{
    /**
     * Identificador único da entidade.
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer", options={"unsigned"=true})
     */
    private ?int $id = null;

    /**
     * ID do tenant - Campo obrigatório para controle multi-tenant.
     *
     * @ORM\Column(type="integer", nullable=false, options={"unsigned"=true})
     */
    private int $tenant_id;

    /**
     * Nome da entidade.
     *
     * @ORM\Column(type="string", length=100, nullable=false)
     */
    private string $name;

    /**
     * Slug da entidade (URL amigável) - Único por tenant.
     *
     * @ORM\Column(type="string", length=100, nullable=false)
     */
    private string $slug;

    /**
     * Descrição da entidade.
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $description = null;

    /**
     * Status da entidade.
     *
     * @ORM\Column(type="boolean", nullable=false, options={"default"=true})
     */
    private bool $active = true;

    /**
     * Data de criação da entidade.
     *
     * @ORM\Column(type="datetime", nullable=false)
     */
    private DateTime $createdAt;

    /**
     * Data de atualização da entidade.
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?DateTime $updatedAt = null;

    // ==================================================================
    // MÉTODOS DE CICLO DE VIDA (DOCTRINE LIFECYCLE CALLBACKS)
    // ==================================================================

    /**
     * Executado antes de persistir a entidade no banco.
     * Define automaticamente created_at.
     *
     * @ORM\PrePersist
     */
    public function prePersist(): void
    {
        $this->createdAt = new DateTime();
    }

    /**
     * Executado antes de atualizar a entidade no banco.
     * Atualiza automaticamente updated_at.
     *
     * @ORM\PreUpdate
     */
    public function preUpdate(): void
    {
        $this->updatedAt = new DateTime();
    }

    // ==================================================================
    // GETTERS E SETTERS
    // ==================================================================

    /**
     * Obtém o ID da entidade.
     *
     * @return int|null ID da entidade
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Obtém o ID do tenant.
     *
     * @return int ID do tenant
     */
    public function getTenantId(): int
    {
        return $this->tenant_id;
    }

    /**
     * Define o ID do tenant.
     *
     * @param int $tenant_id ID do tenant
     * @return self
     * @throws \InvalidArgumentException Se o tenant_id for inválido
     */
    public function setTenantId( int $tenant_id ): self
    {
        if ( $tenant_id <= 0 ) {
            throw new \InvalidArgumentException( 'O ID do tenant deve ser um número positivo.' );
        }

        $this->tenant_id = $tenant_id;
        return $this;
    }

    /**
     * Obtém o nome da entidade.
     *
     * @return string Nome da entidade
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Define o nome da entidade.
     *
     * @param string $name Nome da entidade (máx. 100 caracteres)
     * @return self
     * @throws \InvalidArgumentException Se o nome for inválido
     */
    public function setName( string $name ): self
    {
        $name = trim( $name );

        if ( empty( $name ) ) {
            throw new \InvalidArgumentException( 'O nome da entidade é obrigatório.' );
        }

        if ( strlen( $name ) > 100 ) {
            throw new \InvalidArgumentException( 'O nome da entidade deve ter no máximo 100 caracteres.' );
        }

        $this->name = $name;
        return $this;
    }

    /**
     * Obtém o slug da entidade.
     *
     * @return string Slug da entidade
     */
    public function getSlug(): string
    {
        return $this->slug;
    }

    /**
     * Define o slug da entidade.
     *
     * @param string $slug Slug da entidade (máx. 100 caracteres, formato válido)
     * @return self
     * @throws \InvalidArgumentException Se o slug for inválido
     */
    public function setSlug( string $slug ): self
    {
        $slug = trim( $slug );

        if ( empty( $slug ) ) {
            throw new \InvalidArgumentException( 'O slug da entidade é obrigatório.' );
        }

        if ( strlen( $slug ) > 100 ) {
            throw new \InvalidArgumentException( 'O slug da entidade deve ter no máximo 100 caracteres.' );
        }

        if ( !preg_match( '/^[a-z0-9-]+$/', $slug ) ) {
            throw new \InvalidArgumentException( 'O slug deve conter apenas letras minúsculas, números e hífens.' );
        }

        $this->slug = $slug;
        return $this;
    }

    /**
     * Obtém a descrição da entidade.
     *
     * @return string|null Descrição da entidade
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Define a descrição da entidade.
     *
     * @param string|null $description Descrição da entidade
     * @return self
     */
    public function setDescription( ?string $description ): self
    {
        $this->description = $description ? trim( $description ) : null;
        return $this;
    }

    /**
     * Verifica se a entidade está ativa.
     *
     * @return bool Status da entidade
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * Define o status da entidade.
     *
     * @param bool $active Status da entidade
     * @return self
     */
    public function setActive( bool $active ): self
    {
        $this->active = $active;
        return $this;
    }

    /**
     * Obtém a data de criação.
     *
     * @return DateTime Data de criação
     */
    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    /**
     * Obtém a data de atualização.
     *
     * @return DateTime|null Data de atualização
     */
    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }

    // ==================================================================
    // MÉTODOS DE VALIDAÇÃO MULTI-TENANT
    // ==================================================================

    /**
     * Verifica se a entidade pertence ao tenant especificado.
     *
     * IMPORTANTE: Método de segurança para validação multi-tenant.
     *
     * @param int $tenant_id ID do tenant a verificar
     * @return bool true se pertence ao tenant, false caso contrário
     */
    public function belongsToTenant( int $tenant_id ): bool
    {
        return $this->tenant_id === $tenant_id;
    }

    /**
     * Valida acesso ao tenant antes de operações sensíveis.
     *
     * @param int $tenant_id ID do tenant a validar
     * @throws \InvalidArgumentException Se o acesso for negado
     */
    public function validateTenantAccess( int $tenant_id ): void
    {
        if ( !$this->belongsToTenant( $tenant_id ) ) {
            throw new \InvalidArgumentException(
                "Acesso negado: Entidade pertence ao tenant {$this->tenant_id}, mas foi solicitado acesso para tenant {$tenant_id}.",
            );
        }
    }

    // ==================================================================
    // MÉTODOS DE SERIALIZAÇÃO
    // ==================================================================

    /**
     * Serializa a entidade para JSON.
     *
     * IMPORTANTE: Usado para logs de auditoria e APIs.
     * Inclui tenant_id para rastreabilidade.
     *
     * @return array<string, mixed> Dados serializados
     */
    public function jsonSerialize(): array
    {
        return [ 
            'id'          => $this->id,
            'tenant_id'   => $this->tenant_id,
            'name'        => $this->name,
            'slug'        => $this->slug,
            'description' => $this->description,
            'active'      => $this->active,
            'created_at'  => $this->createdAt->format( 'Y-m-d H:i:s' ),
            'updated_at'  => $this->updatedAt?->format( 'Y-m-d H:i:s' ),
        ];
    }

    /**
     * Converte a entidade para array.
     *
     * Método auxiliar para compatibilidade.
     *
     * @return array<string, mixed> Dados da entidade
     */
    public function toArray(): array
    {
        return $this->jsonSerialize();
    }

    /**
     * Representação em string da entidade.
     *
     * @return string Nome da entidade com identificação do tenant
     */
    public function __toString(): string
    {
        return "[Tenant {$this->tenant_id}] {$this->name}";
    }

    // ==================================================================
    // MÉTODOS DE FÁBRICA (FACTORY METHODS)
    // ==================================================================

    /**
     * Método create padrão da interface (implementação obrigatória).
     *
     * @param array<string, mixed> $properties Propriedades da entidade
     * @return static Nova instância da entidade
     * @throws \InvalidArgumentException Se tenant_id não for fornecido
     */
    public static function create( array $properties ): static
    {
        if ( !isset( $properties[ 'tenant_id' ] ) ) {
            throw new \InvalidArgumentException( 'O campo tenant_id é obrigatório para entidades WithTenant.' );
        }

        return self::createWithTenant( $properties, $properties[ 'tenant_id' ] );
    }

    /**
     * Cria uma nova instância da entidade com dados fornecidos.
     *
     * Método estático para facilitar criação com tenant_id obrigatório.
     *
     * @param array<string, mixed> $data Dados para criar a entidade
     * @param int $tenant_id ID do tenant (obrigatório)
     * @return self Nova instância da entidade
     */
    public static function createWithTenant( array $data, int $tenant_id ): self
    {
        $entity = new self();
        $entity->setTenantId( $tenant_id );

        if ( isset( $data[ 'name' ] ) ) {
            $entity->setName( $data[ 'name' ] );
        }

        if ( isset( $data[ 'slug' ] ) ) {
            $entity->setSlug( $data[ 'slug' ] );
        }

        if ( isset( $data[ 'description' ] ) ) {
            $entity->setDescription( $data[ 'description' ] );
        }

        if ( isset( $data[ 'active' ] ) ) {
            $entity->setActive( (bool) $data[ 'active' ] );
        }

        return $entity;
    }

    /**
     * Atualiza a entidade com dados fornecidos.
     *
     * @param array<string, mixed> $data Dados para atualizar
     * @param int $tenant_id ID do tenant para validação
     * @return self A própria instância para encadeamento
     */
    public function updateFrom( array $data, int $tenant_id ): self
    {
        // Validar acesso ao tenant
        $this->validateTenantAccess( $tenant_id );

        if ( isset( $data[ 'name' ] ) ) {
            $this->setName( $data[ 'name' ] );
        }

        if ( isset( $data[ 'slug' ] ) ) {
            $this->setSlug( $data[ 'slug' ] );
        }

        if ( isset( $data[ 'description' ] ) ) {
            $this->setDescription( $data[ 'description' ] );
        }

        if ( isset( $data[ 'active' ] ) ) {
            $this->setActive( (bool) $data[ 'active' ] );
        }

        return $this;
    }

}

/*
EXEMPLOS DE USO:

// 1. Criação via factory method com tenant
$entity = DesignPatternWithTenantEntity::createWithTenant([
    'name' => 'Exemplo',
    'slug' => 'exemplo',
    'description' => 'Descrição do exemplo',
    'active' => true
], $tenant_id);

// 2. Validação de acesso ao tenant
try {
    $entity->validateTenantAccess($tenant_id);
    // Operação autorizada
} catch (\InvalidArgumentException $e) {
    // Acesso negado - log de segurança
}

// 3. Atualização com validação de tenant
$entity->updateFrom([
    'name' => 'Novo Nome',
    'description' => 'Nova descrição'
], $tenant_id);

// 4. Verificação de propriedade
if ($entity->belongsToTenant($tenant_id)) {
    // Entidade pertence ao tenant
}

// 5. Serialização para logs com tenant_id
$logData = $entity->jsonSerialize();
// Resultado inclui tenant_id para auditoria

BENEFÍCIOS DO PADRÃO WITHTENANT:

✅ SEGURANÇA MULTI-TENANT
- Campo tenant_id obrigatório
- Validação de acesso em operações sensíveis
- Índices otimizados por tenant
- Constraints únicas por tenant

✅ AUDITORIA COMPLETA
- tenant_id sempre presente nos logs
- Rastreabilidade por cliente
- Isolamento de dados garantido

✅ PERFORMANCE OTIMIZADA
- Índices compostos com tenant_id
- Consultas eficientes por tenant
- Constraints adequadas

✅ FACILIDADE DE USO
- Factory methods com tenant obrigatório
- Métodos de validação integrados
- Interface intuitiva com segurança
*/
