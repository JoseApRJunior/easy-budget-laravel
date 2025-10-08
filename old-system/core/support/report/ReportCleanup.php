<?php

namespace core\support\report;

use app\database\models\Report;

class ReportCleanup
{
    private $storage;

    public function __construct(
        ReportStorage $storage,
        private Report $report,
    ) {
        $this->storage = $storage;
    }

    public function cleanExpiredReports($reports)
    {
        foreach ($reports as $report) {
            if ($report->expires_at < date('Y-m-d H:i:s')) {
                $this->storage->delete($report[ 'file_path' ]);
                $this->report->delete($report[ 'id' ], $report[ 'tenant_id' ]);
            }
        }
    }

}
