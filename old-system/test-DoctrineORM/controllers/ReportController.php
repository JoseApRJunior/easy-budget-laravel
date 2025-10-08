<?php

namespace app\controllers;

use app\controllers\report\BudgetExcel;
use app\database\entitiesORM\ReportEntity;
use app\database\models\Budget;
use app\database\models\Report;
use app\database\servicesORM\ActivityService;
use core\library\Response;
use core\library\Twig;
use core\support\report\ExcelGenerator;
use core\support\report\PdfGenerator;
use http\Redirect;
use http\Request;

class ReportController extends AbstractController
{
    public function __construct(
        protected Twig $twig,
        private Budget $budget,
        private PdfGenerator $pdfGenerator,
        private ExcelGenerator $excelGenerator,
        private Report $report,
        protected ActivityService $activityService,
        private BudgetExcel $budgetExcel,

        Request $request,
    ) {
        parent::__construct( $request );
    }

    /**
     * Summary of index
     * @return Response
     */
    public function index(): Response
    {

        return new Response( $this->twig->env->render( 'pages/report/index.twig' ) );
    }

    public function customers(): Response
    {
        return new Response( $this->twig->env->render( 'pages/report/customer/customer.twig' ) );
    }

    public function products(): Response
    {
        return new Response( $this->twig->env->render( 'pages/report/product/product.twig' ) );
    }

    public function budgets(): Response
    {
        return new Response( $this->twig->env->render( 'pages/report/budget/budget.twig' ) );
    }

    public function budgets_pdf(): Response
    {
        try {
            // Pegar os filtros da query string e sanitiza
            $data = $this->request->all();

            // Buscar dados filtrados
            $budgets = $this->budget->getBudgetsByFilterReport(
                $data,
                $this->authenticated->tenant_id,
            );

            // Calcular totais
            $totals = [ 
                'count' => count( $budgets ),
                'sum'   => array_sum( array_column( $budgets, 'total' ) ),
                'avg'   => count( $budgets ) > 0 ? array_sum( array_column( $budgets, 'total' ) ) / count( $budgets ) : 0,
            ];
            $date   = new \DateTime();

            $pdf_name = sprintf(
                'relatorio_orcamentos_%s_%s_registros.pdf',
                $date->format( 'Ymd_H_i_s' ),
                str_pad( (string) count( $budgets ), 3, '0', STR_PAD_LEFT ),
            );

            $html = $this->twig->env->render( 'pages/report/budget/pdf_budget.twig', [ 
                'budgets' => $budgets,
                'filters' => $data,
                'totals'  => $totals,
                'date'    => $date,
            ] );

            $pdfGenerated = $this->pdfGenerator->generate( $html, $pdf_name );

            $pdf = new Response( $pdfGenerated[ 'content' ], headers: [ 
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => "inline; filename=\"$pdf_name\"",
            ] );

            // Report
            $properties                  = getConstructorProperties( ReportEntity::class);
            $properties[ 'tenant_id' ]   = $this->authenticated->tenant_id;
            $properties[ 'user_id' ]     = $this->authenticated->user_id;
            $properties[ 'hash' ]        = generateReportHash( $html, $data, $this->authenticated->user_id, $this->authenticated->tenant_id );
            $properties[ 'type' ]        = 'budget';
            $properties[ 'description' ] = \generateDescriptionPipe( $data );
            $properties[ 'file_name' ]   = $pdf_name;
            $properties[ 'status' ]      = 'generated';
            $properties[ 'format' ]      = 'pdf';
            $properties[ 'size' ]        = $pdfGenerated[ 'size' ][ 'mb' ];

            // popula model ReportEntity
            $entity = ReportEntity::create( removeUnnecessaryIndexes(
                $properties,
                [ 'id', 'created_at' ],
                [],
            ) );

            // Criar Report e retorna o id
            $respose = $this->report->create( $entity );

            // verifica se o report foi criado com sucesso, se não, retorna false
            if ( $respose[ 'status' ] === 'error' ) {
                return Redirect::redirect( '/provider/reports/budgets' )->withMessage( 'error', "Falha ao gerar o relatório, tente novamente mais tarde ou entre em contato com suporte!" );
            }

            $this->activityLogger(
                $this->authenticated->tenant_id,
                $this->authenticated->user_id,
                'report_created',
                'report',
                $respose[ 'data' ][ 'id' ],
                "Relatório de orçamentos gerado com sucesso!",
                $data,
            );

            return $pdf;
        } catch ( Throwable $e ) {
            getDetailedErrorInfo( $e );
            Session::flash( 'error', "Falha ao gerar o relatório de orçamentos, tente novamente mais tarde ou entre em contato com suporte!" );
            return Redirect::redirect( '/provider/reports/budgets' );
        }
    }

    public function budgets_excel(): Response
    {
        try {
            // Pegar os filtros da query string e sanitiza
            $data = $this->request->all();

            // Buscar dados filtrados
            $budgets = $this->budget->getBudgetsByFilterReport(
                $data,
                $this->authenticated->tenant_id,
            );

            // Calcular totais
            $totals = [ 
                'count' => count( $budgets ),
                'sum'   => array_sum( array_column( $budgets, 'total' ) ),
                'avg'   => count( $budgets ) > 0 ? array_sum( array_column( $budgets, 'total' ) ) / count( $budgets ) : 0,
            ];

            $date       = new \DateTime();
            $excel_name = sprintf(
                'relatorio_orcamentos_%s_%s_registros.xlsx',
                $date->format( 'Ymd_H_i_s' ),
                str_pad( (string) count( $budgets ), 3, '0', STR_PAD_LEFT ),
            );

            $excelGenerated = $this->excelGenerator->generate(
                $this->budgetExcel->generateExcel(
                    $this->authenticated,
                    $budgets,
                    $data,
                    $date,
                    $totals,
                    $excel_name,
                ),
            );

            $excel = new Response( $excelGenerated[ 'content' ], headers: [ 
                'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => "inline; filename=\"$excel_name\"",
                'Cache-Control'       => 'max-age=0',
            ] );

            // Report
            $properties                  = getConstructorProperties( ReportEntity::class);
            $properties[ 'tenant_id' ]   = $this->authenticated->tenant_id;
            $properties[ 'user_id' ]     = $this->authenticated->user_id;
            $properties[ 'hash' ]        = generateReportHash( $excelGenerated[ 'content' ], $data, $this->authenticated->user_id, $this->authenticated->tenant_id );
            $properties[ 'type' ]        = 'budget';
            $properties[ 'description' ] = \generateDescriptionPipe( $data );
            $properties[ 'file_name' ]   = $excel_name;
            $properties[ 'status' ]      = 'generated';
            $properties[ 'format' ]      = 'excel';
            $properties[ 'size' ]        = $excelGenerated[ 'size' ][ 'mb' ];

            // popula model ReportEntity
            $entity = ReportEntity::create( removeUnnecessaryIndexes(
                $properties,
                [ 'id', 'created_at' ],
                [],
            ) );

            // Criar Report e retorna o id
            $respose = $this->report->create( $entity );

            // verifica se o report foi criado com sucesso, se não, retorna false
            if ( $respose[ 'status' ] === 'error' ) {
                return Redirect::redirect( '/provider/reports/budgets' )->withMessage( 'error', "Falha ao gerar o relatório, tente novamente mais tarde ou entre em contato com suporte!" );
            }

            $this->activityLogger(
                $this->authenticated->tenant_id,
                $this->authenticated->user_id,
                'report_created',
                'report',
                $respose[ 'data' ][ 'id' ],
                "Relatório de orçamentos gerado com sucesso!",
                $data,
            );

            return $excel;
        } catch ( Throwable $e ) {
            getDetailedErrorInfo( $e );
            Session::flash( 'error', "Falha ao gerar o relatório de orçamentos, tente novamente mais tarde ou entre em contato com suporte!" );
            return Redirect::redirect( '/provider/reports/budgets' );
        }
    }

    public function services(): Response
    {
        return new Response( $this->twig->env->render( 'pages/report/service/view_service.twig' ) );
    }

    /**
     * @inheritDoc
     */
    public function activityLogger( int $tenant_id, int $user_id, string $action_type, string $entity_type, int $entity_id, string $description, array $metadata = [] ): void
    {
        $this->activityService->logActivity( $tenant_id, $user_id, $action_type, $entity_type, $entity_id, $description, $metadata );
    }

}
