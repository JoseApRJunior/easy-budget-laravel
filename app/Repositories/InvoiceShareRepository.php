<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\InvoiceShare;
use App\Repositories\Abstracts\AbstractTenantRepository;
use Illuminate\Database\Eloquent\Model;

class InvoiceShareRepository extends AbstractTenantRepository
{
    /**
     * Define o Model a ser utilizado pelo Repositório.
     */
    protected function makeModel(): Model
    {
        return new InvoiceShare();
    }

    /**
     * Encontra um compartilhamento pelo token.
     */
    public function findByToken(string $token): ?InvoiceShare
    {
        return $this->model->withoutGlobalScopes()
            ->where('share_token', $token)
            ->first();
    }

    /**
     * Encontra um compartilhamento ativo pelo token.
     */
    public function findActiveByToken(string $token): ?InvoiceShare
    {
        return $this->model->withoutGlobalScopes()
            ->where('share_token', $token)
            ->where('is_active', true)
            ->where('status', \App\Enums\InvoiceShareStatus::ACTIVE)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->first();
    }
}
