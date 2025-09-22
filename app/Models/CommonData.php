<?php
declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

class CommonData extends Model
{
    use TenantScoped;

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
        'birth_date'          => 'immutable_date',
        'cnpj'                => 'string',
        'cpf'                 => 'string',
        'company_name'        => 'string',
        'description'         => 'string',
        'area_of_activity_id' => 'integer',
        'profession_id'       => 'integer',
        'created_at'          => 'immutable_datetime',
        'updated_at'          => 'immutable_datetime',
    ];

    /**
     * Get the provider that owns the CommonData.
     */
    public function provider(): HasOne
    {
        return $this->hasOne( Provider::class);
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
        return $this->belongsTo( AreaOfActivity::class);
    }

    /**
     * Get the profession that owns the CommonData.
     */
    public function profession(): BelongsTo
    {
        return $this->belongsTo( Profession::class);
    }

    /**
     * Get the customer associated with the CommonData.
     */
    public function customer(): HasOne
    {
        return $this->hasOne( Customer::class);
    }

}
