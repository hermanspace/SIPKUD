<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait HasAuditLog
{
    /**
     * Log audit untuk action tertentu
     *
     * @param  string  $action  created, updated, deleted, restored, voided, posted
     * @param  array|null  $oldValues  Data sebelum perubahan
     * @param  array|null  $newValues  Data setelah perubahan
     * @param  string|null  $description  Keterangan tambahan
     */
    protected function logAudit(
        string $action,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $description = null
    ): AuditLog {
        return AuditLog::create([
            'model_type' => static::class,
            'model_id' => $this->id,
            'action' => $action,
            'user_id' => Auth::id(),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'description' => $description,
        ]);
    }

    /**
     * Boot method untuk auto-log pada model events
     */
    protected static function bootHasAuditLog(): void
    {
        // Log saat create
        static::created(function ($model) {
            $model->logAudit('created', null, $model->getAttributes());
        });

        // Log saat update
        static::updated(function ($model) {
            $model->logAudit('updated', $model->getOriginal(), $model->getChanges());
        });

        // Log saat delete (soft delete)
        static::deleted(function ($model) {
            if (method_exists($model, 'trashed') && $model->trashed()) {
                $model->logAudit('deleted', $model->getOriginal(), null, 'Soft deleted');
            }
        });

        // Log saat restore
        static::restored(function ($model) {
            $model->logAudit('restored', null, $model->getAttributes(), 'Restored from soft delete');
        });
    }

    /**
     * Get all audit logs for this model
     */
    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'model');
    }
}
