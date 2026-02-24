<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiExecutionTurn extends Model
{
    protected $fillable = [
        'ai_execution_id',
        'step_index',
        'user_prompt',
        'model_response',
        'tool_result',
    ];

    protected function casts(): array
    {
        return [
            'model_response' => 'array',
            'tool_result' => 'array',
        ];
    }

    public function execution(): BelongsTo
    {
        return $this->belongsTo(AiExecution::class, 'ai_execution_id');
    }
}
