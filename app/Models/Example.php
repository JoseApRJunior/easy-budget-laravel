<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo de exemplo para demonstração do padrão WithTenant.
 *
 * Esta entidade representa um exemplo de como implementar
 * uma entidade tenant-aware no sistema Easy Budget.
 */
class Example extends Model
{
    /**
     * Nome da tabela no banco de dados.
     *
     * @var string
     */
    protected $table = 'examples';

    /**
     * Atributos que podem ser preenchidos em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [ 
        'tenant_id',
        'name',
        'description',
        'status',
        'example_type',
        'metadata',
    ];

    /**
     * Atributos que devem ser convertidos para tipos específicos.
     *
     * @var array<string, string>
     */
    protected $casts = [ 
        'tenant_id' => 'integer',
        'status'    => 'string',
        'metadata'  => 'array',
    ];

    /**
     * Relacionamento com o tenant.
     *
     * @return BelongsTo
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo( Tenant::class, 'tenant_id' );
    }

    /**
     * Verifica se o exemplo está ativo.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Verifica se o exemplo é do tipo específico.
     *
     * @param string $type
     * @return bool
     */
    public function isType( string $type ): bool
    {
        return $this->example_type === $type;
    }

}
