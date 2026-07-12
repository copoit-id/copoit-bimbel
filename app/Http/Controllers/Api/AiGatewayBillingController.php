<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiGatewayClient;
use App\Models\AiGatewayPlan;
use App\Models\AiGatewaySubscription;
use App\Models\AiGatewayTransaction;
use App\Models\AiGatewayUserTrial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AiGatewayBillingController extends Controller
{
    private function client(Request $r): AiGatewayClient
    {
        $c = AiGatewayClient::where('api_key_hash', hash('sha256', (string) $r->header('X-AI-Gateway-Key')))->where('is_active', true)->first();
        abort_unless($c, 401, 'Gateway key tidak valid.');

        return $c;
    }

    public function status(Request $r)
    {
        $c = $this->client($r);
        $userId = (string) $r->validate(['external_user_id' => 'required|string|max:120'])['external_user_id'];
        $this->syncLatestPendingPayment($c, $userId);
        $s = AiGatewaySubscription::with('plan')->where('ai_gateway_client_id', $c->id)->where('external_user_id', $userId)->where('status', 'active')->where('ends_at', '>', now())->latest()->first();
        $trial = AiGatewayUserTrial::where('ai_gateway_client_id', $c->id)->where('external_user_id', $userId)->first();

        return ['project' => $c->name, 'subscription' => $s, 'trial' => ['available' => $c->free_token_limit > 0 || $c->free_chat_limit > 0, 'token_limit' => $c->free_token_limit, 'chat_limit' => $c->free_chat_limit, 'tokens_used' => $trial?->tokens_used ?? 0, 'chats_used' => $trial?->chats_used ?? 0]];
    }

    public function checkout(Request $r)
    {
        $c = $this->client($r);
        $d = $r->validate(['plan_id' => 'required|integer|exists:ai_gateway_plans,id', 'external_user_id' => 'required|string|max:120', 'customer_name' => 'required|string|max:100', 'customer_email' => 'required|email', 'success_redirect_url' => 'nullable|url|max:2048', 'failure_redirect_url' => 'nullable|url|max:2048']);
        $p = AiGatewayPlan::where('is_active', true)->where('token_limit', '>', 0)->findOrFail($d['plan_id']);
        $s = AiGatewaySubscription::create(['ai_gateway_client_id' => $c->id, 'ai_gateway_plan_id' => $p->id, 'external_user_id' => $d['external_user_id'], 'external_user_name' => $d['customer_name'], 'external_user_email' => $d['customer_email'], 'status' => 'pending']);
        $id = 'AIGW-'.$c->id.'-'.$s->id.'-'.Str::upper(Str::random(8));
        $res = Http::withBasicAuth((string) config('services.xendit.secret_key'), '')->post(rtrim((string) config('services.xendit.base_url'), '/').'/v2/invoices', ['external_id' => $id, 'amount' => $p->price, 'description' => 'Paket AI Gateway: '.$p->name, 'invoice_duration' => 86400, 'success_redirect_url' => $d['success_redirect_url'] ?? null, 'failure_redirect_url' => $d['failure_redirect_url'] ?? null, 'customer' => ['given_names' => $d['customer_name'], 'email' => $d['customer_email']]]);
        if (! $res->successful()) {
            $s->delete();

            return response()->json(['message' => 'Gagal membuat invoice pusat.'], 422);
        }$i = $res->json();
        AiGatewayTransaction::create(['ai_gateway_client_id' => $c->id, 'ai_gateway_plan_id' => $p->id, 'ai_gateway_subscription_id' => $s->id, 'external_id' => $id, 'provider' => 'xendit', 'provider_invoice_id' => $i['id'] ?? null, 'amount' => $p->price, 'status' => 'pending', 'details' => $i]);

        return ['invoice_url' => $i['invoice_url'] ?? null, 'external_id' => $id];
    }

    public function webhook(Request $r)
    {
        if (! hash_equals((string) config('services.xendit.webhook_token'), (string) $r->header('X-CALLBACK-TOKEN'))) {
            return response()->json(['message' => 'Invalid callback token'], 401);
        }$t = AiGatewayTransaction::where('external_id', $r->input('external_id'))->firstOrFail();
        if ($r->input('status') === 'PAID') {
            $t->update(['status' => 'paid', 'paid_at' => now()]);
            $s = AiGatewaySubscription::find($t->ai_gateway_subscription_id);
            $s->update(['status' => 'active', 'starts_at' => now(), 'ends_at' => now()->addDays($t->plan?->duration_days ?? 30)]);
        } elseif (in_array($r->input('status'), ['EXPIRED', 'FAILED'])) {
            $t->update(['status' => strtolower($r->input('status'))]);
        }

        return ['message' => 'OK'];
    }

    private function syncLatestPendingPayment(AiGatewayClient $client, string $externalUserId): void
    {
        $transaction = AiGatewayTransaction::query()
            ->where('ai_gateway_client_id', $client->id)
            ->where('status', 'pending')
            ->whereHas('subscription', fn ($query) => $query->where('external_user_id', $externalUserId))
            ->latest()
            ->first();

        if (!$transaction || blank($transaction->provider_invoice_id)) {
            return;
        }

        try {
            $response = Http::withBasicAuth((string) config('services.xendit.secret_key'), '')
                ->timeout(10)
                ->get(rtrim((string) config('services.xendit.base_url'), '/') . '/v2/invoices/' . $transaction->provider_invoice_id);
            if (strtoupper((string) $response->json('status')) === 'PAID') {
                $transaction->update(['status' => 'paid', 'paid_at' => now()]);
                $subscription = AiGatewaySubscription::find($transaction->ai_gateway_subscription_id);
                $subscription?->update(['status' => 'active', 'starts_at' => now(), 'ends_at' => now()->addDays($transaction->plan?->duration_days ?? 30)]);
            }
        } catch (\Throwable) {
            // Webhook tetap menjadi jalur utama; sinkronisasi ini hanya fallback saat status dibuka user.
        }
    }
}
