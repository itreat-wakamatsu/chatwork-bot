<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_settings', function (Blueprint $table) {
            $table->id();
            $table->text('system_prompt');
            $table->string('chatwork_api_token')->nullable();
            $table->string('chatwork_webhook_token')->nullable();
            $table->string('chatwork_bot_account_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_settings');
    }
};
