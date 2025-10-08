<?php

namespace app\database\services;

use app\database\entitiesORM\ReportEntity;
use app\database\models\Report;
use core\library\Session;
use core\support\report\ReportStorage;

class ReportStorageService
{
    protected string $table = 'reports';
    private mixed $authenticated;

    public function __construct(
        private Report $report,
        private ReportStorage $reportStorage,
    ) {
        if ( Session::has( 'auth' ) ) {
            $this->authenticated = Session::get( 'auth' );
        }
    }

    /**
     * Manipula a geração e armazenamento de relatórios.
     *
     * @param mixed $content Conteúdo do relatório.
     * @param mixed $data Dados do relatório.
     * @return mixed Resultado da operação.
     */
    public function handleReport( mixed $content, mixed $data ): mixed
    {
        try {
            // Gera hash do relatório
            $reportHash = generateReportHash( $content, $data, $this->authenticated->user_id, $this->authenticated->tenant_id );

            // Verifica se existe relatório idêntico recente
            $existingReport = $this->report->findByHash( $reportHash, $this->authenticated->tenant_id );

            if ( $existingReport && !$this->isExpired( $existingReport ) ) {
                // Retorna relatório existente
                return [ 
                    'id'           => $existingReport[ 'id' ],
                    'file_path'    => $existingReport[ 'file_path' ],
                    'is_duplicate' => true,
                ];
            }

            // Salva o relatório
            $data = $this->reportStorage->store( $data, $content );

            // Report
            $properties                 = getConstructorProperties( ReportEntity::class);
            $properties[ 'tenant_id' ]  = $this->authenticated->tenant_id;
            $properties[ 'user_id' ]    = $this->authenticated->user_id;
            $properties[ 'file_path' ]  = $data[ 'file_path' ];
            $properties[ 'size' ]       = $data[ 'size' ];
            $properties[ 'expires_at' ] = \dateExpirate( '+1 week' );

            // popula model ReportEntity
            $entity = ReportEntity::create( removeUnnecessaryIndexes(
                $properties,
                [ 'id', 'created_at' ],
                $data,
            ) );

            // Criar Report e retorna o id
            $result = $this->report->create( $entity );

            // verifica se o report foi criado com sucesso, se não, retorna false
            if ( $result[ 'status' ] === 'error' ) {
                return false;
            }
            return true;

        } catch ( \Exception $e ) {
            echo "Erro: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Verifica se um relatório está expirado.
     *
     * @param mixed $report Dados do relatório.
     * @return bool True se expirado, false caso contrário.
     */
    private function isExpired( mixed $report ): bool
    {
        return strtotime( $report->expires_at ) < time();
    }

}
