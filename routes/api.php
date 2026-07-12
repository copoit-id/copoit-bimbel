<?php

use App\Http\Controllers\WebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Webhook untuk AI Similarity Service (tanpa auth)
Route::post('/webhook/ai-callback', [WebhookController::class, 'aiCallback'])->name('webhook.ai-callback');
Route::get('/webhook/health', [WebhookController::class, 'healthCheck'])->name('webhook.health');
Route::post('/ai-gateway/discussion', [\App\Http\Controllers\Api\AiGatewayController::class, 'discussion'])->middleware('throttle:60,1');
Route::get('/ai-gateway/plans', fn () => \App\Models\AiGatewayPlan::where('is_active', true)->where('token_limit', '>', 0)->orderBy('price')->get(['id', 'name', 'slug', 'price', 'token_limit', 'duration_days']));
Route::post('/ai-gateway/checkout', [\App\Http\Controllers\Api\AiGatewayBillingController::class, 'checkout']);
Route::get('/ai-gateway/subscription', [\App\Http\Controllers\Api\AiGatewayBillingController::class, 'status']);
Route::post('/webhook/ai-gateway/xendit', [\App\Http\Controllers\Api\AiGatewayBillingController::class, 'webhook']);
