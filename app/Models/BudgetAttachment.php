<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class BudgetAttachment extends Model
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
     */
    protected $table = 'budget_attachments';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'tenant_id',
        'budget_id',
        'file_name',
        'original_name',
        'file_path',
        'mime_type',
        'file_size',
        'file_hash',
        'description',
        'is_public',
        'download_count',
        'last_downloaded_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'tenant_id' => 'integer',
        'budget_id' => 'integer',
        'file_size' => 'integer',
        'is_public' => 'boolean',
        'download_count' => 'integer',
        'last_downloaded_at' => 'datetime',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Regras de validação para o modelo BudgetAttachment.
     */
    public static function businessRules(): array
    {
        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'budget_id' => 'required|integer|exists:budgets,id',
            'file_name' => 'required|string|max:255',
            'original_name' => 'required|string|max:255',
            'file_path' => 'required|string|max:500',
            'mime_type' => 'required|string|max:100',
            'file_size' => 'required|integer|min:1|max:52428800', // Máximo 50MB
            'file_hash' => 'nullable|string|max:64',
            'description' => 'nullable|string|max:1000',
            'is_public' => 'required|boolean',
            'download_count' => 'required|integer|min:0',
        ];
    }

    /**
     * Get the tenant that owns the BudgetAttachment.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the budget that owns the BudgetAttachment.
     */
    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    /**
     * Scope para anexos públicos.
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope para anexos privados.
     */
    public function scopePrivate($query)
    {
        return $query->where('is_public', false);
    }

    /**
     * Scope para anexos de um orçamento específico.
     */
    public function scopeForBudget($query, int $budgetId)
    {
        return $query->where('budget_id', $budgetId);
    }

    /**
     * Scope para anexos ordenados por data.
     */
    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Incrementa o contador de downloads.
     */
    public function incrementDownloadCount(): bool
    {
        $this->increment('download_count');
        $this->last_downloaded_at = Carbon::now();

        return $this->save();
    }

    /**
     * Verifica se o anexo pode ser baixado pelo usuário.
     */
    public function canBeDownloadedBy(int $userId): bool
    {
        // Se é público, qualquer um pode baixar
        if ($this->is_public) {
            return true;
        }

        // Se é privado, apenas o criador do orçamento pode baixar
        return $this->budget->user_id === $userId;
    }

    /**
     * Obtém o tamanho do arquivo formatado.
     */
    public function getFormattedFileSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < 3; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    /**
     * Obtém a extensão do arquivo.
     */
    public function getFileExtensionAttribute(): string
    {
        return strtolower(pathinfo($this->file_name, PATHINFO_EXTENSION));
    }

    /**
     * Verifica se é um arquivo de imagem.
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Verifica se é um arquivo PDF.
     */
    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    /**
     * Obtém a URL completa do arquivo.
     */
    public function getFullUrlAttribute(): string
    {
        return asset('storage/'.$this->file_path);
    }

    /**
     * Obtém o caminho completo do arquivo no sistema.
     */
    public function getFullPathAttribute(): string
    {
        return storage_path('app/public/'.$this->file_path);
    }
}
