<?php
declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class CommonData extends Model
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
    protected $table = 'common_datas';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'first_name',
        'last_name',
        'birth_date',
        'cnpj',
        'cpf',
        'company_name',
        'description',
        'area_of_activity_id',
        'profession_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tenant_id'           => 'integer',
        'first_name'          => 'string',
        'last_name'           => 'string',
        'birth_date'          => 'date',
        'cnpj'                => 'string',
        'cpf'                 => 'string',
        'company_name'        => 'string',
        'description'         => 'string',
        'area_of_activity_id' => 'integer',
        'profession_id'       => 'integer',
        'created_at'          => 'immutable_datetime',
        'updated_at'          => 'datetime',
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
     * Get the tenant that owns the CommonData.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo( Tenant::class);
    }

    /**
     * Get the area of activity that owns the CommonData.
     */
    public function areaOfActivity(): BelongsTo
    {
        return $this->belongsTo( AreaOfActivity::class, 'area_of_activity_id' );
    }

    /**
     * Get the profession that owns the CommonData.
     */
    public function profession(): BelongsTo
    {
        return $this->belongsTo( Profession::class, 'profession_id' );
    }

    /**
     * Get the customers associated with the CommonData.
     */
    public function customers(): HasMany
    {
        return $this->hasMany( Customer::class);
    }

}
