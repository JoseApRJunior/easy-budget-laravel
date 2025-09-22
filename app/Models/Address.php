<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Address extends Model
{
    use BelongsToTenant;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'addresses';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [ 
        'tenant_id',
        'address',
        'address_number',
        'neighborhood',
        'city',
        'state',
        'cep',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [ 
        'tenant_id'      => 'integer',
        'address'        => 'string',
        'address_number' => 'string',
        'neighborhood'   => 'string',
        'city'           => 'string',
        'state'          => 'string',
        'cep'            => 'string',
        'created_at'     => 'datetime_immutable',
        'updated_at'     => 'datetime_immutable',
    ];

    /**
     * Get the tenant that owns the Address.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo( Tenant::class);
    }

}