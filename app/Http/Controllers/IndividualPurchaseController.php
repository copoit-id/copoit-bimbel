<?php

namespace App\Http\Controllers;

use App\Models\Discount;
use App\Models\IndividualPurchase;
use App\Models\Material;
use App\Models\TesKoran;
use App\Models\Tryout;
use App\Models\UserMaterialAccess;
use App\Models\UserTryoutAccess;
use App\Services\PurchaseAccessDuration;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class IndividualPurchaseController extends Controller
{
    public function buy(Request $request)
    {
        if (!Auth::check()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Silakan login terlebih dahulu.'
                ], 401);
            }
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $request->validate([
            'type' => 'required|in:material,tryout,tes_koran',
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
        } elseif ($type === 'tryout') {
            $item = Tryout::find($id);
            $purchasableType = Tryout::class;
        } else {
            $item = TesKoran::find($id);
            $purchasableType = TesKoran::class;
        }

        if (!$item) {
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

        // Check if already has pending purchase
        $pendingPurchase = IndividualPurchase::where('user_id', $userId)
            ->where('purchasable_type', $purchasableType)
            ->where('purchasable_id', $id)
            ->where('status', 'pending')
            ->first();

        if ($pendingPurchase) {
            $message = 'Anda sudah memiliki permintaan pembelian yang pending.';
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $message], 400)
                : redirect()->back()->with('error', $message);
        }

        // Check access via package
        if ($item->canUserAccess($userId)) {
            $itemLabel = match ($type) {
                'material' => 'materi',
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
                ->map(fn (\Illuminate\Http\UploadedFile $proof) => $proof->store('conditional-proofs/individual', 'public'))
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
                : redirect()->route('user.individual-purchase.history')->with('success', $message);
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

        // For manual payment, require payment proof
        $paymentMode = config('client.branding.payment_mode', 'gateway');
        
        if ($paymentMode === 'manual') {
            $request->validate([
                'payment_proof' => 'required|file|mimes:jpg,jpeg,png,pdf|max:20480',
            ], [
                'payment_proof.required' => 'Bukti pembayaran wajib diunggah.',
            ]);

            $proofPath = $request->file('payment_proof')->store('payment-proofs/individual', 'public');
            $transactionId = 'IND-' . strtoupper($type) . '-' . $id . '-' . $userId . '-' . time();

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
                : redirect()->route('user.individual-purchase.history')->with('success', $message);
        }

        // For gateway payment, redirect to payment
        $redirectUrl = route('user.individual-purchase.gateway', [
            'type' => $type,
            'id' => $id,
            'discount_code' => $discountCode ?: null,
        ]);

        return $request->expectsJson()
            ? response()->json(['success' => true, 'redirect_url' => $redirectUrl])
            : redirect()->away($redirectUrl);
    }

    public function gatewayRedirect(Request $request, string $type, int $id)
    {
        if (!in_array($type, ['material', 'tryout', 'tes_koran'], true)) {
            abort(404);
        }

        // Placeholder for gateway integration
        // For now, create pending purchase and redirect to history
        $userId = Auth::id();

        if ($type === 'material') {
            $item = Material::find($id);
            $purchasableType = Material::class;
        } elseif ($type === 'tryout') {
            $item = Tryout::find($id);
            $purchasableType = Tryout::class;
        } else {
            $item = TesKoran::find($id);
            $purchasableType = TesKoran::class;
        }

        if (!$item) {
            return redirect()->back()->with('error', 'Item tidak ditemukan.');
        }

        if (! $item->isPaidIndividualAccess()) {
            return redirect()->route('user.individual-purchase.history')
                ->with('error', 'Item ini tidak memerlukan pembayaran gateway.');
        }

        $discountCode = Discount::normalizeCode($request->input('discount_code'));
        $discountData = $this->resolveIndividualDiscount($discountCode, $item, $type, $userId);

        if ($discountData['error']) {
            return redirect()->back()->with('error', $discountData['error']);
        }

        $discount = $discountData['discount'];
        $discountAmount = $discountData['discount_amount'];
        $totalAmount = max(0, (int) $item->price - $discountAmount);

        $transactionId = 'IND-' . strtoupper($type) . '-' . $id . '-' . $userId . '-' . time();

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
            'payment_method' => 'gateway',
            'status' => IndividualPurchase::STATUS_PENDING,
            'transaction_id' => $transactionId,
        ]);

        $this->sendPurchaseNotificationToAdmin($purchase);

        // TODO: Integrate with Xendit/Midtrans here

        return redirect()->route('user.individual-purchase.history')
            ->with('success', 'Silakan selesaikan pembayaran.');
    }

    public function history()
    {
        $purchases = IndividualPurchase::where('user_id', Auth::id())
            ->with('purchasable')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('user.pages.individual-purchase.history', compact('purchases'));
    }

    private function resolveIndividualDiscount(?string $code, $item, string $type, int $userId): array
    {
        if (!$code) {
            return [
                'discount' => null,
                'discount_amount' => 0,
                'error' => null,
            ];
        }

        $discount = Discount::query()->voucher()->where('code', $code)->first();

        if (!$discount) {
            return [
                'discount' => null,
                'discount_amount' => 0,
                'error' => 'Kode voucher tidak ditemukan.',
            ];
        }

        $itemId = match ($type) {
            'material' => (int) $item->material_id,
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
        $recipient = config('client.branding.smtp_notification_email')
            ?: config('client.branding.smtp_email');

        if (!$recipient) {
            return;
        }

        $purchase->load(['user', 'purchasable']);

        $purchaseType = match (true) {
            $purchase->purchasable instanceof \App\Models\Material => 'Materi',
            $purchase->purchasable instanceof \App\Models\Tryout => 'Tryout',
            $purchase->purchasable instanceof \App\Models\TesKoran => 'Tes Koran',
            default => 'Item',
        };

        $itemName = $purchase->purchasable?->name ?? '-';
        $brandName = config('client.branding.name', config('app.name'));

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
    ): IndividualPurchase
    {
        $transactionId = 'IND-' . strtoupper($type) . '-' . $id . '-' . $userId . '-' . time();
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
                    'status' => 'in_progress',
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
                    'status' => 'active',
                    'assigned_at' => now(),
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
