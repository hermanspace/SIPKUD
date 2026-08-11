<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'model_type',
        'model_id',
        'action',
        'user_id',
        'ip_address',
        'user_agent',
        'old_values',
        'new_values',
        'description',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    /**
     * Get the parent model (polymorphic)
     */
    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who performed the action
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope untuk filter berdasarkan model
     */
    public function scopeForModel($query, string $modelType, ?int $modelId = null)
    {
        $query->where('model_type', $modelType);

        if ($modelId !== null) {
            $query->where('model_id', $modelId);
        }

        return $query;
    }

    /**
     * Scope untuk filter berdasarkan action
     */
    public function scopeForAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope untuk filter berdasarkan user
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
