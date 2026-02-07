<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\ReportGenerated;
use Illuminate\Support\Facades\Log;

class LogReportGeneration
{
    public function handle(ReportGenerated $event): void
    {
        $report = $event->report;
        Log::info('report_generated', [
            'report_id' => $report->id,
            'type' => $report->type,
            'format' => $report->format,
            'status' => $report->status,
            'tenant_id' => $report->tenant_id ?? null,
            'user_id' => $report->user_id ?? null,
            'generated_at' => $report->generated_at,
        ]);
    }
}
