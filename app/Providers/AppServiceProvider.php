<?php

namespace App\Providers;

use App\Contracts\Interfaces\Auth\OAuthClientInterface;
use App\Contracts\Interfaces\Auth\SocialAuthenticationInterface;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Provider;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use App\Observers\BudgetObserver;
use App\Observers\CategoryObserver;
use App\Observers\CustomerObserver;
use App\Observers\InvoiceObserver;
use App\Observers\ProductObserver;
use App\Observers\ProviderObserver;
use App\Observers\ServiceObserver;
use App\Observers\TenantObserver;
use App\Observers\UserObserver;
use App\Policies\SchedulePolicy;
use App\Repositories\Contracts\BaseRepositoryInterface;
use App\Services\AlertService;
use App\Services\Application\Auth\SocialAuthenticationService;
use App\Services\Application\UserRegistrationService;
use App\Services\Infrastructure\OAuth\GoogleOAuthClient;
use App\Services\NotificationService;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\Paginator;
use App\Models\Resource;
use Laravel\Pennant\Feature;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Binding para autenticação social - Google OAuth
        $this->app->bind(OAuthClientInterface::class, GoogleOAuthClient::class);
        $this->app->bind(SocialAuthenticationInterface::class, SocialAuthenticationService::class);

        // Binding contextual para SocialAuthenticationService usar UserRegistrationService
        $this->app->when(SocialAuthenticationService::class)
            ->needs(UserRegistrationService::class)
            ->give(function ($app) {
                return $app->make(UserRegistrationService::class);
            });

        // Binding padrão para BaseRepositoryInterface (fallback)
        $this->app->bind(BaseRepositoryInterface::class, function ($app) {
            // Retorna uma implementação básica que pode ser usada como fallback
            // Serviços específicos devem usar suas próprias implementações de repositório
            return new class implements BaseRepositoryInterface
            {
                public function find(int $id): ?\Illuminate\Database\Eloquent\Model
                {
                    throw new \RuntimeException('BaseRepositoryInterface usado sem implementação específica. Use uma implementação concreta de repositório.');
                }

                public function getAll(): \Illuminate\Database\Eloquent\Collection
                {
                    throw new \RuntimeException('BaseRepositoryInterface usado sem implementação específica. Use uma implementação concreta de repositório.');
                }

                public function create(array $data): \Illuminate\Database\Eloquent\Model
                {
                    throw new \RuntimeException('BaseRepositoryInterface usado sem implementação específica. Use uma implementação concreta de repositório.');
                }

                public function update(int $id, array $data): ?\Illuminate\Database\Eloquent\Model
                {
                    throw new \RuntimeException('BaseRepositoryInterface usado sem implementação específica. Use uma implementação concreta de repositório.');
                }

                public function delete(int $id): bool
                {
                    throw new \RuntimeException('BaseRepositoryInterface usado sem implementação específica. Use uma implementação concreta de repositório.');
                }

                public function findOneBy(string|array $field, mixed $value = null, array $with = [], bool $withTrashed = false): ?\Illuminate\Database\Eloquent\Model
                {
                    throw new \RuntimeException('BaseRepositoryInterface usado sem implementação específica. Use uma implementação concreta de repositório.');
                }

                public function findBy(string|array $field, mixed $value = null): \Illuminate\Database\Eloquent\Collection
                {
                    throw new \RuntimeException('BaseRepositoryInterface usado sem implementação específica. Use uma implementação concreta de repositório.');
                }
            };
        });

        // Bindings para serviços de alertas e notificações
        $this->app->singleton(AlertService::class, function ($app) {
            return new AlertService($app->make(NotificationService::class));
        });

        $this->app->singleton(NotificationService::class);
    }

    public function boot()
    {
        // Share theme colors globally
        view()->share('pdfColors', config('theme.colors'));

        // Register model observers for automatic audit logging
        User::observe(UserObserver::class);
        Provider::observe(ProviderObserver::class);
        Customer::observe(CustomerObserver::class);
        Invoice::observe(InvoiceObserver::class);
        Product::observe(ProductObserver::class);
        Tenant::observe(TenantObserver::class);
        Category::observe(CategoryObserver::class);

        // Register policies
        $this->app->make('Illuminate\Contracts\Auth\Access\Gate')->policy(Schedule::class, SchedulePolicy::class);

        // Feature Flags com Laravel Pennant (Integrado com seu modelo Resource)
        Feature::define('feature', function ($user, string $featureSlug) {
            return Resource::where('slug', $featureSlug)
                ->where('status', Resource::STATUS_ACTIVE)
                ->exists();
        });

        // Sobrescreve o Gate para usar o Pennant internamente se necessário
        Gate::define('feature', fn ($user, string $featureSlug) => Feature::active('feature', $featureSlug));

        Blade::if('feature', fn (string $featureSlug) => Feature::active('feature', $featureSlug));

        Blade::if('role', fn ($role) => auth()->check() && auth()->user()->hasRole($role));
        Blade::if('anyrole', fn ($roles) => auth()->check() && auth()->user()->hasAnyRole((array) $roles));

        Paginator::useBootstrapFive();

        // Aumentar limite de memória para evitar erros em requisições pesadas
        ini_set('memory_limit', '256M');

        // Otimizar respostas JSON removendo o wrap 'data' desnecessário
        JsonResource::withoutWrapping();
    }
}
