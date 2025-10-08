<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request para validação de cadastro de Pessoa Física
 *
 * Implementa validação avançada para clientes pessoa física com
 * suporte a múltiplos endereços e contatos.
 */
class CustomerPessoaFisicaRequest extends FormRequest
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
            // Dados básicos do cliente
            'name'                     => 'required|string|max:255|regex:/^[a-zA-ZÀ-ÿ\s]+$/',
            'email'                    => 'required|email|max:255|unique:customers,email',
            'document'                 => 'required|string|size:11|unique:customers,document|regex:/^\d{11}$/',
            'birth_date'               => 'nullable|date|before:today|after:1900-01-01',
            'profession'               => 'nullable|string|max:100',
            'notes'                    => 'nullable|string|max:1000',

            // Configurações do cliente
            'customer_type'            => 'required|in:individual',
            'priority_level'           => 'required|in:normal,vip,premium',
            'status'                   => 'required|in:active,inactive',

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

            // Contatos (mínimo 1 contato obrigatório)
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
            'name'                     => 'nome completo',
            'email'                    => 'email',
            'document'                 => 'CPF',
            'birth_date'               => 'data de nascimento',
            'profession'               => 'profissão',
            'notes'                    => 'observações',
            'customer_type'            => 'tipo de cliente',
            'priority_level'           => 'nível de prioridade',
            'status'                   => 'status',
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
            'name.required'                => 'O nome completo é obrigatório.',
            'name.regex'                   => 'O nome deve conter apenas letras e espaços.',
            'email.required'               => 'O email é obrigatório.',
            'email.email'                  => 'Digite um email válido.',
            'email.unique'                 => 'Este email já está cadastrado.',
            'document.required'            => 'O CPF é obrigatório.',
            'document.size'                => 'O CPF deve ter 11 dígitos.',
            'document.regex'               => 'Digite um CPF válido (apenas números).',
            'document.unique'              => 'Este CPF já está cadastrado.',
            'birth_date.before'            => 'A data de nascimento deve ser anterior a hoje.',
            'birth_date.after'             => 'A data de nascimento deve ser posterior a 1900.',
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
        // Formatar CPF removendo caracteres especiais
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

            // Validação adicional: CPF deve ser válido
            if ( $this->document && !$this->isValidCpf( $this->document ) ) {
                $validator->errors()->add( 'document', 'O CPF informado não é válido.' );
            }
        } );
    }

    /**
     * Validate Brazilian CPF.
     */
    private function isValidCpf( string $cpf ): bool
    {
        // Remove non-numeric characters
        $cpf = preg_replace( '/\D/', '', $cpf );

        // CPF must have 11 digits
        if ( strlen( $cpf ) !== 11 ) {
            return false;
        }

        // Check if all digits are the same
        if ( preg_match( '/^(\d)\1+$/', $cpf ) ) {
            return false;
        }

        // Calculate first check digit
        $sum = 0;
        for ( $i = 0; $i < 9; $i++ ) {
            $sum += (int) $cpf[ $i ] * ( 10 - $i );
        }

        $remainder = $sum % 11;
        $digit1    = $remainder < 2 ? 0 : 11 - $remainder;

        if ( (int) $cpf[ 9 ] !== $digit1 ) {
            return false;
        }

        // Calculate second check digit
        $sum = 0;
        for ( $i = 0; $i < 10; $i++ ) {
            $sum += (int) $cpf[ $i ] * ( 11 - $i );
        }

        $remainder = $sum % 11;
        $digit2    = $remainder < 2 ? 0 : 11 - $remainder;

        return (int) $cpf[ 10 ] === $digit2;
    }

}
