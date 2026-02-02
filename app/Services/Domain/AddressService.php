<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\DTOs\Common\AddressDTO;
use App\Repositories\AddressRepository;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Support\ServiceResult;

class AddressService extends AbstractBaseService
{
    private AddressRepository $addressRepository;

    public function __construct(AddressRepository $addressRepository)
    {
        parent::__construct($addressRepository);
        $this->addressRepository = $addressRepository;
    }

    public function createAddress(AddressDTO $dto): ServiceResult
    {
        return $this->safeExecute(function () use ($dto) {
            $address = $this->addressRepository->createFromDTO($dto);

            return $this->success($address, 'Endereço criado com sucesso');
        }, 'Erro ao criar endereço.');
    }

    public function updateAddress(int $id, AddressDTO $dto): ServiceResult
    {
        return $this->safeExecute(function () use ($id, $dto) {
            $address = $this->addressRepository->updateFromDTO($id, $dto);
            if (! $address) {
                return $this->error(\App\Enums\OperationStatus::NOT_FOUND, 'Endereço não encontrado');
            }

            return $this->success($address, 'Endereço atualizado com sucesso');
        }, 'Erro ao atualizar endereço.');
    }

    public function deleteAddress(int $id): ServiceResult
    {
        return $this->safeExecute(function () use ($id) {
            $deleted = $this->addressRepository->delete($id);
            if (! $deleted) {
                return $this->error(\App\Enums\OperationStatus::NOT_FOUND, 'Endereço não encontrado');
            }

            return $this->success(null, 'Endereço removido com sucesso');
        }, 'Erro ao remover endereço.');
    }
}
