<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBotSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'system_prompt' => ['required', 'string'],
            'chatwork_api_token' => ['nullable', 'string', 'max:255'],
            'chatwork_webhook_token' => ['nullable', 'string', 'max:255'],
            'chatwork_bot_account_id' => ['nullable', 'string', 'max:255'],
            'gemini_api_key' => ['nullable', 'string', 'max:255'],
            'gemini_model' => ['nullable', 'string', 'max:255'],
            'enabled_tools' => ['array'],
            'enabled_tools.*' => ['string'],
            'alert_window_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'alert_failure_threshold' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'alert_room_id' => ['nullable', 'integer', 'min:1'],
            'change_reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
