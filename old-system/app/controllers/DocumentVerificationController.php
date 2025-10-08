<?php

namespace app\controllers;

use app\database\models\Budget;
use app\database\models\Report;
use app\database\models\Service;
use core\dbal\EntityNotFound;
use core\library\Response;
use core\library\Twig;
use http\Request;

class DocumentVerificationController extends AbstractController
{
    public function __construct(
        private Twig $twig,
        private Budget $budget,
        private Service $service,
        private Report $report,
        Request $request,
    ) {
        parent::__construct($request);
    }

    public function verify(string $hash): Response
    {
        $document = null;
        $type = 'desconhecido';
        // Procura o hash em diferentes tabelas
        $document = $this->budget->findBy([ 'pdf_verification_hash' => $hash ]);
        if (!$document instanceof EntityNotFound) {
            $type = 'Orçamento';
        }

        if ($document instanceof EntityNotFound) {
            $document = $this->service->findBy([ 'pdf_verification_hash' => $hash ]);
            if (!$document instanceof EntityNotFound) {
                $type = 'Ordem de Serviço';
            }
        }

        if ($document instanceof EntityNotFound) {
            $document = $this->report->findBy([ 'hash' => $hash ]);
            if (!$document instanceof EntityNotFound) {
                $type = 'Relatório';
            }
        }

        return new Response(
            $this->twig->env->render('pages/document/verify.twig', [
                'found' => $document instanceof EntityNotFound ? null : $document,
                'document' => $document,
                'type' => $type,
                'hash' => $hash,
            ]),
        );
    }

    public function activityLogger(int $tenant_id, int $user_id, string $action_type, string $entity_type, int $entity_id, string $description, array $metadata = [])
    {
    }

}
