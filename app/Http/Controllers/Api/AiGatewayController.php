<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiGatewayClient;
use App\Models\AiGatewaySubscription;
use App\Models\AiGatewayUsageLog;
use App\Models\AiGatewayUserTrial;
use App\Services\AiDiscussionService;
use Illuminate\Http\Request;

class AiGatewayController extends Controller
{
    public function discussion(Request $request, AiDiscussionService $ai)
    {
        $key = (string) $request->header('X-AI-Gateway-Key');
        $client = AiGatewayClient::where('api_key_hash', hash('sha256', $key))->where('is_active', true)->first();
        abort_unless($client, 401, 'Gateway key tidak valid.');
        $data = $request->validate(['message' => 'required|string|max:1200', 'external_user_id' => 'required|string|max:120', 'external_user_name' => 'nullable|string|max:255', 'external_user_email' => 'nullable|email|max:255', 'project_base_url' => 'nullable|url|max:2048', 'question_reference' => 'nullable|string|max:120', 'context' => 'required|array', 'context.tryout_name' => 'nullable|string|max:255', 'context.subtest_name' => 'nullable|string|max:255', 'context.question_text' => 'required|string|max:20000', 'context.question_type' => 'nullable|string|max:80', 'context.options' => 'nullable|array', 'context.selected_answer' => 'nullable|string|max:5000', 'context.explanation' => 'nullable|string|max:20000']);
        $subscription = AiGatewaySubscription::with('plan')
            ->where('ai_gateway_client_id', $client->id)
            ->where('external_user_id', $data['external_user_id'])
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->latest()
            ->first();
        $trial = null;
        if ($subscription) {
            $subscriptionTokenLimit = (int) ($subscription->token_limit ?: $subscription->plan->token_limit);
            if ($subscriptionTokenLimit <= 0) {
                return response()->json(['message' => 'Paket AI ini belum memiliki batas token yang valid. Hubungi pengelola gateway.'], 422);
            }
            if ($subscription->tokens_used >= $subscriptionTokenLimit) {
                return response()->json(['message' => 'Kuota paket Diskusi AI Anda habis.'], 429);
            }
        } else {
            if ($client->free_token_limit <= 0 && $client->free_chat_limit <= 0) {
                return response()->json(['message' => 'Paket Diskusi AI belum aktif. Silakan beli paket AI terlebih dahulu.'], 403);
            }
            $trial = AiGatewayUserTrial::firstOrCreate([
                'ai_gateway_client_id' => $client->id,
                'external_user_id' => $data['external_user_id'],
            ], [
                'external_user_name' => $data['external_user_name'] ?? null,
                'external_user_email' => $data['external_user_email'] ?? null,
            ]);
            if (($client->free_token_limit > 0 && $trial->tokens_used >= $client->free_token_limit) || ($client->free_chat_limit > 0 && $trial->chats_used >= $client->free_chat_limit)) {
                return response()->json(['message' => 'Kuota coba gratis Diskusi AI Anda sudah habis. Silakan beli paket AI untuk melanjutkan.'], 429);
            }
        }
        $remainingTokenQuota = $subscription
            ? max(1, $subscriptionTokenLimit - (int) $subscription->tokens_used)
            : ($trial && $client->free_token_limit > 0
                ? max(1, (int) $client->free_token_limit - (int) $trial->tokens_used)
                : null);
        try {
            $result = $ai->chat($data['message'], $data['context'], true, $remainingTokenQuota);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
        if ($remainingTokenQuota !== null && (int) ($result['usage']['total'] ?? 0) > $remainingTokenQuota) {
            $billableInput = min((int) ($result['usage']['input'] ?? 0), $remainingTokenQuota);
            $result['usage'] = [
                'input' => $billableInput,
                'output' => max(0, $remainingTokenQuota - $billableInput),
                'total' => $remainingTokenQuota,
            ];
        }
        AiGatewayUsageLog::create(['ai_gateway_client_id' => $client->id, 'external_user_id' => $data['external_user_id'] ?? null, 'external_user_name' => $data['external_user_name'] ?? null, 'external_user_email' => $data['external_user_email'] ?? null, 'origin_base_url' => $data['project_base_url'] ?? null, 'question_reference' => $data['question_reference'] ?? null, 'provider' => $result['provider'], 'model' => $result['model'], 'input_tokens' => $result['usage']['input'], 'output_tokens' => $result['usage']['output'], 'total_tokens' => $result['usage']['total'], 'response_time_ms' => $result['response_time_ms']]);
        if ($subscription) {
            $subscription->increment('tokens_used', (int) $result['usage']['total']);
            $subscription->increment('chats_used');
            $subscription->refresh();
        }
        if ($trial) {
            $trial->increment('tokens_used', (int) $result['usage']['total']);
            $trial->increment('chats_used');
            $trial->refresh();
        }
        $client->update(['last_used_at' => now()]);

        $quota = $subscription
            ? ['type' => 'package', 'token_limit' => (int) ($subscription->token_limit ?: $subscription->plan->token_limit), 'tokens_used' => (int) $subscription->tokens_used, 'chats_used' => (int) $subscription->chats_used]
            : ['type' => 'trial', 'token_limit' => (int) $client->free_token_limit, 'tokens_used' => (int) $trial->tokens_used, 'chat_limit' => (int) $client->free_chat_limit, 'chats_used' => (int) $trial->chats_used];

        return response()->json([...$result, 'quota' => $quota]);
    }
}
