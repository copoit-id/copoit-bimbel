<?php

use App\Http\Controllers\WebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/webhook/ai-callback', [WebhookController::class, 'aiCallback'])
    ->middleware('throttle:120,1')
    ->name('webhook.ai-callback');
Route::get('/webhook/health', [WebhookController::class, 'healthCheck'])
    ->middleware('throttle:30,1')
    ->name('webhook.health');
Route::post('/ai-gateway/discussion', [\App\Http\Controllers\Api\AiGatewayController::class, 'discussion'])
    ->middleware('throttle:60,1');
Route::get('/ai-gateway/plans', [\App\Http\Controllers\Api\AiGatewayBillingController::class, 'plans']);
Route::post('/ai-gateway/checkout', [\App\Http\Controllers\Api\AiGatewayBillingController::class, 'checkout'])
    ->middleware('throttle:20,1');
Route::get('/ai-gateway/subscription', [\App\Http\Controllers\Api\AiGatewayBillingController::class, 'status'])
    ->middleware('throttle:60,1');
Route::post('/webhook/ai-gateway/xendit', [\App\Http\Controllers\Api\AiGatewayBillingController::class, 'webhook'])
    ->middleware('throttle:120,1')
    ->name('webhook.ai-gateway.xendit');
Route::post('/webhook/ai-gateway/midtrans', [\App\Http\Controllers\Api\AiGatewayBillingController::class, 'midtransWebhook'])
    ->middleware('throttle:120,1')
    ->name('webhook.ai-gateway.midtrans');
Route::post('/webhook/ai-gateway/ipaymu', [\App\Http\Controllers\Api\AiGatewayBillingController::class, 'ipaymuWebhook'])
    ->middleware('throttle:120,1')
    ->name('webhook.ai-gateway.ipaymu');
Route::get('/ai-gateway/payments/{externalId}/qris/status', [\App\Http\Controllers\Api\AiGatewayBillingController::class, 'qrisStatus'])
    ->middleware('throttle:60,1')
    ->name('ai-gateway-payments.qris.status');
