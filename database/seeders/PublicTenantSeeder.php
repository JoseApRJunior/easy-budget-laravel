<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Address;
use App\Models\CommonData;
use App\Models\Contact;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder para criar o tenant público/padrão com dados completos.
 *
 * Este tenant especial é usado para:
 * - Mensagens de contato de usuários não autenticados
 * - Dados públicos que não pertencem a nenhum tenant específico
 * - Funcionalidades que precisam de tenant_id mas são globais
 */
class PublicTenantSeeder extends Seeder
{
    /**
     * ID fixo para o tenant público.
     */
    public const PUBLIC_TENANT_ID = 1;

    /**
     * ID fixo para o admin padrão (apenas para organização de dados).
     */
    public const DEFAULT_ADMIN_ID = 999; // ID alto para não conflitar

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info( '🌐 Criando tenant público...' );

        // Criar ou atualizar o tenant público
        $tenant = Tenant::updateOrCreate(
            [ 'id' => self::PUBLIC_TENANT_ID ],
            [
                'name'       => 'Sistema Público',
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        // Criar usuário administrador público
        $publicUser = $this->createPublicUser( $tenant );

        // Criar dados completos para o tenant público
        $this->createPublicTenantData( $tenant, $publicUser );

        $this->command->info( '✅ Tenant público criado com ID: ' . self::PUBLIC_TENANT_ID );
        $this->command->info( '📧 Nome: Sistema Público' );
        $this->command->info( '👤 Admin técnico: admin@easybudget.com.br (ID: ' . self::DEFAULT_ADMIN_ID . ')' );
        $this->command->info( '🎯 Usado para: Mensagens de contato de usuários não autenticados' );
        $this->command->info( '⚠️  Este tenant é apenas para dados públicos, não para login de usuários' );
    }

    /**
     * Cria usuário administrador para o tenant público.
     */
    private function createPublicUser( Tenant $tenant ): User
    {
        return User::updateOrCreate(
            [ 'id' => self::DEFAULT_ADMIN_ID ],
            [
                'id'                => self::DEFAULT_ADMIN_ID,
                'tenant_id'         => $tenant->id,
                'email'             => 'admin@easybudget.com.br',
                'password'          => Hash::make( 'AdminPassword1@' ),
                'is_active'         => true,
                'email_verified_at' => now(),
            ],
        );
    }

    /**
     * Cria dados completos para o tenant público.
     */
    private function createPublicTenantData( Tenant $tenant, User $user ): void
    {
        // 1. Criar endereço da empresa
        $address = Address::firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'cep'       => '01310-100',
            ],
            [
                'tenant_id'      => $tenant->id,
                'address'        => 'Av. Paulista',
                'address_number' => '1578',
                'neighborhood'   => 'Bela Vista',
                'city'           => 'São Paulo',
                'state'          => 'SP',
                'cep'            => '01310-100',
            ],
        );

        // 2. Criar contato da empresa
        $contact = Contact::firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'email'     => 'contato@easybudget.net.br',
            ],
            [
                'tenant_id'      => $tenant->id,
                'email'          => 'contato@easybudget.net.br',
                'phone'          => '(11) 3000-0000',
                'email_business' => 'suporte@easybudget.net.br',
                'phone_business' => '(11) 3000-0001',
                'website'        => 'https://easybudget.net.br',
            ],
        );

        // 3. Criar dados da empresa
        $commonData = CommonData::firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'cnpj'      => '00000000000100',
            ],
            [
                'tenant_id'           => $tenant->id,
                'first_name'          => 'Easy',
                'last_name'           => 'Budget',
                'birth_date'          => null,
                'cpf'                 => null,
                'cnpj'                => '00000000000100',
                'company_name'        => 'Easy Budget - Sistema Público',
                'description'         => 'Tenant público para mensagens de contato e dados não relacionados a empresas específicas',
                'area_of_activity_id' => $this->getOrCreateAreaOfActivity(),
                'profession_id'       => null,
            ],
        );

        // 4. Criar role de administrador público (sem acesso de provider)
        $adminRole = Role::firstOrCreate(
            [ 'name' => 'Public Admin' ],
            [
                'name'        => 'Public Admin',
                'description' => 'Administrador do tenant público - apenas para gerenciar dados públicos, não para login'
            ],
        );

        // 5. Associar role ao usuário
        UserRole::firstOrCreate(
            [
                'user_id'   => $user->id,
                'role_id'   => $adminRole->id,
                'tenant_id' => $tenant->id,
            ],
            [
                'user_id'   => $user->id,
                'role_id'   => $adminRole->id,
                'tenant_id' => $tenant->id,
            ],
        );

        // 6. Criar configurações do sistema para o tenant público
        $this->createPublicSystemSettings( $tenant, $commonData, $address, $contact );

        // 7. Criar configurações do usuário administrador
        $this->createPublicUserSettings( $tenant, $user );

        $this->command->info( 'に戙 Dados completos do tenant público criados' );
    }

    /**
     * Obtém ou cria área de atividade para tecnologia.
     */
    private function getOrCreateAreaOfActivity(): int
    {
        $area = \App\Models\AreaOfActivity::firstOrCreate(
            [ 'slug' => 'tecnologia-software' ],
            [
                'slug'      => 'tecnologia-software',
                'name'      => 'Tecnologia e Software',
                'is_active' => true,
            ],
        );

        return $area->id;
    }

    /**
     * Cria configurações do sistema para o tenant público.
     */
    private function createPublicSystemSettings( Tenant $tenant, CommonData $commonData, Address $address, Contact $contact ): void
    {
        \App\Models\SystemSettings::firstOrCreate(
            [ 'tenant_id' => $tenant->id ],
            [
                'tenant_id'                   => $tenant->id,
                'company_name'                => $commonData->company_name,
                'contact_email'               => $contact->email,
                'phone'                       => $contact->phone,
                'website'                     => $contact->website,
                'logo'                        => null,
                'currency'                    => 'BRL',
                'timezone'                    => 'America/Sao_Paulo',
                'language'                    => 'pt-BR',
                'address_street'              => $address->address,
                'address_number'              => $address->address_number,
                'address_neighborhood'        => $address->neighborhood,
                'address_city'                => $address->city,
                'address_state'               => $address->state,
                'address_zip_code'            => $address->cep,
                'address_country'             => 'Brasil',
                'maintenance_mode'            => false,
                'maintenance_message'         => null,
                'registration_enabled'        => true,
                'email_verification_required' => false, // Público não precisa verificação
                'session_lifetime'            => 120,
                'max_login_attempts'          => 10, // Mais permissivo para público
                'lockout_duration'            => 5, // Menos restritivo
                'allowed_file_types'          => json_encode( [
                    'image/jpeg',
                    'image/png',
                    'image/gif',
                    'image/webp',
                    'application/pdf',
                    'text/plain',
                ] ),
                'max_file_size'               => 1024, // Menor para público
                'system_preferences'          => json_encode( [
                    'public_contact_form'     => true,
                    'auto_response_enabled'   => true,
                    'support_ticket_creation' => true,
                    'email_notifications'     => true,
                ] ),
            ],
        );
    }

    /**
     * Cria configurações do usuário administrador público.
     */
    private function createPublicUserSettings( Tenant $tenant, User $user ): void
    {
        \App\Models\UserSettings::firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'user_id'   => $user->id,
            ],
            [
                'tenant_id'                 => $tenant->id,
                'user_id'                   => $user->id,
                'avatar'                    => null,
                'full_name'                 => 'Admin Padrão do Sistema',
                'bio'                       => 'Administrador técnico do tenant público - usado apenas para organização de dados públicos, não para acesso ao sistema',
                'phone'                     => '(11) 3000-0000',
                'birth_date'                => null,
                'social_facebook'           => 'https://facebook.com/easybudget',
                'social_twitter'            => 'https://twitter.com/easybudget',
                'social_linkedin'           => 'https://linkedin.com/company/easybudget',
                'social_instagram'          => 'https://instagram.com/easybudget',
                'theme'                     => 'light',
                'primary_color'             => '#0D6EFD',
                'layout_density'            => 'normal',
                'sidebar_position'          => 'left',
                'animations_enabled'        => true,
                'sound_enabled'             => false, // Desabilitado para admin público
                'email_notifications'       => true,
                'transaction_notifications' => false, // Não aplicável para público
                'weekly_reports'            => true,
                'security_alerts'           => true,
                'newsletter_subscription'   => false,
                'push_notifications'        => true,
                'custom_preferences'        => json_encode( [
                    'contact_form_notifications' => true,
                    'support_ticket_alerts'      => true,
                    'auto_assign_tickets'        => true,
                    'email_signature'            => 'Equipe Easy Budget - Suporte',
                ] ),
            ],
        );
    }

}
