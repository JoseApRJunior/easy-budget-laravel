<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo CustomerContact - Contatos dos clientes
 *
 * Gerencia múltiplos tipos de contato por cliente (email, telefone, WhatsApp, etc)
 * com suporte a verificação e diferentes níveis de prioridade.
 */
class CustomerContact extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'customer_contacts';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'customer_id',
        'type',
        'label',
        'value',
        'is_primary',
        'is_verified',
        'verified_at',
        'notes',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'customer_id' => 'integer',
        'type'        => 'string',
        'label'       => 'string',
        'value'       => 'string',
        'is_primary'  => 'boolean',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'notes'       => 'string',
        'is_active'   => 'boolean',
        'created_at'  => 'immutable_datetime',
        'updated_at'  => 'datetime',
    ];

    /**
     * Regras de validação para o modelo CustomerContact.
     */
    public static function businessRules(): array
    {
        return [
            'customer_id' => 'required|integer|exists:customers,id',
            'type'        => 'required|string|in:email,phone,whatsapp,linkedin,site,skype,outros',
            'label'       => 'nullable|string|max:100',
            'value'       => 'required|string|max:255',
            'is_primary'  => 'boolean',
            'is_verified' => 'boolean',
            'verified_at' => 'nullable|date',
            'notes'       => 'nullable|string|max:500',
            'is_active'   => 'boolean',
        ];
    }

    /**
     * Get the customer that owns the contact.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo( Customer::class);
    }

    /**
     * Scope para buscar apenas contatos ativos.
     */
    public function scopeActive( $query )
    {
        return $query->where( 'is_active', true );
    }

    /**
     * Scope para buscar apenas contatos principais.
     */
    public function scopePrimary( $query )
    {
        return $query->where( 'is_primary', true );
    }

    /**
     * Scope para buscar contatos por tipo.
     */
    public function scopeOfType( $query, string $type )
    {
        return $query->where( 'type', $type );
    }

    /**
     * Scope para buscar apenas contatos verificados.
     */
    public function scopeVerified( $query )
    {
        return $query->where( 'is_verified', true );
    }

    /**
     * Get the contact type label.
     */
    public function getTypeLabelAttribute(): string
    {
        return match ( $this->type ) {
            'email'    => 'Email',
            'phone'    => 'Telefone',
            'whatsapp' => 'WhatsApp',
            'linkedin' => 'LinkedIn',
            'site'     => 'Website',
            'skype'    => 'Skype',
            'outros'   => 'Outros',
            default    => ucfirst( $this->type ),
        };
    }

    /**
     * Get the formatted contact value.
     */
    public function getFormattedValueAttribute(): string
    {
        return match ( $this->type ) {
            'phone', 'whatsapp' => $this->formatPhone( $this->value ),
            'email'             => strtolower( $this->value ),
            default             => $this->value,
        };
    }

    /**
     * Check if this contact is of email type.
     */
    public function isEmail(): bool
    {
        return $this->type === 'email';
    }

    /**
     * Check if this contact is of phone type.
     */
    public function isPhone(): bool
    {
        return in_array( $this->type, [ 'phone', 'whatsapp' ] );
    }

    /**
     * Check if this contact is a social media profile.
     */
    public function isSocialMedia(): bool
    {
        return in_array( $this->type, [ 'linkedin', 'skype' ] );
    }

    /**
     * Check if this contact is a website.
     */
    public function isWebsite(): bool
    {
        return $this->type === 'site';
    }

    /**
     * Mark contact as verified.
     */
    public function markAsVerified(): void
    {
        $this->update( [
            'is_verified' => true,
            'verified_at' => now(),
        ] );
    }

    /**
     * Mark contact as unverified.
     */
    public function markAsUnverified(): void
    {
        $this->update( [
            'is_verified' => false,
            'verified_at' => null,
        ] );
    }

    /**
     * Set this contact as primary for the customer.
     */
    public function setAsPrimary(): void
    {
        // Remove primary flag from other contacts of the same customer
        static::where( 'customer_id', $this->customer_id )
            ->where( 'id', '!=', $this->id )
            ->update( [ 'is_primary' => false ] );

        // Set this contact as primary
        $this->update( [ 'is_primary' => true ] );
    }

    /**
     * Get the contact's priority level.
     */
    public function getPriorityLevelAttribute(): string
    {
        if ( $this->is_primary ) {
            return 'primary';
        }

        return 'secondary';
    }

    /**
     * Format phone number for display.
     */
    private function formatPhone( string $phone ): string
    {
        // Remove all non-numeric characters
        $phone = preg_replace( '/\D/', '', $phone );

        // Check if it's a Brazilian phone number
        if ( strlen( $phone ) === 11 ) {
            // Cell phone with country code: (11) 99999-9999
            return '(' . substr( $phone, 0, 2 ) . ') ' . substr( $phone, 2, 5 ) . '-' . substr( $phone, 7 );
        } elseif ( strlen( $phone ) === 10 ) {
            // Landline with area code: (11) 9999-9999
            return '(' . substr( $phone, 0, 2 ) . ') ' . substr( $phone, 2, 4 ) . '-' . substr( $phone, 6 );
        }

        return $phone; // Return original if format doesn't match
    }

    /**
     * Get the contact's verification status label.
     */
    public function getVerificationStatusLabelAttribute(): string
    {
        return $this->is_verified ? 'Verificado' : 'Não verificado';
    }

}
