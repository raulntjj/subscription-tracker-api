<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Shared\Infrastructure\Persistence\Concerns\HasUserActionColumns;

return new class extends Migration
{
    use HasUserActionColumns;
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('webhook_configs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->string('url', 500);
            $table->string('secret', 255)->nullable();
            $table->boolean('is_active')->default(true);
            
            $this->addTimestampsWithUserActions($table);
            
            // Foreign keys
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
                
            // Indexes
            $table->index('user_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_configs');
    }
};
