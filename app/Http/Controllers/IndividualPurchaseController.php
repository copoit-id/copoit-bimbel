<?php

namespace App\Http\Controllers;

use App\Models\ClassModel;
use App\Models\Discount;
use App\Models\IndividualPurchase;
use App\Models\Material;
use App\Models\Payment;
use App\Models\TesKoran;
use App\Models\Tryout;
use App\Models\UserClassAccess;
use App\Models\UserMaterialAccess;
use App\Models\UserTryoutAccess;
use App\Services\Payments\IpaymuGateway;
use App\Services\PurchaseAccessDuration;
use App\Support\MailSafety;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class IndividualPurchaseController extends Controller
{
    public function buy(Request $request)
    {
        if (! Auth::check()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Silakan login terlebih dahulu.',
                ], 401);
            }

            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $request->validate([
            'type' => 'required|in:material,class,tryout,tes_koran',
            'id' => 'required|integer',
            'discount_code' => 'nullable|string|max:50',
        ]);

        $type = $request->type;
        $id = $request->id;
        $userId = Auth::id();

        // Get the item
        if ($type === 'material') {
            $item = Material::find($id);
            $purchasableType = Material::class;
        } elseif ($type === 'class') {
            $item = ClassModel::find($id);
            $purchasableType = ClassModel::class;
        } elseif ($type === 'tryout') {
            $item = Tryout::find($id);
            $purchasableType = Tryout::class;
        } else {
            $item = TesKoran::find($id);
            $purchasableType = TesKoran::class;
        }

        if (! $item) {
            $message = 'Item tidak ditemukan.';

            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $message], 404)
                : redirect()->back()->with('error', $message);
        }

        if (! ($item->is_displayed ?? true) || ! $item->isIndividuallyAvailable()) {
            $message = 'Item ini tidak dijual terpisah.';

            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $message], 400)
                : redirect()->back()->with('error', $message);
        }

        // Check if already purchased
        $existingPurchase = IndividualPurchase::where('user_id', $userId)
            ->where('purchasable_type', $purchasableType)
            ->where('purchasable_id', $id)
            ->where('status', 'approved')
            ->where(function ($query) {
                $query->whereNull('access_expires_at')
                    ->orWhere('access_expires_at', '>', now());
            })
            ->first();

        if ($existingPurchase) {
            $message = 'Anda sudah memiliki item ini.';

            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $message], 400)
                : redirect()->back()->with('error', $message);
        }

        $pendingPurchase = $this->reusablePendingPurchase($userId, $purchasableType, $id);

        if ($pendingPurchase) {
            $redirectUrl = $this->pendingGatewayPurchaseRedirectUrl($pendingPurchase);
            $message = $pendingPurchase->payment_method === 'manual'
                ? 'Bukti pembayaran untuk item ini masih menunggu verifikasi admin.'
                : 'Anda masih memiliki tagihan pending untuk item ini. Silakan lanjutkan pembayaran sebelumnya.';

            return $request->expectsJson()
                ? response()->json([
                    'success' => true,
                    'message' => $message,
                    'redirect_url' => $redirectUrl ?: route('user.package.riwayatPembelian'),
                ])
                : ($redirectUrl
                    ? redirect()->away($redirectUrl)
                    : redirect()->route('user.package.riwayatPembelian')->with('info', $message));
        }

        // Check access via package
        if ($item->canUserAccess($userId)) {
            $itemLabel = match ($type) {
                'material' => 'materi',
                'class' => 'kelas',
                'tryout' => 'tryout',
                default => 'tes koran',
            };

            $message = "Anda sudah memiliki akses ke {$itemLabel} ini.";

            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $message], 400)
                : redirect()->back()->with('error', $message);
        }

        if ($item->isFreeUnconditionalIndividualAccess()) {
            $purchase = $this->createFreePurchase($item, $purchasableType, $id, $type, $userId, true);
            $this->grantApprovedAccess($purchase);

            $message = 'Akses gratis berhasil diaktifkan.';

            return $request->expectsJson()
                ? response()->json(['success' => true, 'message' => $message, 'reload' => true])
                : redirect()->back()->with('success', $message);
        }

        if ($item->isFreeConditionalIndividualAccess()) {
            $validated = $request->validate([
                'requirement_proofs' => 'required|array|min:1',
                'requirement_proofs.*' => 'required|file|mimes:jpg,jpeg,png,pdf,mp4,webm|max:2048',
                'requirement_user_notes' => 'nullable|string|max:1000',
            ], [
                'requirement_proofs.required' => 'Bukti pemenuhan syarat wajib diunggah.',
                'requirement_proofs.array' => 'Bukti pemenuhan syarat tidak valid.',
                'requirement_proofs.min' => 'Minimal unggah 1 bukti syarat.',
                'requirement_proofs.*.mimes' => 'Format bukti harus berupa JPG, PNG, PDF, MP4, atau WEBM.',
                'requirement_proofs.*.max' => 'Ukuran setiap file maksimal 2MB.',
            ]);

            $proofPaths = collect($request->file('requirement_proofs', []))
                ->map(fn (UploadedFile $proof) => $proof->store('conditional-proofs/individual', 'public'))
                ->values()
                ->all();

            $purchase = $this->createFreePurchase(
                $item,
                $purchasableType,
                $id,
                $type,
                $userId,
                false,
                $proofPaths,
                $validated['requirement_user_notes'] ?? null
            );
            $this->sendPurchaseNotificationToAdmin($purchase);

            $message = 'Permintaan akses gratis bersyarat berhasil dikirim. Mohon tunggu verifikasi admin.';

            return $request->expectsJson()
                ? response()->json(['success' => true, 'message' => $message])
                : redirect()->route('user.package.riwayatPembelian')->with('success', $message);
        }

        // Resolve optional voucher
        $discountCode = Discount::normalizeCode($request->input('discount_code'));
        $discountData = $this->resolveIndividualDiscount($discountCode, $item, $type, $userId);

        if ($discountData['error']) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $discountData['error'],
                ], 422);
            }

            return redirect()->back()->with('error', $discountData['error']);
        }

        $discount = $discountData['discount'];
        $discountAmount = $discountData['discount_amount'];
        $totalAmount = max(0, (int) $item->price - $discountAmount);

        if ($totalAmount <= 0) {
            $purchase = IndividualPurchase::create([
                'user_id' => $userId,
                'purchasable_type' => $purchasableType,
                'purchasable_id' => $id,
                'discount_id' => $discount?->id,
                'discount_code' => $discount?->code,
                'discount_amount' => $discountAmount,
                'price' => $item->price,
                'admin_fee' => 0,
                'total_amount' => 0,
                'payment_method' => 'discount',
                'status' => IndividualPurchase::STATUS_APPROVED,
                'transaction_id' => 'IND-DISC-'.strtoupper($type).'-'.$id.'-'.$userId.'-'.time(),
                'payment_details' => [
                    'base_amount' => (int) $item->price,
                    'discount_code' => $discount?->code,
                    'discount_amount' => $discountAmount,
                ],
                'approved_at' => Carbon::now(),
                'access_expires_at' => PurchaseAccessDuration::expiresAt($item, Carbon::now()),
            ]);

            $this->grantApprovedAccess($purchase);

            return $request->expectsJson()
                ? response()->json(['success' => true, 'message' => 'Pembelian berhasil. Akses sudah aktif.', 'reload' => true])
                : redirect()->route('user.package.riwayatPembelian')->with('success', 'Pembelian berhasil. Akses sudah aktif.');
        }

        // For manual payment, require payment proof
        $paymentMode = config('client.branding.payment_mode', 'gateway');

        if ($paymentMode === 'manual') {
            $request->validate([
                'payment_proof' => 'required|file|mimes:jpg,jpeg,png,pdf|max:20480',
            ], [
                'payment_proof.required' => 'Bukti pembayaran wajib diunggah.',
            ]);

            $proofPath = $request->file('payment_proof')->store('payment-proofs/individual', 'public');
            $transactionId = 'IND-'.strtoupper($type).'-'.$id.'-'.$userId.'-'.time();

            $purchase = IndividualPurchase::create([
                'user_id' => $userId,
                'purchasable_type' => $purchasableType,
                'purchasable_id' => $id,
                'discount_id' => $discount?->id,
                'discount_code' => $discount?->code,
                'discount_amount' => $discountAmount,
                'price' => $item->price,
                'admin_fee' => 0,
                'total_amount' => $totalAmount,
                'payment_method' => 'manual',
                'status' => IndividualPurchase::STATUS_PENDING,
                'transaction_id' => $transactionId,
                'payment_details' => json_encode([
                    'proof_path' => $proofPath,
                    'proof_name' => $request->file('payment_proof')->getClientOriginalName(),
                ]),
            ]);

            $this->sendPurchaseNotificationToAdmin($purchase);

            $message = 'Bukti pembayaran berhasil dikirim. Mohon tunggu verifikasi admin.';

            return $request->expectsJson()
                ? response()->json(['success' => true, 'message' => $message])
                : redirect()->route('user.package.riwayatPembelian')->with('success', $message);
        }

        $paymentResponse = $this->createGatewayPurchase($item, $purchasableType, $id, $type, $userId, $discount, $discountAmount, $totalAmount);

        if ($paymentResponse['success'] ?? false) {
            return $request->expectsJson()
                ? response()->json([
                    'success' => true,
                    'message' => 'Silakan selesaikan pembayaran.',
                    'redirect_url' => $paymentResponse['redirect_url'],
                ])
                : redirect()->away($paymentResponse['redirect_url']);
        }

        return $request->expectsJson()
            ? response()->json(['success' => false, 'message' => $paymentResponse['message'] ?? 'Gagal membuat pembayaran.'], 422)
            : redirect()->back()->with('error', $paymentResponse['message'] ?? 'Gagal membuat pembayaran.');
    }

    public function gatewayRedirect(Request $request, string $type, int $id)
    {
        abort_unless(in_array($type, ['material', 'class', 'tryout', 'tes_koran'], true), 404);

        return redirect()->route('user.package.riwayatPembelian')
            ->with('info', 'Cek riwayat pembelian untuk melanjutkan pembayaran yang masih pending.');
    }

    public function history()
    {
        return redirect()->route('user.package.riwayatPembelian');
    }

    private function reusablePendingPurchase(int $userId, string $purchasableType, int $purchasableId): ?IndividualPurchase
    {
        $pendingPurchases = IndividualPurchase::query()
            ->where('user_id', $userId)
            ->where('purchasable_type', $purchasableType)
            ->where('purchasable_id', $purchasableId)
            ->where('status', IndividualPurchase::STATUS_PENDING)
            ->latest()
            ->get();

        foreach ($pendingPurchases as $purchase) {
            if ($this->pendingPurchaseIsExpired($purchase)) {
                $details = is_array($purchase->payment_details) ? $purchase->payment_details : [];
                $details['auto_rejected_reason'] = 'Pending payment expired before completion.';
                $details['auto_rejected_at'] = now()->toDateTimeString();

                $purchase->update([
                    'status' => IndividualPurchase::STATUS_REJECTED,
                    'payment_details' => $details,
                ]);

                continue;
            }

            return $purchase;
        }

        return null;
    }

    private function pendingPurchaseIsExpired(IndividualPurchase $purchase): bool
    {
        $details = is_array($purchase->payment_details) ? $purchase->payment_details : [];
        $expiresAt = $details['expires_at']
            ?? $details['expired_at']
            ?? $details['expiry_date']
            ?? $details['expiration_date']
            ?? null;

        if ($expiresAt) {
            return Carbon::parse($expiresAt)->isPast();
        }

        if (in_array($purchase->payment_method, ['xendit', 'midtrans', 'ipaymu'], true)) {
            return ($purchase->created_at ?: now())->copy()->addDay()->isPast();
        }

        if ($purchase->payment_method === 'interactive_qris') {
            return ($purchase->created_at ?: now())->copy()->addMinutes(30)->isPast();
        }

        return false;
    }

    private function pendingGatewayPurchaseRedirectUrl(IndividualPurchase $purchase): ?string
    {
        $details = is_array($purchase->payment_details) ? $purchase->payment_details : [];

        return match ($purchase->payment_method) {
            'interactive_qris' => route('user.package.payment.qris.show', $purchase->transaction_id),
            'xendit' => $details['invoice_url'] ?? null,
            'midtrans', 'ipaymu' => $details['redirect_url'] ?? null,
            default => null,
        };
    }

    private function createGatewayPurchase(object $item, string $purchasableType, int $id, string $type, int $userId, ?Discount $discount, int $discountAmount, int $totalAmount): array
    {
        $gateway = strtolower((string) config('services.payment_gateway', 'xendit'));

        return match ($gateway) {
            'midtrans' => $this->createMidtransPurchase($item, $purchasableType, $id, $type, $userId, $discount, $discountAmount, $totalAmount),
            'ipaymu' => $this->createIpaymuPurchase($item, $purchasableType, $id, $type, $userId, $discount, $discountAmount, $totalAmount),
            'interactive_qris' => $this->createInteractiveQrisPurchase($item, $purchasableType, $id, $type, $userId, $discount, $discountAmount, $totalAmount),
            default => $this->createXenditPurchase($item, $purchasableType, $id, $type, $userId, $discount, $discountAmount, $totalAmount),
        };
    }

    private function createXenditPurchase(object $item, string $purchasableType, int $id, string $type, int $userId, ?Discount $discount, int $discountAmount, int $totalAmount): array
    {
        $secretKey = config('services.xendit.secret_key');

        if (! $secretKey) {
            return ['success' => false, 'message' => 'Xendit secret key tidak dikonfigurasi.'];
        }

        $uniqueCode = $this->paymentUniqueCodeFor($totalAmount);
        $payableTotal = $totalAmount + ($uniqueCode ?? 0);
        $transactionId = 'IND-XENDIT-'.strtoupper($type).'-'.$id.'-'.$userId.'-'.time();
        $itemName = $item->title ?? $item->name ?? 'Item';

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Basic '.base64_encode($secretKey.':'),
                'Content-Type' => 'application/json',
            ])->post(rtrim(config('services.xendit.base_url', 'https://api.xendit.co'), '/').'/v2/invoices', [
                'external_id' => $transactionId,
                'amount' => $payableTotal,
                'description' => 'Pembelian '.$itemName,
                'invoice_duration' => 86400,
                'customer' => [
                    'given_names' => Auth::user()->name,
                    'email' => Auth::user()->email,
                ],
                'success_redirect_url' => route('user.package.payment.success'),
                'failure_redirect_url' => route('user.package.payment.failed'),
            ]);

            if (! $response->successful()) {
                return ['success' => false, 'message' => $response->json('message') ?: 'Gagal membuat pembayaran Xendit.'];
            }

            $invoiceData = $response->json();
            $purchase = IndividualPurchase::create([
                'user_id' => $userId,
                'purchasable_type' => $purchasableType,
                'purchasable_id' => $id,
                'discount_id' => $discount?->id,
                'discount_code' => $discount?->code,
                'discount_amount' => $discountAmount,
                'price' => $item->price,
                'admin_fee' => 0,
                'total_amount' => $payableTotal,
                'payment_method' => 'xendit',
                'status' => IndividualPurchase::STATUS_PENDING,
                'transaction_id' => $transactionId,
                'payment_details' => [
                    'invoice_id' => $invoiceData['id'] ?? null,
                    'invoice_url' => $invoiceData['invoice_url'] ?? null,
                    'external_id' => $transactionId,
                    'expires_at' => now()->addDay()->toDateTimeString(),
                    'base_amount' => (int) $item->price,
                    'payable_amount' => $totalAmount,
                    'unique_code' => $uniqueCode,
                    'discount_code' => $discount?->code,
                    'discount_amount' => $discountAmount,
                    'purchase_type' => $type,
                ],
            ]);

            return ['success' => true, 'redirect_url' => $invoiceData['invoice_url'], 'purchase' => $purchase];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Error koneksi ke Xendit: '.$e->getMessage()];
        }
    }

    private function createMidtransPurchase(object $item, string $purchasableType, int $id, string $type, int $userId, ?Discount $discount, int $discountAmount, int $totalAmount): array
    {
        $serverKey = config('services.midtrans.server_key');

        if (! $serverKey) {
            return ['success' => false, 'message' => 'Midtrans server key tidak dikonfigurasi.'];
        }

        $uniqueCode = $this->paymentUniqueCodeFor($totalAmount);
        $payableTotal = $totalAmount + ($uniqueCode ?? 0);
        $transactionId = 'IND-MIDTRANS-'.strtoupper($type).'-'.$id.'-'.$userId.'-'.time();
        $itemName = $item->title ?? $item->name ?? 'Item';

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Basic '.base64_encode($serverKey.':'),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post(config('services.midtrans.snap_url', 'https://app.sandbox.midtrans.com/snap/v1/transactions'), [
                'transaction_details' => [
                    'order_id' => $transactionId,
                    'gross_amount' => $payableTotal,
                ],
                'item_details' => [[
                    'id' => (string) $id,
                    'price' => $payableTotal,
                    'quantity' => 1,
                    'name' => Str::limit($itemName, 50, ''),
                ]],
                'customer_details' => [
                    'first_name' => Auth::user()->name,
                    'email' => Auth::user()->email,
                ],
                'callbacks' => [
                    'finish' => route('user.package.payment.success'),
                    'error' => route('user.package.payment.failed'),
                    'pending' => route('user.package.riwayatPembelian'),
                ],
            ]);

            if (! $response->successful()) {
                return ['success' => false, 'message' => $response->json('error_messages.0') ?: 'Gagal membuat pembayaran Midtrans.'];
            }

            $data = $response->json();
            $purchase = IndividualPurchase::create([
                'user_id' => $userId,
                'purchasable_type' => $purchasableType,
                'purchasable_id' => $id,
                'discount_id' => $discount?->id,
                'discount_code' => $discount?->code,
                'discount_amount' => $discountAmount,
                'price' => $item->price,
                'admin_fee' => 0,
                'total_amount' => $payableTotal,
                'payment_method' => 'midtrans',
                'status' => IndividualPurchase::STATUS_PENDING,
                'transaction_id' => $transactionId,
                'payment_details' => [
                    'snap_token' => $data['token'] ?? null,
                    'redirect_url' => $data['redirect_url'] ?? null,
                    'external_id' => $transactionId,
                    'expires_at' => now()->addDay()->toDateTimeString(),
                    'base_amount' => (int) $item->price,
                    'payable_amount' => $totalAmount,
                    'unique_code' => $uniqueCode,
                    'discount_code' => $discount?->code,
                    'discount_amount' => $discountAmount,
                    'purchase_type' => $type,
                ],
            ]);

            return ['success' => true, 'redirect_url' => $data['redirect_url'], 'purchase' => $purchase];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Error koneksi ke Midtrans: '.$e->getMessage()];
        }
    }

    private function createIpaymuPurchase(object $item, string $purchasableType, int $id, string $type, int $userId, ?Discount $discount, int $discountAmount, int $totalAmount): array
    {
        $uniqueCode = $this->paymentUniqueCodeFor($totalAmount);
        $purchase = IndividualPurchase::create([
            'user_id' => $userId,
            'purchasable_type' => $purchasableType,
            'purchasable_id' => $id,
            'discount_id' => $discount?->id,
            'discount_code' => $discount?->code,
            'discount_amount' => $discountAmount,
            'price' => $item->price,
            'admin_fee' => 0,
            'total_amount' => $totalAmount + ($uniqueCode ?? 0),
            'payment_method' => 'ipaymu',
            'status' => IndividualPurchase::STATUS_PENDING,
            'transaction_id' => 'IND-IPAYMU-'.strtoupper($type).'-'.$id.'-'.$userId.'-'.time(),
            'payment_details' => [
                'base_amount' => (int) $item->price,
                'payable_amount' => $totalAmount,
                'unique_code' => $uniqueCode,
                'discount_code' => $discount?->code,
                'discount_amount' => $discountAmount,
                'purchase_type' => $type,
            ],
        ]);

        $response = app(IpaymuGateway::class)->createIndividualPurchasePayment($purchase, $item, $type);

        if (! ($response['success'] ?? false)) {
            $details = is_array($purchase->payment_details) ? $purchase->payment_details : [];
            $details['auto_rejected_reason'] = $response['message'] ?? 'Gagal membuat pembayaran iPaymu.';
            $details['auto_rejected_at'] = now()->toDateTimeString();
            $purchase->update([
                'status' => IndividualPurchase::STATUS_REJECTED,
                'payment_details' => $details,
            ]);
        }

        return $response + ['purchase' => $purchase];
    }

    private function createInteractiveQrisPurchase(object $item, string $purchasableType, int $id, string $type, int $userId, ?Discount $discount, int $discountAmount, int $totalAmount): array
    {
        $apiKey = config('services.interactive_qris.api_key');
        $merchantId = config('services.interactive_qris.mid');

        if (! $apiKey || ! $merchantId) {
            return ['success' => false, 'message' => 'Credential InterActive QRIS belum dikonfigurasi.'];
        }

        $uniqueCode = $this->paymentUniqueCodeFor($totalAmount);
        $payableTotal = $totalAmount + ($uniqueCode ?? 0);
        $transactionId = 'IND-QRIS-'.strtoupper($type).'-'.$id.'-'.$userId.'-'.time();

        try {
            $response = Http::timeout(20)->get(rtrim(config('services.interactive_qris.base_url', 'https://qris.interactive.co.id/restapi/qris'), '/').'/show_qris.php', [
                'do' => 'create-invoice',
                'apikey' => $apiKey,
                'mID' => $merchantId,
                'cliTrxNumber' => $transactionId,
                'cliTrxAmount' => $payableTotal,
                'useTip' => config('services.interactive_qris.use_tip', false) ? 'yes' : 'no',
            ]);

            if (! $response->successful()) {
                return ['success' => false, 'message' => 'Gagal membuat invoice InterActive QRIS.'];
            }

            $payload = $response->json();
            if (($payload['status'] ?? null) !== 'success') {
                return ['success' => false, 'message' => $payload['data']['qris_status'] ?? 'InterActive QRIS menolak pembuatan invoice.'];
            }

            $data = $payload['data'] ?? [];
            $requestDate = Carbon::parse($data['qris_request_date'] ?? now());
            $purchase = IndividualPurchase::create([
                'user_id' => $userId,
                'purchasable_type' => $purchasableType,
                'purchasable_id' => $id,
                'discount_id' => $discount?->id,
                'discount_code' => $discount?->code,
                'discount_amount' => $discountAmount,
                'price' => $item->price,
                'admin_fee' => 0,
                'total_amount' => $payableTotal,
                'payment_method' => 'interactive_qris',
                'status' => IndividualPurchase::STATUS_PENDING,
                'transaction_id' => $transactionId,
                'payment_details' => [
                    'qris_content' => $data['qris_content'] ?? null,
                    'qris_request_date' => $requestDate->toDateTimeString(),
                    'qris_invoiceid' => $data['qris_invoiceid'] ?? null,
                    'qris_nmid' => $data['qris_nmid'] ?? null,
                    'expires_at' => $requestDate->copy()->addMinutes(30)->toDateTimeString(),
                    'external_id' => $transactionId,
                    'base_amount' => (int) $item->price,
                    'payable_amount' => $totalAmount,
                    'unique_code' => $uniqueCode,
                    'discount_code' => $discount?->code,
                    'discount_amount' => $discountAmount,
                    'purchase_type' => $type,
                ],
            ]);

            return [
                'success' => true,
                'redirect_url' => route('user.package.payment.qris.show', $transactionId),
                'purchase' => $purchase,
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Error koneksi ke InterActive QRIS: '.$e->getMessage()];
        }
    }

    private function paymentUniqueCodeFor(int $amount): ?int
    {
        if ($amount <= 0 || ! (bool) config('client.branding.payment_unique_code_enabled', true)) {
            return null;
        }

        return Payment::generateManualUniqueCode();
    }

    private function resolveIndividualDiscount(?string $code, $item, string $type, int $userId): array
    {
        if (! $code) {
            return [
                'discount' => null,
                'discount_amount' => 0,
                'error' => null,
            ];
        }

        $discount = Discount::query()->voucher()->where('code', $code)->first();

        if (! $discount) {
            return [
                'discount' => null,
                'discount_amount' => 0,
                'error' => 'Kode voucher tidak ditemukan.',
            ];
        }

        $itemId = match ($type) {
            'material' => (int) $item->material_id,
            'class' => (int) $item->class_id,
            'tryout' => (int) $item->tryout_id,
            'tes_koran' => (int) $item->id,
        };

        $error = $discount->validationErrorFor((int) $item->price, $userId, null, $type, $itemId);

        if ($error) {
            return [
                'discount' => $discount,
                'discount_amount' => 0,
                'error' => $error,
            ];
        }

        return [
            'discount' => $discount,
            'discount_amount' => $discount->calculateDiscountAmount((int) $item->price),
            'error' => null,
        ];
    }

    private function sendPurchaseNotificationToAdmin(IndividualPurchase $purchase): void
    {
        $recipient = MailSafety::email(
            config('client.branding.smtp_notification_email')
                ?: config('client.branding.smtp_email')
        );

        if (! $recipient) {
            return;
        }

        $purchase->load(['user', 'purchasable']);

        $purchaseType = match (true) {
            $purchase->purchasable instanceof Material => 'Materi',
            $purchase->purchasable instanceof Tryout => 'Tryout',
            $purchase->purchasable instanceof TesKoran => 'Tes Koran',
            default => 'Item',
        };

        $itemName = $purchase->purchasable?->name ?? '-';
        $brandName = MailSafety::header(
            (string) config('client.branding.name', config('app.name')),
            'Copoit Academy'
        );

        try {
            Mail::send('emails.individual-purchase-notification', [
                'purchase' => $purchase,
                'purchaseType' => $purchaseType,
                'itemName' => $itemName,
                'brandName' => $brandName,
            ], function ($message) use ($recipient, $brandName) {
                $message->to($recipient);
                $message->subject("Pembelian Baru - {$brandName}");
            });
        } catch (\Throwable $exception) {
            Log::warning('Failed to send individual purchase notification email.', [
                'email' => $recipient,
                'purchase_id' => $purchase->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function createFreePurchase(
        object $item,
        string $purchasableType,
        int $id,
        string $type,
        int $userId,
        bool $approved,
        array $requirementProofPaths = [],
        ?string $requirementUserNotes = null
    ): IndividualPurchase {
        $transactionId = 'IND-'.strtoupper($type).'-'.$id.'-'.$userId.'-'.time();
        $approvedAt = $approved ? Carbon::now() : null;
        $accessExpiresAt = $approved ? PurchaseAccessDuration::expiresAt($item, $approvedAt) : null;

        return IndividualPurchase::create([
            'user_id' => $userId,
            'purchasable_type' => $purchasableType,
            'purchasable_id' => $id,
            'discount_amount' => 0,
            'price' => 0,
            'admin_fee' => 0,
            'total_amount' => 0,
            'payment_method' => $approved ? 'free_unconditional' : 'free_conditional',
            'status' => $approved ? IndividualPurchase::STATUS_APPROVED : IndividualPurchase::STATUS_PENDING,
            'transaction_id' => $transactionId,
            'payment_details' => [
                'conditional_requirement' => $item->conditional_requirement,
                'requirement_proof_paths' => $requirementProofPaths,
                'requirement_user_notes' => $requirementUserNotes ? trim($requirementUserNotes) : null,
            ],
            'approved_at' => $approvedAt,
            'access_expires_at' => $accessExpiresAt,
        ]);
    }

    private function grantApprovedAccess(IndividualPurchase $purchase): void
    {
        $purchase->loadMissing('purchasable');
        $approvedAt = $purchase->approved_at ?: Carbon::now();
        $accessExpiresAt = $purchase->access_expires_at
            ?: ($purchase->purchasable ? PurchaseAccessDuration::expiresAt($purchase->purchasable, $approvedAt) : null);

        if ($purchase->purchasable_type === Material::class) {
            UserMaterialAccess::updateOrCreate(
                [
                    'user_id' => $purchase->user_id,
                    'material_id' => $purchase->purchasable_id,
                ],
                [
                    'access_type' => 'purchased',
                    'access_source' => 'direct',
                    'source_id' => $purchase->id,
                    'status' => 'in_progress',
                    'started_at' => now(),
                    'expires_at' => $accessExpiresAt,
                ]
            );
        } elseif ($purchase->purchasable_type === ClassModel::class) {
            UserClassAccess::updateOrCreate(
                [
                    'user_id' => $purchase->user_id,
                    'class_id' => $purchase->purchasable_id,
                ],
                [
                    'access_type' => 'purchased',
                    'access_source' => 'direct',
                    'source_id' => $purchase->id,
                    'status' => 'active',
                    'started_at' => now(),
                    'expires_at' => $accessExpiresAt,
                ]
            );
        } elseif ($purchase->purchasable_type === Tryout::class) {
            UserTryoutAccess::updateOrCreate(
                [
                    'user_id' => $purchase->user_id,
                    'tryout_id' => $purchase->purchasable_id,
                ],
                [
                    'access_type' => 'purchased',
                    'access_source' => 'direct',
                    'source_id' => $purchase->id,
                    'status' => 'not_started',
                    'expires_at' => $accessExpiresAt,
                ]
            );
        }

        $purchase->update([
            'status' => IndividualPurchase::STATUS_APPROVED,
            'approved_at' => $approvedAt,
            'access_expires_at' => $accessExpiresAt,
        ]);
    }
}
