<?php

declare(strict_types=1);

namespace core\library;

use app\database\entitiesORM\ProviderEntity;
use app\database\entitiesORM\SessionEntity;
use app\database\entitiesORM\UserEntity;
use app\database\repositories\ProviderRepository;
use app\database\repositories\SessionRepository;
use app\database\repositories\UserRepository;
use app\enums\OperationStatus;
use app\support\ServiceResult;
use core\library\Session;
use Exception;

/**
 * Serviço de Autenticação Avançado para o Sistema Easy Budget
 *
 * Esta classe fornece funcionalidades completas de autenticação, integrando
 * o melhor da implementação clássica com a arquitetura moderna baseada em Doctrine ORM.
 * Suporta verificação de credenciais, gerenciamento de sessões, roles e permissões,
 * com foco em segurança, escalabilidade e compatibilidade retroativa.
 *
 * Recursos principais:
 * - Validação rigorosa de credenciais e status de conta
 * - Integração com multi-tenancy via ProviderEntity
 * - Carregamento automático de roles e permissões
 * - Retorno estruturado via ServiceResult para tratamento de erros
 * - Métodos estáticos para compatibilidade com código legado
 * - Suporte a exceções e logging para depuração
 *
 * @package core\library
 * @author Easy Budget Team
 * @version 2.0 - Integrada melhorias
 */
class AuthService
{
    /**
     * Construtor do AuthService.
     *
     * Injeta os repositórios necessários para acesso a dados de usuários, providers e sessões.
     * Promove injeção de dependências explícita para melhor testabilidade e desacoplamento.
     *
     * @param UserRepository $userRepository Repositório para operações com UserEntity
     * @param ProviderRepository $providerRepository Repositório para operações com ProviderEntity
     * @param SessionRepository $sessionRepository Repositório para gerenciamento de SessionEntity
     */
    public function __construct(
        private UserRepository $userRepository,
        private ProviderRepository $providerRepository,
        private SessionRepository $sessionRepository,
    ) {}

    /**
     * Realiza tentativa de autenticação do usuário com base nas credenciais fornecidas.
     *
     * Este método integra validação de entrada, verificação de senha via password_verify(),
     * checagem de status de conta ativa, carregamento de provider associado ao tenant,
     * e inicialização da sessão com roles e permissões. Retorna um ServiceResult estruturado
     * para facilitar o tratamento de sucessos e falhas em camadas superiores.
     *
     * Fluxo principal:
     * 1. Validação de credenciais obrigatórias (email e password)
     * 2. Busca do usuário por email
     * 3. Verificação de senha hashed
     * 4. Confirmação de conta ativa
     * 5. Carregamento de provider e verificação de tenant
     * 6. Inicialização de sessão com dados, roles e permissões
     * 7. Definição de flag de admin se aplicável
     *
     * @param array $credentials Array associativo contendo 'email' (string) e 'password' (string)
     * @return ServiceResult Sucesso com array de dados (user, provider, roles, permissions, is_admin) ou erro com status e mensagem
     * @throws Exception Em caso de erro interno no banco de dados ou sistema
     */
    public function attempt( array $credentials ): ServiceResult
    {
        try {
            // Validar parâmetros de entrada
            if ( empty( $credentials[ 'email' ] ) || empty( $credentials[ 'password' ] ) ) {
                return ServiceResult::error( OperationStatus::INVALID_DATA, 'Email e senha são obrigatórios.' );
            }

            $email    = $credentials[ 'email' ];
            $password = $credentials[ 'password' ];

            $userRepository = $this->userRepository;

            /** @var UserRepository $userRepository */
            $user = $userRepository->findOneBy( [ 'email' => $email ] );

            if ( $user === null ) {
                return ServiceResult::error( OperationStatus::INVALID_DATA, 'Email ou senha incorretos.' );
            }

            /** @var UserEntity $user */
            if ( !password_verify( $password, $user->getPassword() ) ) {
                return ServiceResult::error( OperationStatus::INVALID_DATA, 'Email ou senha incorretos.' );
            }

            if ( !$user->isActive() ) {
                return ServiceResult::error( OperationStatus::FORBIDDEN, 'Sua conta não está ativada.' );
            }

            // Buscar provider associado usando ProviderRepository
            $providerRepository = $this->providerRepository;

            $provider = $providerRepository->findProviderFullByUserId(
                $user->getId(),
                $user->getTenant()->getId(),
            );

            if ( $provider === null ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Prestador não encontrado.' );
            }

            /** @var ProviderEntity $provider */
            if ( $provider->getTenant()->getId() !== $user->getTenant()->getId() ) {
                return ServiceResult::error( OperationStatus::FORBIDDEN, 'Prestador não pertence ao tenant.' );
            }

            // Carregar roles do usuário
            $roles = $userRepository->findUserRolesByTenantId(
                $user->getId(),
                $user->getTenant()->getId(),
            );

            if ( empty( $roles ) ) {
                return ServiceResult::error( OperationStatus::FORBIDDEN, 'Nenhuma função encontrada para o usuário.' );
            }

            // Extrair dados serializáveis para sessão (evita problemas de unserialização de entidades Doctrine)
            $userData = [ 
                'id'        => $user->getId(),
                'email'     => $user->getEmail(),
                'tenant_id' => $user->getTenant()->getId(),
                'user_id'   => $user->getId(), // Para compatibilidade
            ];

            $providerData = [ 
                'id'        => $provider->getId(),
                'user_id'   => $provider->getUser()->getId(),
                'tenant_id' => $provider->getTenant()->getId(),
                // Adicionar outros campos necessários
            ];

            Session::set( 'user', $userData );
            Session::set( 'auth', $providerData );
            Session::set( 'userRoles', $roles );

            // Carregar permissions do usuário
            $permissions = [];
            if ( method_exists( $userRepository, 'findUserPermissionsByTenantId' ) ) {
                $permissions = $userRepository->findUserPermissionsByTenantId(
                    $user->getId(),
                    $user->getTenant()->getId(),
                );
                if ( !empty( $permissions ) ) {
                    Session::set( 'userPermissions', $permissions );
                }
            }

            // Verificar se é admin e definir sessão especial
            if ( in_array( 'admin', $roles ) ) {
                Session::set( 'admin', $providerData );
            }

            // Retornar sucesso com dados estruturados
            return ServiceResult::success( [ 
                'user'        => $user,
                'provider'    => $provider,
                'roles'       => $roles,
                'permissions' => $permissions,
                'is_admin'    => $this->isAdmin()
            ], 'Login realizado com sucesso.' );

        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro interno no sistema de autenticação: ' . $e->getMessage() );
        }
    }

    /**
     * Verifica se existe uma sessão de autenticação ativa.
     *
     * Método otimizado para checagem rápida de presença de chave 'auth' na sessão.
     * Útil para middlewares de autorização ou guards de rotas.
     *
     * @return bool True se o usuário está autenticado via 'auth', false caso contrário
     */
    public function isAuthenticated(): bool
    {
        return Session::has( 'auth' );
    }

    /**
     * Verifica autenticação compatível com sistemas legados.
     *
     * Suporta tanto a chave 'user' (implementação clássica) quanto 'auth' (ORM moderna).
     * Ideal para transições graduais no código existente.
     *
     * @return bool True se há sessão de usuário ou auth ativa, false caso contrário
     */
    public function isAuth(): bool
    {
        return Session::has( 'user' ) || Session::has( 'auth' );
    }

    /**
     * Verifica se o usuário autenticado possui privilégios de administrador.
     *
     * Implementação em camadas: primeiro checa sessão direta ('admin'), depois roles na sessão,
     * e como fallback consulta o banco via repositório. Suporta variações como 'admin' ou 'administrator'.
     * Garante segurança em cenários de sessão corrompida ou expiração.
     *
     * @return bool True se o usuário é admin, false caso contrário
     * @throws Exception Em falhas de consulta ao banco de dados durante fallback
     */
    public function isAdmin(): bool
    {
        if ( !$this->isAuth() ) {
            return false;
        }

        // Verificação direta via sessão (mais rápida)
        if ( Session::has( 'admin' ) ) {
            return true;
        }

        // Verificação via roles na sessão
        if ( Session::has( 'userRoles' ) ) {
            $roles = Session::get( 'userRoles', [] );
            if ( in_array( 'admin', $roles ) ) {
                return true;
            }
        }

        // Verificação via banco de dados como fallback
        $userData = Session::get( 'user' ) ?? Session::get( 'auth' );
        if ( $userData && isset( $userData[ 'user_id' ], $userData[ 'tenant_id' ] ) ) {
            $roles = $this->userRepository->findUserRolesByTenantId( $userData[ 'user_id' ], $userData[ 'tenant_id' ] );
            return in_array( 'admin', $roles ) || in_array( 'administrator', $roles );
        }

        $userData = Session::get( 'user' ) ?? ( Session::get( 'auth' )?->getUser() ?? null );
        if ( $userData instanceof UserEntity ) {
            $roles = $this->userRepository->findUserRolesByTenantId(
                $userData->getId(),
                $userData->getTenant()->getId(),
            );
            return in_array( 'admin', $roles );
        }

        return false;
    }

    /**
     * Verifica se o usuário possui uma role específica atribuída.
     *
     * Consulta as roles armazenadas na sessão para verificação rápida.
     * Requer autenticação prévia; retorna false para usuários não logados.
     * Usa comparação estrita para evitar falsos positivos.
     *
     * @param string $role Nome exato da role (ex: 'provider', 'manager')
     * @return bool True se a role está presente, false caso contrário
     */
    public function hasRole( string $role ): bool
    {
        if ( !$this->isAuth() ) {
            return false;
        }

        $roles = Session::get( 'userRoles', [] );
        return in_array( $role, $roles, true );
    }

    /**
     * Verifica se o usuário possui uma permissão específica.
     *
     * Similar ao hasRole(), mas para permissões granulares (ex: 'create_budget').
     * Baseado em array de permissões na sessão, com comparação estrita.
     * Essencial para controle de acesso baseado em ABAC (Attribute-Based Access Control).
     *
     * @param string $permission Nome exato da permissão (ex: 'edit_user', 'delete_budget')
     * @return bool True se a permissão está presente, false caso contrário
     */
    public function hasPermission( string $permission ): bool
    {
        if ( !$this->isAuth() ) {
            return false;
        }

        $permissions = Session::get( 'userPermissions', [] );
        return in_array( $permission, $permissions, true );
    }

    /**
     * Verifica se o usuário é um prestador de serviços (provider).
     *
     * Delega para hasRole('provider'), integrando com o sistema de roles.
     * Útil para rotas específicas de providers no multi-tenancy.
     *
     * @return bool True se possui role 'provider', false caso contrário
     */
    public function isProvider(): bool
    {
        return $this->hasRole( 'provider' );
    }

    /**
     * Retorna os dados do usuário autenticado em formato array.
     *
     * Prioriza 'user' na sessão (clássica), fallback para 'auth' (ORM).
     * Retorna null se não autenticado. Compatível com código legado que espera array.
     *
     * @return array|null Dados do usuário (incluindo id, email, etc.) ou null
     */
    /**
     * Retorna os dados do usuário autenticado em formato array.
     *
     * Prioriza 'user' na sessão (clássica), fallback para 'auth' (ORM).
     * Retorna null se não autenticado. Compatível com código legado que espera array.
     * Se sessão contiver entidade inválida (de sessões antigas), destrói para evitar erros.
     *
     * @return array|null Dados do usuário (incluindo id, email, etc.) ou null
     */
    public function user(): ?array
    {
        if ( !$this->isAuth() ) {
            return null;
        }

        $userData = Session::get( 'user' );
        if ( $userData instanceof \__PHP_Incomplete_Class ) {
            // Sessão corrompida de versão anterior; limpar
            $this->logout();
            return null;
        }

        return $userData ?? Session::get( 'auth' );
    }

    /**
     * Retorna os dados de autenticação do provider.
     *
     * Focado na entidade ProviderEntity para compatibilidade com Auth.php legado.
     * Útil para acessar dados de prestador após login.
     *
     * @return array|null Dados do provider (incluindo tenant_id, user_id) ou null
     */
    /**
     * Retorna os dados de autenticação do provider.
     *
     * Focado na entidade ProviderEntity para compatibilidade com Auth.php legado.
     * Útil para acessar dados de prestador após login. Limpa sessão inválida se detectada.
     *
     * @return array|null Dados do provider (incluindo tenant_id, user_id) ou null
     */
    public function auth(): ?array
    {
        $authData = Session::get( 'auth' );
        if ( $authData instanceof \__PHP_Incomplete_Class ) {
            $this->logout();
            return null;
        }
        return $authData;
    }

    /**
     * Retorna a entidade UserEntity do usuário autenticado.
     *
     * Busca por user_id na sessão e recupera via repositório.
     * Inclui tratamento de exceções com logging para falhas de recuperação.
     * Alternativa otimizada ao getUserEntity() para cenários ORM puros.
     *
     * @return UserEntity|null Instância completa da entidade ou null em falha
     * @throws Exception Em erros de persistência, logado via error_log
     */
    /**
     * Retorna a entidade UserEntity do usuário autenticado.
     *
     * Busca por user_id na sessão e recupera via repositório.
     * Inclui tratamento de exceções com logging para falhas de recuperação.
     * Alternativa otimizada ao getUserEntity() para cenários ORM puros.
     *
     * @return UserEntity|null Instância completa da entidade ou null em falha
     * @throws Exception Em erros de persistência, logado via error_log
     */
    public function getAuthenticatedUser(): ?UserEntity
    {
        try {
            $userId = Session::get( 'user_id' ) ?? ( Session::get( 'user' )[ 'id' ] ?? null );
            if ( $userId ) {
                return $this->userRepository->find( (int) $userId );
            }
            return null;
        } catch ( Exception $e ) {
            error_log( "Erro ao obter usuário autenticado: " . $e->getMessage() );
            return null;
        }
    }

    /**
     * Retorna a entidade UserEntity baseada nos dados da sessão.
     *
     * Compatível com arrays de sessão legados; busca por id e recupera entidade completa.
     * Fallback para cenários onde user_id não está diretamente disponível.
     *
     * @return UserEntity|null Instância da entidade ou null se não encontrado
     * @throws Exception Em falhas de consulta ao repositório
     */
    public function getUserEntity(): ?UserEntity
    {
        if ( !$this->isAuth() ) {
            return null;
        }

        $userData = $this->user();
        if ( !$userData || !isset( $userData[ 'id' ] ) ) {
            return null;
        }

        return $this->userRepository->find( $userData[ 'id' ] );
    }

    /**
     * Retorna o array de roles atribuídas ao usuário autenticado.
     *
     * Recupera de sessão para performance; fallback para array vazio se não definido.
     * Útil para iterações em views ou lógica de negócios baseada em roles.
     *
     * @return array Lista de strings com nomes das roles (ex: ['admin', 'provider'])
     */
    public function getUserRoles(): array
    {
        return Session::get( 'userRoles', [] );
    }

    /**
     * Retorna o array de permissões atribuídas ao usuário autenticado.
     *
     * Similar a getUserRoles(), mas para permissões granulares.
     * Essencial para autorização fina em endpoints da API.
     *
     * @return array Lista de strings com nomes das permissões (ex: ['create_budget'])
     */
    public function getUserPermissions(): array
    {
        return Session::get( 'userPermissions', [] );
    }

    /**
     * Retorna a entidade SessionEntity da sessão atual ativa.
     *
     * Prioriza busca por token na sessão, fallback para user_id.
     * Inclui logging de erros para auditoria de sessões inválidas ou expiradas.
     * Útil para gerenciamento avançado de sessões (ex: invalidação em múltiplos dispositivos).
     *
     * @return SessionEntity|null Instância da sessão ativa ou null se não encontrada
     * @throws Exception Em falhas de repositório, logado via error_log
     */
    /**
     * Retorna a entidade SessionEntity da sessão atual ativa.
     *
     * Prioriza busca por token na sessão, fallback para user_id.
     * Inclui logging de erros para auditoria de sessões inválidas ou expiradas.
     * Útil para gerenciamento avançado de sessões (ex: invalidação em múltiplos dispositivos).
     *
     * @return SessionEntity|null Instância da sessão ativa ou null se não encontrada
     * @throws Exception Em falhas de repositório, logado via error_log
     */
    public function getCurrentSession(): ?SessionEntity
    {
        try {
            $sessionToken = Session::get( 'session_token' );
            if ( $sessionToken ) {
                return $this->sessionRepository->findActiveByToken( $sessionToken );
            }

            $userId = Session::get( 'user_id' ) ?? ( Session::get( 'user' )[ 'id' ] ?? null );
            if ( $userId ) {
                return $this->sessionRepository->findActiveByUserId( (int) $userId );
            }

            return null;
        } catch ( Exception $e ) {
            error_log( "Erro ao obter sessão atual: " . $e->getMessage() );
            return null;
        }
    }

    /**
     * Verifica acesso combinado: autenticação + role de admin.
     *
     * Shortcut para guards de rotas administrativas, combinando isAuthenticated() e isAdmin().
     * Otimizado para uso em middlewares ou controllers.
     *
     * @return bool True se autenticado e admin, false caso contrário
     */
    public function canAccessAdmin(): bool
    {
        return $this->isAuthenticated() && $this->isAdmin();
    }

    /**
     * Verifica acesso combinado: autenticação + role de provider.
     *
     * Similar a canAccessAdmin(), mas para prestadores de serviços.
     * Ideal para dashboards ou funcionalidades tenant-specific.
     *
     * @return bool True se autenticado e provider, false caso contrário
     */
    public function canAccessProvider(): bool
    {
        return $this->isAuthenticated() && $this->isProvider();
    }

    /**
     * Realiza logout completo, destruindo todas as chaves da sessão.
     *
     * Limpa dados de usuário, auth, roles, permissões e admin.
     * Garante invalidação segura de sessão para proteção contra session hijacking.
     * Não persiste invalidação no banco; use getCurrentSession() para gerenciamento ORM se necessário.
     *
     * @return void
     */
    public function logout(): void
    {
        Session::removeAll();
    }

    // ========================================================================
    // MÉTODOS ESTÁTICOS PARA COMPATIBILIDADE COM Auth.php LEGADO
    // ========================================================================

    /**
     * Verifica autenticação de forma estática (compatibilidade legado).
     *
     * Equivalente a isAuth(), mas sem instância da classe.
     * Útil para chamadas diretas em código antigo sem injeção de dependências.
     *
     * @return bool True se há sessão de usuário ou auth, false caso contrário
     */
    public static function isAuthStatic(): bool
    {
        return Session::has( 'user' ) || Session::has( 'auth' );
    }

    /**
     * Verifica admin de forma estática (compatibilidade legado).
     *
     * Equivalente a isAdmin(), focado em chave 'admin' na sessão.
     * Rápido para checagens simples em views ou helpers.
     *
     * @return bool True se sessão 'admin' existe, false caso contrário
     */
    public static function isAdminStatic(): bool
    {
        return Session::has( 'admin' );
    }

    /**
     * Retorna dados de autenticação de forma estática (compatibilidade legado).
     *
     * Equivalente a auth(), acessa diretamente a sessão 'auth'.
     * Para uso em contextos sem instância disponível.
     *
     * @return array|null Dados do provider ou null se não autenticado
     */
    public static function authStatic(): ?array
    {
        return Session::get( 'auth' );
    }

    /**
     * Realiza logout de forma estática (compatibilidade legado).
     *
     * Equivalente a logout(), limpa toda a sessão sem instância.
     * Ideal para botões de logout em templates ou scripts globais.
     *
     * @return void
     */
    public static function logoutStatic(): void
    {
        Session::removeAll();
    }

}