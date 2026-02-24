<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bot_settings', function (Blueprint $table) {
            $table->unsignedInteger('alert_window_minutes')->default(15)->after('chatwork_bot_account_id');
            $table->unsignedInteger('alert_failure_threshold')->default(5)->after('alert_window_minutes');
            $table->unsignedBigInteger('alert_room_id')->nullable()->after('alert_failure_threshold');
        });
    }

    public function down(): void
    {
        Schema::table('bot_settings', function (Blueprint $table) {
            $table->dropColumn(['alert_window_minutes', 'alert_failure_threshold', 'alert_room_id']);
        });
    }
};
