<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

/**
 * Form Request para atualização de clientes (Pessoa Física e Jurídica)
 *
 * Implementa validação dinâmica baseada no tipo de cliente (PF/PJ)
 * com suporte a validação de unicidade via CustomerRepository.
 */
class CustomerUpdateRequest extends FormRequest
{
    private ?int    $excludeCustomerId = null;
    private ?string $customerType      = null;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Obter ID do customer
        $this->excludeCustomerId = $this->route( 'customer' )?->id;

        // Determinar tipo do customer baseado nos dados existentes
        $customer = $this->route( 'customer' );
        if ( $customer && $customer->relationLoaded( 'commonData' ) ) {
            $commonData = $customer->commonData;
            if ( $commonData?->cnpj ) {
                $this->customerType = 'pessoa_juridica';
            } elseif ( $commonData?->cpf ) {
                $this->customerType = 'pessoa_fisica';
            }
        }
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $tenantId = auth()->user()->tenant_id;
        $rules    = [
            // Status sempre atualizável
            'status' => 'sometimes|in:active,inactive,deleted',
        ];

        // Regras comuns para ambos os tipos
        $commonRules = [
            'first_name'          => 'sometimes|required|string|max:100',
            'last_name'           => 'sometimes|required|string|max:100',
            'birth_date'          => 'sometimes|nullable|date|before:today|after:1900-01-01',
            'area_of_activity_id' => 'sometimes|nullable|integer|exists:areas_of_activity,id',
            'profession_id'       => 'sometimes|nullable|integer|exists:professions,id',
            'description'         => 'sometimes|nullable|string|max:500',
            'website'             => 'sometimes|nullable|url|max:255',
            'phone'               => 'sometimes|nullable|string|regex:/^\(\d{2}\)\s\d{4,5}-\d{4}$/',
            'phone_business'      => 'sometimes|nullable|string|regex:/^\(\d{2}\)\s\d{4,5}-\d{4}$/',

            // Endereço
            'cep'                 => 'sometimes|required|string|regex:/^\d{5}-?\d{3}$/',
            'address'             => 'sometimes|required|string|max:255',
            'address_number'      => 'sometimes|nullable|string|max:20',
            'neighborhood'        => 'sometimes|required|string|max:100',
            'city'                => 'sometimes|required|string|max:100',
            'state'               => 'sometimes|required|string|size:2|alpha',
        ];

        // Adicionar regras comuns
        $rules = array_merge( $rules, $commonRules );

        // Regras específicas baseadas no tipo
        if ( $this->customerType === 'pessoa_fisica' ) {
            $rules = array_merge( $rules, [
                'email'          => [
                    'sometimes',
                    'required',
                    'email',
                    'max:255',
                    function ( $attribute, $value, $fail ) use ( $tenantId ) {
                        $customerRepo = app( \App\Repositories\CustomerRepository::class);
                        if ( !$customerRepo->isEmailUnique( $value, $tenantId, $this->excludeCustomerId ) ) {
                            $fail( 'Este e-mail já está em uso por outro cliente.' );
                        }
                    }
                ],
                'cpf'            => [
                    'sometimes',
                    'required',
                    'string',
                    'regex:/^\d{11}$/',
                    function ( $attribute, $value, $fail ) use ( $tenantId ) {
                        // Limpar CPF (apenas números)
                        $cleanCpf = preg_replace( '/[^0-9]/', '', $value );

                        // Validar estrutura
                        if ( strlen( $cleanCpf ) !== 11 ) {
                            $fail( 'O CPF deve conter 11 dígitos.' );
                            return;
                        }

                        // Validar algoritmo
                        if ( !\App\Helpers\ValidationHelper::isValidCpf( $cleanCpf ) ) {
                            $fail( 'O CPF informado não é válido.' );
                            return;
                        }

                        // Validar unicidade
                        $customerRepo = app( \App\Repositories\CustomerRepository::class);
                        if ( !$customerRepo->isCpfUnique( $cleanCpf, $tenantId, $this->excludeCustomerId ) ) {
                            $fail( 'Este CPF já está em uso por outro cliente.' );
                        }
                    }
                ],
                'email_business' => 'sometimes|nullable|email|max:255',
            ] );
        } else {
            // Pessoa Jurídica
            $rules = array_merge( $rules, [
                'email'                  => 'sometimes|required|email|max:255',
                'email_business'         => [
                    'sometimes',
                    'required',
                    'email',
                    'max:255',
                    function ( $attribute, $value, $fail ) use ( $tenantId ) {
                        $customerRepo = app( \App\Repositories\CustomerRepository::class);
                        if ( !$customerRepo->isEmailUnique( $value, $tenantId, $this->excludeCustomerId ) ) {
                            $fail( 'Este e-mail empresarial já está em uso por outro cliente.' );
                        }
                    }
                ],
                'company_name'           => 'sometimes|required|string|max:255',
                'cnpj'                   => [
                    'sometimes',
                    'required',
                    'string',
                    'regex:/^\d{14}$/',
                    function ( $attribute, $value, $fail ) use ( $tenantId ) {
                        // Limpar CNPJ (apenas números)
                        $cleanCnpj = preg_replace( '/[^0-9]/', '', $value );

                        // Validar estrutura
                        if ( strlen( $cleanCnpj ) !== 14 ) {
                            $fail( 'O CNPJ deve conter 14 dígitos.' );
                            return;
                        }

                        // Validar algoritmo
                        if ( !\App\Helpers\ValidationHelper::isValidCnpj( $cleanCnpj ) ) {
                            $fail( 'O CNPJ informado não é válido.' );
                            return;
                        }

                        // Validar unicidade
                        $customerRepo = app( \App\Repositories\CustomerRepository::class);
                        if ( !$customerRepo->isCnpjUnique( $cleanCnpj, $tenantId, $this->excludeCustomerId ) ) {
                            $fail( 'Este CNPJ já está em uso por outro cliente.' );
                        }
                    }
                ],

                // Dados específicos PJ (BusinessData)
                'fantasy_name'           => 'sometimes|nullable|string|max:255',
                'state_registration'     => 'sometimes|nullable|string|max:50',
                'municipal_registration' => 'sometimes|nullable|string|max:50',
                'founding_date'          => 'sometimes|nullable|date|before:today|after:1800-01-01',
                'industry'               => 'sometimes|nullable|string|max:255',
                'company_size'           => 'sometimes|nullable|in:micro,pequena,media,grande',
                'notes'                  => 'sometimes|nullable|string|max:1000',
            ] );

            // Área de atuação obrigatória para PJ
            $rules[ 'area_of_activity_id' ] = 'sometimes|required|integer|exists:areas_of_activity,id';
        }

        return $rules;
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'first_name'             => 'nome',
            'last_name'              => 'sobrenome',
            'company_name'           => 'razão social',
            'email'                  => 'e-mail',
            'email_business'         => 'e-mail empresarial',
            'phone'                  => 'telefone',
            'phone_business'         => 'telefone comercial',
            'cpf'                    => 'CPF',
            'cnpj'                   => 'CNPJ',
            'fantasy_name'           => 'nome fantasia',
            'state_registration'     => 'inscrição estadual',
            'municipal_registration' => 'inscrição municipal',
            'founding_date'          => 'data de fundação',
            'area_of_activity_id'    => 'área de atuação',
            'profession_id'          => 'profissão',
            'company_size'           => 'porte da empresa',
            'industry'               => 'setor de atuação',
            'notes'                  => 'observações',
            'birth_date'             => 'data de nascimento',
            'description'            => 'descrição',
            'website'                => 'website',
            'cep'                    => 'CEP',
            'address'                => 'endereço',
            'address_number'         => 'número',
            'neighborhood'           => 'bairro',
            'city'                   => 'cidade',
            'state'                  => 'estado',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            // Mensagens de status
            'status.in'                    => 'Status deve ser: ativo, inativo ou excluído.',

            // Mensagens de campos obrigatórios
            'first_name.required'          => 'O nome é obrigatório.',
            'last_name.required'           => 'O sobrenome é obrigatório.',
            'company_name.required'        => 'A razão social é obrigatória.',
            'email.required'               => 'O e-mail é obrigatório.',
            'email_business.required'      => 'O e-mail empresarial é obrigatório.',
            'phone.required'               => 'O telefone é obrigatório.',
            'area_of_activity_id.required' => 'A área de atuação é obrigatória para pessoa jurídica.',

            // Mensagens de validação de e-mail
            'email.email'                  => 'Digite um e-mail válido.',
            'email.unique'                 => 'Este e-mail já está em uso por outro cliente.',
            'email_business.email'         => 'Digite um e-mail empresarial válido.',
            'email_business.unique'        => 'Este e-mail empresarial já está em uso por outro cliente.',

            // Mensagens de validação de documentos
            'cpf.required'                 => 'O CPF é obrigatório.',
            'cpf.regex'                    => 'O CPF deve conter 11 dígitos numéricos.',
            'cpf.unique'                   => 'Este CPF já está em uso por outro cliente.',
            'cnpj.required'                => 'O CNPJ é obrigatório para pessoa jurídica.',
            'cnpj.regex'                   => 'O CNPJ deve conter 14 dígitos numéricos.',
            'cnpj.unique'                  => 'Este CNPJ já está em uso por outro cliente.',

            // Mensagens de validação de telefone
            'phone.regex'                  => 'Digite um telefone válido no formato (00) 00000-0000.',
            'phone_business.regex'         => 'Digite um telefone comercial válido no formato (00) 00000-0000.',

            // Mensagens de validação de data
            'birth_date.before'            => 'A data de nascimento deve ser anterior a hoje.',
            'birth_date.after'             => 'A data de nascimento deve ser posterior a 1900.',
            'founding_date.before'         => 'A data de fundação deve ser anterior a hoje.',
            'founding_date.after'          => 'A data de fundação deve ser posterior a 1800.',

            // Mensagens de validação de outros campos
            'description.max'              => 'A descrição deve ter no máximo 500 caracteres.',
            'notes.max'                    => 'As observações devem ter no máximo 1000 caracteres.',
            'website.url'                  => 'Digite uma URL válida.',
            'area_of_activity_id.exists'   => 'A área de atuação selecionada não existe.',
            'company_size.in'              => 'O porte da empresa deve ser: micro, pequena, média ou grande.',

            // Mensagens de validação de endereço
            'cep.required'                 => 'O CEP é obrigatório.',
            'cep.regex'                    => 'Digite um CEP válido no formato 00000-000.',
            'address.required'             => 'O endereço é obrigatório.',
            'neighborhood.required'        => 'O bairro é obrigatório.',
            'city.required'                => 'A cidade é obrigatória.',
            'state.required'               => 'O estado é obrigatório.',
            'state.size'                   => 'O estado deve ter 2 caracteres.',
            'state.alpha'                  => 'O estado deve conter apenas letras.',
        ];
    }

}
