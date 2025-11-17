<?php

namespace App\Providers;

use App\Contracts\Interfaces\Auth\OAuthClientInterface;
use App\Contracts\Interfaces\Auth\SocialAuthenticationInterface;
use App\Models\Budget;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Provider;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\User;
use App\Observers\BudgetObserver;
use App\Observers\CustomerObserver;
use App\Observers\InvoiceObserver;
use App\Observers\ProductObserver;
use App\Observers\ProviderObserver;
use App\Observers\ServiceObserver;
use App\Observers\UserObserver;
use App\Policies\SchedulePolicy;
use App\Repositories\AuditLogRepository;
use App\Repositories\Contracts\BaseRepositoryInterface;
use App\Services\Application\Auth\SocialAuthenticationService;
use App\Services\Infrastructure\OAuth\GoogleOAuthClient;
use App\Services\AlertService;
use App\Services\NotificationService;
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

        // Bindings para serviços de alertas e notificações
        $this->app->singleton(AlertService::class, function ($app) {
            return new AlertService($app->make(NotificationService::class));
        });

        $this->app->singleton(NotificationService::class);
    }

    public function boot()
    {
        // Register model observers for automatic audit logging
        User::observe(UserObserver::class);
        Provider::observe(ProviderObserver::class);
        Customer::observe(CustomerObserver::class);
        Budget::observe(BudgetObserver::class);
        Invoice::observe(InvoiceObserver::class);
        Product::observe(ProductObserver::class);
        Service::observe(ServiceObserver::class);

        // Register policies
        $this->app->make('Illuminate\Contracts\Auth\Access\Gate')->policy(Schedule::class, SchedulePolicy::class);

        Blade::if( 'role', fn( $role ) => auth()->check() && auth()->user()->hasRole( $role ) );
        Blade::if( 'anyrole', fn( $roles ) => auth()->check() && auth()->user()->hasAnyRole( (array) $roles ) );
    }

}
