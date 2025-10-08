<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OperationStatus;
use App\Interfaces\ServiceNoTenantInterface;
use App\Models\PlanSubscription;
use App\Repositories\PlanRepository;
use App\Services\Abstracts\BaseNoTenantService;
use App\Support\ServiceResult;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PlanService extends BaseNoTenantService
{

    private PlanRepository $planRepository;

    /**
     * @param PlanRepository $planRepository
     */
    public function __construct( PlanRepository $planRepository )
    {
        parent::__construct();
        $this->planRepository = $planRepository;
    }

    /**
     * Retorna a classe do modelo Plan.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function getModelClass(): \Illuminate\Database\Eloquent\Model
    {
        return new \App\Models\Plan();
    }

    /**
     * Cria uma nova entidade Plan a partir dos dados fornecidos.
     * Este método apenas preenche os atributos; a persistência é gerenciada pela classe base.
     *
     * @param array $data Dados para preencher a entidade Plan
     * @return \Illuminate\Database\Eloquent\Model Nova instância de Plan preenchida
     */
    protected function createEntity( array $data ): \Illuminate\Database\Eloquent\Model
    {
        $plan = new \App\Models\Plan();
        $plan->fill( $data );
        return $plan;
    }

    /**
     * Verifica se uma entidade Plan pode ser deletada.
     * Um plano não pode ser deletado se possui assinaturas ativas.
     *
     * @param \Illuminate\Database\Eloquent\Model $entity Entidade Plan a verificar
     * @return bool true se pode deletar, false caso contrário
     */
    protected function canDeleteEntity( \Illuminate\Database\Eloquent\Model $entity ): bool
    {
        $subscriptionCount = PlanSubscription::where( 'plan_id', $entity->id )->count();
        return $subscriptionCount === 0;
    }

    /**
     * Encontra entidade Plan por ID.
     *
     * @param int $id ID da entidade
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    protected function findEntityById( int $id ): ?\Illuminate\Database\Eloquent\Model
    {
        return $this->model->find( $id );
    }

    /**
     * Lista entidades Plan com filtros.
     *
     * @param ?array $orderBy Ordenação opcional
     * @param ?int $limit Limite de resultados
     * @return array
     */
    protected function listEntities( ?array $orderBy = null, ?int $limit = null ): array
    {
        $query = $this->model->query();
        if ( !empty( $orderBy ) ) {
            foreach ( $orderBy as $field => $direction ) {
                $query->orderBy( $field, $direction );
            }
        }
        if ( $limit ) {
            $query->limit( $limit );
        }
        return $query->get()->toArray();
    }

    /**
     * Deleta entidade Plan.
     *
     * @param int $id ID da entidade
     * @return bool
     */
    protected function deleteEntity( int $id ): bool
    {
        $entity = $this->findEntityById( $id );
        if ( !$entity ) {
            return false;
        }
        return $entity->delete();
    }

    /**
     * Atualiza entidade Plan.
     *
     * @param int $id ID da entidade
     * @param array $data Dados para atualização
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function updateEntity( int $id, array $data ): \Illuminate\Database\Eloquent\Model
    {
        $entity = $this->findEntityById( $id );
        if ( !$entity ) {
            throw new \Exception( 'Entidade não encontrada para atualização.' );
        }
        $entity->fill( $data );
        return $entity;
    }

    /**
     * @inheritDoc
     */
    public function create( array $data ): ServiceResult
    {
        return parent::create( $data );
    }

    /**
     * @inheritDoc
     */
    public function update( int $id, array $data ): ServiceResult
    {
        return parent::update( $id, $data );
    }

    /**
     * @inheritDoc
     */
    public function list( array $filters = [] ): ServiceResult
    {
        return parent::list( $filters );
    }

    /**
    /**
     * Validação específica para planos globais.
     *
     * @param array $data Dados a serem validados
     * @param bool $isUpdate Se é uma operação de atualização
     * @return ServiceResult Resultado da validação
     */
    protected function validateForGlobal( array $data, bool $isUpdate = false ): ServiceResult
    {
        $id    = $data[ 'id' ] ?? null;
        $rules = [
            'name'        => [
                'required',
                'string',
                'max:255',
                $isUpdate ? 'unique:plans,name,' . $id : 'unique:plans,name'
            ],
            'description' => 'nullable|string|max:1000',
            'price'       => 'required|numeric|min:0',
            'status'      => 'required|in:active,inactive,suspended',
            'features'    => 'nullable|array',
            'features.*'  => 'string|max:255'
        ];

        $validator = Validator::make( $data, $rules );
        if ( $validator->fails() ) {
            $messages = $validator->errors()->all();
            return $this->error( OperationStatus::INVALID_DATA, implode( ', ', $messages ) );
        }

        return $this->success();
    }

    /**
     * Salva entidade no banco de dados.
     *
     * @param \Illuminate\Database\Eloquent\Model $entity Entidade a ser salva
     * @return bool True se salva com sucesso, false caso contrário
     */
    protected function saveEntity( \Illuminate\Database\Eloquent\Model $entity ): bool
    {
        try {
            return $entity->save();
        } catch ( \Exception $e ) {
            return false;
        }
    }

    /**
     * Validação para tenant (não aplicável em serviços sem tenant).
     *
     * @param array $data Dados para validação
     * @param int $tenant_id ID do tenant (ignorado)
     * @param bool $is_update Se é uma atualização (ignorado)
     * @return ServiceResult Resultado da validação
     */
    protected function validateForTenant( array $data, int $tenant_id, bool $is_update = false ): ServiceResult
    {
        return ServiceResult::error(
            OperationStatus::NOT_SUPPORTED,
            'Validação por tenant não é aplicável para serviços sem tenant',
        );
    }

}
