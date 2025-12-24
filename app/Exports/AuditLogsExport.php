<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AuditLogsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $logs;

    public function __construct($logs)
    {
        $this->logs = collect($logs);
    }

    public function collection()
    {
        return $this->logs;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Data/Hora',
            'Usuário',
            'Ação',
            'Modelo',
            'Severidade',
            'IP',
            'Descrição',
        ];
    }

    public function map($log): array
    {
        return [
            $log->id,
            $log->created_at->format('Y-m-d H:i:s'),
            $log->user?->email ?? 'Sistema',
            $log->action,
            $log->model_type,
            $log->severity,
            $log->ip_address,
            $log->description,
        ];
    }
}
