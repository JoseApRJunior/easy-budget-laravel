<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\DTOs\Common\ContactDTO;
use App\Repositories\ContactRepository;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Support\ServiceResult;

class ContactService extends AbstractBaseService
{
    private ContactRepository $contactRepository;

    public function __construct(ContactRepository $contactRepository)
    {
        parent::__construct($contactRepository);
        $this->contactRepository = $contactRepository;
    }

    public function createContact(ContactDTO $dto): ServiceResult
    {
        return $this->safeExecute(function () use ($dto) {
            $contact = $this->contactRepository->createFromDTO($dto);

            return $this->success($contact, 'Contato criado com sucesso');
        }, 'Erro ao criar contato.');
    }

    public function updateContact(int $id, ContactDTO $dto): ServiceResult
    {
        return $this->safeExecute(function () use ($id, $dto) {
            $contact = $this->contactRepository->updateFromDTO($id, $dto);
            if (! $contact) {
                return $this->error(\App\Enums\OperationStatus::NOT_FOUND, 'Contato não encontrado');
            }

            return $this->success($contact, 'Contato atualizado com sucesso');
        }, 'Erro ao atualizar contato.');
    }

    public function deleteContact(int $id): ServiceResult
    {
        return $this->safeExecute(function () use ($id) {
            $deleted = $this->contactRepository->delete($id);
            if (! $deleted) {
                return $this->error(\App\Enums\OperationStatus::NOT_FOUND, 'Contato não encontrado');
            }

            return $this->success(null, 'Contato removido com sucesso');
        }, 'Erro ao remover contato.');
    }
}
