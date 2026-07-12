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
        $subscriptions = AiGatewaySubscription::with('plan')->where('ai_gateway_client_id', $c->id)->where('external_user_id', $userId)->where('status', 'active')->where('ends_at', '>', now())->latest()->get();
        $s = $subscriptions->first();
        $trial = AiGatewayUserTrial::where('ai_gateway_client_id', $c->id)->where('external_user_id', $userId)->first();
        $pendingPayment = AiGatewayTransaction::query()
            ->with(['plan:id,name', 'subscription:id,external_user_id'])
            ->where('ai_gateway_client_id', $c->id)
            ->where('status', 'pending')
            ->where('created_at', '>', now()->subDay())
            ->whereHas('subscription', fn ($query) => $query->where('external_user_id', $userId))
            ->latest()
            ->first();

        return ['project' => $c->name, 'subscription' => $s, 'subscriptions' => $subscriptions, 'pending_payment' => $pendingPayment ? ['plan_name' => $pendingPayment->plan?->name, 'invoice_url' => data_get($pendingPayment->details, 'invoice_url'), 'expires_at' => $pendingPayment->created_at?->copy()->addDay()->toIso8601String()] : null, 'trial' => ['available' => $c->free_token_limit > 0 || $c->free_chat_limit > 0, 'token_limit' => $c->free_token_limit, 'chat_limit' => $c->free_chat_limit, 'tokens_used' => $trial?->tokens_used ?? 0, 'chats_used' => $trial?->chats_used ?? 0]];
    }

    public function checkout(Request $r)
    {
        $c = $this->client($r);
        $d = $r->validate(['plan_id' => 'required|integer|exists:ai_gateway_plans,id', 'external_user_id' => 'required|string|max:120', 'customer_name' => 'required|string|max:100', 'customer_email' => 'required|email', 'success_redirect_url' => 'nullable|url|max:2048', 'failure_redirect_url' => 'nullable|url|max:2048']);
        $p = AiGatewayPlan::where('is_active', true)->where('token_limit', '>', 0)->findOrFail($d['plan_id']);
        $this->syncLatestPendingPayment($c, $d['external_user_id']);
        $pendingTransaction = AiGatewayTransaction::query()
            ->with('subscription')
            ->where('ai_gateway_client_id', $c->id)
            ->where('ai_gateway_plan_id', $p->id)
            ->where('status', 'pending')
            ->where('created_at', '>', now()->subDay())
            ->whereHas('subscription', fn ($query) => $query->where('external_user_id', $d['external_user_id']))
            ->latest()
            ->first();

        if ($pendingTransaction && filled(data_get($pendingTransaction->details, 'invoice_url'))) {
            return [
                'invoice_url' => data_get($pendingTransaction->details, 'invoice_url'),
                'external_id' => $pendingTransaction->external_id,
                'reused_pending_invoice' => true,
            ];
        }

        if ($pendingTransaction) {
            $pendingTransaction->update(['status' => 'expired']);
            $pendingTransaction->subscription?->update(['status' => 'expired']);
        }
        $s = AiGatewaySubscription::create(['ai_gateway_client_id' => $c->id, 'ai_gateway_plan_id' => $p->id, 'token_limit' => $p->token_limit, 'external_user_id' => $d['external_user_id'], 'external_user_name' => $d['customer_name'], 'external_user_email' => $d['customer_email'], 'status' => 'pending']);
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
            $this->activateTransaction($t);
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
            $http = Http::withBasicAuth((string) config('services.xendit.secret_key'), '')
                ->timeout(10)
                ->acceptJson();
            $baseUrl = rtrim((string) config('services.xendit.base_url'), '/');
            $response = $http->get($baseUrl . '/v2/invoices/' . $transaction->provider_invoice_id);
            $invoice = $response->successful() ? $response->json() : null;

            if (!is_array($invoice) || blank($invoice['status'] ?? null)) {
                $lookup = $http->get($baseUrl . '/v2/invoices', ['external_id' => $transaction->external_id]);
                $invoice = data_get($lookup->json(), 'data.0', $lookup->json());
            }

            if (strtoupper((string) data_get($invoice, 'status')) === 'PAID') {
                $this->activateTransaction($transaction);
            }
        } catch (\Throwable $exception) {
            report($exception);
            // Webhook tetap menjadi jalur utama; sinkronisasi ini hanya fallback saat status dibuka user.
        }
    }

    private function activateTransaction(AiGatewayTransaction $transaction): void
    {
        if ($transaction->status === 'paid') {
            return;
        }

        $pendingSubscription = AiGatewaySubscription::with('plan')->find($transaction->ai_gateway_subscription_id);
        if (!$pendingSubscription) {
            return;
        }

        $tokenCredit = max(1, (int) ($pendingSubscription->token_limit ?: $transaction->plan?->token_limit ?: 0));
        $durationDays = max(1, (int) ($transaction->plan?->duration_days ?? 30));
        $activeSubscription = AiGatewaySubscription::query()
            ->where('ai_gateway_client_id', $pendingSubscription->ai_gateway_client_id)
            ->where('external_user_id', $pendingSubscription->external_user_id)
            ->where('ai_gateway_plan_id', $pendingSubscription->ai_gateway_plan_id)
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->latest()
            ->first();

        if ($activeSubscription) {
            $currentLimit = (int) ($activeSubscription->token_limit ?: $activeSubscription->plan?->token_limit ?: 0);
            $activeSubscription->update([
                'token_limit' => $currentLimit + $tokenCredit,
                'ends_at' => $activeSubscription->ends_at->copy()->addDays($durationDays),
            ]);
            $pendingSubscription->update(['status' => 'merged']);
        } else {
            $pendingSubscription->update([
                'status' => 'active',
                'token_limit' => $tokenCredit,
                'starts_at' => now(),
                'ends_at' => now()->addDays($durationDays),
            ]);
        }

        $transaction->update(['status' => 'paid', 'paid_at' => now()]);
    }
}
