<?php

namespace app\database\services;

use app\database\entities\ReportEntity;
use app\database\models\Report;
use core\library\Session;
use core\support\report\ReportStorage;

class ReportStorageService
{
    protected string $table = 'reports';
    private $authenticated;

    public function __construct(
        private Report $report,
        private ReportStorage $reportStorage,
    ) {
        if (Session::has('auth')) {
            $this->authenticated = Session::get('auth');
        }
    }

    public function handleReport($content, $data)
    {
        try {
            // Gera hash do relatório
            $reportHash = generateReportHash($content, $data, $this->authenticated->user_id, $this->authenticated->tenant_id);

            // Verifica se existe relatório idêntico recente
            $existingReport = $this->report->findByHash($reportHash, $this->authenticated->tenant_id);

            if ($existingReport && !$this->isExpired($existingReport)) {
                // Retorna relatório existente
                return [
                    'id' => $existingReport[ 'id' ],
                    'file_path' => $existingReport[ 'file_path' ],
                    'is_duplicate' => true,
                ];
            }

            // Salva o relatório
            $data = $this->reportStorage->store($data, $content);

            // Report
            $properties = getConstructorProperties(ReportEntity::class);
            $properties[ 'tenant_id' ] = $this->authenticated->tenant_id;
            $properties[ 'user_id' ] = $this->authenticated->user_id;
            $properties[ 'file_path' ] = $data[ 'file_path' ];
            $properties[ 'size' ] = $data[ 'size' ];
            $properties[ 'expires_at' ] = \dateExpirate('+1 week');

            // popula model ReportEntity
            $entity = ReportEntity::create(removeUnnecessaryIndexes(
                $properties,
                [ 'id', 'created_at' ],
                $data,
            ));

            // Criar Report e retorna o id
            $report_id = $this->report->create($entity);

            // verifica se o report foi criado com sucesso, se não, retorna false
            if (!$report_id) {
                return false;
            }

        } catch (\Exception $e) {
            echo "Erro: " . $e->getMessage();
        }
    }

    private function isExpired($report)
    {
        return strtotime($report->expires_at) < time();
    }

}
