<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Model para histórico de métricas de middleware, scoped por tenant.
 */
class MiddlewareMetricHistory extends Model
{
    use HasFactory, TenantScoped;

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();
        static::bootTenantScoped();
    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'middleware_metrics_history';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'middleware_name',
        'endpoint',
        'method',
        'response_time',
        'memory_usage',
        'cpu_usage',
        'status_code',
        'error_message',
        'user_id',
        'ip_address',
        'user_agent',
        'request_size',
        'response_size',
        'database_queries',
        'cache_hits',
        'cache_misses',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'response_time'    => 'float',
        'memory_usage'     => 'integer',
        'cpu_usage'        => 'float',
        'status_code'      => 'integer',
        'tenant_id'        => 'integer',
        'user_id'          => 'integer',
        'ip_address'       => 'string',
        'user_agent'       => 'string',
        'request_size'     => 'integer',
        'response_size'    => 'integer',
        'database_queries' => 'integer',
        'cache_hits'       => 'integer',
        'cache_misses'     => 'integer',
        'created_at'       => 'datetime',
    ];


        /**
     * Regras de validação para o modelo Plan.
     */
    public static function businessRules(): array
    {
        return [

        ];
    }

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Get the tenant that owns the MiddlewareMetricHistory.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo( Tenant::class);
    }

    /**
     * Get the user that owns the MiddlewareMetricHistory.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo( User::class);
    }

}
