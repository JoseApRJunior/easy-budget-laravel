<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Validação para criação e atualização de prestadores.
 * Migração das regras do sistema legacy app/request/ProviderFormRequest.php.
 * Inclui validação de relacionamentos com usuário, dados comuns, contato e endereço.
 *
 * @package App\Http\Requests
 * @author IA
 */
class ProviderFormRequest extends FormRequest
{
    /**
     * Determina se o usuário está autorizado a fazer este request.
     * O usuário deve ser o próprio prestador ou ter permissões administrativas.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        $user = Auth::user();
        if ( !$user ) {
            return false;
        }

        // Para criação, o usuário deve estar logado
        // Para atualização, deve ser o próprio prestador ou admin
        if ( $this->isMethod( 'post' ) ) {
            return true;
        }

        $providerId = $this->route( 'provider' ) ?? $this->input( 'id' );
        if ( !$providerId ) {
            return false;
        }

        // Verifica se o usuário é o prestador ou tem role admin
        return \Illuminate\Support\Facades\DB::table( 'providers' )
            ->join( 'users', 'providers.user_id', '=', 'users.id' )
            ->where( 'providers.id', $providerId )
            ->where( function ($query) use ($user) {
                $query->where( 'users.id', $user->id )
                    ->orWhere( 'users.role_id', 1 ); // Admin role
            } )
            ->exists();
    }

    /**
     * Regras de validação para criação/atualização de prestador.
     *
     * @return array<string, array|Rule|string>
     */
    public function rules(): array
    {
        $userId   = Auth::id();
        $tenantId = Auth::user()?->tenant_id;

        return [ 
            'user_id'                   => [ 
                'required',
                'integer',
                'exists:users,id',
                Rule::unique( 'providers', 'user_id' )->ignore( $this->provider )
            ],
            'common_data_id'            => [ 
                'required',
                'integer',
                'exists:common_data,id',
                function ($attribute, $value, $fail) use ($tenantId) {
                    $commonDataTenant = \Illuminate\Support\Facades\DB::table( 'common_data' )
                        ->where( 'id', $value )
                        ->value( 'tenant_id' );

                    if ( $commonDataTenant !== $tenantId ) {
                        $fail( 'Os dados comuns não pertencem ao seu tenant.' );
                    }
                }
            ],
            'contact_id'                => [ 
                'required',
                'integer',
                'exists:contacts,id',
                function ($attribute, $value, $fail) use ($tenantId) {
                    $contactTenant = \Illuminate\Support\Facades\DB::table( 'contacts' )
                        ->where( 'id', $value )
                        ->value( 'tenant_id' );

                    if ( $contactTenant !== $tenantId ) {
                        $fail( 'O contato não pertence ao seu tenant.' );
                    }
                }
            ],
            'address_id'                => [ 
                'required',
                'integer',
                'exists:addresses,id',
                function ($attribute, $value, $fail) use ($tenantId) {
                    $addressTenant = \Illuminate\Support\Facades\DB::table( 'addresses' )
                        ->where( 'id', $value )
                        ->value( 'tenant_id' );

                    if ( $addressTenant !== $tenantId ) {
                        $fail( 'O endereço não pertence ao seu tenant.' );
                    }
                }
            ],
            'terms_accepted'            => [ 
                'required',
                'accepted'
            ],
            'provider_type'             => [ 
                'required',
                Rule::in( [ 'individual', 'company' ] )
            ],
            'registration_number'       => [ 
                'nullable',
                'string',
                'max:50'
            ],
            'tax_regime'                => [ 
                'nullable',
                Rule::in( [ 'simples_nacional', 'lucro_presumido', 'lucro_real', 'none' ] )
            ],
            'bank_account'              => [ 
                'nullable',
                'array'
            ],
            'bank_account.bank'         => [ 
                'nullable',
                'string',
                'max:50'
            ],
            'bank_account.agency'       => [ 
                'nullable',
                'string',
                'max:20'
            ],
            'bank_account.account'      => [ 
                'nullable',
                'string',
                'max:20'
            ],
            'bank_account.account_type' => [ 
                'nullable',
                Rule::in( [ 'checking', 'savings' ] )
            ],
            'tenant_id'                 => [ 
                'required',
                'integer',
                'exists:tenants,id'
            ],
            'is_active'                 => [ 
                'nullable',
                'boolean'
            ],
            'profile_image'             => [ 
                'nullable',
                'image',
                'mimes:jpeg,png,jpg',
                'max:2048'
            ],
        ];
    }

    /**
     * Mensagens de erro personalizadas em português.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [ 
            'user_id.required'             => 'O usuário é obrigatório.',
            'user_id.integer'              => 'O ID do usuário deve ser um número inteiro.',
            'user_id.exists'               => 'O usuário selecionado não existe.',
            'user_id.unique'               => 'Este usuário já está registrado como prestador.',
            'common_data_id.required'      => 'Os dados comuns são obrigatórios.',
            'common_data_id.integer'       => 'O ID dos dados comuns deve ser um número inteiro.',
            'common_data_id.exists'        => 'Os dados comuns selecionados não existem.',
            'contact_id.required'          => 'O contato é obrigatório.',
            'contact_id.integer'           => 'O ID do contato deve ser um número inteiro.',
            'contact_id.exists'            => 'O contato selecionado não existe.',
            'address_id.required'          => 'O endereço é obrigatório.',
            'address_id.integer'           => 'O ID do endereço deve ser um número inteiro.',
            'address_id.exists'            => 'O endereço selecionado não existe.',
            'terms_accepted.required'      => 'Você deve aceitar os termos de uso.',
            'terms_accepted.accepted'      => 'Você deve aceitar os termos de uso para continuar.',
            'provider_type.required'       => 'O tipo de prestador é obrigatório.',
            'provider_type.in'             => 'O tipo de prestador deve ser indivíduo ou empresa.',
            'registration_number.string'   => 'O número de registro deve ser uma string.',
            'registration_number.max'      => 'O número de registro não pode ter mais de 50 caracteres.',
            'tax_regime.in'                => 'O regime tributário deve ser uma opção válida.',
            'bank_account.array'           => 'Os dados bancários devem ser um array.',
            'bank_account.bank.string'     => 'O nome do banco deve ser uma string.',
            'bank_account.bank.max'        => 'O nome do banco não pode ter mais de 50 caracteres.',
            'bank_account.agency.string'   => 'A agência deve ser uma string.',
            'bank_account.agency.max'      => 'A agência não pode ter mais de 20 caracteres.',
            'bank_account.account.string'  => 'A conta deve ser uma string.',
            'bank_account.account.max'     => 'A conta não pode ter mais de 20 caracteres.',
            'bank_account.account_type.in' => 'O tipo de conta deve ser corrente ou poupança.',
            'tenant_id.required'           => 'O ID do tenant é obrigatório.',
            'tenant_id.integer'            => 'O ID do tenant deve ser um número inteiro.',
            'tenant_id.exists'             => 'O tenant informado não existe.',
            'is_active.boolean'            => 'O status ativo deve ser verdadeiro ou falso.',
            'profile_image.image'          => 'A imagem de perfil deve ser uma imagem válida.',
            'profile_image.mimes'          => 'A imagem de perfil deve ser JPEG, PNG ou JPG.',
            'profile_image.max'            => 'A imagem de perfil não pode ser maior que 2MB.',
        ];
    }

    /**
     * Campos que devem ser retornados com erros de validação.
     *
     * @return array<int, string>
     */
    public function attributes(): array
    {
        return [ 
            'user_id'                   => 'usuário',
            'common_data_id'            => 'dados comuns',
            'contact_id'                => 'contato',
            'address_id'                => 'endereço',
            'terms_accepted'            => 'aceitação dos termos',
            'provider_type'             => 'tipo de prestador',
            'registration_number'       => 'número de registro',
            'tax_regime'                => 'regime tributário',
            'bank_account'              => 'dados bancários',
            'bank_account.bank'         => 'banco',
            'bank_account.agency'       => 'agência',
            'bank_account.account'      => 'conta',
            'bank_account.account_type' => 'tipo de conta',
            'tenant_id'                 => 'tenant',
            'is_active'                 => 'status ativo',
            'profile_image'             => 'imagem de perfil',
        ];
    }

    /**
     * Preparar os dados para validação.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $this->merge( [ 
            'tenant_id'      => Auth::user()?->tenant_id,
            'user_id'        => (int) $this->user_id,
            'common_data_id' => (int) $this->common_data_id,
            'contact_id'     => (int) $this->contact_id,
            'address_id'     => (int) $this->address_id,
            'is_active'      => (bool) $this->is_active,
            'terms_accepted' => (bool) $this->terms_accepted,
        ] );
    }

    /**
     * Validar regras de negócio personalizadas após a validação básica.
     *
     * @return array<string, array>
     */
    public function withValidator( $validator ): void
    {
        $validator->after( function ($validator) {
            // Verificar se o usuário não está tentando se associar a si mesmo como prestador de outro tenant
            if ( $this->user_id && Auth::id() ) {
                $userTenant = \Illuminate\Support\Facades\DB::table( 'users' )
                    ->where( 'id', Auth::id() )
                    ->value( 'tenant_id' );

                $targetUserTenant = \Illuminate\Support\Facades\DB::table( 'users' )
                    ->where( 'id', $this->user_id )
                    ->value( 'tenant_id' );

                if ( $userTenant !== $targetUserTenant ) {
                    $validator->errors()->add( 'user_id', 'Você não pode registrar prestadores de outros tenants.' );
                }
            }

            // Verificar se os relacionamentos são consistentes (todos pertencem ao mesmo tenant)
            $entities  = [ 'common_data_id', 'contact_id', 'address_id' ];
            $tenantIds = [];

            foreach ( $entities as $entity ) {
                if ( $this->$entity ) {
                    $table    = str_replace( '_id', 's', $entity );
                    $tenantId = \Illuminate\Support\Facades\DB::table( $table )
                        ->where( 'id', $this->$entity )
                        ->value( 'tenant_id' );

                    if ( $tenantId ) {
                        $tenantIds[] = $tenantId;
                    }
                }
            }

            if ( !empty( $tenantIds ) && count( array_unique( $tenantIds ) ) > 1 ) {
                $validator->errors()->add( 'common_data_id', 'Todos os dados (comuns, contato, endereço) devem pertencer ao mesmo tenant.' );
            }
        } );
    }

}