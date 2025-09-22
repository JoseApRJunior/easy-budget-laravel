<?php

namespace app\database\models;

use app\database\entitiesORM\PaymentMercadoPagoInvoicesEntity;
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
     * Cria uma nova instância de PaymentMercadoPagoInvoicesEntity a partir dos dados fornecidos.
     *
     * @param array<string, mixed> $data Os dados para criar a entidade.
     * @return Entity A instância criada de PaymentMercadoPagoInvoicesEntity.
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

    /**
     * Busca pagamentos por fatura.
     *
     * @param int $invoice_id ID da fatura
     * @param int $tenant_id ID do tenant
     * @return PaymentMercadoPagoInvoicesEntity|array<int, Entity>|Entity Pagamentos encontrados
     */
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
