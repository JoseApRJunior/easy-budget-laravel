<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailVariable extends Model
{
    use HasFactory;
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
    protected $table = 'email_variables';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'description',
        'category',
        'data_type',
        'default_value',
        'validation_rules',
        'is_system',
        'is_active',
        'sort_order',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tenant_id'        => 'integer',
        'validation_rules' => 'array',
        'default_value'    => 'string',
        'is_system'        => 'boolean',
        'is_active'        => 'boolean',
        'sort_order'       => 'integer',
        'metadata'         => 'array',
        'created_at'       => 'immutable_datetime',
        'updated_at'       => 'datetime',
    ];

    /**
     * Campos que devem ser tratados como datas imutáveis.
     */
    protected $dates = [
        'created_at',
        'updated_at',
    ];

    /**
     * Regras de validação para o modelo EmailVariable.
     */
    public static function businessRules(): array
    {
        return [
            'tenant_id'        => 'required|integer|exists:tenants,id',
            'name'             => 'required|string|max:255',
            'slug'             => 'required|string|max:100|unique:email_variables,slug',
            'description'      => 'required|string|max:500',
            'category'         => 'required|in:system,user,customer,budget,invoice,company',
            'data_type'        => 'required|in:string,number,date,boolean,array',
            'default_value'    => 'nullable|string|max:1000',
            'validation_rules' => 'nullable|array',
            'is_system'        => 'boolean',
            'is_active'        => 'boolean',
            'sort_order'       => 'integer|min:0',
            'metadata'         => 'nullable|array',
        ];
    }

    /**
     * Regras de validação para criação de variável.
     */
    public static function createRules(): array
    {
        $rules         = self::businessRules();
        $rules[ 'name' ] = 'required|string|max:255|unique:email_variables,name';
        $rules[ 'slug' ] = 'required|string|max:100|unique:email_variables,slug';

        return $rules;
    }

    /**
     * Regras de validação para atualização de variável.
     */
    public static function updateRules( int $variableId ): array
    {
        $rules         = self::businessRules();
        $rules[ 'name' ] = 'required|string|max:255|unique:email_variables,name,' . $variableId;
        $rules[ 'slug' ] = 'required|string|max:100|unique:email_variables,slug,' . $variableId;

        return $rules;
    }

    /**
     * Get the tenant that owns the EmailVariable.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo( Tenant::class);
    }

    /**
     * Scope para variáveis ativas.
     */
    public function scopeActive( $query )
    {
        return $query->where( 'is_active', true );
    }

    /**
     * Scope para variáveis do sistema.
     */
    public function scopeSystem( $query )
    {
        return $query->where( 'is_system', true );
    }

    /**
     * Scope para variáveis customizadas.
     */
    public function scopeCustom( $query )
    {
        return $query->where( 'is_system', false );
    }

    /**
     * Scope para variáveis por categoria.
     */
    public function scopeByCategory( $query, string $category )
    {
        return $query->where( 'category', $category );
    }

    /**
     * Scope para variáveis por tipo de dado.
     */
    public function scopeByDataType( $query, string $dataType )
    {
        return $query->where( 'data_type', $dataType );
    }

    /**
     * Scope para ordenação padrão.
     */
    public function scopeOrdered( $query )
    {
        return $query->orderBy( 'category' )->orderBy( 'sort_order' )->orderBy( 'name' );
    }

    /**
     * Verifica se a variável pode ser editada.
     */
    public function canBeEdited(): bool
    {
        return !$this->is_system;
    }

    /**
     * Verifica se a variável pode ser excluída.
     */
    public function canBeDeleted(): bool
    {
        return !$this->is_system;
    }

    /**
     * Valida o valor da variável baseado nas regras de validação.
     */
    public function validateValue( $value ): array
    {
        $rules = $this->validation_rules ?? [];

        // Adicionar regras baseadas no tipo de dado
        switch ( $this->data_type ) {
            case 'string':
                $rules[] = 'string';
                if ( !isset( $rules[ 'max' ] ) ) {
                    $rules[] = 'max:1000';
                }
                break;
            case 'number':
                $rules[] = 'numeric';
                break;
            case 'date':
                $rules[] = 'date';
                break;
            case 'boolean':
                $rules[] = 'boolean';
                break;
            case 'array':
                $rules[] = 'array';
                break;
        }

        // Se não há valor e existe valor padrão, usar o padrão
        if ( empty( $value ) && !empty( $this->default_value ) ) {
            $value = $this->default_value;
        }

        // Se ainda não há valor, verificar se é obrigatório
        if ( empty( $value ) && !in_array( 'nullable', $rules ) ) {
            return [
                'valid' => false,
                'error' => 'O valor é obrigatório para esta variável.'
            ];
        }

        // Se o valor está vazio e é nullable, é válido
        if ( empty( $value ) && in_array( 'nullable', $rules ) ) {
            return [
                'valid' => true,
                'value' => null
            ];
        }

        // Aplicar validação Laravel
        $validator = \Illuminate\Support\Facades\Validator::make(
            [ $this->slug => $value ],
            [ $this->slug => $rules ],
        );

        if ( $validator->fails() ) {
            return [
                'valid' => false,
                'error' => implode( ', ', $validator->errors()->all() )
            ];
        }

        return [
            'valid' => true,
            'value' => $value
        ];
    }

    /**
     * Obtém o valor formatado da variável.
     */
    public function formatValue( $value ): string
    {
        if ( empty( $value ) ) {
            return '';
        }

        switch ( $this->data_type ) {
            case 'date':
                try {
                    return \Carbon\Carbon::parse( $value )->format( 'd/m/Y' );
                } catch ( \Exception $e ) {
                    return (string) $value;
                }
            case 'number':
                return number_format( (float) $value, 2, ',', '.' );
            case 'boolean':
                return $value ? 'Sim' : 'Não';
            case 'array':
                return is_array( $value ) ? implode( ', ', $value ) : (string) $value;
            default:
                return (string) $value;
        }
    }

    /**
     * Obtém variáveis agrupadas por categoria.
     */
    public static function getGroupedByCategory( int $tenantId ): array
    {
        $variables = static::where( 'tenant_id', $tenantId )
            ->active()
            ->ordered()
            ->get();

        $grouped = [];
        foreach ( $variables as $variable ) {
            $grouped[ $variable->category ][] = $variable;
        }

        return $grouped;
    }

    /**
     * Obtém variáveis disponíveis para uso em templates.
     */
    public static function getAvailableForTemplates( int $tenantId ): array
    {
        $variables = static::where( 'tenant_id', $tenantId )
            ->active()
            ->ordered()
            ->get();

        $available = [];
        foreach ( $variables as $variable ) {
            $available[ $variable->slug ] = [
                'name'        => $variable->name,
                'description' => $variable->description,
                'category'    => $variable->category,
                'data_type'   => $variable->data_type,
                'default'     => $variable->default_value,
            ];
        }

        return $available;
    }

}
