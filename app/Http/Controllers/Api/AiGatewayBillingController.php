<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiGatewayClient;
use App\Models\AiGatewayPlan;
use App\Models\AiGatewaySubscription;
use App\Models\AiGatewayTransaction;
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
        $s = AiGatewaySubscription::where('ai_gateway_client_id', $c->id)->where('status', 'active')->where('ends_at', '>', now())->latest()->first();

        return ['project' => $c->name, 'subscription' => $s];
    }

    public function checkout(Request $r)
    {
        $c = $this->client($r);
        $d = $r->validate(['plan_id' => 'required|integer|exists:ai_gateway_plans,id', 'customer_name' => 'nullable|string|max:100', 'customer_email' => 'nullable|email']);
        $p = AiGatewayPlan::where('is_active', true)->findOrFail($d['plan_id']);
        $s = AiGatewaySubscription::create(['ai_gateway_client_id' => $c->id, 'ai_gateway_plan_id' => $p->id, 'status' => 'pending']);
        $id = 'AIGW-'.$c->id.'-'.$s->id.'-'.Str::upper(Str::random(8));
        $res = Http::withBasicAuth((string) config('services.xendit.secret_key'), '')->post(rtrim((string) config('services.xendit.base_url'), '/').'/v2/invoices', ['external_id' => $id, 'amount' => $p->price, 'description' => 'Paket AI Gateway: '.$p->name, 'invoice_duration' => 86400, 'customer' => ['given_names' => $d['customer_name'] ?? $c->name, 'email' => $d['customer_email'] ?? null]]);
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
}
