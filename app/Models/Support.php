<?php

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;

class Support extends Model
{
    use TenantScoped;

    /** Status: Chamado aberto, aguardando atendimento */
    public const STATUS_ABERTO = 'ABERTO';

    /** Status: Chamado respondido pela equipe */
    public const STATUS_RESPONDIDO = 'RESPONDIDO';

    /** Status: Chamado resolvido */
    public const STATUS_RESOLVIDO = 'RESOLVIDO';

    /** Status: Chamado fechado */
    public const STATUS_FECHADO = 'FECHADO';

    /** Status: Chamado em andamento */
    public const STATUS_EM_ANDAMENTO = 'EM_ANDAMENTO';

    /** Status: Aguardando resposta do cliente */
    public const STATUS_AGUARDANDO_RESPOSTA = 'AGUARDANDO_RESPOSTA';

    /** Status: Chamado cancelado */
    public const STATUS_CANCELADO = 'CANCELADO';

    /** Lista de todos os status disponíveis */
    public const ALL_STATUSES = [
        self::STATUS_ABERTO,
        self::STATUS_RESPONDIDO,
        self::STATUS_RESOLVIDO,
        self::STATUS_FECHADO,
        self::STATUS_EM_ANDAMENTO,
        self::STATUS_AGUARDANDO_RESPOSTA,
        self::STATUS_CANCELADO,
    ];

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
    protected $table = 'supports';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'subject',
        'message',
        'status',// criar const dos status
        'tenant_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tenant_id'  => 'integer',
        'status'     => 'string',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Regras de validação para o modelo Support.
     */
    public static function businessRules(): array
    {
        return [
            'first_name' => 'nullable|string|max:255',
            'last_name'  => 'nullable|string|max:255',
            'email'      => 'required|email|max:255',
            'subject'    => 'required|string|max:255',
            'message'    => 'required|string',
            'status'     => 'required|string|in:' . implode( ',', self::ALL_STATUSES ),
            'tenant_id'  => 'required|integer|exists:tenants,id',
        ];
    }

    /**
     * Get the tenant that owns the Support.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo( Tenant::class);
    }

    /**
     * Verifica se o chamado está aberto
     *
     * @return bool
     */
    public function isAberto(): bool
    {
        return $this->status === self::STATUS_ABERTO;
    }

    /**
     * Verifica se o chamado está resolvido
     *
     * @return bool
     */
    public function isResolvido(): bool
    {
        return $this->status === self::STATUS_RESOLVIDO;
    }

    /**
     * Verifica se o chamado está fechado
     *
     * @return bool
     */
    public function isFechado(): bool
    {
        return $this->status === self::STATUS_FECHADO;
    }

    /**
     * Verifica se o chamado está em andamento
     *
     * @return bool
     */
    public function isEmAndamento(): bool
    {
        return $this->status === self::STATUS_EM_ANDAMENTO;
    }

    /**
     * Retorna a descrição do status atual
     *
     * @return string
     */
    public function getStatusDescription(): string
    {
        return match ($this->status) {
            self::STATUS_ABERTO => 'Chamado aberto, aguardando atendimento',
            self::STATUS_RESPONDIDO => 'Chamado respondido pela equipe',
            self::STATUS_RESOLVIDO => 'Chamado resolvido',
            self::STATUS_FECHADO => 'Chamado fechado',
            self::STATUS_EM_ANDAMENTO => 'Chamado em andamento',
            self::STATUS_AGUARDANDO_RESPOSTA => 'Aguardando resposta do cliente',
            self::STATUS_CANCELADO => 'Chamado cancelado',
            default => 'Status desconhecido',
        };
    }

}
