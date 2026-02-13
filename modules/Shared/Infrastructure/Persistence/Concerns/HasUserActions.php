<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Persistence\Concerns;

use Illuminate\Database\Eloquent\SoftDeletes;

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
        // Ao criar, define o created_by
        static::creating(function ($model) {
            if (auth()->check()) {
                $model->created_by = auth()->id();
            }
        });

        // Ao atualizar, define o updated_by
        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });

        // Ao deletar (soft delete), define o deleted_by
        static::deleting(function ($model) {
            if (auth()->check() && method_exists($model, 'isForceDeleting') && !$model->isForceDeleting()) {
                $model->deleted_by = auth()->id();
                $model->save();
            }
        });
    }

    /**
     * Get the user who created this record
     */
    public function creator()
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'created_by');
    }

    /**
     * Get the user who last updated this record
     */
    public function updater()
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'updated_by');
    }

    /**
     * Get the user who deleted this record
     */
    public function deleter()
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'deleted_by');
    }
}
