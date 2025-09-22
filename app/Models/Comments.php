<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Comments extends Model
{
    use BelongsToTenant;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'comments';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [ 
        'tenant_id',
        'budget_id',
        'activity_id',
        'user_id',
        'content',
        'parent_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [ 
        'created_at' => 'datetime_immutable',
        'updated_at' => 'datetime_immutable',
    ];

    /**
     * Get the budget that owns the comment.
     */
    public function budget(): BelongsTo
    {
        return $this->belongsTo( Budget::class);
    }

    /**
     * Get the activity that owns the comment.
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo( Activity::class);
    }

    /**
     * Get the user that owns the comment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo( User::class);
    }

    /**
     * Get the parent comment.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo( Comments::class, 'parent_id' );
    }

    /**
     * Get the child comments.
     */
    public function children(): HasMany
    {
        return $this->hasMany( Comments::class, 'parent_id' );
    }

}