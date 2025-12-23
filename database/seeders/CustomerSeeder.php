<?php

namespace Database\Seeders;

use App\Helpers\DocumentGeneratorHelper;
use App\Models\Address;
use App\Models\CommonData;
use App\Models\Contact;
use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::first();
        $user   = User::where( 'tenant_id', $tenant->id )->first();

        if ( !$tenant || !$user ) {
            $this->command->info( 'Tenant or User not found. Please run other seeders first.' );
            return;
        }

        $customers = [
            [
                'common_data' => [
                    'first_name' => 'João',
                    'last_name'  => 'Silva',
                    'cpf'        => DocumentGeneratorHelper::generateValidCpf(),
                    'birth_date' => '1985-03-15',
                ],
                'contact'     => [
                    'email_personal' => 'joao.silva@email.com',
                    'phone_personal' => DocumentGeneratorHelper::generateValidPhone(),
                    'email_business' => null,
                    'phone_business' => null,
                ],
                'address'     => [
                    'address'        => 'Rua das Flores',
                    'address_number' => '123',
                    'neighborhood'   => 'Centro',
                    'city'           => 'São Paulo',
                    'state'          => 'SP',
                    'cep'            => '01000-000',
                ]
            ],
            [
                'common_data' => [
                    'company_name' => 'Empresa ABC Ltda',
                    'cnpj'         => DocumentGeneratorHelper::generateValidCnpj(),
                    'description'  => 'Empresa de consultoria e tecnologia',
                ],
                'contact'     => [
                    'email_personal' => null,
                    'phone_personal' => null,
                    'email_business' => 'contato@empresaabc.com',
                    'phone_business' => DocumentGeneratorHelper::generateValidPhone(),
                ],
                'address'     => [
                    'address'        => 'Avenida Paulista',
                    'address_number' => '1000',
                    'neighborhood'   => 'Bela Vista',
                    'city'           => 'São Paulo',
                    'state'          => 'SP',
                    'cep'            => '01310-100',
                ]
            ],
            [
                'common_data' => [
                    'first_name' => 'Maria',
                    'last_name'  => 'Santos',
                    'cpf'        => DocumentGeneratorHelper::generateValidCpf(),
                    'birth_date' => '1990-07-22',
                ],
                'contact'     => [
                    'email_personal' => 'maria.santos@email.com',
                    'phone_personal' => DocumentGeneratorHelper::generateValidPhone(),
                    'email_business' => null,
                    'phone_business' => null,
                ],
                'address'     => [
                    'address'        => 'Rua do Comércio',
                    'address_number' => '456',
                    'neighborhood'   => 'Vila Nova',
                    'city'           => 'Rio de Janeiro',
                    'state'          => 'RJ',
                    'cep'            => '20000-000',
                ]
            ]
        ];

        foreach ( $customers as $customerData ) {
            // Criar Customer primeiro
            $customer = Customer::create( [
                'tenant_id' => $tenant->id,
                'status'    => 'active',
            ] );

            // Criar CommonData com referência ao customer
            $commonData = CommonData::create( array_merge(
                [
                    'tenant_id'   => $tenant->id,
                    'customer_id' => $customer->id,
                    'type'        => !empty( $customerData[ 'common_data' ][ 'cnpj' ] ) ? 'company' : 'individual',
                ],
                $customerData[ 'common_data' ],
            ) );

            // Criar Contact com referência ao customer
            $contact = Contact::create( array_merge(
                [
                    'tenant_id'   => $tenant->id,
                    'customer_id' => $customer->id,
                ],
                $customerData[ 'contact' ],
            ) );

            // Criar Address com referência ao customer
            $address = Address::create( array_merge(
                [
                    'tenant_id'   => $tenant->id,
                    'customer_id' => $customer->id,
                ],
                $customerData[ 'address' ],
            ) );
        }

        $this->command->info( 'Test customers created successfully!' );
    }

}
