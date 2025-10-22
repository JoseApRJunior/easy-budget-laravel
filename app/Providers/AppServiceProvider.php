<?php

namespace App\Providers;

use App\Contracts\Interfaces\Auth\OAuthClientInterface;
use App\Contracts\Interfaces\Auth\SocialAuthenticationInterface;
use App\Repositories\AuditLogRepository;
use App\Repositories\Contracts\BaseRepositoryInterface;
use App\Services\Application\Auth\SocialAuthenticationService;
use App\Services\Application\UserRegistrationService;
use App\Services\Infrastructure\OAuth\GoogleOAuthClient;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Binding para autenticação social - Google OAuth
        $this->app->bind( OAuthClientInterface::class, GoogleOAuthClient::class);
        $this->app->bind( SocialAuthenticationInterface::class, SocialAuthenticationService::class);

        // Binding contextual para SocialAuthenticationService usar UserRegistrationService
        $this->app->when( SocialAuthenticationService::class)
            ->needs( UserRegistrationService::class)
            ->give( function ( $app ) {
                return $app->make( UserRegistrationService::class);
            } );

        // Binding contextual para ActivityService usar AuditLogRepository
        $this->app->when( \App\Services\Domain\ActivityService::class)
            ->needs( BaseRepositoryInterface::class)
            ->give( function ( $app ) {
                return $app->make( AuditLogRepository::class);
            } );

        // Binding padrão para BaseRepositoryInterface (fallback)
        $this->app->bind( BaseRepositoryInterface::class, function ( $app ) {
            // Retorna uma implementação básica que pode ser usada como fallback
            // Serviços específicos devem usar suas próprias implementações de repositório
            return new class implements BaseRepositoryInterface
            {
                public function find( int $id ): ?\Illuminate\Database\Eloquent\Model
                {
                    throw new \RuntimeException( 'BaseRepositoryInterface usado sem implementação específica. Use uma implementação concreta de repositório.' );
                }

                public function getAll(): \Illuminate\Database\Eloquent\Collection
                {
                    throw new \RuntimeException( 'BaseRepositoryInterface usado sem implementação específica. Use uma implementação concreta de repositório.' );
                }

                public function create( array $data ): \Illuminate\Database\Eloquent\Model
                {
                    throw new \RuntimeException( 'BaseRepositoryInterface usado sem implementação específica. Use uma implementação concreta de repositório.' );
                }

                public function update( int $id, array $data ): ?\Illuminate\Database\Eloquent\Model
                {
                    throw new \RuntimeException( 'BaseRepositoryInterface usado sem implementação específica. Use uma implementação concreta de repositório.' );
                }

                public function delete( int $id ): bool
                {
                    throw new \RuntimeException( 'BaseRepositoryInterface usado sem implementação específica. Use uma implementação concreta de repositório.' );
                }

            };
        } );
    }

    public function boot()
    {
        Blade::if( 'role', fn( $role ) => auth()->check() && auth()->user()->hasRole( $role ) );
        Blade::if( 'anyrole', fn( $roles ) => auth()->check() && auth()->user()->hasAnyRole( (array) $roles ) );
    }

}
