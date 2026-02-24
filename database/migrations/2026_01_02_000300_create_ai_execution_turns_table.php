<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_execution_turns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_execution_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('step_index');
            $table->longText('user_prompt');
            $table->json('model_response');
            $table->json('tool_result')->nullable();
            $table->timestamps();

            $table->unique(['ai_execution_id', 'step_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_execution_turns');
    }
};
