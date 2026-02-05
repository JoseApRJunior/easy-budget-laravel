<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Helpers\DocumentGeneratorHelper;
use App\Models\Address;
use App\Models\Category;
use App\Models\CommonData;
use App\Models\Contact;
use App\Models\Plan;
use App\Models\PlanSubscription;
use App\Models\Provider;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder para criar o tenant Admin com dados completos.
 *
 * Este tenant é usado para:
 * - Login do administrador do sistema
 * - Acesso completo a todas as funcionalidades
 * - Gerenciamento geral do sistema
 */
class AdminTenantSeeder extends Seeder
{
    /**
     * ID fixo para o tenant admin.
     */
    public const ADMIN_TENANT_ID = 2;

    /**
     * ID fixo para o usuário admin.
     */
    public const ADMIN_USER_ID = 2;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('👑 Criando tenant Admin...');

        // Criar tenant Admin
        $tenant = Tenant::updateOrCreate(
            ['id' => self::ADMIN_TENANT_ID],
            [
                'name' => 'Admin Tenant',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        // Criar usuário administrador
        $adminUser = $this->createAdminUser($tenant);

        // Criar dados completos para o tenant admin
        $this->createAdminTenantData($tenant, $adminUser);

        $this->command->info('✅ Tenant Admin criado com ID: '.self::ADMIN_TENANT_ID);
        $this->command->info('👤 Admin: admin@easybudget.net.br (ID: '.self::ADMIN_USER_ID.')');
        $this->command->info('🔑 Senha: AdminPassword1@');
        $this->command->info('🎯 Usado para: Login e administração completa do sistema');
    }

    /**
     * Cria usuário administrador para o tenant admin.
     */
    private function createAdminUser(Tenant $tenant): User
    {
        return User::updateOrCreate(
            ['id' => self::ADMIN_USER_ID],
            [
                'id' => self::ADMIN_USER_ID,
                'tenant_id' => $tenant->id,
                'email' => 'admin@easybudget.net.br', // Email diferente para evitar conflito
                'password' => Hash::make('AdminPassword1@'),
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );
    }

    /**
     * Cria dados completos para o tenant admin.
     */
    private function createAdminTenantData(Tenant $tenant, User $user): void
    {
        // 1. Criar endereço da empresa admin
        $address = Address::firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'cep' => '01310-200',
            ],
            [
                'tenant_id' => $tenant->id,
                'address' => 'Av. Paulista',
                'address_number' => '2000',
                'neighborhood' => 'Bela Vista',
                'city' => 'São Paulo',
                'state' => 'SP',
                'cep' => '01310-200',
            ],
        );

        // 2. Criar contato da empresa admin
        $contact = Contact::firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'email_personal' => 'admin@easybudget.net.br',
            ],
            [
                'tenant_id' => $tenant->id,
                'email_personal' => 'admin@easybudget.net.br',
                'phone_personal' => DocumentGeneratorHelper::generateValidPhone(),
                'email_business' => 'admin@easybudget.net.br',
                'phone_business' => DocumentGeneratorHelper::generateValidPhone(),
                'website' => 'https://admin.easybudget.com.br',
            ],
        );

        // 3. Criar dados da empresa admin
        $validCnpj = DocumentGeneratorHelper::generateValidCnpj();
        $commonData = CommonData::firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'cnpj' => $validCnpj,
            ],
            [
                'tenant_id' => $tenant->id,
                'type' => 'company',
                'first_name' => null,
                'last_name' => null,
                'birth_date' => null,
                'cpf' => null,
                'cnpj' => $validCnpj,
                'company_name' => 'Easy Budget - Administração',
                'description' => 'Tenant administrativo para gerenciamento completo do sistema Easy Budget',
                'area_of_activity_id' => $this->getOrCreateAreaOfActivity(),
                'profession_id' => null,
            ],
        );

        // 4. Criar provider para o admin (necessário para acesso completo)
        $provider = Provider::firstOrCreate(
            [
                'user_id' => $user->id,
                'tenant_id' => $tenant->id,
            ],
            [
                'user_id' => $user->id,
                'tenant_id' => $tenant->id,
                'terms_accepted' => true,
            ],
        );

        // Vincular dados relacionados ao provider
        // Verifica se já existe um CommonData vinculado a este provider neste tenant
        $existingCommonData = CommonData::where('tenant_id', $tenant->id)
            ->where('provider_id', $provider->id)
            ->first();

        if ($existingCommonData) {
            $commonData = $existingCommonData;
        } else {
            // Se não existe, tenta atualizar o que acabamos de criar/recuperar
            // Mas apenas se ele ainda não tiver provider_id (para evitar sobrescrever outro vínculo)
            if (is_null($commonData->provider_id)) {
                $commonData->update(['provider_id' => $provider->id]);
            } else {
                // Se o commonData recuperado já tem provider_id (e não é o nosso), cria um novo
                // Isso não deve acontecer com firstOrCreate por CNPJ, a menos que o CNPJ seja reutilizado
                // Mas por segurança, se chegarmos aqui, algo estranho ocorreu.
                // Vamos forçar a criação de um novo se o CNPJ for diferente, mas o firstOrCreate já tratou isso.
                // O problema real é a constraint unique.
                
                // Vamos tentar atualizar ignorando erro de duplicidade se já existir
                try {
                    $commonData->update(['provider_id' => $provider->id]);
                } catch (\Illuminate\Database\QueryException $e) {
                    if ($e->errorInfo[1] == 1062) { // Duplicate entry
                         // Se der duplicidade, significa que JÁ EXISTE um registro com esse tenant_id e provider_id
                         // Então vamos buscar esse registro e usar ele
                         $commonData = CommonData::where('tenant_id', $tenant->id)
                            ->where('provider_id', $provider->id)
                            ->firstOrFail();
                    } else {
                        throw $e;
                    }
                }
            }
        }

        // Mesma lógica para Contact
        $existingContact = Contact::where('tenant_id', $tenant->id)
            ->where('provider_id', $provider->id)
            ->first();

        if ($existingContact) {
            $contact = $existingContact;
        } else {
             try {
                $contact->update(['provider_id' => $provider->id]);
            } catch (\Illuminate\Database\QueryException $e) {
                if ($e->errorInfo[1] == 1062) {
                     $contact = Contact::where('tenant_id', $tenant->id)
                        ->where('provider_id', $provider->id)
                        ->firstOrFail();
                } else {
                    throw $e;
                }
            }
        }

        // Mesma lógica para Address
        $existingAddress = Address::where('tenant_id', $tenant->id)
            ->where('provider_id', $provider->id)
            ->first();

        if ($existingAddress) {
            $address = $existingAddress;
        } else {
            try {
                $address->update(['provider_id' => $provider->id]);
            } catch (\Illuminate\Database\QueryException $e) {
                if ($e->errorInfo[1] == 1062) {
                     $address = Address::where('tenant_id', $tenant->id)
                        ->where('provider_id', $provider->id)
                        ->firstOrFail();
                } else {
                    throw $e;
                }
            }
        }

        // 5. Criar roles necessárias
        $this->createAdminRoles($tenant, $user);

        // 6. Criar assinatura de plano para o admin
        $this->createAdminPlanSubscription($tenant, $provider);

        // 7. Criar configurações do sistema
        $this->createAdminSystemSettings($tenant, $commonData, $address, $contact);

        // 8. Criar configurações do usuário
        $this->createAdminUserSettings($tenant, $user);

        $this->command->info('📋 Dados completos do tenant Admin criados');

        // 9. Criar produtos da área de pintura para o admin
        $this->createAdminPaintingProducts($tenant);
    }

    /**
     * Cria roles necessárias para o admin.
     */
    private function createAdminRoles(Tenant $tenant, User $user): void
    {
        // Role de administrador
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            [
                'name' => 'admin',
                'description' => 'Acesso total ao sistema',
            ],
        );

        // Role de provider (para acesso às funcionalidades)
        $providerRole = Role::firstOrCreate(
            ['name' => 'provider'],
            [
                'name' => 'provider',
                'description' => 'Provedor de serviços - acesso completo',
            ],
        );

        // Associar ambas as roles ao usuário admin
        UserRole::firstOrCreate(
            [
                'user_id' => $user->id,
                'role_id' => $adminRole->id,
                'tenant_id' => $tenant->id,
            ],
            [
                'user_id' => $user->id,
                'role_id' => $adminRole->id,
                'tenant_id' => $tenant->id,
            ],
        );

        UserRole::firstOrCreate(
            [
                'user_id' => $user->id,
                'role_id' => $providerRole->id,
                'tenant_id' => $tenant->id,
            ],
            [
                'user_id' => $user->id,
                'role_id' => $providerRole->id,
                'tenant_id' => $tenant->id,
            ],
        );

        $this->command->info('   🔑 Roles criadas: admin + provider');
    }

    /**
     * Cria assinatura de plano para o admin.
     */
    private function createAdminPlanSubscription(Tenant $tenant, Provider $provider): void
    {
        $plan = Plan::first();
        if ($plan) {
            PlanSubscription::firstOrCreate(
                [
                    'provider_id' => $provider->id,
                    'plan_id' => $plan->id,
                    'tenant_id' => $tenant->id,
                ],
                [
                    'provider_id' => $provider->id,
                    'plan_id' => $plan->id,
                    'tenant_id' => $tenant->id,
                    'status' => 'active',
                    'transaction_amount' => 0.00,
                    'start_date' => now(),
                    'end_date' => now()->addYears(10), // Admin nunca expira
                    'payment_method' => 'admin',
                    'payment_id' => 'ADMIN_UNLIMITED',
                    'public_hash' => 'ADMIN_HASH_'.uniqid(),
                ],
            );

            $this->command->info('   💳 Plano ilimitado criado para admin');
        }
    }

    /**
     * Obtém ou cria área de atividade para administração.
     */
    private function getOrCreateAreaOfActivity(): int
    {
        $area = \App\Models\AreaOfActivity::firstOrCreate(
            ['slug' => 'administracao-sistemas'],
            [
                'slug' => 'administracao-sistemas',
                'name' => 'Administração de Sistemas',
                'is_active' => true,
            ],
        );

        return $area->id;
    }

    /**
     * Obtém ou cria profissão para administrador.
     */
    private function getOrCreateProfession(): int
    {
        $profession = \App\Models\Profession::firstOrCreate(
            ['slug' => 'administrador-sistemas'],
            [
                'slug' => 'administrador-sistemas',
                'name' => 'Administrador de Sistemas',
                'is_active' => true,
            ],
        );

        return $profession->id;
    }

    /**
     * Cria configurações do sistema para o tenant admin.
     */
    private function createAdminSystemSettings(Tenant $tenant, CommonData $commonData, Address $address, Contact $contact): void
    {
        \App\Models\SystemSettings::firstOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'tenant_id' => $tenant->id,
                'company_name' => $commonData->company_name,
                'contact_email' => $contact->email,
                'phone' => $contact->phone,
                'website' => $contact->website,
                'logo' => null,
                'currency' => 'BRL',
                'timezone' => 'America/Sao_Paulo',
                'language' => 'pt-BR',
                'address_street' => $address->address,
                'address_number' => $address->address_number,
                'address_neighborhood' => $address->neighborhood,
                'address_city' => $address->city,
                'address_state' => $address->state,
                'address_zip_code' => $address->cep,
                'address_country' => 'Brasil',
                'maintenance_mode' => false,
                'maintenance_message' => null,
                'registration_enabled' => true,
                'email_verification_required' => false, // Admin não precisa verificação
                'session_lifetime' => 480, // 8 horas para admin
                'max_login_attempts' => 20, // Mais permissivo para admin
                'lockout_duration' => 1, // Menos restritivo
                'allowed_file_types' => json_encode([
                    'image/jpeg',
                    'image/png',
                    'image/gif',
                    'image/webp',
                    'application/pdf',
                    'text/plain',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/vnd.ms-excel',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ]),
                'max_file_size' => 10240, // 10MB para admin
                'system_preferences' => json_encode([
                    'admin_access' => true,
                    'full_system_access' => true,
                    'debug_mode' => true,
                    'advanced_features' => true,
                ]),
            ],
        );

        $this->command->info('   ⚙️  Configurações administrativas criadas');
    }

    /**
     * Cria configurações do usuário administrador.
     */
    private function createAdminUserSettings(Tenant $tenant, User $user): void
    {
        \App\Models\UserSettings::firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
            ],
            [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'avatar' => null,
                'full_name' => 'Administrador do Sistema',
                'bio' => 'Administrador principal com acesso completo ao sistema Easy Budget',
                'phone' => DocumentGeneratorHelper::generateValidPhone(),
                'birth_date' => '1980-01-01',
                'social_facebook' => null,
                'social_twitter' => null,
                'social_linkedin' => null,
                'social_instagram' => null,
                'primary_color' => '#DC2626', // Vermelho para admin
                'layout_density' => 'compact', // Layout compacto para admin
                'sidebar_position' => 'left',
                'animations_enabled' => false, // Desabilitado para performance
                'sound_enabled' => false,
                'email_notifications' => true,
                'transaction_notifications' => true,
                'weekly_reports' => true,
                'security_alerts' => true,
                'newsletter_subscription' => false,
                'push_notifications' => true,
                'custom_preferences' => json_encode([
                    'admin_dashboard' => true,
                    'system_monitoring' => true,
                    'advanced_logs' => true,
                    'debug_toolbar' => true,
                    'performance_metrics' => true,
                ]),
            ],
        );

        $this->command->info('   👤 Configurações do admin criadas');
    }

    /**
     * Cria 10 produtos da área de pintura para o tenant admin.
     */
    private function createAdminPaintingProducts(Tenant $tenant): void
    {
        $category = Category::where('slug', 'pintura')->first();
        if (! $category) {
            $this->command->warn('   ⚠️ Categoria "pintura" não encontrada. Pulando criação de produtos.');

            return;
        }

        $service = app(\App\Services\Domain\ProductService::class);

        $products = [
            ['name' => 'Tinta Acrílica Premium 18L', 'price' => 320.00],
            ['name' => 'Tinta Látex 18L',           'price' => 280.00],
            ['name' => 'Massa Corrida 25kg',        'price' => 95.00],
            ['name' => 'Rolo de Pintura 23cm',      'price' => 29.90],
            ['name' => 'Pincel 2"',                 'price' => 18.50],
            ['name' => 'Fita Crepe 48mm',           'price' => 12.90],
            ['name' => 'Lixa d’água 120',           'price' => 2.50],
            ['name' => 'Diluyente/Thinner 900ml',   'price' => 22.00],
            ['name' => 'Bandeja de Pintura 2L',     'price' => 16.90],
            ['name' => 'Selador Acrílico 18L',      'price' => 210.00],
        ];

        foreach ($products as $p) {
            $service->createProduct([
                'tenant_id' => $tenant->id,
                'category_id' => $category->id,
                'name' => $p['name'],
                'description' => 'Linha de pintura - produto para serviços de pintura',
                'price' => $p['price'],
                'unit' => 'un',
                'active' => true,
            ]);
        }

        $this->command->info('   🎨 10 produtos de pintura criados para o admin');
    }
}
