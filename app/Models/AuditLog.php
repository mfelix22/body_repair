<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLog extends Model
{
    protected $fillable = [
        'model_type',
        'model_id',
        'action',
        'user_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    /**
     * Get the user who performed the action
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the model that this audit log belongs to
     */
    public function model(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'model_type', 'model_id');
    }

    /**
     * Get a human-readable description of the action
     */
    public function getDescriptionAttribute(): string
    {
        $action = match ($this->action) {
            'created'  => 'created',
            'updated'  => 'updated',
            'deleted'  => 'deleted',
            'printed'  => 'printed',
            default    => $this->action,
        };

        $modelName = class_basename($this->model_type);
        return "{$action} {$modelName} #{$this->model_id}";
    }

    /**
     * Log a document print event.
     */
    public static function logPrint(Model $model, string $documentNumber): void
    {
        static::create([
            'model_type' => get_class($model),
            'model_id'   => $model->id,
            'action'     => 'printed',
            'user_id'    => Auth::id(),
            'old_values' => null,
            'new_values' => [
                'document_number' => $documentNumber,
                'printed_at'      => now()->toDateTimeString(),
            ],
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}
