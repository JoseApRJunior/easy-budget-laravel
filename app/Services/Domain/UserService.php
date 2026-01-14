<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\DTOs\User\UserDTO;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Support\Enums\OperationStatus;
use App\Support\ServiceResult;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Serviço para operações de usuário com tenant.
 *
 * Migra lógica legacy: criação com hash de senha, tokens de confirmação,
 * ativação de conta, gerenciamento de usuários. Usa Eloquent via repositórios.
 * Mantém compatibilidade API com métodos *ByTenantId.
 */
class UserService extends AbstractBaseService
{
    public function __construct(UserRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Define o Model a ser utilizado pelo Service.
     */
    protected function makeModel(): Model
    {
        return new User;
    }

    /**
     * Encontra usuário por ID (usa tenant do usuário atual).
     */
    public function findById(int $id, array $with = []): ServiceResult
    {
        return $this->safeExecute(function () use ($id, $with) {
            $user = $this->repository->find($id, $with);

            if (! $user) {
                return $this->error(OperationStatus::NOT_FOUND, 'Usuário não encontrado');
            }

            return $this->success($user, 'Usuário encontrado com sucesso');
        }, 'Erro ao buscar usuário');
    }

    /**
     * Encontra usuário por ID e tenant ID.
     */
    public function findByIdAndTenantId(int $id, int $tenantId): ServiceResult
    {
        return $this->safeExecute(function () use ($id) {
            $user = $this->repository->find($id);

            if (! $user) {
                return $this->error(OperationStatus::NOT_FOUND, 'Usuário não encontrado');
            }

            return $this->success($user, 'Usuário encontrado com sucesso');
        }, 'Erro ao buscar usuário');
    }

    /**
     * Encontra usuário por email.
     */
    public function findByEmail(string $email): ServiceResult
    {
        return $this->safeExecute(function () use ($email) {
            $user = $this->repository->findByEmail($email);

            if (! $user) {
                return $this->error(OperationStatus::NOT_FOUND, 'Usuário não encontrado');
            }

            return $this->success($user, 'Usuário encontrado com sucesso');
        }, 'Erro ao buscar usuário');
    }

    /**
     * Atualiza dados pessoais do usuário (dados básicos + redes sociais).
     */
    public function updatePersonalData(array $data, int $tenantId): ServiceResult
    {
        return $this->safeExecute(function () use ($data) {
            $user = Auth::user();

            if (! $user) {
                return $this->error(OperationStatus::UNAUTHORIZED, 'Usuário não autenticado');
            }

            // Preparar dados para atualização via DTO
            $dtoData = array_merge([
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => (bool) $user->is_active,
                'tenant_id' => $user->tenant_id,
            ], $data);

            $dto = UserDTO::fromRequest($dtoData);

            // Atualizar usuário via repositório
            $this->repository->updateFromDTO($user->id, $dto);

            // Atualizar configurações de redes sociais se existirem
            if (
                isset($data['social_facebook']) || isset($data['social_twitter']) ||
                isset($data['social_linkedin']) || isset($data['social_instagram'])
            ) {

                $settingsService = app(\App\Services\Domain\SettingsService::class);
                $settingsService->updateUserSettings([
                    'social_facebook' => $data['social_facebook'] ?? null,
                    'social_twitter' => $data['social_twitter'] ?? null,
                    'social_linkedin' => $data['social_linkedin'] ?? null,
                    'social_instagram' => $data['social_instagram'] ?? null,
                ], $user);
            }

            return $this->success($this->repository->find($user->id), 'Dados pessoais atualizados com sucesso');
        }, 'Erro ao atualizar dados pessoais');
    }

    /**
     * Obtém dados do perfil do usuário para edição.
     */
    public function getProfileData(int $tenantId): ServiceResult
    {
        return $this->safeExecute(function () use ($tenantId) {
            $user = Auth::user();

            if (! $user) {
                return $this->error(OperationStatus::UNAUTHORIZED, 'Usuário não autenticado');
            }

            // Verificar se o usuário pertence ao tenant correto
            if ($user->tenant_id !== $tenantId) {
                return $this->error(OperationStatus::FORBIDDEN, 'Usuário não pertence ao tenant especificado');
            }

            // Carregar relacionamentos necessários usando repository
            $userWithRelations = $this->repository->find($user->id, ['provider.commonData', 'provider.contact', 'settings']);

            $settingsService = app(\App\Services\Domain\SettingsService::class);
            $completeSettings = $settingsService->getCompleteUserSettings($user);

            return $this->success([
                'user' => $userWithRelations ?? $user,
                'settings' => $completeSettings,
            ], 'Dados do perfil obtidos com sucesso');
        }, 'Erro ao obter dados do perfil');
    }
}
