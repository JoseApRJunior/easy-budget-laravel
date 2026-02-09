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
     */
    public function generate(?string $serviceCode = null): string
    {
        return DB::transaction(function () {
            $date = date('Ymd');

            // Padrão planejado: FAT-YYYYMMDD-XXXX
            // Onde XXXX é um sequencial diário
            $lastInvoice = Invoice::where('code', 'like', "FAT-{$date}-%")
                ->lockForUpdate()
                ->orderBy('code', 'desc')
                ->first();

            $sequential = 1;
            if ($lastInvoice && preg_match('/FAT-\d{8}-(\d{4})$/', $lastInvoice->code, $matches)) {
                $sequential = (int) $matches[1] + 1;
            }

            return "FAT-{$date}-".str_pad((string) $sequential, 4, '0', STR_PAD_LEFT);
        });
    }
}
