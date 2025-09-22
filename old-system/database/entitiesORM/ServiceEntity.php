<?php

namespace app\database\entitiesORM;

use app\database\entitiesORM\AbstractEntityORM;
use app\database\entitiesORM\BudgetEntity;
use app\database\entitiesORM\CategoryEntity;
use app\database\entitiesORM\ServiceStatusesEntity;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * Representa a entidade Serviço no sistema.
 *
 * Esta classe define o mapeamento da tabela `services` para um objeto PHP,
 * utilizando o Doctrine ORM. Ela inclui os relacionamentos com Orçamento,
 * Categoria, Status do Serviço e Itens de Serviço.
 *
 * @package app\database\entitiesORM
 */
#[ORM\Entity(repositoryClass: \app\database\repositories\ServiceRepository::class) ]
#[ORM\Table(name: 'services') ]
#[ORM\HasLifecycleCallbacks ]
class ServiceEntity extends AbstractEntityORM
{
    /**
     * O identificador único do serviço.
     *
     * @var int|null
     */
    #[ORM\Id ]
    #[ORM\GeneratedValue(strategy: 'AUTO') ]
    #[ORM\Column(type: 'integer') ]
    private ?int $id = null;

    /**
     * O código único do serviço.
     *
     * @var string|null
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true) ]
    private ?string $code = null;

    /**
     * A descrição do serviço.
     *
     * @var string|null
     */
    #[ORM\Column(type: 'text', nullable: true) ]
    private ?string $description = null;

    /**
     * O valor do serviço.
     *
     * @var float|null
     */
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true) ]
    private ?float $total = null;

    /**
     * O desconto aplicado ao serviço.
     *
     * @var float|null
     */
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true) ]
    private ?float $discount = 0.0;

    /**
     * O hash para verificação do PDF.
     *
     * @var string|null
     */
    #[ORM\Column(name: 'pdf_verification_hash', type: 'string', length: 255, nullable: true) ]
    private ?string $pdfVerificationHash = null;

    /**
     * Data de criação do registro.
     *
     * @var DateTimeImmutable|null
     */
    #[ORM\Column(type: 'datetime_immutable') ]
    private ?DateTimeImmutable $createdAt = null;

    /**
     * Data de atualização do registro.
     *
     * @var DateTimeImmutable|null
     */
    #[ORM\Column(type: 'datetime_immutable', nullable: true) ]
    private ?DateTimeImmutable $updatedAt = null;

    /**
     * O orçamento ao qual este serviço pertence.
     *
     * @var BudgetEntity|null
     */
    #[ORM\ManyToOne(targetEntity: BudgetEntity::class, inversedBy: 'services', fetch: 'LAZY') ]
    #[ORM\JoinColumn(name: 'budget_id', referencedColumnName: 'id', nullable: true) ]
    private ?BudgetEntity $budget = null;

    /**
     * A categoria do serviço.
     *
     * @var CategoryEntity|null
     */
    #[ORM\ManyToOne(targetEntity: CategoryEntity::class) ]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id', nullable: true) ]
    private ?CategoryEntity $category = null;

    /**
     * O status do serviço.
     *
     * @var ServiceStatusesEntity|null
     */
    #[ORM\ManyToOne(targetEntity: ServiceStatusesEntity::class) ]
    #[ORM\JoinColumn(name: 'service_statuses_id', referencedColumnName: 'id', nullable: true) ]
    private ?ServiceStatusesEntity $serviceStatus = null;

    /**
     * Observações sobre o serviço.
     *
     * @var string|null
     */
    #[ORM\Column(type: 'text', nullable: true) ]
    private ?string $observation = null;

    /**
     * Data de vencimento do serviço.
     *
     * @var DateTimeInterface|null
     */
    #[ORM\Column(type: 'datetime', nullable: true) ]
    private ?DateTimeInterface $dueDate = null;

    /**
     * ID do tenant (inquilino) ao qual o serviço pertence.
     *
     * @var int|null
     */
    #[ORM\Column(name: 'tenant_id', type: 'integer', nullable: true) ]
    private ?int $tenantId = null;

    // Getters e Setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode( ?string $code ): static
    {
        $this->code = $code;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription( ?string $description ): static
    {
        $this->description = $description;
        return $this;
    }

    public function getTotal(): ?float
    {
        return $this->total;
    }

    public function setTotal( ?float $total ): static
    {
        $this->total = $total;
        return $this;
    }

    public function getDiscount(): ?float
    {
        return $this->discount;
    }

    public function setDiscount( ?float $discount ): static
    {
        $this->discount = $discount;
        return $this;
    }

    public function getPdfVerificationHash(): ?string
    {
        return $this->pdfVerificationHash;
    }

    public function setPdfVerificationHash( ?string $pdfVerificationHash ): static
    {
        $this->pdfVerificationHash = $pdfVerificationHash;
        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt( DateTimeImmutable $createdAt ): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt( ?DateTimeImmutable $updatedAt ): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * Callback executado antes de persistir a entidade.
     */
    #[ORM\PrePersist ]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new DateTimeImmutable();
    }

    /**
     * Callback executado antes de atualizar a entidade.
     */
    #[ORM\PreUpdate ]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getBudget(): ?BudgetEntity
    {
        return $this->budget;
    }

    public function setBudget( ?BudgetEntity $budget ): static
    {
        $this->budget = $budget;
        return $this;
    }

    public function getBudgetId(): ?int
    {
        return $this->budget?->getId();
    }

    public function setBudgetId( ?int $budgetId ): static
    {
        // Este método é mantido para compatibilidade, mas o ideal é usar setBudget()
        // A implementação real deveria buscar a entidade BudgetEntity pelo ID
        return $this;
    }

    public function getCategory(): ?CategoryEntity
    {
        return $this->category;
    }

    public function setCategory( ?CategoryEntity $category ): static
    {
        $this->category = $category;
        return $this;
    }

    public function getCategoryId(): ?int
    {
        return $this->category?->getId();
    }

    public function setCategoryId( ?int $categoryId ): static
    {
        // Este método é mantido para compatibilidade, mas o ideal é usar setCategory()
        // A implementação real deveria buscar a entidade CategoryEntity pelo ID
        return $this;
    }

    public function getServiceStatus(): ?ServiceStatusesEntity
    {
        return $this->serviceStatus;
    }

    public function setServiceStatus( ?ServiceStatusesEntity $serviceStatus ): static
    {
        $this->serviceStatus = $serviceStatus;
        return $this;
    }

    public function getServiceStatusId(): ?int
    {
        return $this->serviceStatus?->getId();
    }

    public function setServiceStatusId( ?int $serviceStatusId ): static
    {
        // Este método é mantido para compatibilidade, mas o ideal é usar setServiceStatus()
        // A implementação real deveria buscar a entidade ServiceStatusesEntity pelo ID
        return $this;
    }

    public function getObservation(): ?string
    {
        return $this->observation;
    }

    public function setObservation( ?string $observation ): static
    {
        $this->observation = $observation;
        return $this;
    }

    public function getDueDate(): ?DateTimeInterface
    {
        return $this->dueDate;
    }

    public function setDueDate( ?DateTimeInterface $dueDate ): static
    {
        $this->dueDate = $dueDate;
        return $this;
    }

    public function getTenantId(): ?int
    {
        return $this->tenantId;
    }

    public function setTenantId( ?int $tenantId ): static
    {
        $this->tenantId = $tenantId;
        return $this;
    }

}