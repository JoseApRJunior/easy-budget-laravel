<?php

namespace App\Mail\Concerns;

use App\Mail\Concerns\BaseEmail;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Infrastructure\LinkService;

/**
 * Classe base abstrata para e-mails que envolvem tokens de confirmação.
 *
 * Especializada em lidar com links de confirmação fornecidos externamente,
 * seguindo o padrão identificado nos e-mails de verificação e boas-vindas.
 */
abstract class AbstractBaseConfirmationEmail extends BaseEmail
{
    /**
     * URL de verificação personalizada.
     */
    protected ?string $confirmationLink;

    /**
     * Cria uma nova instância da mailable de confirmação.
     */
    public function __construct(
        User $user,
        ?Tenant $tenant = null,
        ?string $confirmationLink = null,
        ?LinkService $linkService = null,
    ) {
        parent::__construct( $user, $tenant, $linkService );

        $this->confirmationLink = $confirmationLink;
    }

    /**
     * Obtém dados específicos para e-mails de confirmação.
     *
     * @return array Dados para template de confirmação
     */
    protected function getConfirmationData(): array
    {
        return array_merge( $this->getUserBasicData(), [
            'confirmationLink' => $this->confirmationLink,
            'tenant_name'      => $this->tenant?->name ?? 'Easy Budget',
        ] );
    }

}
