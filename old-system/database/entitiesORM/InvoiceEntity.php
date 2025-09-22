<?php

declare(strict_types=1);

namespace app\database\entitiesORM;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity ]
#[ORM\Table(name: 'invoices', uniqueConstraints: [ new \Doctrine\ORM\Mapping\UniqueConstraint( name: 'unique_tenant_code', columns: [ 'tenant_id', 'code' ] ) ]) ]
class InvoiceEntity
{
    #[ORM\Id ]
    #[ORM\GeneratedValue ]
    #[ORM\Column(type: 'integer') ]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255, nullable: false) ]
    private string $code;

    #[ORM\Column(type: 'text', nullable: true) ]
    private ?string $description = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true) ]
    private ?float $subtotal = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: false) ]
    private float $total;

    #[ORM\Column(type: 'datetime_immutable', nullable: true) ]
    private ?\DateTimeImmutable $dueDate = null;

    #[ORM\ManyToOne(targetEntity: CustomerEntity::class) ]
    #[ORM\JoinColumn(name: 'customer_id', referencedColumnName: 'id', nullable: false) ]
    private ?CustomerEntity $customer = null;

    #[ORM\ManyToOne(targetEntity: InvoiceStatusesEntity::class) ]
    #[ORM\JoinColumn(name: 'invoice_statuses_id', referencedColumnName: 'id', nullable: false) ]
    private ?InvoiceStatusesEntity $invoiceStatuses = null;

    #[ORM\Column(name: 'tenant_id', type: 'integer', nullable: false) ]
    private int $tenantId;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable') ]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable', nullable: true) ]
    private ?\DateTimeImmutable $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode( string $code ): self
    {
        $this->code = $code;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription( ?string $description ): self
    {
        $this->description = $description;
        return $this;
    }

    public function getSubtotal(): ?float
    {
        return $this->subtotal;
    }

    public function setSubtotal( ?float $subtotal ): self
    {
        $this->subtotal = $subtotal;
        return $this;
    }

    public function getTotal(): float
    {
        return $this->total;
    }

    public function setTotal( float $total ): self
    {
        $this->total = $total;
        return $this;
    }

    public function getDueDate(): ?\DateTimeImmutable
    {
        return $this->dueDate;
    }

    public function setDueDate( ?\DateTimeImmutable $dueDate ): self
    {
        $this->dueDate = $dueDate;
        return $this;
    }

    public function getCustomer(): ?CustomerEntity
    {
        return $this->customer;
    }

    public function getCustomerId(): ?int
    {
        return $this->customer?->getId();
    }

    public function setCustomer( CustomerEntity $customer ): self
    {
        $this->customer = $customer;
        return $this;
    }

    public function getInvoiceStatusesId(): ?int
    {
        return $this->invoiceStatuses?->getId();
    }

    public function setInvoiceStatuses( InvoiceStatusesEntity $invoiceStatuses ): self
    {
        $this->invoiceStatuses = $invoiceStatuses;
        return $this;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function setTenantId( int $tenantId ): self
    {
        $this->tenantId = $tenantId;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt( \DateTimeImmutable $createdAt ): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt( ?\DateTimeImmutable $updatedAt ): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

}