<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Persistence\Concerns;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trait para adicionar campos de auditoria de usuário aos models
 *
 * Adiciona automaticamente:
 * - created_by (quem criou)
 * - updated_by (quem atualizou)
 * - deleted_by (quem deletou - soft delete)
 */
trait HasUserActions
{
    use SoftDeletes;

    /**
     * Boot do trait
     */
    protected static function bootHasUserActions(): void
    {
        static::creating(function ($model) {
            if (auth()->check()) {
                $model->created_by = auth()->id();
            }
        });

        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });

        static::deleting(function ($model) {
            if (auth()->check() && method_exists($model, 'isForceDeleting') && !$model->isForceDeleting()) {
                $model->deleted_by = auth()->id();
                $model->save();
            }
        });
    }

    /**
     * Obtém o usuário que criou este registro
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'created_by');
    }

    /**
     * Obtém o usuário que atualizou este registro
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'updated_by');
    }

    /**
     * Obtém o usuário que deletou este registro
     */
    public function deleter(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'deleted_by');
    }
}
