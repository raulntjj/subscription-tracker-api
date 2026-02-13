<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Shared\Infrastructure\Persistence\Concerns\HasUserActionColumns;

return new class extends Migration
{
    use HasUserActionColumns;

    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->bigInteger('price')->comment('Price in cents to avoid precision issues');
            $table->string('currency', 3)->default('BRL')->comment('ISO 4217 currency code');
            $table->enum('billing_cycle', ['monthly', 'yearly'])->default('monthly');
            $table->date('next_billing_date')->comment('Next date when this subscription will be billed');
            $table->string('category')->comment('Category like Streaming, DevTools, Work, etc');
            $table->enum('status', ['active', 'paused', 'cancelled'])->default('active');
            $table->uuid('user_id');
            
            // Adiciona timestamps + colunas de auditoria organizadas
            // created_at + created_by, updated_at + updated_by, deleted_at + deleted_by
            $this->addTimestampsWithUserActions($table);

            // Índices para otimização de queries
            $table->index('user_id');
            $table->index('status');
            $table->index('next_billing_date');
            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'next_billing_date']);
            
            // Foreign key
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
