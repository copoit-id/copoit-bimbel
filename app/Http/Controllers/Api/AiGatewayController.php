<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiGatewayClient;
use App\Models\AiGatewaySubscription;
use App\Models\AiGatewayUsageLog;
use App\Services\AiDiscussionService;
use Illuminate\Http\Request;

class AiGatewayController extends Controller
{
    public function discussion(Request $request, AiDiscussionService $ai)
    {
        $key = (string) $request->header('X-AI-Gateway-Key');
        $client = AiGatewayClient::where('api_key_hash', hash('sha256', $key))->where('is_active', true)->first();
        abort_unless($client, 401, 'Gateway key tidak valid.');
        $subscription = AiGatewaySubscription::with('plan')->where('ai_gateway_client_id', $client->id)->where('status', 'active')->where('ends_at', '>', now())->latest()->first();
        if ($subscription && $subscription->plan->token_limit > 0 && $subscription->tokens_used >= $subscription->plan->token_limit) {
            return response()->json(['message' => 'Kuota paket AI habis.'], 429);
        }
        $data = $request->validate(['message' => 'required|string|max:1200', 'external_user_id' => 'nullable|string|max:120', 'external_user_name' => 'nullable|string|max:255', 'external_user_email' => 'nullable|email|max:255', 'project_base_url' => 'nullable|url|max:2048', 'question_reference' => 'nullable|string|max:120', 'context' => 'required|array', 'context.tryout_name' => 'nullable|string|max:255', 'context.subtest_name' => 'nullable|string|max:255', 'context.question_text' => 'required|string|max:20000', 'context.question_type' => 'nullable|string|max:80', 'context.options' => 'nullable|array', 'context.selected_answer' => 'nullable|string|max:5000', 'context.explanation' => 'nullable|string|max:20000']);
        $used = (int) AiGatewayUsageLog::where('ai_gateway_client_id', $client->id)->where('created_at', '>=', now()->startOfMonth())->sum('total_tokens');
        if ($client->monthly_token_limit > 0 && $used >= $client->monthly_token_limit) {
            return response()->json(['message' => 'Kuota token project ini habis.'], 429);
        }
        try {
            $result = $ai->chat($data['message'], $data['context'], true);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
        AiGatewayUsageLog::create(['ai_gateway_client_id' => $client->id, 'external_user_id' => $data['external_user_id'] ?? null, 'external_user_name' => $data['external_user_name'] ?? null, 'external_user_email' => $data['external_user_email'] ?? null, 'origin_base_url' => $data['project_base_url'] ?? null, 'question_reference' => $data['question_reference'] ?? null, 'provider' => $result['provider'], 'model' => $result['model'], 'input_tokens' => $result['usage']['input'], 'output_tokens' => $result['usage']['output'], 'total_tokens' => $result['usage']['total'], 'response_time_ms' => $result['response_time_ms']]);
        if ($subscription) {
            $subscription->increment('tokens_used', (int) $result['usage']['total']);
        }
        $client->update(['last_used_at' => now()]);

        return response()->json($result);
    }
}
