<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AiExecutionController;
use App\Http\Controllers\Admin\PlaygroundController;
use App\Http\Controllers\Admin\RetryExecutionController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\ChatworkWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhook/chatwork', ChatworkWebhookController::class)
    ->middleware('chatwork.signature');

Route::middleware(['auth.basic', 'admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/', AdminDashboardController::class)->name('dashboard');

    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
    Route::post('/settings/revisions/{revision}/rollback', [SettingController::class, 'rollback'])->name('settings.rollback');

    Route::get('/executions', [AiExecutionController::class, 'index'])->name('executions.index');
    Route::get('/executions/{execution}', [AiExecutionController::class, 'show'])->name('executions.show');
    Route::post('/executions/{execution}/retry', RetryExecutionController::class)->name('executions.retry');

    Route::get('/playground', [PlaygroundController::class, 'index'])->name('playground.index');
    Route::post('/playground', [PlaygroundController::class, 'run'])->name('playground.run');
});
