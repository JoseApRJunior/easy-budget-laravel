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
        protected Twig $twig,
        private Budget $budget,
        protected Service $service,
        private Report $report,
        Request $request,
    ) {
        parent::__construct( $request );
    }

    public function verify( string $hash ): Response
    {
        $document = null;
        $type     = 'desconhecido';
        // Procura o hash em diferentes tabelas
        $document = $this->budget->findBy( [ 'pdf_verification_hash' => $hash ] );
        if ( $document[ 'success' ] ) {
            $type = 'Orçamento';
        }

        if ( !$document[ 'success' ] ) {
            $document = $this->service->findBy( [ 'pdf_verification_hash' => $hash ] );
            if ( $document[ 'success' ] ) {
                $type = 'Ordem de Serviço';
            }
        }

        if ( !$document[ 'success' ] ) {
            $document = $this->report->findBy( [ 'hash' => $hash ] );
            if ( $document[ 'success' ] ) {
                $type = 'Relatório';
            }
        }

        return new Response(
            $this->twig->env->render( 'pages/document/verify.twig', [ 
                'found'    => $document[ 'success' ] ? $document[ 'data' ] : null,
                'document' => $document[ 'success' ] ? $document[ 'data' ] : null,
                'type'     => $type,
                'hash'     => $hash,
            ] ),
        );
    }

    public function activityLogger( int $tenant_id, int $user_id, string $action_type, string $entity_type, int $entity_id, string $description, array $metadata = [] ): void {}

}
