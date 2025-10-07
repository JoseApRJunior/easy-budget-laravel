<?php

namespace app\controllers;

use app\database\services\ActivityService;
use http\Request;

class ModelReportController extends AbstractController
{
    public function __construct(
        private ActivityService $activityService,
        Request $request,
    ) {
        parent::__construct($request);
    }

    public function index($report_id, $data)
    {
        $this->activityLogger(
            $this->authenticated->tenant_id,
            $this->authenticated->user_id,
            'report_created',
            'report',
            $report_id,
            "Relatório de orçamentos gerado com sucesso!",
            $data,
        );
    }

    /**
     * @inheritDoc
     */
    public function activityLogger(int $tenant_id, int $user_id, string $action_type, string $entity_type, int $entity_id, string $description, array $metadata = [])
    {
        $this->activityService->logActivity($tenant_id, $user_id, $action_type, $entity_type, $entity_id, $description, $metadata);
    }

}
