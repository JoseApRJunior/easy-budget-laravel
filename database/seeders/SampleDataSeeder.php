<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Budget;
use App\Models\BudgetStatus;
use App\Models\Category;
use App\Models\CommonData;
use App\Models\Contact;
use App\Models\Customer;
use App\Models\Provider;
use App\Models\Service;
use App\Models\ServiceStatus;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SampleDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if ( !app()->environment( 'local', 'testing' ) ) {
            return;
        }

        // Criar tenant de amostra
        $tenant = Tenant::updateOrCreate(
            [ 'name' => 'Sample Tenant' ],
            [ 'created_at' => now(), 'updated_at' => now() ],
        );

        // CommonData para admin
        $commonData = CommonData::updateOrCreate(
            [ 'first_name' => 'Admin', 'last_name' => 'Sample' ],
            [ 
                'tenant_id'    => $tenant->id,
                'birth_date'   => '1990-01-01',
                'cpf'          => '123.456.789-01',
                'company_name' => 'Sample Company',
                'description'  => 'Administrador de amostra',
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
        );

        // Contact para admin
        $contact = Contact::updateOrCreate(
            [ 'email' => 'admin@sample.com' ],
            [ 
                'tenant_id'      => $tenant->id,
                'email_business' => 'admin@sample.com',
                'phone'          => '(11) 99999-9999',
                'phone_business' => '(11) 99999-9999',
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
        );

        // Address para admin
        $address = Address::updateOrCreate(
            [ 'address' => 'Rua Sample' ],
            [ 
                'tenant_id'      => $tenant->id,
                'address_number' => '123',
                'neighborhood'   => 'Bairro Sample',
                'city'           => 'Sample City',
                'state'          => 'SP',
                'cep'            => '12345-678',
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
        );

        // User admin
        $user = User::updateOrCreate(
            [ 'email' => 'admin@sample.com' ],
            [ 
                'tenant_id'  => $tenant->id,
                'password'   => bcrypt( 'password' ),
                'is_active'  => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        // Provider admin
        $provider = Provider::updateOrCreate(
            [ 'user_id' => $user->id ],
            [ 
                'tenant_id'      => $tenant->id,
                'common_data_id' => $commonData->id,
                'contact_id'     => $contact->id,
                'address_id'     => $address->id,
                'terms_accepted' => 1,
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
        );

        // Customer de amostra
        $customerCommonData = CommonData::updateOrCreate(
            [ 'first_name' => 'Cliente', 'last_name' => 'Sample' ],
            [ 
                'tenant_id'    => $tenant->id,
                'cpf'          => '987.654.321-00',
                'company_name' => 'Cliente Sample Ltda',
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
        );

        $customerContact = Contact::updateOrCreate(
            [ 'email' => 'cliente@sample.com' ],
            [ 
                'tenant_id'  => $tenant->id,
                'phone'      => '(11) 88888-8888',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        $customerAddress = Address::updateOrCreate(
            [ 'address' => 'Avenida Cliente' ],
            [ 
                'tenant_id'      => $tenant->id,
                'address_number' => '456',
                'city'           => 'Sample City',
                'state'          => 'SP',
                'cep'            => '98765-432',
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
        );

        $customer = Customer::updateOrCreate(
            [ 'common_data_id' => $customerCommonData->id ],
            [ 
                'tenant_id'  => $tenant->id,
                'contact_id' => $customerContact->id,
                'address_id' => $customerAddress->id,
                'status'     => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        // Category de amostra (se não existir)
        $category = Category::updateOrCreate(
            [ 'slug' => 'sample-service' ],
            [ 
                'name'       => 'Serviço de Amostra',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        // BudgetStatus de amostra (se não existir)
        $budgetStatus = BudgetStatus::updateOrCreate(
            [ 'slug' => 'PENDING' ],
            [ 
                'name'        => 'Pendente',
                'description' => 'Orçamento pendente',
                'color'       => '#ffc107',
                'icon'        => 'bi-clock',
                'order_index' => 1,
                'is_active'   => 1,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        );

        // ServiceStatus de amostra (se não existir)
        $serviceStatus = ServiceStatus::updateOrCreate(
            [ 'slug' => 'PENDING' ],
            [ 
                'name'        => 'Pendente',
                'description' => 'Serviço pendente',
                'color'       => '#ffc107',
                'icon'        => 'bi-clock',
                'order_index' => 1,
                'is_active'   => 1,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        );

        // Budget de amostra
        $budget = Budget::updateOrCreate(
            [ 'code' => 'SAMPLE-001' ],
            [ 
                'tenant_id'          => $tenant->id,
                'customer_id'        => $customer->id,
                'budget_statuses_id' => $budgetStatus->id,
                'due_date'           => now()->addMonth(),
                'discount'           => 0.00,
                'total'              => 1000.00,
                'description'        => 'Orçamento de amostra para serviço de teste',
                'payment_terms'      => 'Pagamento em 30 dias',
                'created_at'         => now(),
                'updated_at'         => now(),
            ],
        );

        // Service de amostra
        $service = Service::updateOrCreate(
            [ 'code' => 'SAMPLE-S001' ],
            [ 
                'tenant_id'           => $tenant->id,
                'budget_id'           => $budget->id,
                'category_id'         => $category->id,
                'service_statuses_id' => $serviceStatus->id,
                'description'         => 'Serviço de amostra para teste do sistema',
                'discount'            => 0.00,
                'total'               => 1000.00,
                'due_date'            => now()->addMonth(),
                'created_at'          => now(),
                'updated_at'          => now(),
            ],
        );

        $this->command->info( 'Dados de amostra criados com sucesso!' );
    }

}