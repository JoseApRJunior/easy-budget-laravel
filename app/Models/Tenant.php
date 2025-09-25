<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Activity;
use App\Models\Address;
use App\Models\AreaOfActivity;
use App\Models\Budget;
use App\Models\Category;
use App\Models\CommonData;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\MiddlewareMetricHistory;
use App\Models\MonitoringAlertHistory;
use App\Models\PlanSubscription;
use App\Models\Product;
use App\Models\Profession;
use App\Models\Provider;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class Tenant extends Model
{
    use HasFactory;

    /**
     * Compatibilidade com schema atual dos tenants.
     * Campos permitidos conforme estrutura do banco.
     */
    protected $fillable = [
        'name',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Relações com entidades do sistema - Tenant é a entidade raiz para multi-tenancy.
     * Não usa TenantScoped trait pois é o modelo pai.
     */

    /**
     * Usuários pertencentes a este tenant.
     */
    public function users(): HasMany
    {
        return $this->hasMany( User::class);
    }

    /**
     * Provedores pertencentes a este tenant.
     */
    public function providers(): HasMany
    {
        return $this->hasMany( Provider::class);
    }

    /**
     * Orçamentos deste tenant.
     */
    public function budgets(): HasMany
    {
        return $this->hasMany( Budget::class);
    }

    /**
     * Serviços deste tenant.
     */
    public function services(): HasMany
    {
        return $this->hasMany( Service::class);
    }

    /**
     * Faturas deste tenant.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany( Invoice::class);
    }

    /**
     * Atividades de auditoria deste tenant.
     */
    public function activities(): HasMany
    {
        return $this->hasMany( Activity::class);
    }

    /**
     * Histórico de métricas de middleware deste tenant.
     */
    public function middlewareMetricHistories(): HasMany
    {
        return $this->hasMany( MiddlewareMetricHistory::class);
    }

    /**
     * Histórico de alertas de monitoramento deste tenant.
     */
    public function monitoringAlertHistories(): HasMany
    {
        return $this->hasMany( MonitoringAlertHistory::class);
    }

    /**
     * Assinaturas de planos deste tenant.
     */
    public function planSubscriptions(): HasMany
    {
        return $this->hasMany( PlanSubscription::class);
    }

    /**
     * Dados comuns (clientes/provedores) deste tenant.
     */
    public function commonData(): HasMany
    {
        return $this->hasMany( CommonData::class);
    }

    /**
     * Produtos deste tenant.
     */
    public function products(): HasMany
    {
        return $this->hasMany( Product::class);
    }

    /**
     * Relatórios gerados para este tenant (se aplicável).
     */
    public function reports(): HasMany
    {
        return $this->hasMany( Report::class);
    }

    /**
     * Endereços deste tenant.
     */
    public function addresses(): HasMany
    {
        return $this->hasMany( Address::class);
    }

    /**
     * Contatos deste tenant.
     */
    public function contacts(): HasMany
    {
        return $this->hasMany( Contact::class);
    }

    /**
     * Clientes deste tenant.
     */
    public function customers(): HasMany
    {
        return $this->hasMany( Customer::class);
    }

    /**
     * Movimentações de inventário deste tenant.
     */
    public function inventoryMovements(): HasMany
    {
        return $this->hasMany( InventoryMovement::class);
    }

    /**
     * Obtém o tenant atual baseado no contexto de autenticação.
     *
     * @return self|null
     */
    public static function current(): ?self
    {
        if ( Auth::check() ) {
            return Auth::user()->tenant ?? null;
        }
        return null;
    }

}