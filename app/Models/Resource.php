<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Resource extends Model
{
    /**
     * Status constants.
     */
    const STATUS_ACTIVE = 'active';

    const STATUS_INACTIVE = 'inactive';

    const STATUS_DELETED = 'deleted';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'resources';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'in_dev',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'in_dev' => 'boolean',
        'status' => 'string',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Regras de validação para o modelo Resource.
     */
    public static function businessRules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'slug' => 'required|string|max:100|unique:resources,slug',
            'in_dev' => 'boolean',
            'status' => 'required|in:'.implode(',', [
                self::STATUS_ACTIVE,
                self::STATUS_INACTIVE,
                self::STATUS_DELETED,
            ]),
        ];
    }

    /**
     * Verifica se o recurso é visível para um determinado usuário.
     */
    public function isVisibleTo(?User $user = null): bool
    {
        // Se deletado ou inativo, nunca é visível publicamente
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        // Se em desenvolvimento, apenas admin ou beta podem ver
        if ((bool) $this->in_dev) {
            if (! $user) {
                return false;
            }

            // Verifica se é admin (independente de tenant se possível) ou se é beta
            $isAdmin = method_exists($user, 'hasRole') ? $user->hasRole('admin') : false;
            $isBeta = $user->is_beta ?? false;

            return $isAdmin || $isBeta;
        }

        return true;
    }
}
