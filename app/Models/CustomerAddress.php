<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo CustomerAddress - Endereços dos clientes
 *
 * Gerencia múltiplos endereços por cliente com suporte a geolocalização
 * e diferentes tipos de endereço (principal, trabalho, filial, etc).
 */
class CustomerAddress extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'customer_addresses';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'customer_id',
        'type',
        'cep',
        'street',
        'number',
        'complement',
        'neighborhood',
        'city',
        'state',
        'latitude',
        'longitude',
        'formatted_address',
        'is_primary',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'customer_id'       => 'integer',
        'type'              => 'string',
        'cep'               => 'string',
        'street'            => 'string',
        'number'            => 'string',
        'complement'        => 'string',
        'neighborhood'      => 'string',
        'city'              => 'string',
        'state'             => 'string',
        'latitude'          => 'decimal:8',
        'longitude'         => 'decimal:8',
        'formatted_address' => 'string',
        'is_primary'        => 'boolean',
        'is_active'         => 'boolean',
        'created_at'        => 'immutable_datetime',
        'updated_at'        => 'datetime',
    ];

    /**
     * Regras de validação para o modelo CustomerAddress.
     */
    public static function businessRules(): array
    {
        return [
            'customer_id'       => 'required|integer|exists:customers,id',
            'type'              => 'required|string|max:50',
            'cep'               => 'required|string|size:9|regex:/^\d{5}-?\d{3}$/',
            'street'            => 'required|string|max:255',
            'number'            => 'required|string|max:20',
            'complement'        => 'nullable|string|max:100',
            'neighborhood'      => 'required|string|max:100',
            'city'              => 'required|string|max:100',
            'state'             => 'required|string|size:2',
            'latitude'          => 'nullable|numeric|between:-90,90',
            'longitude'         => 'nullable|numeric|between:-180,180',
            'formatted_address' => 'nullable|string|max:500',
            'is_primary'        => 'boolean',
            'is_active'         => 'boolean',
        ];
    }

    /**
     * Get the customer that owns the address.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo( Customer::class);
    }

    /**
     * Scope para buscar apenas endereços ativos.
     */
    public function scopeActive( $query )
    {
        return $query->where( 'is_active', true );
    }

    /**
     * Scope para buscar apenas endereços principais.
     */
    public function scopePrimary( $query )
    {
        return $query->where( 'is_primary', true );
    }

    /**
     * Scope para buscar endereços por tipo.
     */
    public function scopeOfType( $query, string $type )
    {
        return $query->where( 'type', $type );
    }

    /**
     * Get the address's full formatted address.
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter( [
            $this->street,
            $this->number,
            $this->complement,
            $this->neighborhood,
            $this->city,
            $this->state,
            $this->cep,
        ] );

        return implode( ', ', $parts );
    }

    /**
     * Get the address's formatted CEP.
     */
    public function getFormattedCepAttribute(): string
    {
        if ( $this->cep ) {
            return substr( $this->cep, 0, 5 ) . '-' . substr( $this->cep, 5, 3 );
        }

        return '';
    }

    /**
     * Check if this address has geolocation data.
     */
    public function hasGeolocation(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    /**
     * Get the distance from another point (in kilometers).
     */
    public function getDistanceTo( float $latitude, float $longitude ): float
    {
        if ( !$this->hasGeolocation() ) {
            return 0;
        }

        $earthRadius = 6371; // km

        $dLat = deg2rad( $latitude - $this->latitude );
        $dLng = deg2rad( $longitude - $this->longitude );

        $a = sin( $dLat / 2 ) * sin( $dLat / 2 ) + cos( deg2rad( $this->latitude ) ) * cos( deg2rad( $latitude ) ) * sin( $dLng / 2 ) * sin( $dLng / 2 );
        $c = 2 * atan2( sqrt( $a ), sqrt( 1 - $a ) );

        return $earthRadius * $c;
    }

    /**
     * Set this address as primary for the customer.
     */
    public function setAsPrimary(): void
    {
        // Remove primary flag from other addresses of the same customer
        static::where( 'customer_id', $this->customer_id )
            ->where( 'id', '!=', $this->id )
            ->update( [ 'is_primary' => false ] );

        // Set this address as primary
        $this->update( [ 'is_primary' => true ] );
    }

    /**
     * Get the address type label.
     */
    public function getTypeLabelAttribute(): string
    {
        return match ( $this->type ) {
            'principal' => 'Principal',
            'trabalho'  => 'Trabalho',
            'filial'    => 'Filial',
            'cobranca'  => 'Cobrança',
            'entrega'   => 'Entrega',
            'outros'    => 'Outros',
            default     => ucfirst( $this->type ),
        };
    }

}
