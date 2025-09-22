<?php

namespace app\database\entitiesORM;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entidade que representa os pagamentos de faturas via Mercado Pago.
 *
 * @ORM\Entity
 * @ORM\Table(name="payment_mercado_pago_invoices")
 * @ORM\HasLifecycleCallbacks
 */
#[ORM\Entity ]
#[ORM\Table(name: 'payment_mercado_pago_invoices') ]
#[ORM\HasLifecycleCallbacks ]
class PaymentMercadoPagoInvoicesEntity extends AbstractEntityORM
{
    /**
     * @var int ID do registro.
     */
    #[ORM\Id ]
    #[ORM\Column(type: 'integer') ]
    #[ORM\GeneratedValue ]
    private ?int $id = null;

    /**
     * @var TenantEntity Inquilino associado ao pagamento.
     */
    #[ORM\ManyToOne(targetEntity: TenantEntity::class) ]
    #[ORM\JoinColumn(name: 'tenant_id', referencedColumnName: 'id', nullable: false) ]
    private TenantEntity $tenant;

    /**
     * @var InvoiceEntity Fatura associada ao pagamento.
     */
    #[ORM\ManyToOne(targetEntity: InvoiceEntity::class) ]
    #[ORM\JoinColumn(name: 'invoice_id', referencedColumnName: 'id', nullable: false) ]
    private InvoiceEntity $invoice;

    /**
     * @var int ID do pagamento no Mercado Pago.
     */
    #[ORM\Column(name: 'payment_id', type: 'integer') ]
    private int $paymentId;

    /**
     * @var string Status do pagamento.
     */
    #[ORM\Column(type: 'string') ]
    private string $status;

    /**
     * @var string Método de pagamento utilizado.
     */
    #[ORM\Column(name: 'payment_method', type: 'string') ]
    private string $paymentMethod;

    /**
     * @var float Valor da transação.
     */
    #[ORM\Column(name: 'transaction_amount', type: 'float') ]
    private float $transactionAmount;

    /**
     * @var DateTimeImmutable Data da transação.
     */
    #[ORM\Column(name: 'transaction_date', type: 'datetime_immutable') ]
    private DateTimeImmutable $transactionDate;

    /**
     * @var DateTimeImmutable Data de criação do registro.
     */
    #[ORM\Column(name: 'created_at', type: 'datetime_immutable') ]
    private DateTimeImmutable $createdAt;

    /**
     * @var DateTimeImmutable Data da última atualização do registro.
     */
    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable') ]
    private DateTimeImmutable $updatedAt;

    /**
     * Atualiza a data de criação antes da persistência inicial.
     */
    #[ORM\PrePersist ]
    public function onPrePersist(): void
    {
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Atualiza a data de modificação antes de cada atualização.
     */
    #[ORM\PreUpdate ]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return TenantEntity
     */
    public function getTenant(): TenantEntity
    {
        return $this->tenant;
    }

    /**
     * @param TenantEntity $tenant
     * @return self
     */
    public function setTenant( TenantEntity $tenant ): self
    {
        $this->tenant = $tenant;
        return $this;
    }

    /**
     * @return InvoiceEntity
     */
    public function getInvoice(): InvoiceEntity
    {
        return $this->invoice;
    }

    /**
     * @param InvoiceEntity $invoice
     * @return self
     */
    public function setInvoice( InvoiceEntity $invoice ): self
    {
        $this->invoice = $invoice;
        return $this;
    }

    /**
     * @return int
     */
    public function getPaymentId(): int
    {
        return $this->paymentId;
    }

    /**
     * @param int $paymentId
     * @return self
     */
    public function setPaymentId( int $paymentId ): self
    {
        $this->paymentId = $paymentId;
        return $this;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     * @return self
     */
    public function setStatus( string $status ): self
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return string
     */
    public function getPaymentMethod(): string
    {
        return $this->paymentMethod;
    }

    /**
     * @param string $paymentMethod
     * @return self
     */
    public function setPaymentMethod( string $paymentMethod ): self
    {
        $this->paymentMethod = $paymentMethod;
        return $this;
    }

    /**
     * @return float
     */
    public function getTransactionAmount(): float
    {
        return $this->transactionAmount;
    }

    /**
     * @param float $transactionAmount
     * @return self
     */
    public function setTransactionAmount( float $transactionAmount ): self
    {
        $this->transactionAmount = $transactionAmount;
        return $this;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getTransactionDate(): DateTimeImmutable
    {
        return $this->transactionDate;
    }

    /**
     * @param DateTimeImmutable $transactionDate
     * @return self
     */
    public function setTransactionDate( DateTimeImmutable $transactionDate ): self
    {
        $this->transactionDate = $transactionDate;
        return $this;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

}
