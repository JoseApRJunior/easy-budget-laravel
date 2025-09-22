<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class ServiceStatus extends Model
{

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'service_statuses';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [ 
        'slug',
        'name',
        'description',
        'color',
        'icon',
        'order_index',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [ 
        'slug'        => 'string',
        'name'        => 'string',
        'description' => 'string',
        'color'       => 'string',
        'icon'        => 'string',
        'order_index' => 'integer',
        'is_active'   => 'boolean',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];

    /**
     * Get the services for the ServiceStatus.
     */
    public function services(): HasMany
    {
        return $this->hasMany( Service::class, 'service_statuses_id' );
    }

}
