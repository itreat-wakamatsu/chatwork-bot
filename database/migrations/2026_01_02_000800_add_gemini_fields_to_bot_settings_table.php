<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bot_settings', function (Blueprint $table) {
            $table->string('gemini_api_key')->nullable()->after('chatwork_bot_account_id');
            $table->string('gemini_model')->nullable()->after('gemini_api_key');
        });
    }

    public function down(): void
    {
        Schema::table('bot_settings', function (Blueprint $table) {
            $table->dropColumn(['gemini_api_key', 'gemini_model']);
        });
    }
};
