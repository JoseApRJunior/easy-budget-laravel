<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // Register policies here
        \App\Models\Schedule::class => \App\Policies\SchedulePolicy::class,
        \App\Models\Tenant::class => \App\Policies\TenantPolicy::class,
        \App\Models\Plan::class => \App\Policies\PlanPolicy::class,
        \App\Models\Customer::class => \App\Policies\CustomerPolicy::class,
        \App\Models\Provider::class => \App\Policies\ProviderPolicy::class,
        \App\Models\Category::class => \App\Policies\CategoryPolicy::class,
        \App\Models\Activity::class => \App\Policies\ActivityPolicy::class,
        \App\Models\Profession::class => \App\Policies\ProfessionPolicy::class,
        \App\Models\Product::class => \App\Policies\ProductPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Global admin gate - allows admins to do anything
        Gate::before(function (User $user, string $ability) {
            if ($user->isAdmin()) {
                return true;
            }
            return null;
        });

        // Admin Management Gates
        $this->registerAdminGates();
        
        // System Management Gates
        $this->registerSystemGates();
        
        // Content Management Gates
        $this->registerContentGates();
        
        // User Management Gates
        $this->registerUserGates();
        
        // Financial Management Gates
        $this->registerFinancialGates();
        
        // Report and Analytics Gates
        $this->registerReportGates();
        
        // Inventory Management Gates
        $this->registerInventoryGates();
    }

    /**
     * Register admin management gates
     */
    protected function registerAdminGates(): void
    {
        // Global Settings Management
        Gate::define('manage-global-settings', function (User $user) {
            return $user->isAdmin();
        });

        // System Configuration
        Gate::define('manage-system-configuration', function (User $user) {
            return $user->isAdmin();
        });

        // Application Settings
        Gate::define('manage-application-settings', function (User $user) {
            return $user->isAdmin();
        });

        // Backup and Restore
        Gate::define('manage-backups', function (User $user) {
            return $user->isAdmin();
        });

        // Cache Management
        Gate::define('manage-cache', function (User $user) {
            return $user->isAdmin();
        });

        // Alerts Management
        Gate::define('manage-alerts', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('create-alert', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('update-alert', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('delete-alert', function (User $user) {
            return $user->isAdmin();
        });
    }

    /**
     * Register system management gates
     */
    protected function registerSystemGates(): void
    {
        // Tenant Management
        Gate::define('manage-tenants', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('create-tenant', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('update-tenant', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('delete-tenant', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('suspend-tenant', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('activate-tenant', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('impersonate-tenant', function (User $user) {
            return $user->isAdmin();
        });

        // Plan Management
        Gate::define('manage-plans', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('create-plan', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('update-plan', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('delete-plan', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('duplicate-plan', function (User $user) {
            return $user->isAdmin();
        });

        // Queue Management
        Gate::define('manage-queues', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('control-queues', function (User $user) {
            return $user->isAdmin();
        });

        // Monitoring
        Gate::define('view-monitoring', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('manage-monitoring', function (User $user) {
            return $user->isAdmin();
        });

        // Audit Logs
        Gate::define('view-audit-logs', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('manage-audit-logs', function (User $user) {
            return $user->isAdmin();
        });
    }

    /**
     * Register content management gates
     */
    protected function registerContentGates(): void
    {
        // Category Management
        Gate::define('manage-categories', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('create-category', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('update-category', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('delete-category', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('duplicate-category', function (User $user) {
            return $user->isAdmin();
        });

        // Activity Management
        Gate::define('manage-activities', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('create-activity', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('update-activity', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('delete-activity', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('duplicate-activity', function (User $user) {
            return $user->isAdmin();
        });

        // Profession Management
        Gate::define('manage-professions', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('create-profession', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('update-profession', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('delete-profession', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('duplicate-profession', function (User $user) {
            return $user->isAdmin();
        });
    }

    /**
     * Register user management gates
     */
    protected function registerUserGates(): void
    {
        // User Management
        Gate::define('manage-users', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('create-user', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('update-user', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('delete-user', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('block-user', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('unblock-user', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('impersonate-user', function (User $user) {
            return $user->isAdmin();
        });

        // Customer Management
        Gate::define('manage-customers', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('create-customer', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('update-customer', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('delete-customer', function (User $user) {
            return $user->isAdmin();
        });

        // Provider Management
        Gate::define('manage-providers', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('create-provider', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('update-provider', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('delete-provider', function (User $user) {
            return $user->isAdmin();
        });
    }

    /**
     * Register financial management gates
     */
    protected function registerFinancialGates(): void
    {
        // Financial Control
        Gate::define('manage-financial', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('view-financial-reports', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('manage-budget-alerts', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('view-provider-finances', function (User $user) {
            return $user->isAdmin();
        });
    }

    /**
     * Register report and analytics gates
     */
    protected function registerReportGates(): void
    {
        // System Reports
        Gate::define('view-system-reports', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('generate-system-reports', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('export-system-reports', function (User $user) {
            return $user->isAdmin();
        });

        // AI Analytics
        Gate::define('view-ai-analytics', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('manage-ai-analytics', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('retrain-ai-models', function (User $user) {
            return $user->isAdmin();
        });

        // Dashboard Analytics
        Gate::define('view-admin-dashboard', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('view-system-metrics', function (User $user) {
            return $user->isAdmin();
        });

        // Advanced Metrics
        Gate::define('view-advanced-metrics', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('export-metrics', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('view-realtime-metrics', function (User $user) {
            return $user->isAdmin();
        });
    }

    /**
     * Register inventory management gates
     */
    protected function registerInventoryGates(): void
    {
        // Inventory Management
        Gate::define('manage-inventory', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('view-inventory', function (User $user) {
            return $user->isAdmin() || $user->hasPermission('view-inventory');
        });

        Gate::define('view-inventory-reports', function (User $user) {
            return $user->isAdmin() || $user->hasPermission('view-inventory-reports');
        });

        Gate::define('view-inventory-alerts', function (User $user) {
            return $user->isAdmin() || $user->hasPermission('view-inventory-alerts');
        });

        Gate::define('adjust-inventory', function (User $user) {
            return $user->isAdmin() || $user->hasPermission('adjust-inventory');
        });

        Gate::define('export-inventory-reports', function (User $user) {
            return $user->isAdmin() || $user->hasPermission('export-inventory-reports');
        });
    }
}