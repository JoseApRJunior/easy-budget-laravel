<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request para validação de cadastro de Pessoa Jurídica
 *
 * Implementa validação avançada para clientes pessoa jurídica com
 * suporte a dados empresariais e múltiplos contatos.
 */
class CustomerPessoaJuridicaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Implementar lógica de autorização conforme necessidade
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Dados empresariais obrigatórios
            'company_name'             => 'required|string|max:255|regex:/^[a-zA-ZÀ-ÿ0-9\s&\-\.]+$/',
            'fantasy_name'             => 'nullable|string|max:255|regex:/^[a-zA-ZÀ-ÿ0-9\s&\-\.]+$/',
            'document'                 => 'required|string|size:14|unique:customers,document|regex:/^\d{14}$/',
            'state_registration'       => 'nullable|string|max:20|regex:/^[0-9]+$/',
            'municipal_registration'   => 'nullable|string|max:20|regex:/^[0-9]+$/',
            'founding_date'            => 'nullable|date|before:today|after:1800-01-01',
            'company_email'            => 'required|email|max:255|unique:customers,email',
            'company_phone'            => 'required|string|regex:/^\(\d{2}\)\s\d{4,5}-\d{4}$/',
            'company_website'          => 'nullable|url|max:255',
            'industry'                 => 'nullable|string|max:100',
            'company_size'             => 'nullable|string|in:micro,pequena,media,grande',
            'notes'                    => 'nullable|string|max:1000',

            // Dados do contato principal (opcional)
            'contact_person_name'      => 'nullable|string|max:255|regex:/^[a-zA-ZÀ-ÿ\s]+$/',
            'contact_person_role'      => 'nullable|string|max:100',
            'contact_person_email'     => 'nullable|email|max:255',
            'contact_person_phone'     => 'nullable|string|regex:/^\(\d{2}\)\s\d{4,5}-\d{4}$/',

            // Configurações do cliente
            'customer_type'            => 'required|in:company',
            'priority_level'           => 'required|in:normal,vip,premium',
            'status'                   => 'required|in:active,inactive',

            // Área de atuação
            'area_of_activity_id'      => 'nullable|integer|exists:area_of_activities,id',

            // Tags
            'tags'                     => 'nullable|array',
            'tags.*'                   => 'integer|exists:customer_tags,id',

            // Endereços (mínimo 1 endereço obrigatório)
            'addresses'                => 'required|array|min:1|max:10',
            'addresses.*.type'         => 'required|string|in:principal,trabalho,cobranca,entrega,outros',
            'addresses.*.cep'          => 'required|string|size:9|regex:/^\d{5}-?\d{3}$/',
            'addresses.*.street'       => 'required|string|max:255',
            'addresses.*.number'       => 'required|string|max:20',
            'addresses.*.complement'   => 'nullable|string|max:100',
            'addresses.*.neighborhood' => 'required|string|max:100',
            'addresses.*.city'         => 'required|string|max:100',
            'addresses.*.state'        => 'required|string|size:2|alpha',
            'addresses.*.latitude'     => 'nullable|numeric|between:-90,90',
            'addresses.*.longitude'    => 'nullable|numeric|between:-180,180',

            // Contatos adicionais (mínimo 1 contato obrigatório)
            'contacts'                 => 'required|array|min:1|max:10',
            'contacts.*.type'          => 'required|string|in:email,phone,whatsapp,linkedin,site,skype,outros',
            'contacts.*.label'         => 'nullable|string|max:100',
            'contacts.*.value'         => 'required|string|max:255',
            'contacts.*.is_primary'    => 'boolean',

            // Validações condicionais para tipos específicos de contato
            'contacts.*.value'         => 'required_if:contacts.*.type,email|email|unique:customer_contacts,value',
            'contacts.*.value'         => 'required_if:contacts.*.type,phone|regex:/^\(\d{2}\)\s\d{4,5}-\d{4}$/',
            'contacts.*.value'         => 'required_if:contacts.*.type,whatsapp|regex:/^\(\d{2}\)\s\d{4,5}-\d{4}$/',
            'contacts.*.value'         => 'required_if:contacts.*.type,site|url',
            'contacts.*.value'         => 'required_if:contacts.*.type,linkedin|url|regex:/^https:\/\/[www\.]?linkedin\.com/',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'company_name'             => 'razão social',
            'fantasy_name'             => 'nome fantasia',
            'document'                 => 'CNPJ',
            'state_registration'       => 'inscrição estadual',
            'municipal_registration'   => 'inscrição municipal',
            'founding_date'            => 'data de fundação',
            'company_email'            => 'email empresarial',
            'company_phone'            => 'telefone empresarial',
            'company_website'          => 'website',
            'industry'                 => 'setor de atuação',
            'company_size'             => 'porte da empresa',
            'contact_person_name'      => 'nome do contato',
            'contact_person_role'      => 'cargo do contato',
            'contact_person_email'     => 'email do contato',
            'contact_person_phone'     => 'telefone do contato',
            'customer_type'            => 'tipo de cliente',
            'priority_level'           => 'nível de prioridade',
            'status'                   => 'status',
            'area_of_activity_id'      => 'área de atuação',
            'addresses'                => 'endereços',
            'addresses.*.type'         => 'tipo de endereço',
            'addresses.*.cep'          => 'CEP',
            'addresses.*.street'       => 'logradouro',
            'addresses.*.number'       => 'número',
            'addresses.*.complement'   => 'complemento',
            'addresses.*.neighborhood' => 'bairro',
            'addresses.*.city'         => 'cidade',
            'addresses.*.state'        => 'estado',
            'contacts'                 => 'contatos',
            'contacts.*.type'          => 'tipo de contato',
            'contacts.*.label'         => 'rótulo do contato',
            'contacts.*.value'         => 'valor do contato',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'company_name.required'        => 'A razão social é obrigatória.',
            'company_name.regex'           => 'A razão social deve conter apenas letras, números e símbolos comuns.',
            'fantasy_name.regex'           => 'O nome fantasia deve conter apenas letras, números e símbolos comuns.',
            'document.required'            => 'O CNPJ é obrigatório.',
            'document.size'                => 'O CNPJ deve ter 14 dígitos.',
            'document.regex'               => 'Digite um CNPJ válido (apenas números).',
            'document.unique'              => 'Este CNPJ já está cadastrado.',
            'company_email.required'       => 'O email empresarial é obrigatório.',
            'company_email.email'          => 'Digite um email empresarial válido.',
            'company_email.unique'         => 'Este email empresarial já está cadastrado.',
            'company_phone.required'       => 'O telefone empresarial é obrigatório.',
            'company_phone.regex'          => 'Digite um telefone empresarial válido no formato (00) 00000-0000.',
            'company_website.url'          => 'Digite uma URL válida para o website.',
            'founding_date.before'         => 'A data de fundação deve ser anterior a hoje.',
            'founding_date.after'          => 'A data de fundação deve ser posterior a 1800.',
            'state_registration.regex'     => 'A inscrição estadual deve conter apenas números.',
            'municipal_registration.regex' => 'A inscrição municipal deve conter apenas números.',
            'addresses.required'           => 'Pelo menos um endereço deve ser informado.',
            'addresses.min'                => 'Pelo menos um endereço deve ser informado.',
            'addresses.*.cep.size'         => 'O CEP deve ter 9 caracteres (formato: 00000-000).',
            'addresses.*.cep.regex'        => 'Digite um CEP válido.',
            'addresses.*.state.size'       => 'O estado deve ter 2 caracteres.',
            'addresses.*.state.alpha'      => 'O estado deve conter apenas letras.',
            'contacts.required'            => 'Pelo menos um contato deve ser informado.',
            'contacts.min'                 => 'Pelo menos um contato deve ser informado.',
            'contacts.*.value.required_if' => 'O valor do contato é obrigatório para este tipo.',
            'contacts.*.value.email'       => 'Digite um email válido.',
            'contacts.*.value.unique'      => 'Este email já está cadastrado.',
            'contacts.*.value.regex'       => 'Digite um telefone válido no formato (00) 00000-0000.',
            'contacts.*.value.url'         => 'Digite uma URL válida.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Formatar CNPJ removendo caracteres especiais
        if ( $this->document ) {
            $this->merge( [
                'document' => preg_replace( '/\D/', '', $this->document ),
            ] );
        }

        // Formatar CEP removendo caracteres especiais
        if ( $this->addresses ) {
            foreach ( $this->addresses as $index => $address ) {
                if ( isset( $address[ 'cep' ] ) ) {
                    $this->addresses[ $index ][ 'cep' ] = preg_replace( '/\D/', '', $address[ 'cep' ] );
                }
            }
        }

        // Formatar telefones removendo caracteres especiais
        $phoneFields = [ 'company_phone', 'contact_person_phone' ];
        foreach ( $phoneFields as $field ) {
            if ( $this->$field ) {
                $this->merge( [
                    $field => preg_replace( '/\D/', '', $this->$field ),
                ] );
            }
        }

        // Formatar telefones dos contatos
        if ( $this->contacts ) {
            foreach ( $this->contacts as $index => $contact ) {
                if ( isset( $contact[ 'value' ] ) && in_array( $contact[ 'type' ], [ 'phone', 'whatsapp' ] ) ) {
                    $this->contacts[ $index ][ 'value' ] = preg_replace( '/\D/', '', $contact[ 'value' ] );
                }
            }
        }

        // Garantir que pelo menos um endereço seja principal
        if ( $this->addresses && !$this->hasPrimaryAddress() ) {
            $this->addresses[ 0 ][ 'is_primary' ] = true;
        }

        // Garantir que pelo menos um contato seja principal
        if ( $this->contacts && !$this->hasPrimaryContact() ) {
            $this->contacts[ 0 ][ 'is_primary' ] = true;
        }
    }

    /**
     * Check if addresses has at least one primary address.
     */
    private function hasPrimaryAddress(): bool
    {
        if ( !$this->addresses ) {
            return false;
        }

        foreach ( $this->addresses as $address ) {
            if ( isset( $address[ 'is_primary' ] ) && $address[ 'is_primary' ] ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if contacts has at least one primary contact.
     */
    private function hasPrimaryContact(): bool
    {
        if ( !$this->contacts ) {
            return false;
        }

        foreach ( $this->contacts as $contact ) {
            if ( isset( $contact[ 'is_primary' ] ) && $contact[ 'is_primary' ] ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator( $validator ): void
    {
        $validator->after( function ( $validator ) {
            // Validação adicional: garantir que pelo menos um email seja informado
            $hasEmail = false;
            if ( $this->contacts ) {
                foreach ( $this->contacts as $contact ) {
                    if ( isset( $contact[ 'type' ] ) && $contact[ 'type' ] === 'email' ) {
                        $hasEmail = true;
                        break;
                    }
                }
            }

            if ( !$hasEmail ) {
                $validator->errors()->add( 'contacts', 'Pelo menos um email deve ser informado.' );
            }

            // Validação adicional: CNPJ deve ser válido
            if ( $this->document && !$this->isValidCnpj( $this->document ) ) {
                $validator->errors()->add( 'document', 'O CNPJ informado não é válido.' );
            }

            // Validação adicional: se contato pessoal for informado, validar consistência
            if ( $this->contact_person_email && $this->company_email === $this->contact_person_email ) {
                $validator->errors()->add( 'contact_person_email', 'O email do contato deve ser diferente do email empresarial.' );
            }
        } );
    }

    /**
     * Validate Brazilian CNPJ.
     */
    private function isValidCnpj( string $cnpj ): bool
    {
        // Remove non-numeric characters
        $cnpj = preg_replace( '/\D/', '', $cnpj );

        // CNPJ must have 14 digits
        if ( strlen( $cnpj ) !== 14 ) {
            return false;
        }

        // Check if all digits are the same
        if ( preg_match( '/^(\d)\1+$/', $cnpj ) ) {
            return false;
        }

        // Calculate first check digit
        $sum        = 0;
        $multiplier = 2;
        for ( $i = 11; $i >= 0; $i-- ) {
            $sum += (int) $cnpj[ $i ] * $multiplier;
            $multiplier = $multiplier === 9 ? 2 : $multiplier + 1;
        }

        $remainder = $sum % 11;
        $digit1    = $remainder < 2 ? 0 : 11 - $remainder;

        if ( (int) $cnpj[ 12 ] !== $digit1 ) {
            return false;
        }

        // Calculate second check digit
        $sum        = 0;
        $multiplier = 2;
        for ( $i = 12; $i >= 0; $i-- ) {
            $sum += (int) $cnpj[ $i ] * $multiplier;
            $multiplier = $multiplier === 9 ? 2 : $multiplier + 1;
        }

        $remainder = $sum % 11;
        $digit2    = $remainder < 2 ? 0 : 11 - $remainder;

        return (int) $cnpj[ 13 ] === $digit2;
    }

}
