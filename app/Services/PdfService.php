<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OperationStatus;
use App\Interfaces\ServiceNoTenantInterface;
use App\Models\Pdf;
use App\Services\Abstracts\BaseNoTenantService;
use App\Support\ServiceResult;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class PdfService extends BaseNoTenantService implements ServiceNoTenantInterface
{
    private ActivityService $activityService;

    public function __construct( ActivityService $activityService )
    {
        $this->activityService = $activityService;
    }

    protected function findEntityById( int $id ): ?Model
    {
        // Assume Pdf model exists for stored PDFs
        return \App\Models\Pdf::find( $id );
    }

    protected function listEntities( array $filters = [] ): array
    {
        $query = \App\Models\Pdf::query();

        if ( isset( $filters[ 'order' ] ) ) {
            $order = $filters[ 'order' ];
            $query->orderBy( $order[ 0 ] ?? 'id', $order[ 1 ] ?? 'asc' );
        }

        if ( isset( $filters[ 'limit' ] ) ) {
            $query->limit( (int) $filters[ 'limit' ] );
        }

        return $query->get()->all();
    }

    protected function createEntity( array $data ): Model
    {
        $pdf = new \App\Models\Pdf();
        $pdf->fill( $data );
        $pdf->save();
        return $pdf;
    }

    protected function updateEntity( int $id, array $data ): Model
    {
        $entity = $this->findEntityById( $id );
        if ( !$entity ) {
            throw new \Exception( 'PDF not found' );
        }

        $entity->fill( $data );
        $this->saveEntity( $entity );

        return $entity;
    }

    protected function deleteEntity( int $id ): bool
    {
        $entity = $this->findEntityById( $id );
        if ( !$entity ) {
            return false;
        }
        return $entity->delete();
    }

    protected function canDeleteEntity( int $id ): bool
    {
        // Lógica para verificar se pode deletar (ex: não referenciada)
        return true;
    }

    /**
     * Gera PDF (invoice, report, etc.).
     */
    public function generatePdf( array $data, string $type = 'invoice' ): ServiceResult
    {
        $validation = $this->validatePdfData( $data, $type );
        if ( !$validation->isSuccess() ) {
            return $validation;
        }

        // Stub para geração de PDF (implementar com Dompdf ou similar futuramente)
        $pdfPath = storage_path( 'app/pdfs/' . uniqid() . '.pdf' );
        // Simular geração: file_put_contents($pdfPath, 'PDF content stub for ' . $type);

        $pdfData = [
            'path'         => $pdfPath,
            'type'         => $type,
            'data'         => $data,
            'generated_at' => now(),
        ];

        $entity  = $this->createEntity( $pdfData );
        $logData = [ 'type' => $type, 'pdf_id' => $entity->id ];
        $this->activityService->logActivity( 'pdf_generated', $logData );

        return $this->success( $entity, 'PDF gerado com sucesso.' );
    }

    /**
     * Gera PDF específico para budget.
     *
     * @param \App\Models\Budget $budget Entidade do budget
     * @param string $type Tipo de PDF (invoice, report, etc.)
     * @return ServiceResult
     */
    public function generateBudgetPdf( \App\Models\Budget $budget, string $type = 'invoice' ): ServiceResult
    {
        try {
            $pdfData = [
                'title'       => "Orçamento #{$budget->id}",
                'content'     => "PDF do orçamento #{$budget->id} - Cliente: {$budget->customer->name}",
                'budget_id'   => $budget->id,
                'customer_id' => $budget->customer_id,
                'amount'      => $budget->amount,
                'description' => $budget->description,
            ];

            if ( $type === 'invoice' ) {
                $pdfData[ 'invoice_id' ] = $budget->id;
            }

            return $this->generatePdf( $pdfData, $type );
        } catch ( \Exception $e ) {
            return $this->error( \App\Enums\OperationStatus::ERROR, 'Falha ao gerar PDF do orçamento: ' . $e->getMessage() );
        }
    }

    private function validatePdfData( array $data, string $type ): ServiceResult
    {
        $rules = [
            'title'   => 'required|string|max:255',
            'content' => 'required|string', // ou array para templates
        ];
        if ( $type === 'invoice' ) {
            $rules[ 'invoice_id' ] = 'required|integer';
        }
        $validator = Validator::make( $data, $rules );
        if ( $validator->fails() ) {
            $messages = $validator->errors()->all();
            return $this->error( OperationStatus::INVALID_DATA, implode( ', ', $messages ) );
        }
        return $this->success();
    }

    // Métodos da interface (delegados ou custom)
    public function create( array $data ): ServiceResult
    {
        return parent::create( $data );
    }

    public function getById( int $id ): ServiceResult
    {
        return parent::getById( $id );
    }

    // listAll removed - use list( ['order' => $orderBy, 'limit' => $limit] ) instead


    /**
     * Validação específica para PDFs globais.
     *
     * @param array $data Dados a serem validados
     * @param bool $isUpdate Se é uma operação de atualização
     * @return ServiceResult Resultado da validação
     */
    protected function validateForGlobal( array $data, bool $isUpdate = false ): ServiceResult
    {
        $id    = $data[ 'id' ] ?? null;
        $rules = [
            'path'     => [
                'required',
                'string',
                'max:500',
                $isUpdate ? 'unique:pdfs,path,' . $id : 'unique:pdfs,path'
            ],
            'type'     => 'required|string|max:50',
            'data'     => 'required|array',
            'metadata' => 'nullable|array'
        ];

        $validator = Validator::make( $data, $rules );
        if ( $validator->fails() ) {
            $messages = $validator->errors()->all();
            return $this->error( OperationStatus::INVALID_DATA, implode( ', ', $messages ) );
        }

        return $this->success();
    }

}