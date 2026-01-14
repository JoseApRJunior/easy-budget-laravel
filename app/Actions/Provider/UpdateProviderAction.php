<?php

declare(strict_types=1);

namespace App\Actions\Provider;

use App\DTOs\Common\AddressDTO;
use App\DTOs\Common\BusinessDataDTO;
use App\DTOs\Common\CommonDataDTO;
use App\DTOs\Common\ContactDTO;
use App\DTOs\Provider\ProviderUpdateDTO;
use App\Models\CommonData;
use App\Models\Provider;
use App\Models\User;
use App\Repositories\AddressRepository;
use App\Repositories\BusinessDataRepository;
use App\Repositories\CommonDataRepository;
use App\Repositories\ContactRepository;
use App\Repositories\ProviderRepository;
use App\Repositories\UserRepository;
use App\Services\Infrastructure\FileUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Exception;

/**
 * Action responsável por atualizar os dados de um prestador.
 * Lida com a transição entre PF e PJ e atualiza as tabelas relacionadas.
 */
class UpdateProviderAction
{
    public function __construct(
        private UserRepository $userRepository,
        private ProviderRepository $providerRepository,
        private CommonDataRepository $commonDataRepository,
        private ContactRepository $contactRepository,
        private AddressRepository $addressRepository,
        private BusinessDataRepository $businessDataRepository,
        private FileUploadService $fileUploadService
    ) {}

    /**
     * Executa a atualização do provedor.
     *
     * @param Provider $provider
     * @param User $user
     * @param ProviderUpdateDTO $dto
     * @param int $tenantId
     * @return Provider
     * @throws Exception
     */
    public function execute(Provider $provider, User $user, ProviderUpdateDTO $dto, int $tenantId): Provider
    {
        return DB::transaction(function () use ($provider, $dto, $user, $tenantId) {
            // 1. Upload do Logo (se houver)
            $logoPath = $user->logo;
            if ($dto->logo instanceof UploadedFile) {
                $logoPath = $this->fileUploadService->uploadProviderLogo($dto->logo, $user->logo);
            }

            // 2. Atualizar Usuário (email e logo)
            $this->userRepository->update($user->id, array_filter([
                'email' => $dto->email,
                'logo' => $logoPath,
            ], fn ($value) => $value !== null));

            // 3. Detectar Tipo de Pessoa (PF ou PJ)
            $type = $dto->person_type === 'pj' ? CommonData::TYPE_COMPANY : CommonData::TYPE_INDIVIDUAL;

            // 4. Atualizar Dados Comuns (CommonData)
            if ($provider->commonData) {
                $this->commonDataRepository->updateFromDTO(
                    $provider->commonData->id,
                    CommonDataDTO::fromRequest(array_merge($dto->toArray(), ['type' => $type]))
                );
            }

            // 5. Atualizar Contatos (Contact)
            if ($provider->contact) {
                $this->contactRepository->updateFromDTO(
                    $provider->contact->id,
                    ContactDTO::fromRequest(array_merge($dto->toArray(), [
                        'email_personal' => $dto->email_personal ?? $dto->email,
                    ]))
                );
            }

            // 6. Atualizar Endereço (Address)
            if ($provider->address) {
                $this->addressRepository->updateFromDTO(
                    $provider->address->id,
                    AddressDTO::fromRequest($dto->toArray())
                );
            }

            // 7. Lógica Específica para PJ (BusinessData)
            if ($type === CommonData::TYPE_COMPANY) {
                $businessDataDTO = BusinessDataDTO::fromRequest(array_merge($dto->toArray(), [
                    'provider_id' => $provider->id,
                    'tenant_id' => $tenantId,
                ]));

                if ($provider->businessData) {
                    $this->businessDataRepository->updateFromDTO($provider->businessData->id, $businessDataDTO);
                } else {
                    $this->businessDataRepository->createFromDTO($businessDataDTO);
                }
            }

            $provider->refresh();

            return $provider;
        });
    }
}
