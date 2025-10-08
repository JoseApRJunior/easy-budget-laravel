<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para configurações gerais do sistema
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $company_name
 * @property string $contact_email
 * @property string|null $phone
 * @property string|null $website
 * @property string|null $logo
 * @property string $currency
 * @property string $timezone
 * @property string $language
 * @property string|null $address_street
 * @property string|null $address_number
 * @property string|null $address_complement
 * @property string|null $address_neighborhood
 * @property string|null $address_city
 * @property string|null $address_state
 * @property string|null $address_zip_code
 * @property string|null $address_country
 * @property bool $maintenance_mode
 * @property string|null $maintenance_message
 * @property bool $registration_enabled
 * @property bool $email_verification_required
 * @property int $session_lifetime
 * @property int $max_login_attempts
 * @property int $lockout_duration
 * @property array|null $allowed_file_types
 * @property int $max_file_size
 * @property array|null $system_preferences
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class SystemSettings extends Model
{
    use HasFactory, TenantScoped;

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
     */
    protected $table = 'system_settings';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'tenant_id',
        'company_name',
        'contact_email',
        'phone',
        'website',
        'logo',
        'currency',
        'timezone',
        'language',
        'address_street',
        'address_number',
        'address_complement',
        'address_neighborhood',
        'address_city',
        'address_state',
        'address_zip_code',
        'address_country',
        'maintenance_mode',
        'maintenance_message',
        'registration_enabled',
        'email_verification_required',
        'session_lifetime',
        'max_login_attempts',
        'lockout_duration',
        'allowed_file_types',
        'max_file_size',
        'system_preferences',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'tenant_id'                   => 'integer',
        'maintenance_mode'            => 'boolean',
        'registration_enabled'        => 'boolean',
        'email_verification_required' => 'boolean',
        'session_lifetime'            => 'integer',
        'max_login_attempts'          => 'integer',
        'lockout_duration'            => 'integer',
        'allowed_file_types'          => 'array',
        'max_file_size'               => 'integer',
        'system_preferences'          => 'array',
        'created_at'                  => 'immutable_datetime',
        'updated_at'                  => 'datetime',
    ];

    /**
     * Default values for attributes.
     */
    protected $attributes = [
        'currency'                    => 'BRL',
        'timezone'                    => 'America/Sao_Paulo',
        'language'                    => 'pt-BR',
        'maintenance_mode'            => false,
        'registration_enabled'        => true,
        'email_verification_required' => true,
        'session_lifetime'            => 120, // minutos
        'max_login_attempts'          => 5,
        'lockout_duration'            => 15, // minutos
        'max_file_size'               => 2048, // KB
    ];

    /**
     * Regras de validação para o modelo.
     */
    public static function businessRules(): array
    {
        return [
            'tenant_id'                   => 'required|integer|exists:tenants,id',
            'company_name'                => 'required|string|max:255',
            'contact_email'               => 'required|email:rfc,dns|max:255',
            'phone'                       => 'nullable|string|max:20',
            'website'                     => 'nullable|url|max:255',
            'logo'                        => 'nullable|string|max:255',
            'currency'                    => 'required|in:BRL,USD,EUR',
            'timezone'                    => 'required|timezone',
            'language'                    => 'required|in:pt-BR,en-US,es-ES',
            'address_street'              => 'nullable|string|max:255',
            'address_number'              => 'nullable|string|max:20',
            'address_complement'          => 'nullable|string|max:100',
            'address_neighborhood'        => 'nullable|string|max:100',
            'address_city'                => 'nullable|string|max:100',
            'address_state'               => 'nullable|string|max:50',
            'address_zip_code'            => 'nullable|string|max:10',
            'address_country'             => 'nullable|string|max:50',
            'maintenance_mode'            => 'boolean',
            'maintenance_message'         => 'nullable|string|max:500',
            'registration_enabled'        => 'boolean',
            'email_verification_required' => 'boolean',
            'session_lifetime'            => 'required|integer|min:5|max:10080', // 5 min a 1 semana
            'max_login_attempts'          => 'required|integer|min:3|max:10',
            'lockout_duration'            => 'required|integer|min:1|max:60',
            'allowed_file_types'          => 'nullable|array',
            'max_file_size'               => 'required|integer|min:1|max:10240', // Máximo 10MB
            'system_preferences'          => 'nullable|array',
        ];
    }

    /**
     * Get the tenant that owns the settings.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo( Tenant::class);
    }

    /**
     * Get logo URL.
     */
    public function getLogoUrlAttribute(): ?string
    {
        if ( !$this->logo ) {
            return null;
        }

        // Se for URL externa, retorna como está
        if ( filter_var( $this->logo, FILTER_VALIDATE_URL ) ) {
            return $this->logo;
        }

        // Caso contrário, assume que é caminho do storage
        return asset( 'storage/' . $this->logo );
    }

    /**
     * Get formatted phone number.
     */
    public function getFormattedPhoneAttribute(): ?string
    {
        if ( !$this->phone ) {
            return null;
        }

        // Remove todos os caracteres não numéricos
        $phone = preg_replace( '/\D/', '', $this->phone );

        // Formatação para telefone brasileiro
        if ( strlen( $phone ) === 11 ) {
            return '(' . substr( $phone, 0, 2 ) . ') ' . substr( $phone, 2, 5 ) . '-' . substr( $phone, 7 );
        } elseif ( strlen( $phone ) === 10 ) {
            return '(' . substr( $phone, 0, 2 ) . ') ' . substr( $phone, 2, 4 ) . '-' . substr( $phone, 6 );
        }

        return $this->phone;
    }

    /**
     * Get full address as formatted string.
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter( [
            $this->address_street,
            $this->address_number,
            $this->address_complement,
            $this->address_neighborhood,
            $this->address_city,
            $this->address_state,
            $this->address_zip_code,
            $this->address_country,
        ] );

        return implode( ', ', $parts );
    }

    /**
     * Get currency symbol.
     */
    public function getCurrencySymbolAttribute(): string
    {
        return match ( $this->currency ) {
            'BRL'   => 'R$',
            'USD'   => '$',
            'EUR'   => '€',
            default => $this->currency,
        };
    }

    /**
     * Get language name.
     */
    public function getLanguageNameAttribute(): string
    {
        return match ( $this->language ) {
            'pt-BR' => 'Português (Brasil)',
            'en-US' => 'English (US)',
            'es-ES' => 'Español',
            default => $this->language,
        };
    }

    /**
     * Get timezone display name.
     */
    public function getTimezoneNameAttribute(): string
    {
        $timezoneNames = [
            'America/Sao_Paulo' => 'São Paulo (UTC-3)',
            'America/New_York'  => 'Nova York (UTC-5)',
            'Europe/London'     => 'Londres (UTC+0)',
            'Europe/Paris'      => 'Paris (UTC+1)',
            'Asia/Tokyo'        => 'Tóquio (UTC+9)',
        ];

        return $timezoneNames[ $this->timezone ] ?? $this->timezone;
    }

    /**
     * Check if file type is allowed.
     */
    public function isFileTypeAllowed( string $mimeType ): bool
    {
        if ( !$this->allowed_file_types ) {
            // Se não especificado, permite tipos comuns
            $defaultTypes = [
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/webp',
                'application/pdf',
                'text/plain',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ];

            return in_array( $mimeType, $defaultTypes );
        }

        return in_array( $mimeType, $this->allowed_file_types );
    }

    /**
     * Check if file size is within limits.
     */
    public function isFileSizeAllowed( int $fileSizeInKB ): bool
    {
        return $fileSizeInKB <= $this->max_file_size;
    }

    /**
     * Get formatted session lifetime.
     */
    public function getFormattedSessionLifetimeAttribute(): string
    {
        if ( $this->session_lifetime < 60 ) {
            return $this->session_lifetime . ' minutos';
        }

        $hours   = intdiv( $this->session_lifetime, 60 );
        $minutes = $this->session_lifetime % 60;

        if ( $minutes === 0 ) {
            return $hours . ' hora' . ( $hours > 1 ? 's' : '' );
        }

        return $hours . 'h ' . $minutes . 'min';
    }

    /**
     * Get security settings as array.
     */
    public function getSecuritySettings(): array
    {
        return [
            'email_verification_required' => $this->email_verification_required,
            'session_lifetime'            => $this->session_lifetime,
            'max_login_attempts'          => $this->max_login_attempts,
            'lockout_duration'            => $this->lockout_duration,
        ];
    }

    /**
     * Update security settings.
     */
    public function updateSecuritySettings( array $settings ): bool
    {
        $allowedKeys = [
            'email_verification_required',
            'session_lifetime',
            'max_login_attempts',
            'lockout_duration',
        ];

        $updateData = array_intersect_key( $settings, array_flip( $allowedKeys ) );

        return $this->update( $updateData );
    }

    /**
     * Get default allowed file types.
     */
    public static function getDefaultAllowedFileTypes(): array
    {
        return [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'application/pdf',
            'text/plain',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];
    }

}
