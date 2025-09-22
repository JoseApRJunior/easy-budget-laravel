<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OperationStatus;
use App\Interfaces\ServiceNoTenantInterface;
use App\Services\Abstracts\BaseNoTenantService;
use App\Support\ServiceResult;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SharedService extends BaseNoTenantService implements ServiceNoTenantInterface
{
    private ActivityService $activityService;

    public function __construct( ActivityService $activityService )
    {
        $this->activityService = $activityService;
    }

    // Overrides mínimos para interface (não aplicável para utilities, throw exception)
    protected function findEntityById( int $tenantId ): ?Model
    {
        throw new \LogicException( 'CRUD não suportado em SharedService.' );
    }

    protected function listEntities( array $filters = [] ): array
    {
        throw new \LogicException( 'CRUD não suportado em SharedService.' );
    }

    protected function createEntity( array $data ): Model
    {
        throw new \LogicException( 'CRUD não suportado em SharedService.' );
    }

    protected function updateEntity( int $id, array $data ): Model
    {
        throw new \LogicException( 'CRUD não suportado em SharedService.' );
    }

    protected function deleteEntity( int $id ): bool
    {
        throw new \LogicException( 'CRUD não suportado em SharedService.' );
    }

    protected function canDeleteEntity( int $id ): bool
    {
        throw new \LogicException( 'CRUD não suportado em SharedService.' );
    }

    /**
     * Formata data.
     */
    public function formatDate( string $date, string $format = 'd/m/Y' ): ServiceResult
    {
        try {
            $formatted = Carbon::parse( $date )->format( $format );
            $this->activityService->logActivity( 'date_formatted', [ 'date' => $date, 'format' => $format ] );
            return $this->success( $formatted, 'Data formatada.' );
        } catch ( \Exception $e ) {
            return $this->error( OperationStatus::INVALID_DATA, 'Data inválida: ' . $e->getMessage() );
        }
    }

    /**
     * Formata moeda.
     */
    public function formatCurrency( float $amount, string $currency = 'BRL' ): ServiceResult
    {
        $formatted = number_format( $amount, 2, ',', '.' ) . ' ' . strtoupper( $currency );
        $this->activityService->logActivity( 'currency_formatted', [ 'amount' => $amount, 'currency' => $currency ] );
        return $this->success( $formatted, 'Moeda formatada.' );
    }

    /**
     * Gera token aleatório.
     */
    public function generateToken( int $length = 32 ): ServiceResult
    {
        $token = Str::random( $length );
        $this->activityService->logActivity( 'token_generated', [ 'length' => $length ] );
        return $this->success( $token, 'Token gerado.' );
    }

    /**
     * Converte para slug.
     */
    public function convertToSlug( string $text ): ServiceResult
    {
        $slug = Str::slug( $text );
        $this->activityService->logActivity( 'slug_generated', [ 'text' => $text, 'slug' => $slug ] );
        return $this->success( $slug, 'Slug gerado.' );
    }

    // Métodos da interface (stubs corretos com assinaturas padrão)
    public function create( array $data ): ServiceResult
    {
        return $this->error( OperationStatus::NOT_SUPPORTED, 'Método não suportado em SharedService.' );
    }

    public function getById( int $id ): ServiceResult
    {
        return $this->error( OperationStatus::NOT_SUPPORTED, 'Método não suportado em SharedService.' );
    }

    public function list( array $filters = [] ): ServiceResult
    {
        return $this->error( OperationStatus::NOT_SUPPORTED, 'Método não suportado em SharedService.' );
    }

    public function updateById( int $id, array $data ): ServiceResult
    {
        return $this->error( OperationStatus::NOT_SUPPORTED, 'Método não suportado em SharedService.' );
    }

    public function deleteById( int $id ): ServiceResult
    {
        return $this->error( OperationStatus::NOT_SUPPORTED, 'Método não suportado em SharedService.' );
    }

    protected function validateForGlobal( array $data, bool $isUpdate = false ): ServiceResult
    {
        // Validação genérica para utilities, ou skip
        return $this->success();
    }

}
