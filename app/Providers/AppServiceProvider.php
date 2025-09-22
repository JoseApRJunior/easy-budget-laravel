<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Registrar services principais como singletons
        $this->app->singleton( \App\Services\UserService::class);
        $this->app->singleton( \App\Services\CustomerService::class);
        $this->app->singleton( \App\Services\BudgetService::class);
        $this->app->singleton( \App\Services\PlanService::class);
        $this->app->singleton( \App\Services\RoleService::class);
        $this->app->singleton( \App\Services\InvoiceService::class);
        $this->app->singleton( \App\Services\ActivityService::class);
        $this->app->singleton( \App\Services\NotificationService::class);
        $this->app->singleton( \App\Services\PdfService::class);
        $this->app->singleton( \App\Services\AddressService::class);
        $this->app->singleton( \App\Services\ContactService::class);
        $this->app->singleton( \App\Services\CommonDataService::class);
        $this->app->singleton( \App\Services\ProviderService::class);
        $this->app->singleton( \App\Services\ServiceService::class);
        $this->app->singleton( \App\Services\CategoryService::class);
        $this->app->singleton( \App\Services\BudgetStatusService::class);
        $this->app->singleton( \App\Services\ServiceStatusService::class);
        $this->app->singleton( \App\Services\EncryptionService::class);
        $this->app->singleton( \App\Services\MailerService::class);
        $this->app->singleton( \App\Services\CacheService::class);
        $this->app->singleton( \App\Services\MercadoPagoService::class);
        $this->app->singleton( \App\Services\PaymentMercadoPagoInvoiceService::class);
        $this->app->singleton( \App\Services\PaymentMercadoPagoPlanService::class);
        $this->app->singleton( \App\Services\MerchantOrderMercadoPagoService::class);
        $this->app->singleton( \App\Services\InvoiceStatusService::class);

        // TODO: Implement SupportService, AreaOfActivityService and ProfessionService classes
        // when needed for full functionality
        // $this->app->singleton( \App\Services\SupportService::class);
        // $this->app->singleton( \App\Services\AreaOfActivityService::class);
        // $this->app->singleton( \App\Services\ProfessionService::class);
        $this->app->singleton( \App\Services\UserRegistrationService::class);
        $this->app->singleton( \App\Services\ReportStorageService::class);
        $this->app->singleton( \App\Services\FinancialSummary::class);

        // Registrar services utilitários como singletons
        $this->app->singleton( \App\Services\SharedService::class);

        // Bind interfaces para implementações concretas
        $this->app->bind( \App\Interfaces\BudgetQueryInterface::class, \App\Services\BudgetService::class);
        $this->app->bind( \App\Interfaces\ServiceQueryInterface::class, \App\Services\ServiceService::class);

        // Bind interfaces específicas do MercadoPago
        $this->app->bind( \App\Interfaces\MercadoPagoServiceInterface::class, \App\Services\MercadoPagoService::class);
        $this->app->bind( \App\Interfaces\PaymentMercadoPagoInvoiceServiceInterface::class, \App\Services\PaymentMercadoPagoInvoiceService::class);
        $this->app->bind( \App\Interfaces\PaymentMercadoPagoPlanServiceInterface::class, \App\Services\PaymentMercadoPagoPlanService::class);
        $this->app->bind( \App\Interfaces\MerchantOrderMercadoPagoServiceInterface::class, \App\Services\MerchantOrderMercadoPagoService::class);

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }

}
