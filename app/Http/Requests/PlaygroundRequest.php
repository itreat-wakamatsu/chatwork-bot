<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PlaygroundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'system_prompt' => ['required', 'string'],
            'user_prompt' => ['required', 'string'],
        ];
    }
}
