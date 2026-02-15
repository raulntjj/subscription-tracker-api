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
        Schema::create('billing_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('subscription_id');
            $table->bigInteger('amount_paid')->comment('Amount paid in cents');
            $table->timestamp('paid_at')->comment('When the payment was processed');
            
            $this->addTimestampsWithUserActions($table);

            // Índices para otimização de queries
            $table->index('subscription_id');
            $table->index('paid_at');
            $table->index(['subscription_id', 'paid_at']);
            
            // Foreign key
            $table->foreign('subscription_id')
                ->references('id')
                ->on('subscriptions')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_histories');
    }
};
