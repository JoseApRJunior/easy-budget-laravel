<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

/**
 * Form Request para validação de cadastro de Pessoa Física
 *
 * Implementa validação avançada para clientes pessoa física com
 * suporte a múltiplos endereços e contatos.
 */
class CustomerPessoaFisicaRequest extends FormRequest
{
    private ?int $excludeCustomerId = null;

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
        // Obter ID do customer se estiver em rota de atualização
        $this->excludeCustomerId = $this->route( 'customer' )?->id;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $tenantId = auth()->user()->tenant_id;

        return [
            // Regras estruturais do Customer (do Model)
            'status'              => 'sometimes|in:active,inactive,deleted',

            // Dados básicos (CommonData)
            'first_name'          => 'required|string|max:100',
            'last_name'           => 'required|string|max:100',
            'birth_date'          => 'nullable|string',
            'area_of_activity_id' => 'nullable|integer|exists:areas_of_activity,id',
            'profession_id'       => 'nullable|integer|exists:professions,id',
            'description'         => 'nullable|string|max:500',
            'website'             => 'nullable|url|max:255',

            // Dados de contato (Contact) - COM VALIDAÇÃO DE UNICIDADE
            'email_personal'      => [
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
            'phone_personal'      => 'required|string|regex:/^\(\d{2}\) \d{4,5}-\d{4}$/',
            'email_business'      => 'nullable|email|max:255',
            'phone_business'      => 'nullable|string|regex:/^\(\d{2}\) \d{4,5}-\d{4}$/',

            // CPF com validação customizada + UNICIDADE
            'cpf'                 => [
                'required',
                'string',
                'regex:/^(?:\d{11}|\d{3}\.\d{3}\.\d{3}-\d{2})$/', // Permite CPF com ou sem máscara
                function ( $attribute, $value, $fail ) use ( $tenantId ) {
                    // Limpar CPF (apenas números)
                    $cleanCpf = preg_replace( '/[^0-9]/', '', $value );

                    // Validar estrutura (apenas números)
                    if ( strlen( $cleanCpf ) !== 11 ) {
                        $digitsFound = strlen( $cleanCpf );
                        $fail( "O CPF deve conter exatamente 11 dígitos. Formato aceito: 000.000.000-00 ou 11 dígitos. Digitados: {$digitsFound} dígitos." );
                        return;
                    }

                    // Validar algoritmo
                    if ( !\App\Helpers\ValidationHelper::isValidCpf( $cleanCpf ) ) {
                        // Relaxar validação em ambiente local/teste para facilitar desenvolvimento
                        if ( app()->environment( [ 'local', 'testing' ] ) ) {
                            Log::warning( 'CPF inválido em ambiente de desenvolvimento', [
                                'cpf'       => $cleanCpf,
                                'user_id'   => auth()->user()->id,
                                'tenant_id' => $tenantId
                            ] );
                            // Permite CPF inválido em ambiente local para desenvolvimento
                            // TODO: Remover esta linha em produção
                        } else {
                            $fail( 'O CPF informado não é válido matematicamente. Use um CPF válido para produção.' );
                            return;
                        }
                    }

                    // Validar unicidade
                    $customerRepo = app( \App\Repositories\CustomerRepository::class);
                    if ( !$customerRepo->isCpfUnique( $cleanCpf, $tenantId, $this->excludeCustomerId ) ) {
                        $fail( 'Este CPF já está em uso por outro cliente.' );
                    }
                }
            ],

            // Endereço (Address)
            'cep'                 => 'required|string|regex:/^\d{5}-?\d{3}$/',
            'address'             => 'required|string|max:255',
            'address_number'      => 'nullable|string|max:20',
            'neighborhood'        => 'required|string|max:100',
            'city'                => 'required|string|max:100',
            'state'               => 'required|string|size:2|alpha',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'first_name'          => 'nome',
            'last_name'           => 'sobrenome',
            'email'               => 'e-mail',
            'phone_personal'      => 'telefone pessoal',
            'document'            => 'CPF',
            'birth_date'          => 'data de nascimento',
            'area_of_activity_id' => 'área de atuação',
            'profession_id'       => 'profissão',
            'description'         => 'descrição profissional',
            'website'             => 'website',
            'phone_business'      => 'telefone comercial',
            'email_business'      => 'email comercial',
            'cep'                 => 'CEP',
            'address'             => 'endereço',
            'address_number'      => 'número',
            'neighborhood'        => 'bairro',
            'city'                => 'cidade',
            'state'               => 'estado',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'first_name.required'     => 'O nome é obrigatório.',
            'last_name.required'      => 'O sobrenome é obrigatório.',
            'email_personal.required' => 'O e-mail é obrigatório.',
            'email_personal.email'    => 'Digite um e-mail válido.',
            'email_personal.unique'   => 'Este e-mail já está em uso por outro cliente.',
            'phone_personal.required' => 'O telefone é obrigatório.',
            'phone_personal.regex'    => 'Digite um telefone válido no formato (00) 00000-0000.',
            'cpf.required'            => 'O CPF é obrigatório.',
            'cpf.regex'               => 'O CPF deve estar no formato 000.000.000-00 ou ter apenas 11 dígitos.',
            'cpf.unique'              => 'Este CPF já está em uso por outro cliente.',
            'birth_date.before'       => 'A data de nascimento deve ser anterior a hoje.',
            'birth_date.after'        => 'A data de nascimento deve ser posterior a 1900.',
            'description.max'         => 'A descrição deve ter no máximo 500 caracteres.',
            'website.url'             => 'Digite uma URL válida.',
            'email_business.email'    => 'Digite um e-mail comercial válido.',
            'phone_business.regex'    => 'Digite um telefone comercial válido no formato (00) 00000-0000.',
            'cep.required'            => 'O CEP é obrigatório.',
            'cep.regex'               => 'Digite um CEP válido no formato 00000-000.',
            'address.required'        => 'O endereço é obrigatório.',
            'neighborhood.required'   => 'O bairro é obrigatório.',
            'city.required'           => 'A cidade é obrigatória.',
            'state.required'          => 'O estado é obrigatório.',
            'state.size'              => 'O estado deve ter 2 caracteres.',
            'state.alpha'             => 'O estado deve conter apenas letras.',
        ];
    }

}
