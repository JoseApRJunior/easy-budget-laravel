<?php

namespace App\Mail\Concerns;

use App\Models\Tenant;
use App\Models\User;
use App\Services\Infrastructure\LinkService;

/**
 * Classe base abstrata para e-mails simples sem tokens de confirmação.
 *
 * Especializada em e-mails informativos simples como notificações,
 * seguindo o padrão identificado no sistema.
 */
abstract class AbstractBaseSimpleEmail extends BaseEmail
{
    /**
     * Dados adicionais específicos do e-mail.
     */
    protected array $additionalData;

    /**
     * Cria uma nova instância da mailable simples.
     */
    public function __construct(
        User $user,
        ?Tenant $tenant = null,
        array $additionalData = [],
        ?LinkService $linkService = null,
    ) {
        parent::__construct($user, $tenant, $linkService);

        $this->additionalData = $additionalData;
    }

    /**
     * Obtém dados básicos combinados com dados adicionais.
     *
     * @return array Dados para template
     */
    protected function getTemplateData(): array
    {
        return array_merge($this->getUserBasicData(), $this->additionalData, [
            'tenant' => $this->tenant,
            'user' => $this->user,
        ]);
    }
}
