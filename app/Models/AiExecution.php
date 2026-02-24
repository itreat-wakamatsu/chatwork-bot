<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiExecution extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'trigger_message_id',
        'sender_account_id',
        'status',
        'step_count',
        'reply_body',
        'last_error',
        'error_type',
        'retry_count',
    ];

    public function turns(): HasMany
    {
        return $this->hasMany(AiExecutionTurn::class)->orderBy('step_index');
    }
}
