<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\InvoiceItem;
use App\Repositories\Abstracts\AbstractTenantRepository;
use Illuminate\Database\Eloquent\Model;
use App\DTOs\Invoice\InvoiceItemDTO;

class InvoiceItemRepository extends AbstractTenantRepository
{
    /**
     * Define o Model a ser utilizado pelo Repositório.
     */
    protected function makeModel(): Model
    {
        return new InvoiceItem();
    }

    /**
     * Cria um item de fatura a partir de um DTO.
     */
    public function createFromDTO(InvoiceItemDTO $dto, int $invoiceId): Model
    {
        $data = $dto->toArray();
        $data['invoice_id'] = $invoiceId;
        
        return $this->create($data);
    }

    /**
     * Deleta todos os itens de uma fatura.
     */
    public function deleteByInvoiceId(int $invoiceId): void
    {
        $this->model->where('invoice_id', $invoiceId)->delete();
    }
}
