<?php
// app/controllers/admin/BackupController.php

namespace app\controllers\admin;

use app\controllers\AbstractController;
use core\library\Response;
use core\library\Twig;
use core\services\DatabaseBackupService;
use http\Redirect;
use http\Request;

class BackupController extends AbstractController
{
    public function __construct(
        private Twig $twig,
        private DatabaseBackupService $backupService,
        Request $request,
    ) {
        parent::__construct( $request );
    }

    public function index(): Response
    {
        $backups = $this->backupService->listBackups();

        return new Response( $this->twig->env->render( 'pages/admin/backup/index.twig', [ 
            'backups' => $backups
        ] ) );
    }

    public function create(): Response
    {
        $result = $this->backupService->createBackup( 'manual' );

        if ( $result[ 'status' ] === 'success' ) {
            return Redirect::redirect( '/admin/backups' )
                ->withMessage( 'success', 'Backup criado com sucesso: ' . $result[ 'filename' ] );
        }

        return Redirect::redirect( '/admin/backups' )
            ->withMessage( 'error', 'Erro ao criar backup: ' . $result[ 'message' ] );
    }

    public function restore(): Response
    {
        $filename = $this->request->get( 'filename' );

        if ( !$filename ) {
            return Redirect::redirect( '/admin/backups' )
                ->withMessage( 'error', 'Arquivo não especificado' );
        }

        // Validação básica do nome do arquivo
        if ( !preg_match( '/^[a-zA-Z0-9_\-\.]+$/', $filename ) ) {
            return Redirect::redirect( '/admin/backups' )
                ->withMessage( 'error', 'Nome de arquivo inválido' );
        }

        $result = $this->backupService->restoreBackup( $filename );

        return Redirect::redirect( '/admin/backups' )
            ->withMessage( $result[ 'status' ] === 'success' ? 'success' : 'error', $result[ 'message' ] );
    }

    public function delete(): Response
    {
        $filename = $this->request->get( 'filename' );

        if ( !$filename ) {
            return Redirect::redirect( '/admin/backups' )
                ->withMessage( 'error', 'Arquivo não especificado' );
        }

        // Validação básica do nome do arquivo
        if ( !preg_match( '/^[a-zA-Z0-9_\-\.]+$/', $filename ) ) {
            return Redirect::redirect( '/admin/backups' )
                ->withMessage( 'error', 'Nome de arquivo inválido' );
        }

        if ( $this->backupService->deleteBackup( $filename ) ) {
            return Redirect::redirect( '/admin/backups' )
                ->withMessage( 'success', 'Backup deletado com sucesso' );
        }

        return Redirect::redirect( '/admin/backups' )
            ->withMessage( 'error', 'Erro ao deletar backup' );
    }

    public function cleanup(): Response
    {
        $days = (int) $this->request->get( 'days' );

        $deleted = $this->backupService->cleanOldBackups( $days );

        return Redirect::redirect( '/admin/backups' )
            ->withMessage( 'success', "Limpeza concluída: {$deleted} backups removidos" );
    }

    public function activityLogger( int $tenant_id, int $user_id, string $action_type, string $entity_type, int $entity_id, string $description, array $metadata = [] ) {}

}
