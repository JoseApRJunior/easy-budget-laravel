<?php
declare(strict_types=1);

namespace App\Models\Traits;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function (Model $model) {
            self::logAction('created', $model);
        });

        static::updated(function (Model $model) {
            self::logAction('updated', $model);
        });

        static::deleted(function (Model $model) {
            self::logAction('deleted', $model);
        });
    }

    protected static function logAction(string $action, Model $model): void
    {
        $user = Auth::user();
        $tenantId = $user?->tenant_id ?? ($model->getAttribute('tenant_id') ?? null);
        if ($tenantId === null) {
            return;
        }
        $userId = $user?->id ?? User::where('tenant_id', $tenantId)->value('id');
        if ($userId === null) {
            return;
        }
        $isSystem = $user === null;
        AuditLog::create([
            'tenant_id'        => $tenantId,
            'user_id'          => $userId,
            'is_system_action' => $isSystem,
            'action'           => $action,
            'model_type'       => get_class($model),
            'model_id'         => $model->getKey(),
            'metadata'         => [],
            'description'      => $action.' '.$model->getTable(),
            'severity'         => 'info',
            'category'         => 'data_modification',
            'ip_address'       => Request::ip(),
            'user_agent'       => Request::userAgent(),
        ]);
    }
}
