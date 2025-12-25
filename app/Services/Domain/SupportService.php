<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\DTOs\Support\SupportDTO;
use App\DTOs\Support\SupportUpdateDTO;
use App\Enums\OperationStatus;
use App\Models\Support;
use App\Repositories\SupportRepository;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Serviço para gerenciamento de tickets de suporte.
 *
 * Esta classe implementa toda a lógica de negócio relacionada a tickets de suporte,
 * incluindo criação, salvamento no banco de dados e envio de emails.
 */
class SupportService extends AbstractBaseService
{
    /**
     * Construtor do serviço de suporte.
     *
     * @param SupportRepository $supportRepository Repositório para operações de suporte
     */
    public function __construct(SupportRepository $supportRepository)
    {
        parent::__construct($supportRepository);
    }

    /**
     * Retorna lista de filtros suportados pelo serviço.
     *
     * @return array<string> Lista de campos que podem ser filtrados.
     */
    protected function getSupportedFilters(): array
    {
        return [
            'id',
            'first_name',
            'last_name',
            'email',
            'subject',
            'status',
            'tenant_id',
            'created_at',
            'updated_at',
        ];
    }

    /**
     * Define o Model a ser utilizado pelo Service.
     */
    protected function makeModel(): \Illuminate\Database\Eloquent\Model
    {
        return new Support();
    }

    /**
     * Cria um novo ticket de suporte, salva no banco e dispara evento.
     *
     * @param SupportDTO $dto Dados do ticket de suporte
     * @return ServiceResult Resultado da operação
     */
    public function createSupportTicket(SupportDTO $dto): ServiceResult
    {
        return $this->safeExecute(function () use ($dto) {
            return DB::transaction(function () use ($dto) {
                // Prepara dados adicionais se necessário
                $data = $dto->toArrayWithoutNulls();
                
                if (!isset($data['tenant_id'])) {
                    $data['tenant_id'] = $this->getEffectiveTenantId();
                }

                if (!isset($data['status'])) {
                    $data['status'] = Support::STATUS_ABERTO;
                }

                // Salva o ticket no banco de dados usando o repositório e o DTO
                $support = $this->repository->createFromDTO($dto instanceof SupportDTO ? $dto : SupportDTO::fromRequest($data));

                // Gerar e persistir código de protocolo no padrão SUP-YYYY-MM-XXXX
                $supportCode = $this->generateProtocolCode($support);
                $support->update(['code' => $supportCode]);

                // Obtém tenant efetivo para o evento
                $effectiveTenant = $this->getEffectiveTenant();

                // Dispara evento para processamento assíncrono
                \App\Events\SupportTicketCreated::dispatch($support, $dto->toArray(), $effectiveTenant);

                return $this->success(
                    $support,
                    'Ticket de suporte criado com sucesso. Email será processado em breve.'
                );
            });
        }, 'Erro ao criar ticket de suporte.');
    }

    /**
     * Atualiza um ticket de suporte.
     *
     * @param int $id ID do ticket
     * @param SupportUpdateDTO $dto Dados de atualização
     * @return ServiceResult Resultado da operação
     */
    public function updateSupportTicket(int $id, SupportUpdateDTO $dto): ServiceResult
    {
        return $this->safeExecute(function () use ($id, $dto) {
            $support = $this->repository->updateFromDTO($id, $dto);

            if (!$support) {
                return $this->error('Ticket de suporte não encontrado.');
            }

            return $this->success($support, 'Ticket de suporte atualizado com sucesso.');
        }, 'Erro ao atualizar ticket de suporte.');
    }

    /**
     * Gera código de protocolo no padrão SUP-YYYY-MM-XXXX.
     */
    private function generateProtocolCode(Support $support): string
    {
        $year  = $support->created_at?->format('Y') ?? date('Y');
        $month = $support->created_at?->format('m') ?? date('m');

        // Usa o ID como sequencial base, zero‑pad de 4 dígitos
        $seq = str_pad((string) $support->id, 4, '0', STR_PAD_LEFT);

        return sprintf('SUP-%s-%s-%s', $year, $month, $seq);
    }

    /**
     * Obtém o tenant_id efetivo para o ticket de suporte.
     */
    private function getEffectiveTenantId(): int
    {
        $userTenantId = $this->tenantId();
        return $userTenantId ?: 1; // ID do tenant público
    }

    /**
     * Obtém o tenant efetivo para o ticket de suporte.
     */
    private function getEffectiveTenant(): ?\App\Models\Tenant
    {
        try {
            $userTenant = $this->authUser()?->tenant;
            return $userTenant ?: \App\Models\Tenant::find(1);
        } catch (Exception $e) {
            return \App\Models\Tenant::find(1);
        }
    }
}
