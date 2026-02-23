<?php

return [
    'chatwork' => [
        'api_token' => env('CHATWORK_API_TOKEN'),
        'webhook_token' => env('CHATWORK_WEBHOOK_TOKEN'),
        'bot_account_id' => env('BOT_ACCOUNT_ID'),
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
    ],
];
