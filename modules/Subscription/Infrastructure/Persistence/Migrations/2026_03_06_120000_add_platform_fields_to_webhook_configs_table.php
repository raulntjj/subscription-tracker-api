<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('webhook_configs', function (Blueprint $table) {
            $table->string('platform', 20)->default('other')->after('is_active');
            $table->string('bot_name', 255)->nullable()->after('platform');
            $table->string('server_name', 255)->nullable()->after('bot_name');

            $table->index('platform');
        });
    }

    public function down(): void
    {
        Schema::table('webhook_configs', function (Blueprint $table) {
            $table->dropIndex(['platform']);
            $table->dropColumn(['platform', 'bot_name', 'server_name']);
        });
    }
};
