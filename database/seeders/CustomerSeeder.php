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
                    'cpf'        => '12345678901',
                ],
                'contact'     => [
                    'email' => 'joao.silva@email.com',
                    'phone' => '11999998888',
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
                    'cnpj'         => '12345678000195',
                ],
                'contact'     => [
                    'email_business' => 'contato@empresaabc.com',
                    'phone_business' => '1133334444',
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
                    'cpf'        => '98765432100',
                ],
                'contact'     => [
                    'email' => 'maria.santos@email.com',
                    'phone' => '11977776666',
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
            // Criar CommonData
            $commonData = CommonData::create( array_merge(
                [ 'tenant_id' => $tenant->id ],
                $customerData[ 'common_data' ],
            ) );

            // Criar Contact
            $contact = Contact::create( array_merge(
                [ 'tenant_id' => $tenant->id ],
                $customerData[ 'contact' ],
            ) );

            // Criar Address
            $address = Address::create( array_merge(
                [ 'tenant_id' => $tenant->id ],
                $customerData[ 'address' ],
            ) );

            // Criar Customer
            Customer::create( [
                'tenant_id'      => $tenant->id,
                'common_data_id' => $commonData->id,
                'contact_id'     => $contact->id,
                'address_id'     => $address->id,
                'status'         => 'active',
            ] );
        }

        $this->command->info( 'Test customers created successfully!' );
    }

}
