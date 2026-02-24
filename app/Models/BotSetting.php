<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotSetting extends Model
{
    protected $fillable = [
        'system_prompt',
        'chatwork_api_token',
        'chatwork_webhook_token',
        'chatwork_bot_account_id',
        'gemini_api_key',
        'gemini_model',
        'alert_window_minutes',
        'alert_failure_threshold',
        'alert_room_id',
    ];
}
