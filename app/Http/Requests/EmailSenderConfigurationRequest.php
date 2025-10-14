<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request para validação de configurações de remetente de e-mail.
 *
 * Funcionalidades principais:
 * - Validação rigorosa de endereços de e-mail
 * - Validação de nomes de remetente
 * - Validação de domínios permitidos
 * - Sanitização automática de dados
 * - Integração com regras de negócio
 */
class EmailSenderConfigurationRequest extends FormRequest
{
    /**
     * Determina se o usuário está autorizado a fazer esta requisição.
     */
    public function authorize(): bool
    {
        // Verificar se usuário está autenticado
        if ( !$this->user() ) {
            return false;
        }

        // Verificar se tenant pode personalizar remetentes
        if ( !config( 'email-senders.tenants.customizable' ) ) {
            return false;
        }

        // Verificar se usuário tem permissão específica (se implementado)
        // Por ora, qualquer usuário autenticado pode configurar
        return true;
    }

    /**
     * Obtém as regras de validação que se aplicam à requisição.
     */
    public function rules(): array
    {
        return [
            'email'    => [
                'required',
                'email:rfc,dns',
                'max:' . config( 'email-senders.global.validation.max_email_length', 320 ),
                'unique:tenant_email_configurations,email,' . $this->user()?->tenant_id . ',tenant_id',
                function ( $attribute, $value, $fail ) {
                    $this->validateEmailDomain( $value, $fail );
                },
            ],
            'name'     => [
                'nullable',
                'string',
                'max:' . config( 'email-senders.global.validation.max_name_length', 100 ),
                'regex:/^[\p{L}\p{N}\s\-\.\']+$/u', // Apenas letras, números, espaços e pontuação básica
            ],
            'reply_to' => [
                'nullable',
                'email:rfc,dns',
                'max:' . config( 'email-senders.global.validation.max_email_length', 320 ),
            ],
        ];
    }

    /**
     * Obtém mensagens de erro personalizadas.
     */
    public function messages(): array
    {
        return [
            'email.required' => 'O endereço de e-mail é obrigatório.',
            'email.email'    => 'O endereço de e-mail deve ser válido.',
            'email.max'      => 'O endereço de e-mail não pode ter mais de :max caracteres.',
            'email.unique'   => 'Este endereço de e-mail já está configurado para outro tenant.',

            'name.string'    => 'O nome deve ser um texto válido.',
            'name.max'       => 'O nome não pode ter mais de :max caracteres.',
            'name.regex'     => 'O nome contém caracteres não permitidos.',

            'reply_to.email' => 'O e-mail de resposta deve ser válido.',
            'reply_to.max'   => 'O e-mail de resposta não pode ter mais de :max caracteres.',
        ];
    }

    /**
     * Obtém nomes de atributos personalizados.
     */
    public function attributes(): array
    {
        return [
            'email'    => 'endereço de e-mail',
            'name'     => 'nome do remetente',
            'reply_to' => 'e-mail de resposta',
        ];
    }

    /**
     * Prepara os dados para validação.
     */
    protected function prepareForValidation(): void
    {
        // Sanitizar dados de entrada
        $this->merge( [
            'email'    => filter_var( $this->email, FILTER_SANITIZE_EMAIL ),
            'name'     => $this->sanitizeName( $this->name ),
            'reply_to' => $this->reply_to ? filter_var( $this->reply_to, FILTER_SANITIZE_EMAIL ) : null,
        ] );
    }

    /**
     * Valida domínio do e-mail.
     */
    private function validateEmailDomain( string $email, callable $fail ): void
    {
        $domain = explode( '@', $email )[ 1 ] ?? '';

        if ( empty( $domain ) ) {
            $fail( 'Domínio de e-mail não identificado.' );
            return;
        }

        // Verificar domínios bloqueados
        $blockedDomains = array_filter( explode( ',', config( 'email-senders.global.validation.blocked_domains', '' ) ) );
        if ( in_array( $domain, $blockedDomains ) ) {
            $fail( 'Este domínio de e-mail não é permitido pelo sistema.' );
            return;
        }

        // Verificar domínios permitidos (se configurado)
        $allowedDomains = array_filter( explode( ',', config( 'email-senders.global.validation.allowed_domains', '' ) ) );
        if ( !empty( $allowedDomains ) && !in_array( $domain, $allowedDomains ) ) {
            $fail( 'Este domínio de e-mail não está na lista de domínios autorizados.' );
        }

        // Verificar se domínio tem registros MX válidos (DNS)
        if ( config( 'email-senders.global.validation.require_domain_verification', false ) ) {
            if ( !$this->isDomainValid( $domain ) ) {
                $fail( 'O domínio de e-mail não possui registros MX válidos.' );
            }
        }
    }

    /**
     * Sanitiza nome do remetente.
     */
    private function sanitizeName( ?string $name ): ?string
    {
        if ( !$name ) {
            return null;
        }

        // Remover caracteres potencialmente perigosos
        $sanitized = filter_var( $name, FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH );

        // Remover espaços extras
        $sanitized = preg_replace( '/\s+/', ' ', $sanitized );

        // Remover caracteres de controle
        $sanitized = preg_replace( '/[\x00-\x1F\x7F]/', '', $sanitized );

        return trim( $sanitized );
    }

    /**
     * Verifica se domínio tem registros MX válidos.
     */
    private function isDomainValid( string $domain ): bool
    {
        // Em produção, implementar verificação DNS real
        // Por ora, retorna true para domínios comuns
        $commonDomains = [ 'gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com' ];

        if ( in_array( $domain, $commonDomains ) ) {
            return true;
        }

        // Para outros domínios, verificar se getmxrr funciona
        return getmxrr( $domain, $mxRecords );
    }

    /**
     * Configura validação após a validação inicial.
     */
    protected function passedValidation(): void
    {
        // Log de configuração bem-sucedida
        \Log::info( 'Configuração de remetente validada com sucesso', [
            'user_id'   => $this->user()?->id,
            'tenant_id' => $this->user()?->tenant_id,
            'email'     => $this->email,
            'name'      => $this->name,
        ] );
    }

}
