<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Enums\OperationStatus;
use App\Models\Budget;
use App\Models\Report;
use App\Models\Service;
use App\Support\ServiceResult;
use Illuminate\Support\Facades\Log;

class DocumentVerificationService
{
    public function verifyDocument(string $hash): ServiceResult
    {
        Log::info('document_verification_attempt', ['hash' => $hash, 'ip' => request()->ip()]);

        $budget = Budget::where('pdf_verification_hash', $hash)->first();
        if ($budget) {
            Log::info('document_verified', ['type' => 'budget', 'id' => $budget->id]);

            return ServiceResult::success([
                'document' => $budget,
                'type' => 'Orçamento',
                'entity_type' => 'budget',
            ], 'Documento verificado');
        }

        $service = Service::where('pdf_verification_hash', $hash)->first();
        if ($service) {
            Log::info('document_verified', ['type' => 'service', 'id' => $service->id]);

            return ServiceResult::success([
                'document' => $service,
                'type' => 'Ordem de Serviço',
                'entity_type' => 'service',
            ], 'Documento verificado');
        }

        $report = Report::where('hash', $hash)->first();
        if ($report) {
            Log::info('document_verified', ['type' => 'report', 'id' => $report->id]);

            return ServiceResult::success([
                'document' => $report,
                'type' => 'Relatório',
                'entity_type' => 'report',
            ], 'Documento verificado');
        }

        Log::warning('document_not_found', ['hash' => $hash]);

        return ServiceResult::error(OperationStatus::NOT_FOUND, 'Documento não encontrado', ['hash' => $hash]);
    }
}
