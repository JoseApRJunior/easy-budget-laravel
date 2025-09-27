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
     * Regras de validação para o modelo MiddlewareMetricHistory.
     */
    public static function businessRules(): array
    {
        return [
            'tenant_id'        => 'required|integer|exists:tenants,id',
            'middleware_name'  => 'required|string|max:100',
            'endpoint'         => 'required|string|max:255',
            'method'           => 'required|string|in:GET,POST,PUT,PATCH,DELETE',
            'response_time'    => 'required|numeric|min:0|max:999999.999',
            'memory_usage'     => 'required|integer|min:0',
            'cpu_usage'        => 'nullable|numeric|min:0|max:100.00',
            'status_code'      => 'required|integer|min:100|max:599',
            'error_message'    => 'nullable|string',
            'user_id'          => 'nullable|integer|exists:users,id',
            'ip_address'       => 'nullable|string|max:45',
            'user_agent'       => 'nullable|string',
            'request_size'     => 'nullable|integer|min:0',
            'response_size'    => 'nullable|integer|min:0',
            'database_queries' => 'nullable|integer|min:0',
            'cache_hits'       => 'nullable|integer|min:0',
            'cache_misses'     => 'nullable|integer|min:0',
        ];
    }

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

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

    /**
     * Scope para filtrar por período de tempo.
     */
    public function scopePeriod( $query, Carbon $startDate, Carbon $endDate )
    {
        return $query->whereBetween( 'created_at', [ $startDate, $endDate ] );
    }

    /**
     * Scope para métricas de um middleware específico.
     */
    public function scopeByMiddleware( $query, string $middlewareName )
    {
        return $query->where( 'middleware_name', $middlewareName );
    }

    /**
     * Scope para métricas de um endpoint específico.
     */
    public function scopeByEndpoint( $query, string $endpoint )
    {
        return $query->where( 'endpoint', $endpoint );
    }

    /**
     * Scope para métricas por método HTTP.
     */
    public function scopeByMethod( $query, string $method )
    {
        return $query->where( 'method', $method );
    }

    /**
     * Scope para métricas por status code.
     */
    public function scopeByStatusCode( $query, int $statusCode )
    {
        return $query->where( 'status_code', $statusCode );
    }

    /**
     * Scope para métricas com erro (status >= 400).
     */
    public function scopeWithErrors( $query )
    {
        return $query->where( 'status_code', '>=', 400 );
    }

    /**
     * Scope para métricas bem-sucedidas (status < 400).
     */
    public function scopeSuccessful( $query )
    {
        return $query->where( 'status_code', '<', 400 );
    }

    /**
     * Calcula estatísticas de performance para um período.
     */
    public static function getPerformanceStats( int $tenantId, Carbon $startDate, Carbon $endDate ): array
    {
        return static::where( 'tenant_id', $tenantId )
            ->period( $startDate, $endDate )
            ->selectRaw( '
                COUNT(*) as total_requests,
                AVG(response_time) as avg_response_time,
                MIN(response_time) as min_response_time,
                MAX(response_time) as max_response_time,
                AVG(memory_usage) as avg_memory_usage,
                AVG(cpu_usage) as avg_cpu_usage,
                AVG(database_queries) as avg_database_queries,
                AVG(cache_hits) as avg_cache_hits,
                AVG(cache_misses) as avg_cache_misses,
                COUNT(CASE WHEN status_code >= 400 THEN 1 END) as error_count,
                COUNT(CASE WHEN status_code < 400 THEN 1 END) as success_count
            ' )
            ->first()
            ->toArray();
    }

    /**
     * Obtém os endpoints mais utilizados.
     */
    public static function getTopEndpoints( int $tenantId, Carbon $startDate, Carbon $endDate, int $limit = 10 ): array
    {
        return static::where( 'tenant_id', $tenantId )
            ->period( $startDate, $endDate )
            ->selectRaw( 'endpoint, method, COUNT(*) as request_count, AVG(response_time) as avg_response_time' )
            ->groupBy( 'endpoint', 'method' )
            ->orderBy( 'request_count', 'desc' )
            ->limit( $limit )
            ->get()
            ->toArray();
    }

    /**
     * Obtém os middlewares com pior performance.
     */
    public static function getSlowestMiddlewares( int $tenantId, Carbon $startDate, Carbon $endDate, int $limit = 10 ): array
    {
        return static::where( 'tenant_id', $tenantId )
            ->period( $startDate, $endDate )
            ->selectRaw( 'middleware_name, COUNT(*) as request_count, AVG(response_time) as avg_response_time, AVG(memory_usage) as avg_memory_usage' )
            ->groupBy( 'middleware_name' )
            ->orderBy( 'avg_response_time', 'desc' )
            ->limit( $limit )
            ->get()
            ->toArray();
    }

    /**
     * Calcula taxa de erro por período.
     */
    public static function getErrorRate( int $tenantId, Carbon $startDate, Carbon $endDate ): float
    {
        $stats = static::where( 'tenant_id', $tenantId )
            ->period( $startDate, $endDate )
            ->selectRaw( '
                COUNT(*) as total,
                COUNT(CASE WHEN status_code >= 400 THEN 1 END) as errors
            ' )
            ->first();

        return $stats->total > 0 ? ( $stats->errors / $stats->total ) * 100 : 0;
    }

    /**
     * Verifica se a métrica indica um problema de performance.
     */
    public function isPerformanceIssue(): bool
    {
        // Considera problema se tempo de resposta > 5s ou uso de CPU > 80%
        return $this->response_time > 5000.0 || ( $this->cpu_usage && $this->cpu_usage > 80.0 );
    }

    /**
     * Verifica se a métrica indica um erro.
     */
    public function isError(): bool
    {
        return $this->status_code >= 400;
    }

    /**
     * Obtém uma descrição formatada da métrica.
     */
    public function getFormattedDescription(): string
    {
        return sprintf(
            '%s %s - %s - %dms - %dMB - %s',
            $this->method,
            $this->endpoint,
            $this->middleware_name,
            $this->response_time,
            $this->memory_usage ? round( $this->memory_usage / 1024 / 1024, 2 ) : 0,
            $this->isError() ? 'ERRO' : 'OK'
        );
    }

}
