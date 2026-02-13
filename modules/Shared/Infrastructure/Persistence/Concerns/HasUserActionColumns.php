<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Persistence\Concerns;

use Illuminate\Database\Schema\Blueprint;

/**
 * Trait para adicionar colunas de auditoria de usuário nas migrations
 *
 * Adiciona:
 * - created_by (UUID nullable)
 * - updated_by (UUID nullable)
 * - deleted_by (UUID nullable)
 * - deleted_at (timestamp nullable - soft delete)
 */
trait HasUserActionColumns
{
    /**
     * Adiciona colunas de auditoria organizadas (para CREATE TABLE)
     * Adiciona timestamps + created_by, updated_by e softDeletes + deleted_by de forma organizada
     */
    protected function addTimestampsWithUserActions(Blueprint $table): void
    {
        $table->timestamp('created_at')->nullable();
        $table->uuid('created_by')->nullable();
        $table->timestamp('updated_at')->nullable();
        $table->uuid('updated_by')->nullable();
        $table->softDeletes();
        $table->uuid('deleted_by')->nullable();

        // Foreign keys (se a tabela users existir)
        if (config('database.add_user_action_foreign_keys', false)) {
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('deleted_by')->references('id')->on('users')->nullOnDelete();
        }

        // Índices para performance
        $table->index('created_by');
        $table->index('updated_by');
        $table->index('deleted_by');
    }

    /**
     * Adiciona colunas de auditoria de usuário à tabela (para ALTER TABLE)
     *
     * @param Blueprint $table
     * @param bool $useAfter Se true, usa after() para posicionar colunas
     */
    protected function addUserActionColumns(Blueprint $table, bool $useAfter = true): void
    {
        if ($useAfter) {
            $table->uuid('created_by')->nullable()->after('created_at');
            $table->uuid('updated_by')->nullable()->after('updated_at');
            $table->softDeletes();
            $table->uuid('deleted_by')->nullable()->after('deleted_at');
        } else {
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->softDeletes();
            $table->uuid('deleted_by')->nullable();
        }

        // Foreign keys (se a tabela users existir)
        if (config('database.add_user_action_foreign_keys', false)) {
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('deleted_by')->references('id')->on('users')->nullOnDelete();
        }

        // Índices para performance
        $table->index('created_by');
        $table->index('updated_by');
        $table->index('deleted_by');
    }

    /**
     * Remove colunas de auditoria de usuário da tabela
     */
    protected function dropUserActionColumns(Blueprint $table): void
    {
        if (config('database.add_user_action_foreign_keys', false)) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            $table->dropForeign(['deleted_by']);
        }

        $table->dropColumn(['created_by', 'updated_by', 'deleted_by']);
        $table->dropSoftDeletes();
    }
}
