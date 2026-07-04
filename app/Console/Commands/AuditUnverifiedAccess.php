<?php

namespace App\Console\Commands;

use App\Models\IndividualPurchase;
use App\Models\Material;
use App\Models\Payment;
use App\Models\Tryout;
use App\Models\UserMaterialAccess;
use App\Models\UserPackageAcces;
use App\Models\UserTryoutAccess;
use Illuminate\Console\Command;

class AuditUnverifiedAccess extends Command
{
    protected $signature = 'payment:audit-unverified-access
        {--email= : Filter by user email}
        {--limit=200 : Maximum rows per section}
        {--json : Output JSON instead of tables}
        {--include-legacy-progress : Include old material/tryout progress records that are no longer treated as paid access}';

    protected $description = 'Audit active access records that do not have a verified payment or approved purchase.';

    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $email = $this->option('email') ? trim((string) $this->option('email')) : null;

        $result = [
            'package_access' => $this->packageAccessIssues($limit, $email),
            'material_access' => $this->materialAccessIssues($limit, $email),
            'tryout_access' => $this->tryoutAccessIssues($limit, $email),
        ];

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return self::SUCCESS;
        }

        $this->renderSection('Package access', $result['package_access']);
        $this->renderSection('Material direct access', $result['material_access']);
        $this->renderSection('Tryout direct access', $result['tryout_access']);

        $total = collect($result)->sum(fn (array $rows) => count($rows));
        $this->info("Total suspicious rows: {$total}");

        return self::SUCCESS;
    }

    private function packageAccessIssues(int $limit, ?string $email): array
    {
        return UserPackageAcces::query()
            ->with(['user:id,name,email', 'package:package_id,name,type_price,price'])
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('end_date')->orWhere('end_date', '>', now());
            })
            ->when($email, fn ($query) => $query->whereHas('user', fn ($userQuery) => $userQuery->where('email', $email)))
            ->where(function ($query) {
                $query->where('notes', 'like', 'Auto-activated for development testing%')
                    ->orWhereIn('payment_status', ['pending', 'failed', 'conditional'])
                    ->orWhere('payment_status', 'paid');
            })
            ->latest()
            ->limit($limit)
            ->get()
            ->filter(function (UserPackageAcces $access) {
                $matchingPayment = Payment::query()
                    ->where('user_id', $access->user_id)
                    ->where('package_id', $access->package_id)
                    ->where('status', Payment::STATUS_SUCCESS)
                    ->exists();

                return str_starts_with((string) $access->notes, 'Auto-activated for development testing')
                    || in_array($access->payment_status, ['pending', 'failed', 'conditional'], true)
                    || ($access->payment_status === 'paid' && !$matchingPayment);
            })
            ->map(function (UserPackageAcces $access) {
                $matchingPayment = Payment::query()
                    ->where('user_id', $access->user_id)
                    ->where('package_id', $access->package_id)
                    ->where('status', Payment::STATUS_SUCCESS)
                    ->latest()
                    ->first();

                return [
                    'reason' => $this->packageReason($access, $matchingPayment),
                    'access_id' => $access->user_package_access_id,
                    'user_id' => $access->user_id,
                    'user_email' => $access->user?->email,
                    'package_id' => $access->package_id,
                    'package' => $access->package?->name,
                    'payment_status' => $access->payment_status,
                    'payment_amount' => (int) $access->payment_amount,
                    'access_created_at' => optional($access->created_at)->toDateTimeString(),
                    'access_end_date' => optional($access->end_date)->toDateTimeString(),
                    'notes' => $access->notes,
                    'matching_success_payment' => $matchingPayment?->transaction_id,
                    'matching_payment_method' => $matchingPayment?->payment_method,
                    'gateway_confirmed' => $matchingPayment?->hasGatewayConfirmation() ?? false,
                    'gateway_reference' => $matchingPayment?->gatewayReference(),
                ];
            })
            ->values()
            ->all();
    }

    private function materialAccessIssues(int $limit, ?string $email): array
    {
        return UserMaterialAccess::query()
            ->with(['user:id,name,email', 'material:material_id,title,type_price,price'])
            ->where('access_source', 'direct')
            ->where('status', '!=', 'not_started')
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->when($email, fn ($query) => $query->whereHas('user', fn ($userQuery) => $userQuery->where('email', $email)))
            ->whereIn('access_type', $this->directAccessTypesForAudit())
            ->latest()
            ->limit($limit)
            ->get()
            ->filter(fn (UserMaterialAccess $access) => $access->access_type === 'subscription'
                || !$this->hasApprovedIndividualPurchase($access->user_id, Material::class, $access->material_id)
            )
            ->map(fn (UserMaterialAccess $access) => [
                'reason' => $access->access_type === 'subscription'
                    ? 'legacy_direct_subscription_progress'
                    : 'purchased_direct_access_without_approved_purchase',
                'access_id' => $access->user_material_access_id,
                'user_id' => $access->user_id,
                'user_email' => $access->user?->email,
                'material_id' => $access->material_id,
                'material' => $access->material?->title,
                'access_type' => $access->access_type,
                'status' => $access->status,
                'source_id' => $access->source_id,
                'expires_at' => optional($access->expires_at)->toDateTimeString(),
                'created_at' => optional($access->created_at)->toDateTimeString(),
            ])
            ->values()
            ->all();
    }

    private function tryoutAccessIssues(int $limit, ?string $email): array
    {
        return UserTryoutAccess::query()
            ->with(['user:id,name,email', 'tryout:tryout_id,name,type_price,price'])
            ->where('access_source', 'direct')
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->when($email, fn ($query) => $query->whereHas('user', fn ($userQuery) => $userQuery->where('email', $email)))
            ->whereIn('access_type', $this->directAccessTypesForAudit())
            ->latest()
            ->limit($limit)
            ->get()
            ->filter(fn (UserTryoutAccess $access) => $access->access_type === 'subscription'
                || !$this->hasApprovedIndividualPurchase($access->user_id, Tryout::class, $access->tryout_id)
            )
            ->map(fn (UserTryoutAccess $access) => [
                'reason' => $access->access_type === 'subscription'
                    ? 'legacy_direct_subscription_access'
                    : 'purchased_direct_access_without_approved_purchase',
                'access_id' => $access->user_tryout_access_id,
                'user_id' => $access->user_id,
                'user_email' => $access->user?->email,
                'tryout_id' => $access->tryout_id,
                'tryout' => $access->tryout?->name,
                'access_type' => $access->access_type,
                'status' => $access->status,
                'source_id' => $access->source_id,
                'expires_at' => optional($access->expires_at)->toDateTimeString(),
                'created_at' => optional($access->created_at)->toDateTimeString(),
            ])
            ->values()
            ->all();
    }

    private function packageReason(UserPackageAcces $access, ?Payment $matchingPayment): string
    {
        if (str_starts_with((string) $access->notes, 'Auto-activated for development testing')) {
            return 'auto_activated_success_callback';
        }

        if (in_array($access->payment_status, ['pending', 'failed', 'conditional'], true)) {
            return 'active_access_with_unpaid_status';
        }

        if (!$matchingPayment) {
            return 'paid_access_without_success_payment';
        }

        return 'review';
    }

    private function directAccessTypesForAudit(): array
    {
        $types = ['purchased', 'paid'];

        if ($this->option('include-legacy-progress')) {
            $types[] = 'subscription';
        }

        return $types;
    }

    private function hasApprovedIndividualPurchase(int $userId, string $purchasableType, int $purchasableId): bool
    {
        return IndividualPurchase::query()
            ->where('user_id', $userId)
            ->where('purchasable_type', $purchasableType)
            ->where('purchasable_id', $purchasableId)
            ->where('status', IndividualPurchase::STATUS_APPROVED)
            ->where(function ($query) {
                $query->whereNull('access_expires_at')
                    ->orWhere('access_expires_at', '>', now());
            })
            ->exists();
    }

    private function renderSection(string $title, array $rows): void
    {
        $this->newLine();
        $this->info($title . ' (' . count($rows) . ')');

        if (empty($rows)) {
            $this->line('No rows.');
            return;
        }

        $headers = array_keys($rows[0]);
        $this->table($headers, array_map(fn (array $row) => array_values($row), $rows));
    }
}
