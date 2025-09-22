<?php

namespace design_patern\design_pattern_no_tenant\entities;

use app\interfaces\EntityORMInterface;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

/**
 * Padrão de Entity ORM - Easy Budget
 *
 * PADRÕES IMPLEMENTADOS:
 * ✅ Implementa EntityORMInterface - Contrato padrão do projeto
 * ✅ Implementa JsonSerializable - Para logs e APIs
 * ✅ Anotações Doctrine ORM - Mapeamento objeto-relacional
 * ✅ Timestamps automáticos - created_at e updated_at
 * ✅ Métodos getter/setter padronizados - Encapsulamento
 * ✅ Validação de dados - Regras de negócio
 * ✅ Comentários em português brasileiro - Padrão do projeto
 *
 * BENEFÍCIOS:
 * - Mapeamento automático com banco de dados
 * - Serialização para logs de auditoria
 * - Timestamps controlados pelo Doctrine
 * - Validação de integridade dos dados
 *
 * @ORM\Entity(repositoryClass="app\database\repositories\ExampleRepository")
 * @ORM\Table(name="examples")
 * @ORM\HasLifecycleCallbacks
 */
class DesignPatternNoTenantEntity implements EntityORMInterface
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
     * Nome da entidade.
     *
     * @ORM\Column(type="string", length=100, nullable=false)
     */
    private string $name;

    /**
     * Slug da entidade (URL amigável).
     *
     * @ORM\Column(type="string", length=100, nullable=false, unique=true)
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
     * Define automatically created_at.
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
    // MÉTODOS DE SERIALIZAÇÃO
    // ==================================================================

    /**
     * Serializa a entidade para JSON.
     *
     * IMPORTANTE: Usado para logs de auditoria e APIs.
     *
     * @return array<string, mixed> Dados serializados
     */
    public function jsonSerialize(): array
    {
        return [ 
            'id'          => $this->id,
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
     * @return string Nome da entidade
     */
    public function __toString(): string
    {
        return $this->name;
    }

    // ==================================================================
    // MÉTODOS DE FÁBRICA (FACTORY METHODS)
    // ==================================================================

    /**
     * Cria uma nova instância da entidade com dados fornecidos.
     *
     * Método estático para facilitar criação.
     *
     * @param array<string, mixed> $data Dados para criar a entidade
     * @return self Nova instância da entidade
     */
    public static function create( array $data ): self
    {
        $entity = new self();

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
     * @return self A própria instância para encadeamento
     */
    public function updateFrom( array $data ): self
    {
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

// 1. Criação via construtor tradicional
$entity = new DesignPatternEntity();
$entity->setName('Exemplo');
$entity->setSlug('exemplo');
$entity->setDescription('Descrição do exemplo');

// 2. Criação via factory method
$entity = DesignPatternEntity::create([
    'name' => 'Exemplo',
    'slug' => 'exemplo',
    'description' => 'Descrição do exemplo',
    'active' => true
]);

// 3. Atualização via updateFrom
$entity->updateFrom([
    'name' => 'Novo Nome',
    'description' => 'Nova descrição'
]);

// 4. Serialização para logs
$logData = $entity->jsonSerialize();
echo json_encode($logData);

// 5. Uso com repository
$result = $repository->save($entity);
if ($result !== false) {
    echo "Entidade salva com ID: {$result->getId()}";
    echo "Criado em: {$result->getCreatedAt()->format('Y-m-d H:i:s')}";
}

BENEFÍCIOS DO PADRÃO:

✅ MAPEAMENTO AUTOMÁTICO
- Doctrine cuida do relacionamento objeto-relacional
- Timestamps gerenciados automaticamente
- Validações no nível da entidade

✅ SERIALIZAÇÃO CONSISTENTE
- JsonSerializable para APIs e logs
- toArray() para compatibilidade
- __toString() para debug

✅ VALIDAÇÃO DE DADOS
- Regras de negócio encapsuladas
- Exceções claras para dados inválidos
- Consistência de dados garantida

✅ FACILIDADE DE USO
- Factory methods para criação rápida
- Métodos de atualização em lote
- Interface intuitiva

✅ AUDITORIA
- Dados estruturados para logs
- Timestamps automáticos
- Compatibilidade com ActivityService
*/
