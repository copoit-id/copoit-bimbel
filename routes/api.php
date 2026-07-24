<?php

use App\Http\Controllers\Api\AiGatewayBillingController;
use App\Http\Controllers\Api\AiGatewayController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\WebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('chat')->middleware('auth:sanctum')->group(function () {
    Route::get('conversations', [ChatController::class, 'apiIndex']);
    Route::post('conversations', [ChatController::class, 'apiOpen'])->middleware('throttle:30,1');
    Route::get('conversations/{conversation}/messages', [ChatController::class, 'messages'])->middleware('throttle:120,1');
    Route::post('conversations/{conversation}/messages', [ChatController::class, 'store'])->middleware('throttle:60,1');
    Route::post('conversations/{conversation}/read', [ChatController::class, 'markRead'])->middleware('throttle:120,1');
});

Route::post('/webhook/ai-callback', [WebhookController::class, 'aiCallback'])
    ->middleware('throttle:120,1')
    ->name('webhook.ai-callback');
Route::get('/webhook/health', [WebhookController::class, 'healthCheck'])
    ->middleware('throttle:30,1')
    ->name('webhook.health');
Route::post('/ai-gateway/discussion', [AiGatewayController::class, 'discussion'])
    ->middleware('throttle:60,1');
Route::get('/ai-gateway/plans', [AiGatewayBillingController::class, 'plans']);
Route::post('/ai-gateway/checkout', [AiGatewayBillingController::class, 'checkout'])
    ->middleware('throttle:20,1');
Route::post('/ai-gateway/cancel-pending', [AiGatewayBillingController::class, 'cancelPending'])
    ->middleware('throttle:10,1');
Route::get('/ai-gateway/subscription', [AiGatewayBillingController::class, 'status'])
    ->middleware('throttle:60,1');
Route::post('/webhook/ai-gateway/xendit', [AiGatewayBillingController::class, 'webhook'])
    ->middleware('throttle:120,1')
    ->name('webhook.ai-gateway.xendit');
Route::post('/webhook/ai-gateway/midtrans', [AiGatewayBillingController::class, 'midtransWebhook'])
    ->middleware('throttle:120,1')
    ->name('webhook.ai-gateway.midtrans');
Route::post('/webhook/ai-gateway/ipaymu', [AiGatewayBillingController::class, 'ipaymuWebhook'])
    ->middleware('throttle:120,1')
    ->name('webhook.ai-gateway.ipaymu');
Route::get('/ai-gateway/payments/{externalId}/qris/status', [AiGatewayBillingController::class, 'qrisStatus'])
    ->middleware('throttle:60,1')
    ->name('ai-gateway-payments.qris.status');
