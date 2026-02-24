<?php

use App\Http\Controllers\ChatworkWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhook/chatwork', ChatworkWebhookController::class)
    ->middleware('chatwork.signature');
