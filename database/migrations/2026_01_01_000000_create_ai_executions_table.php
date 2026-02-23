<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_executions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('room_id');
            $table->string('trigger_message_id', 64);
            $table->unsignedBigInteger('sender_account_id');
            $table->enum('status', ['processing', 'completed', 'failed']);
            $table->unsignedInteger('step_count')->default(0);
            $table->text('reply_body')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->unique(['room_id', 'trigger_message_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_executions');
    }
};
