<?php

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Report extends Model
{
    use TenantScoped;

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
    protected $table = 'reports';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'user_id',
        'hash',
        'type',
        'description',
        'file_name',
        'status',
        'format',
        'size',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'hash'        => 'string',
        'type'        => 'string',
        'description' => 'string',
        'file_name'   => 'string',
        'status'      => 'string',
        'format'      => 'string',
        'size'        => 'float',
        'created_at'  => 'immutable_datetime',
    ];

    /**
     * Regras de validação para o modelo Report.
     */
    public static function businessRules(): array
    {
        return [
            'tenant_id'   => 'required|exists:tenants,id',
            'user_id'     => 'required|exists:users,id',
            'hash'        => 'nullable|string|max:64',
            'type'        => 'required|string|max:50',
            'description' => 'nullable|string',
            'file_name'   => 'required|string|max:255',
            'status'      => 'required|string|max:20|in:pending,processing,completed,failed',
            'format'      => 'required|string|max:10|in:pdf,xlsx,csv',
            'size'        => 'required|numeric|min:0',
        ];
    }

    /**
     * Validação personalizada para hash único por tenant.
     * Esta validação deve ser usada no contexto de um request onde o tenant_id está disponível.
     *
     * @param  string|null  $hash
     * @param  int|null  $excludeId
     * @return string
     */
    public static function validateUniqueHashRule( ?string $hash, ?int $excludeId = null ): string
    {
        if ( empty( $hash ) ) {
            return 'nullable|string|max:64';
        }

        $rule = 'unique:reports,hash';

        if ( $excludeId ) {
            $rule .= ',' . $excludeId . ',id';
        }

        return $rule . ',tenant_id,' . request()->user()->tenant_id;
    }

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public const UPDATED_AT = null;

    /**
     * Get the tenant that owns the Report.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo( Tenant::class);
    }

    /**
     * Get the user that owns the Report.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo( User::class);
    }

}
