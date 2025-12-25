<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

class InvoiceCodeGeneratorService
{
    /**
     * Gera um código de fatura único baseado no código do serviço.
     * Utiliza lock de banco de dados para evitar condições de corrida.
     *
     * @param string $serviceCode
     * @return string
     */
    public function generate(string $serviceCode): string
    {
        return DB::transaction(function () use ($serviceCode) {
            $lastInvoice = Invoice::whereHas('service', function ($query) use ($serviceCode) {
                $query->where('code', $serviceCode);
            })
                ->lockForUpdate()
                ->orderBy('code', 'desc')
                ->first();

            $sequential = 1;
            if ($lastInvoice && preg_match('/-INV(\d{3})$/', $lastInvoice->code, $matches)) {
                $sequential = (int) $matches[1] + 1;
            }

            return "{$serviceCode}-INV" . str_pad((string) $sequential, 3, '0', STR_PAD_LEFT);
        });
    }
}
