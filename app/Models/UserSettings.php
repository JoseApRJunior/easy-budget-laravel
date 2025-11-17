<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para configurações específicas do usuário
 *
 * @property int $id
 * @property int $user_id
 * @property int $tenant_id
 * @property string|null $avatar
 * @property string|null $full_name
 * @property string|null $bio
 * @property string|null $phone
 * @property string|null $birth_date
 * @property string|null $social_facebook
 * @property string|null $social_twitter
 * @property string|null $social_linkedin
 * @property string|null $social_instagram
 * @property string $theme
 * @property string $primary_color
 * @property string $layout_density
 * @property string $sidebar_position
 * @property bool $animations_enabled
 * @property bool $sound_enabled
 * @property bool $email_notifications
 * @property bool $transaction_notifications
 * @property bool $weekly_reports
 * @property bool $security_alerts
 * @property bool $newsletter_subscription
 * @property bool $push_notifications
 * @property array|null $custom_preferences
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class UserSettings extends Model
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
    protected $table = 'user_settings';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'tenant_id',
        'avatar',
        'full_name',
        'bio',
        'phone',
        'birth_date',
        'social_facebook',
        'social_twitter',
        'social_linkedin',
        'social_instagram',
        'theme',
        'primary_color',
        'layout_density',
        'sidebar_position',
        'animations_enabled',
        'sound_enabled',
        'email_notifications',
        'transaction_notifications',
        'weekly_reports',
        'security_alerts',
        'newsletter_subscription',
        'push_notifications',
        'custom_preferences',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'tenant_id'                 => 'integer',
        'user_id'                   => 'integer',
        'birth_date'                => 'date',
        'animations_enabled'        => 'boolean',
        'sound_enabled'             => 'boolean',
        'email_notifications'       => 'boolean',
        'transaction_notifications' => 'boolean',
        'weekly_reports'            => 'boolean',
        'security_alerts'           => 'boolean',
        'newsletter_subscription'   => 'boolean',
        'push_notifications'        => 'boolean',
        'custom_preferences'        => 'array',
        'created_at'                => 'immutable_datetime',
        'updated_at'                => 'datetime',
    ];

    /**
     * Default values for attributes.
     */
    protected $attributes = [
        'theme'                     => 'auto',
        'primary_color'             => '#3B82F6',
        'layout_density'            => 'normal',
        'sidebar_position'          => 'left',
        'animations_enabled'        => true,
        'sound_enabled'             => true,
        'email_notifications'       => true,
        'transaction_notifications' => true,
        'weekly_reports'            => false,
        'security_alerts'           => true,
        'newsletter_subscription'   => false,
        'push_notifications'        => false,
    ];

    /**
     * Regras de validação para o modelo.
     */
    public static function businessRules(): array
    {
        return [
            'user_id'                   => 'required|integer|exists:users,id',
            'tenant_id'                 => 'required|integer|exists:tenants,id',
            'avatar'                    => 'nullable|string|max:255',
            'full_name'                 => 'nullable|string|max:255',
            'bio'                       => 'nullable|string|max:1000',
            'phone'                     => 'nullable|string|max:20',
            'birth_date'                => 'nullable|date|before:today',
            'social_facebook'           => 'nullable|url|max:255',
            'social_twitter'            => 'nullable|url|max:255',
            'social_linkedin'           => 'nullable|url|max:255',
            'social_instagram'          => 'nullable|url|max:255',
            'theme'                     => 'required|in:light,dark,auto',
            'primary_color'             => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'layout_density'            => 'required|in:compact,normal,spacious',
            'sidebar_position'          => 'required|in:left,right',
            'animations_enabled'        => 'boolean',
            'sound_enabled'             => 'boolean',
            'email_notifications'       => 'boolean',
            'transaction_notifications' => 'boolean',
            'weekly_reports'            => 'boolean',
            'security_alerts'           => 'boolean',
            'newsletter_subscription'   => 'boolean',
            'push_notifications'        => 'boolean',
            'custom_preferences'        => 'nullable|array',
        ];
    }

    /**
     * Get the user that owns the settings.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo( User::class);
    }

    /**
     * Get the tenant that owns the settings.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo( Tenant::class);
    }

    /**
     * Get avatar URL.
     */
    public function getAvatarUrlAttribute(): ?string
    {
        if ( !$this->avatar ) {
            return null;
        }

        // Se for URL externa, retorna como está
        if ( filter_var( $this->avatar, FILTER_VALIDATE_URL ) ) {
            return $this->avatar;
        }

        // Caso contrário, assume que é caminho do storage
        return asset( 'storage/' . $this->avatar );
    }

    /**
     * Get formatted birth date.
     */
    public function getFormattedBirthDateAttribute(): ?string
    {
        return $this->birth_date?->format( 'd/m/Y' );
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
     * Check if user has social media links.
     */
    public function hasSocialMedia(): bool
    {
        return !empty( $this->social_facebook ) ||
            !empty( $this->social_twitter ) ||
            !empty( $this->social_linkedin ) ||
            !empty( $this->social_instagram );
    }

    /**
     * Get active social media links.
     */
    public function getActiveSocialLinks(): array
    {
        $links = [];

        if ( $this->social_facebook ) {
            $links[ 'facebook' ] = $this->social_facebook;
        }
        if ( $this->social_twitter ) {
            $links[ 'twitter' ] = $this->social_twitter;
        }
        if ( $this->social_linkedin ) {
            $links[ 'linkedin' ] = $this->social_linkedin;
        }
        if ( $this->social_instagram ) {
            $links[ 'instagram' ] = $this->social_instagram;
        }

        return $links;
    }

    /**
     * Get notification preferences as array.
     */
    public function getNotificationPreferences(): array
    {
        return [
            'email_notifications'       => $this->email_notifications,
            'transaction_notifications' => $this->transaction_notifications,
            'weekly_reports'            => $this->weekly_reports,
            'security_alerts'           => $this->security_alerts,
            'newsletter_subscription'   => $this->newsletter_subscription,
            'push_notifications'        => $this->push_notifications,
        ];
    }

    /**
     * Update notification preferences.
     */
    public function updateNotificationPreferences( array $preferences ): bool
    {
        $allowedKeys = [
            'email_notifications',
            'transaction_notifications',
            'weekly_reports',
            'security_alerts',
            'newsletter_subscription',
            'push_notifications'
        ];

        $updateData = array_intersect_key( $preferences, array_flip( $allowedKeys ) );

        return $this->update( $updateData );
    }

}
