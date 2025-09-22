<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Contact extends Model
{
    use BelongsToTenant;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'contacts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [ 
        'tenant_id',
        'email',
        'phone',
        'email_business',
        'phone_business',
        'website',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [ 
        'tenant_id'      => 'integer',
        'email'          => 'string',
        'phone'          => 'string',
        'email_business' => 'string',
        'phone_business' => 'string',
        'website'        => 'string',
        'created_at'     => 'datetime_immutable',
        'updated_at'     => 'datetime_immutable',
    ];

    /**
     * Get the tenant that owns the Contact.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo( Tenant::class);
    }

}