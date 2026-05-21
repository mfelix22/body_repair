<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

trait Auditable
{
    /**
     * Boot the trait
     */
    public static function bootAuditable()
    {
        // Log when model is created
        static::created(function ($model) {
            $model->auditLog('created', null, $model->getAuditableAttributes());
        });

        // Log when model is updated
        static::updated(function ($model) {
            $changes = $model->getChanges();
            $original = $model->getOriginal();

            // Only log if there are actual changes (excluding timestamps)
            $relevantChanges = array_diff_key($changes, array_flip(['updated_at']));
            if (!empty($relevantChanges)) {
                $oldValues = array_intersect_key($original, $relevantChanges);
                $model->auditLog('updated', $oldValues, $relevantChanges);
            }
        });

        // Log when model is deleted
        static::deleted(function ($model) {
            $model->auditLog('deleted', $model->getAuditableAttributes(), null);
        });
    }

    /**
     * Create an audit log entry
     */
    protected function auditLog(string $action, ?array $oldValues, ?array $newValues): void
    {
        AuditLog::create([
            'model_type'  => get_class($this),
            'model_id'    => $this->id,
            'action'      => $action,
            'user_id'     => Auth::id(),
            'old_values'  => $oldValues,
            'new_values'  => $newValues,
            'ip_address'  => request()->ip(),
            'user_agent'  => request()->userAgent(),
        ]);
    }

    /**
     * Get auditable attributes (override in model if needed)
     */
    protected function getAuditableAttributes(): array
    {
        // By default, return all attributes except timestamps and internal fields
        $exclude = ['created_at', 'updated_at', 'deleted_at', 'remember_token'];
        return array_diff_key($this->getAttributes(), array_flip($exclude));
    }

    /**
     * Get audit logs for this model
     */
    public function auditLogs()
    {
        return AuditLog::where('model_type', get_class($this))
            ->where('model_id', $this->id)
            ->orderBy('created_at', 'desc')
            ->with('user')
            ->get();
    }
}
