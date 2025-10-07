<?php

namespace app\database\models;

use app\database\entities\PaymentMercadoPagoInvoicesEntity;
use app\database\Model;
use core\dbal\Entity;
use Exception;
use RuntimeException;

class PaymentMercadoPagoInvoices extends Model
{
    /**
     * The name of the table associated with the model.
     *
     * @var string
     */
    protected string $table = 'payment_mercado_pago_invoices';

    /**
     * Creates a new PaymentsMercadoPagoEntity instance from the provided data array.
     *
     * @param array $data The data to use for creating the entity.
     * @return Entity The created PaymentsMercadoPagoEntity instance.
     */
    protected static function createEntity(array $data): Entity
    {
        return PaymentMercadoPagoInvoicesEntity::create($data);
    }

    public function getPaymentId(string $payment_id, int $tenant_id): PaymentMercadoPagoInvoicesEntity|Entity
    {
        try {
            return $this->findBy([
                'payment_id' => $payment_id,
                'tenant_id' => $tenant_id,
            ]);
        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar o pagamento do usuário, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }
    }

    public function getPaymentsByInvoice(int $invoice_id, int $tenant_id): PaymentMercadoPagoInvoicesEntity|array|Entity
    {
        try {
            return $this->findBy([
                'invoice_id' => $invoice_id,
                'tenant_id' => $tenant_id,
            ]);
        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar o pagamento do usuário, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }
    }

}
