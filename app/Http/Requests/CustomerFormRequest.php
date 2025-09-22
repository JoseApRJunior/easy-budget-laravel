<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomerFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $customerId = $this->route( 'customer' ) ? $this->route( 'customer' )->id : null;

        return [ 
            'name'                   => 'required|string|max:255',
            'email'                  => 'nullable|string|email|max:255|unique:customers,email,' . $customerId,
            'phone'                  => 'nullable|string|max:20',
            'cpf_cnpj'               => 'nullable|string|max:20|unique:customers,cpf_cnpj,' . $customerId,
            'rg'                     => 'nullable|string|max:20',
            'birth_date'             => 'nullable|date|before:today',
            'gender'                 => 'nullable|string|in:M,F,O',
            'marital_status'         => 'nullable|string|in:single,married,divorced,widowed',
            'profession_id'          => 'nullable|integer|exists:professions,id',
            'company_name'           => 'nullable|string|max:255',
            'notes'                  => 'nullable|string|max:1000',

            // Address validation
            'address.street'         => 'nullable|string|max:255',
            'address.number'         => 'nullable|string|max:20',
            'address.complement'     => 'nullable|string|max:100',
            'address.neighborhood'   => 'nullable|string|max:100',
            'address.city'           => 'nullable|string|max:100',
            'address.state'          => 'nullable|string|max:2',
            'address.zip_code'       => 'nullable|string|max:10',
            'address.country'        => 'nullable|string|max:50',

            // Contact validation
            'contacts'               => 'nullable|array',
            'contacts.*.type'        => 'required_with:contacts|string|in:phone,email,whatsapp',
            'contacts.*.value'       => 'required_with:contacts|string|max:255',
            'contacts.*.description' => 'nullable|string|max:100',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [ 
            'name.required'                  => 'O nome é obrigatório.',
            'name.string'                    => 'O nome deve ser uma string.',
            'name.max'                       => 'O nome não pode ter mais de 255 caracteres.',

            'email.string'                   => 'O email deve ser uma string.',
            'email.email'                    => 'O email deve ser um endereço de email válido.',
            'email.max'                      => 'O email não pode ter mais de 255 caracteres.',
            'email.unique'                   => 'Este email já está sendo usado por outro cliente.',

            'phone.string'                   => 'O telefone deve ser uma string.',
            'phone.max'                      => 'O telefone não pode ter mais de 20 caracteres.',

            'cpf_cnpj.string'                => 'O CPF/CNPJ deve ser uma string.',
            'cpf_cnpj.max'                   => 'O CPF/CNPJ não pode ter mais de 20 caracteres.',
            'cpf_cnpj.unique'                => 'Este CPF/CNPJ já está sendo usado por outro cliente.',

            'rg.string'                      => 'O RG deve ser uma string.',
            'rg.max'                         => 'O RG não pode ter mais de 20 caracteres.',

            'birth_date.date'                => 'A data de nascimento deve ser uma data válida.',
            'birth_date.before'              => 'A data de nascimento deve ser anterior à data atual.',

            'gender.string'                  => 'O gênero deve ser uma string.',
            'gender.in'                      => 'O gênero deve ser M (Masculino), F (Feminino) ou O (Outro).',

            'marital_status.string'          => 'O estado civil deve ser uma string.',
            'marital_status.in'              => 'O estado civil deve ser solteiro, casado, divorciado ou viúvo.',

            'profession_id.integer'          => 'A profissão deve ser um número inteiro.',
            'profession_id.exists'           => 'A profissão selecionada não existe.',

            'company_name.string'            => 'O nome da empresa deve ser uma string.',
            'company_name.max'               => 'O nome da empresa não pode ter mais de 255 caracteres.',

            'notes.string'                   => 'As observações devem ser uma string.',
            'notes.max'                      => 'As observações não podem ter mais de 1000 caracteres.',

            // Address messages
            'address.street.string'          => 'A rua deve ser uma string.',
            'address.street.max'             => 'A rua não pode ter mais de 255 caracteres.',

            'address.number.string'          => 'O número deve ser uma string.',
            'address.number.max'             => 'O número não pode ter mais de 20 caracteres.',

            'address.complement.string'      => 'O complemento deve ser uma string.',
            'address.complement.max'         => 'O complemento não pode ter mais de 100 caracteres.',

            'address.neighborhood.string'    => 'O bairro deve ser uma string.',
            'address.neighborhood.max'       => 'O bairro não pode ter mais de 100 caracteres.',

            'address.city.string'            => 'A cidade deve ser uma string.',
            'address.city.max'               => 'A cidade não pode ter mais de 100 caracteres.',

            'address.state.string'           => 'O estado deve ser uma string.',
            'address.state.max'              => 'O estado deve ter exatamente 2 caracteres.',

            'address.zip_code.string'        => 'O CEP deve ser uma string.',
            'address.zip_code.max'           => 'O CEP não pode ter mais de 10 caracteres.',

            'address.country.string'         => 'O país deve ser uma string.',
            'address.country.max'            => 'O país não pode ter mais de 50 caracteres.',

            // Contact messages
            'contacts.array'                 => 'Os contatos devem ser um array.',

            'contacts.*.type.required_with'  => 'O tipo de contato é obrigatório.',
            'contacts.*.type.string'         => 'O tipo de contato deve ser uma string.',
            'contacts.*.type.in'             => 'O tipo de contato deve ser telefone, email ou whatsapp.',

            'contacts.*.value.required_with' => 'O valor do contato é obrigatório.',
            'contacts.*.value.string'        => 'O valor do contato deve ser uma string.',
            'contacts.*.value.max'           => 'O valor do contato não pode ter mais de 255 caracteres.',

            'contacts.*.description.string'  => 'A descrição do contato deve ser uma string.',
            'contacts.*.description.max'     => 'A descrição do contato não pode ter mais de 100 caracteres.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [ 
            'name'                   => 'nome',
            'email'                  => 'email',
            'phone'                  => 'telefone',
            'cpf_cnpj'               => 'CPF/CNPJ',
            'rg'                     => 'RG',
            'birth_date'             => 'data de nascimento',
            'gender'                 => 'gênero',
            'marital_status'         => 'estado civil',
            'profession_id'          => 'profissão',
            'company_name'           => 'nome da empresa',
            'notes'                  => 'observações',
            'address.street'         => 'rua',
            'address.number'         => 'número',
            'address.complement'     => 'complemento',
            'address.neighborhood'   => 'bairro',
            'address.city'           => 'cidade',
            'address.state'          => 'estado',
            'address.zip_code'       => 'CEP',
            'address.country'        => 'país',
            'contacts'               => 'contatos',
            'contacts.*.type'        => 'tipo de contato',
            'contacts.*.value'       => 'valor do contato',
            'contacts.*.description' => 'descrição do contato',
        ];
    }

}