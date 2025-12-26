<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailLog extends Model
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
    protected $table = 'email_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'email_template_id',
        'recipient_email',
        'recipient_name',
        'subject',
        'sender_email',
        'sender_name',
        'status',
        'sent_at',
        'opened_at',
        'clicked_at',
        'bounced_at',
        'error_message',
        'metadata',
        'tracking_id',
        'ip_address',
        'user_agent',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tenant_id' => 'integer',
        'email_template_id' => 'integer',
        'sent_at' => 'datetime',
        'opened_at' => 'datetime',
        'clicked_at' => 'datetime',
        'bounced_at' => 'datetime',
        'metadata' => 'array',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Campos que devem ser tratados como datas imutáveis.
     */
    protected $dates = [
        'sent_at',
        'opened_at',
        'clicked_at',
        'bounced_at',
        'created_at',
        'updated_at',
    ];

    /**
     * Regras de validação para o modelo EmailLog.
     */
    public static function businessRules(): array
    {
        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'email_template_id' => 'required|integer|exists:email_templates,id',
            'recipient_email' => 'required|email|max:255',
            'recipient_name' => 'nullable|string|max:255',
            'subject' => 'required|string|max:500',
            'sender_email' => 'required|email|max:255',
            'sender_name' => 'nullable|string|max:255',
            'status' => 'required|in:pending,sent,delivered,opened,clicked,bounced,failed',
            'sent_at' => 'nullable|datetime',
            'opened_at' => 'nullable|datetime',
            'clicked_at' => 'nullable|datetime',
            'bounced_at' => 'nullable|datetime',
            'error_message' => 'nullable|string|max:1000',
            'metadata' => 'nullable|array',
            'tracking_id' => 'nullable|string|max:100|unique:email_logs,tracking_id',
            'ip_address' => 'nullable|ip',
            'user_agent' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get the tenant that owns the EmailLog.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the email template that owns the EmailLog.
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class, 'email_template_id');
    }
    
    // ... rest of class ...

    /**
     * Scope para logs por status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope para logs enviados.
     */
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    /**
     * Scope para logs abertos.
     */
    public function scopeOpened($query)
    {
        return $query->whereNotNull('opened_at');
    }

    /**
     * Scope para logs clicados.
     */
    public function scopeClicked($query)
    {
        return $query->whereNotNull('clicked_at');
    }

    /**
     * Scope para logs com erro.
     */
    public function scopeFailed($query)
    {
        return $query->whereIn('status', ['failed', 'bounced']);
    }

    /**
     * Scope para logs por período.
     */
    public function scopeByPeriod($query, string $period)
    {
        switch ($period) {
            case 'today':
                return $query->whereDate('created_at', today());
            case 'week':
                return $query->whereBetween('created_at', [
                    now()->startOfWeek(),
                    now()->endOfWeek(),
                ]);
            case 'month':
                return $query->whereMonth('created_at', now()->month);
            case 'year':
                return $query->whereYear('created_at', now()->year);
            default:
                return $query;
        }
    }

    /**
     * Scope para logs por destinatário.
     */
    public function scopeByRecipient($query, string $email)
    {
        return $query->where('recipient_email', $email);
    }

    /**
     * Verifica se o email foi aberto.
     */
    public function isOpened(): bool
    {
        return ! is_null($this->opened_at);
    }

    /**
     * Verifica se o email foi clicado.
     */
    public function isClicked(): bool
    {
        return ! is_null($this->clicked_at);
    }

    /**
     * Verifica se o email falhou.
     */
    public function isFailed(): bool
    {
        return in_array($this->status, ['failed', 'bounced']);
    }

    /**
     * Marca o email como aberto.
     */
    public function markAsOpened(?string $ipAddress = null, ?string $userAgent = null): void
    {
        if (! $this->isOpened()) {
            $this->update([
                'opened_at' => now(),
                'status' => 'opened',
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
            ]);
        }
    }

    /**
     * Marca o email como clicado.
     */
    public function markAsClicked(?string $ipAddress = null, ?string $userAgent = null): void
    {
        $this->update([
            'clicked_at' => now(),
            'status' => 'clicked',
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }

    /**
     * Marca o email como com erro.
     */
    public function markAsFailed(string $errorMessage, string $status = 'failed'): void
    {
        $this->update([
            'status' => $status,
            'error_message' => $errorMessage,
            'bounced_at' => $status === 'bounced' ? now() : null,
        ]);
    }

    /**
     * Obtém estatísticas do log.
     */
    public function getStats(): array
    {
        return [
            'id' => $this->id,
            'recipient' => $this->recipient_name.' <'.$this->recipient_email.'>',
            'status' => $this->status,
            'sent_at' => $this->sent_at?->toISOString(),
            'opened_at' => $this->opened_at?->toISOString(),
            'clicked_at' => $this->clicked_at?->toISOString(),
            'error_message' => $this->error_message,
            'tracking_id' => $this->tracking_id,
            'is_opened' => $this->isOpened(),
            'is_clicked' => $this->isClicked(),
            'is_failed' => $this->isFailed(),
        ];
    }
}
