<?php

namespace app\controllers;

use app\database\servicesORM\ActivityService;
use http\Request;

class ModelReportController extends AbstractController
{
    public function __construct(
        protected ActivityService $activityService,
        Request $request,
    ) {
        parent::__construct( $request );
    }

    /**
     * Registra a criação de um relatório no sistema de atividades.
     *
     * @param int $report_id ID do relatório
     * @param array<string, mixed> $data Dados do relatório
     */
    public function index( int $report_id, array $data ): void
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
    public function activityLogger( int $tenant_id, int $user_id, string $action_type, string $entity_type, int $entity_id, string $description, array $metadata = [] ): void
    {
        $this->activityService->logActivity( $tenant_id, $user_id, $action_type, $entity_type, $entity_id, $description, $metadata );
    }

}
