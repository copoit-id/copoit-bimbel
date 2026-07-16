<?php

namespace App\Http\Controllers\superadmin;

use App\Http\Controllers\Controller;
use App\Models\AiDiscussionUsageLog;
use App\Models\AiGatewayClient;
use App\Models\AiGatewaySubscription;
use App\Models\AiGatewayTransaction;
use App\Models\AiGatewayUsageLog;
use App\Models\ClientProfile;
use App\Services\AiGatewayCostService;
use App\Services\AiGatewaySubscriptionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AiUsageController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->input('month', now()->format('Y-m'));
        try {
            $periodStart = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        } catch (\Throwable) {
            $periodStart = now()->startOfMonth();
            $month = $periodStart->format('Y-m');
        }
        $periodEnd = $periodStart->copy()->endOfMonth();

        $allUsageQuery = AiDiscussionUsageLog::query();
        $baseQuery = (clone $allUsageQuery)->whereBetween('created_at', [$periodStart, $periodEnd]);

        if ($request->filled('provider')) {
            $baseQuery->where('provider', $request->string('provider')->toString());
            $allUsageQuery->where('provider', $request->string('provider')->toString());
        }
        if ($request->filled('model')) {
            $baseQuery->where('model', $request->string('model')->toString());
            $allUsageQuery->where('model', $request->string('model')->toString());
        }

        $periodUsage = collect([
            ['label' => 'Hari ini', 'description' => now()->translatedFormat('d M Y'), 'query' => fn () => (clone $allUsageQuery)->where('created_at', '>=', now()->startOfDay())],
            ['label' => 'Minggu ini', 'description' => now()->startOfWeek()->translatedFormat('d M').' – '.now()->endOfWeek()->translatedFormat('d M'), 'query' => fn () => (clone $allUsageQuery)->where('created_at', '>=', now()->startOfWeek())],
            ['label' => 'Bulan ini', 'description' => now()->translatedFormat('F Y'), 'query' => fn () => (clone $allUsageQuery)->where('created_at', '>=', now()->startOfMonth())],
            ['label' => 'Total', 'description' => 'Sejak awal penggunaan', 'query' => fn () => clone $allUsageQuery],
        ])->map(function ($period) {
            $stats = $period['query']()->selectRaw('COUNT(*) as request_count, COALESCE(SUM(total_tokens), 0) as total_tokens')->first();

            return ['label' => $period['label'], 'description' => $period['description'], 'request_count' => $stats->request_count, 'total_tokens' => $stats->total_tokens];
        });

        $summary = (clone $baseQuery)->selectRaw('
            COUNT(*) as request_count,
            COALESCE(SUM(input_tokens), 0) as input_tokens,
            COALESCE(SUM(output_tokens), 0) as output_tokens,
            COALESCE(SUM(total_tokens), 0) as total_tokens,
            COALESCE(AVG(total_tokens), 0) as avg_tokens,
            COALESCE(AVG(response_time_ms), 0) as avg_response_time,
            COUNT(DISTINCT user_id) as user_count,
            COUNT(DISTINCT question_id) as question_count
        ')->first();

        $profile = ClientProfile::query()->first();
        $settings = is_array($profile?->ai_discussion_settings) ? $profile->ai_discussion_settings : [];
        $monthlyLimit = max(0, (int) ($settings['monthly_token_limit'] ?? 0));
        $usedTokens = (int) $summary->total_tokens;

        $byQuestion = (clone $baseQuery)
            ->select('question_id')
            ->selectRaw('COUNT(*) as request_count, SUM(input_tokens) as input_tokens, SUM(output_tokens) as output_tokens, SUM(total_tokens) as total_tokens')
            ->with('question:question_id,question_text')
            ->groupBy('question_id')
            ->orderByDesc('total_tokens')
            ->limit(10)
            ->get();

        $byUser = (clone $baseQuery)
            ->select('user_id')
            ->selectRaw('COUNT(*) as request_count, SUM(input_tokens) as input_tokens, SUM(output_tokens) as output_tokens, SUM(total_tokens) as total_tokens')
            ->with('user:id,name,email')
            ->groupBy('user_id')
            ->orderByDesc('total_tokens')
            ->limit(10)
            ->get();

        $dailyUsage = (clone $baseQuery)
            ->selectRaw('DATE(created_at) as usage_date, SUM(total_tokens) as total_tokens, COUNT(*) as request_count')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('usage_date')
            ->get();

        $logs = (clone $baseQuery)
            ->with([
                'user:id,name,email',
                'tryout:tryout_id,name',
                'question:question_id,question_text',
            ])
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $providers = AiDiscussionUsageLog::query()->distinct()->orderBy('provider')->pluck('provider');
        $models = AiDiscussionUsageLog::query()->distinct()->orderBy('model')->pluck('model');

        return view('super-admin.ai-usage.index', compact(
            'month', 'summary', 'monthlyLimit', 'usedTokens', 'byQuestion', 'byUser',
            'dailyUsage', 'periodUsage', 'logs', 'providers', 'models'
        ));
    }

    public function gatewayIndex(Request $request)
    {
        $clients = AiGatewayClient::query()
            ->withSum('usageLogs as total_tokens', 'total_tokens')
            ->withCount('usageLogs')
            ->orderBy('name')
            ->get();

        $logs = AiGatewayUsageLog::query()
            ->with('client:id,name,slug,base_url')
            ->when($request->filled('client_id'), fn ($query) => $query->where('ai_gateway_client_id', $request->integer('client_id')))
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = trim((string) $request->input('q'));
                $query->where(function ($q) use ($term) {
                    $q->where('external_user_id', 'like', "%{$term}%")
                        ->orWhere('external_user_name', 'like', "%{$term}%")
                        ->orWhere('external_user_email', 'like', "%{$term}%")
                        ->orWhere('question_reference', 'like', "%{$term}%");
                });
            })
            ->latest()
            ->paginate(30)
            ->withQueryString();

        $filteredLogs = AiGatewayUsageLog::query()
            ->when($request->filled('client_id'), fn ($query) => $query->where('ai_gateway_client_id', $request->integer('client_id')));
        $summary = $filteredLogs->selectRaw('COUNT(*) as request_count, COALESCE(SUM(total_tokens), 0) as total_tokens, COUNT(DISTINCT external_user_id) as user_count')->first();
        $subscriptions = AiGatewaySubscription::query()
            ->with(['client:id,name,base_url', 'plan:id,name,token_limit,chat_limit'])
            ->whereNotNull('external_user_id')
            ->when($request->filled('client_id'), fn ($query) => $query->where('ai_gateway_client_id', $request->integer('client_id')))
            ->latest()
            ->paginate(20, ['*'], 'subscription_page')
            ->withQueryString();

        return view('super-admin.ai-gateway-usage.index', compact('clients', 'logs', 'summary', 'subscriptions'));
    }

    public function gatewayPayments(Request $request, AiGatewayCostService $costService)
    {
        $clients = AiGatewayClient::query()->orderBy('name')->get(['id', 'name']);
        $transactions = AiGatewayTransaction::query()
            ->with(['client:id,name,base_url', 'plan:id,name', 'subscription:id,external_user_name,external_user_email,tokens_used,chats_used'])
            ->when($request->filled('client_id'), fn ($query) => $query->where('ai_gateway_client_id', $request->integer('client_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->latest()
            ->paginate(30)
            ->withQueryString();
        $summary = AiGatewayTransaction::query()
            ->when($request->filled('client_id'), fn ($query) => $query->where('ai_gateway_client_id', $request->integer('client_id')))
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END), 0) as paid_amount, SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_count, SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count")
            ->first();

        $usageCostGroups = AiGatewayUsageLog::query()
            ->select([
                'ai_gateway_client_id',
                'provider',
                'model',
                'input_per_million_usd',
                'output_per_million_usd',
                'usd_to_idr',
            ])
            ->selectRaw('COALESCE(SUM(input_tokens), 0) as input_tokens, COALESCE(SUM(output_tokens), 0) as output_tokens, COALESCE(SUM(total_tokens), 0) as total_tokens, COALESCE(SUM(input_cost_idr), 0) as input_cost_idr, COALESCE(SUM(output_cost_idr), 0) as output_cost_idr, COUNT(*) as request_count')
            ->with('client:id,name')
            ->when($request->filled('client_id'), fn ($query) => $query->where('ai_gateway_client_id', $request->integer('client_id')))
            ->groupBy(
                'ai_gateway_client_id',
                'provider',
                'model',
                'input_per_million_usd',
                'output_per_million_usd',
                'usd_to_idr',
            )
            ->get();

        $costBreakdown = collect();
        $unpricedUsage = collect();
        foreach ($usageCostGroups as $usage) {
            $hasCostSnapshot = $usage->input_per_million_usd !== null
                && $usage->output_per_million_usd !== null
                && $usage->usd_to_idr !== null;

            if ($hasCostSnapshot) {
                $costBreakdown->push([
                    'client_name' => $usage->client?->name ?? 'Project dihapus',
                    'model' => $usage->model,
                    'input_tokens' => (int) $usage->input_tokens,
                    'output_tokens' => (int) $usage->output_tokens,
                    'total_tokens' => (int) $usage->total_tokens,
                    'request_count' => (int) $usage->request_count,
                    'input_per_million_usd' => (float) $usage->input_per_million_usd,
                    'output_per_million_usd' => (float) $usage->output_per_million_usd,
                    'usd_to_idr' => (float) $usage->usd_to_idr,
                    'input_cost_idr' => (float) $usage->input_cost_idr,
                    'output_cost_idr' => (float) $usage->output_cost_idr,
                    'total_cost_idr' => (float) $usage->input_cost_idr + (float) $usage->output_cost_idr,
                ]);

                continue;
            }

            // Log sebelum fitur snapshot tetap dihitung dengan tarif model yang tersedia saat ini.
            $cost = $costService->estimate(
                (string) $usage->provider,
                (string) $usage->model,
                (int) $usage->input_tokens,
                (int) $usage->output_tokens,
            );

            if ($cost !== null) {
                $costBreakdown->push([
                    'client_name' => $usage->client?->name ?? 'Project dihapus',
                    'model' => $usage->model,
                    'input_tokens' => (int) $usage->input_tokens,
                    'output_tokens' => (int) $usage->output_tokens,
                    'total_tokens' => (int) $usage->total_tokens,
                    'request_count' => (int) $usage->request_count,
                    ...$cost,
                ]);

                continue;
            }

            $unpricedUsage->push([
                'client_name' => $usage->client?->name ?? 'Project dihapus',
                'model' => $usage->model,
                'request_count' => (int) $usage->request_count,
                'total_tokens' => (int) $usage->total_tokens,
            ]);
        }

        $apiExpense = (int) round($costBreakdown->sum('total_cost_idr'));
        $financialSummary = [
            'gross_income' => (int) $summary->paid_amount,
            'api_expense' => $apiExpense,
            'net_income' => $unpricedUsage->isEmpty() ? (int) $summary->paid_amount - $apiExpense : null,
            'input_tokens' => (int) $usageCostGroups->sum('input_tokens'),
            'output_tokens' => (int) $usageCostGroups->sum('output_tokens'),
            'total_tokens' => (int) $usageCostGroups->sum('total_tokens'),
            'request_count' => (int) $usageCostGroups->sum('request_count'),
        ];

        return view('super-admin.ai-gateway-payments.index', compact('clients', 'transactions', 'summary', 'financialSummary', 'costBreakdown', 'unpricedUsage'));
    }

    public function approveGatewayPayment(AiGatewayTransaction $transaction, AiGatewaySubscriptionService $subscriptionService)
    {
        if ($transaction->status !== 'pending') {
            return back()->with('error', 'Hanya transaksi pending yang dapat di-ACC.');
        }

        $subscriptionService->activateTransaction($transaction);

        return back()->with('success', 'Pembayaran berhasil di-ACC dan kuota peserta telah diaktifkan.');
    }

    public function rejectGatewayPayment(AiGatewayTransaction $transaction)
    {
        if ($transaction->status !== 'pending') {
            return back()->with('error', 'Hanya transaksi pending yang dapat ditolak.');
        }

        $transaction->update(['status' => 'rejected']);
        $transaction->subscription?->update(['status' => 'rejected']);

        return back()->with('success', 'Pembayaran ditolak. Peserta dapat membuat pembayaran baru.');
    }

    public function storeGatewayClient(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100'], 'base_url' => ['nullable', 'url', 'max:2048'], 'monthly_token_limit' => ['nullable', 'integer', 'min:0'], 'free_token_limit' => ['nullable', 'integer', 'min:0'], 'free_chat_limit' => ['nullable', 'integer', 'min:0']]);
        $plainKey = 'aigw_'.Str::random(48);
        $client = AiGatewayClient::create(['name' => $data['name'], 'slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(6)), 'base_url' => $data['base_url'] ?? null, 'api_key_hash' => hash('sha256', $plainKey), 'monthly_token_limit' => (int) ($data['monthly_token_limit'] ?? 0), 'free_token_limit' => (int) ($data['free_token_limit'] ?? 0), 'free_chat_limit' => (int) ($data['free_chat_limit'] ?? 0)]);

        return back()->with('gateway_key', $plainKey)->with('gateway_key_client_id', $client->id)->with('success', 'Project gateway berhasil dibuat. Salin key sekarang; key hanya tampil sekali.');
    }

    public function updateGatewayClient(Request $request, AiGatewayClient $gatewayClient)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100'], 'base_url' => ['nullable', 'url', 'max:2048'], 'monthly_token_limit' => ['required', 'integer', 'min:0'], 'free_token_limit' => ['nullable', 'integer', 'min:0'], 'free_chat_limit' => ['nullable', 'integer', 'min:0']]);
        $gatewayClient->update($data);

        return back()->with('success', 'Project gateway berhasil diperbarui.');
    }

    public function destroyGatewayClient(AiGatewayClient $gatewayClient)
    {
        $gatewayClient->delete();

        return back()->with('success', 'Project gateway beserta riwayat pemakaiannya berhasil dihapus.');
    }

    public function updateQuota(Request $request)
    {
        $validated = $request->validate([
            'monthly_token_limit' => ['required', 'integer', 'min:0', 'max:1000000000'],
        ]);

        $profile = ClientProfile::query()->firstOrFail();
        $settings = is_array($profile->ai_discussion_settings) ? $profile->ai_discussion_settings : [];
        $settings['monthly_token_limit'] = (int) $validated['monthly_token_limit'];
        $profile->update(['ai_discussion_settings' => $settings]);

        return redirect()->route('super-admin.ai-usage.index')
            ->with('success', $settings['monthly_token_limit'] === 0
                ? 'Kuota token diatur tanpa batas.'
                : 'Kuota token bulanan berhasil diperbarui.');
    }
}
