<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use App\Models\IndividualPurchase;
use App\Models\Material;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Discount;
use App\Models\AffiliateSetting;
use App\Models\UserPackageAcces;
use App\Models\ClassModel;
use App\Models\MaterialProgressLog;
use App\Models\TesKoranResult;
use App\Models\TesKoran;
use App\Models\Tryout;
use App\Models\UserAnswer;
use App\Models\UserMaterialAccess;
use Carbon\Carbon;
use App\Models\UserTryoutAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Services\ActivityLogger;
use App\Services\AiDiscussionService;
use App\Services\AffiliateService;
use App\Services\Payments\IpaymuGateway;
use App\Services\Payments\InteractiveQrisGateway;
use App\Services\PurchaseAccessDuration;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class PackageController extends Controller
{
    public function index(Request $request)
    {
        $tab = 'all';
        $search = trim((string) $request->get('search', ''));
        $sort = $request->get('sort', 'latest');
        
        // Get user's owned package IDs (cast to int for consistent comparison)
        $userOwnedPackageIds = [];
        $pendingConditionalPackageIds = [];
        if (Auth::check()) {
            $userOwnedPackageIds = UserPackageAcces::where('user_id', Auth::id())
                ->where('status', 'active')
                ->where(function ($query) {
                    $query->whereNull('end_date')
                        ->orWhere('end_date', '>', Carbon::now());
                })
                ->pluck('package_id')
                ->map(fn($id) => (int) $id)
                ->toArray();

            $pendingConditionalPackageIds = UserPackageAcces::where('user_id', Auth::id())
                ->where('requirement_status', 'pending')
                ->pluck('package_id')
                ->map(fn($id) => (int) $id)
                ->toArray();
        }
        
        $packagesQuery = Package::where('status', 'active')
            ->where('is_displayed', true)
            ->with(['detailPackages'])
            ->withCount(['materials', 'tryouts', 'tesKorans']);

        if ($search !== '') {
            $packagesQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        match ($sort) {
            'oldest' => $packagesQuery->orderBy('created_at', 'asc'),
            'name_asc' => $packagesQuery->orderBy('name', 'asc'),
            'name_desc' => $packagesQuery->orderBy('name', 'desc'),
            default => $packagesQuery->orderBy('created_at', 'desc'),
        };

        $packages = $packagesQuery->get();

        $pendingPackagePaymentsByPackage = collect();
        if (Auth::check()) {
            $pendingPackagePaymentsByPackage = $this->pendingPackagePaymentsForPackages($packages->pluck('package_id')->all());
        }

        $manualPaymentUniqueCodes = [];
        $paymentMode = strtolower((string) config('client.branding.payment_mode', 'gateway'));
        $paymentUniqueCodeEnabled = (bool) config('client.branding.payment_unique_code_enabled', true);

        if (Auth::check() && $paymentMode === 'manual' && $paymentUniqueCodeEnabled) {
            $reservedCodes = [];

            foreach ($packages->where('type_price', 'paid') as $package) {
                $uniqueCode = Payment::generateManualUniqueCode($reservedCodes);
                $reservedCodes[] = $uniqueCode;
                $manualPaymentUniqueCodes[$package->package_id] = $uniqueCode;
            }
        }

        $publicDiscounts = Discount::query()
            ->publicAvailable()
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->filter(fn (Discount $discount) => $discount->appliesToPurchaseType('package')
                && $packages->contains(fn (Package $package) => $discount->appliesToPackage($package->package_id))
            )
            ->values();
        $automaticDiscounts = Discount::query()
            ->automaticAvailable()
            ->with('tryout:tryout_id,name')
            ->orderBy('created_at', 'desc')
            ->get();
        $packageAutomaticDiscounts = $this->automaticDiscountsForPackages($packages, $automaticDiscounts);
        $affiliateDiscountPreview = $this->affiliateDiscountPreview();
        
        return view('user.pages.package.new-index', compact(
            'packages',
            'tab',
            'userOwnedPackageIds',
            'pendingConditionalPackageIds',
            'pendingPackagePaymentsByPackage',
            'manualPaymentUniqueCodes',
            'publicDiscounts',
            'packageAutomaticDiscounts',
            'affiliateDiscountPreview',
            'search',
            'sort'
        ));
    }

    public function buyPackage(Request $request, $package_id)
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Silakan login terlebih dahulu untuk membeli paket.'
            ], 401);
        }
        
        try {
            $package = Package::where('status', 'active')
                ->where('is_displayed', true)
                ->findOrFail($package_id);

            $existingAccess = UserPackageAcces::where('user_id', Auth::id())
                ->where('package_id', $package_id)
                ->first();

            if (
                $existingAccess
                && $existingAccess->status === 'active'
                && (is_null($existingAccess->end_date) || Carbon::parse($existingAccess->end_date)->greaterThan(Carbon::now()))
            ) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Anda sudah memiliki akses aktif ke paket ini'
                    ], 400);
                }
                return redirect()->route('user.package.my')->with('error', 'Anda sudah memiliki akses aktif ke paket ini.');
            }

            switch ($package->type_price) {
                case 'free_unconditional':
                    $this->grantFreeAccess($package);

                    return response()->json([
                        'success' => true,
                        'message' => 'Paket gratis berhasil diaktifkan!'
                    ]);

                case 'free_conditional':
                    $validated = $request->validate([
                        'requirement_proofs' => 'required|array|min:1',
                        'requirement_proofs.*' => 'required|file|mimes:jpg,jpeg,png,pdf,mp4,webm|max:2048',
                        'requirement_user_notes' => 'nullable|string|max:1000',
                    ], [
                        'requirement_proofs.required' => 'Bukti pemenuhan syarat wajib diunggah.',
                        'requirement_proofs.array' => 'Bukti pemenuhan syarat tidak valid.',
                        'requirement_proofs.min' => 'Minimal unggah 1 bukti syarat.',
                        'requirement_proofs.*.required' => 'Bukti pemenuhan syarat wajib diunggah.',
                        'requirement_proofs.*.mimes' => 'Format bukti harus berupa JPG, PNG, PDF, MP4, atau WEBM.',
                        'requirement_proofs.*.max' => 'Ukuran setiap file maksimal 2MB.',
                        'requirement_user_notes.max' => 'Catatan maksimal 1000 karakter.',
                    ]);

                    $this->saveConditionalRequest(
                        $package,
                        $existingAccess,
                        $request->file('requirement_proofs', []),
                        $validated['requirement_user_notes'] ?? null
                    );

                    return response()->json([
                        'success' => true,
                        'message' => 'Bukti berhasil dikirim. Mohon tunggu verifikasi admin.'
                    ]);

                case 'paid':
                default:
                    if ($package->price <= 0) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Paket berbayar memerlukan harga yang valid.'
                        ], 400);
                    }

                    $discountData = $this->resolveDiscount($request, $package);
                    if ($discountData['error']) {
                        if ($request->expectsJson()) {
                            return response()->json([
                                'success' => false,
                                'message' => $discountData['error'],
                            ], 422);
                        }

                        return redirect()->back()->with('error', $discountData['error']);
                    }

                    $payableAmount = $discountData['payable_amount'];
                    if ($payableAmount <= 0) {
                        $payment = Payment::create([
                            'transaction_id' => 'DISC-' . $package->package_id . '-' . Auth::id() . '-' . time(),
                            'user_id' => Auth::id(),
                            'package_id' => $package->package_id,
                            'discount_id' => $discountData['discount']?->id,
                            'discount_code' => $discountData['discount_code'],
                            'original_amount' => (int) $package->price,
                            'discount_amount' => $discountData['discount_amount'],
                            'amount' => 0,
                            'admin_fee' => 0,
                            'total_amount' => 0,
                            'status' => Payment::STATUS_SUCCESS,
                            'payment_method' => 'discount',
                            'paid_at' => Carbon::now(),
                            'payment_details' => json_encode([
                                'base_amount' => (int) $package->price,
                                'discount_code' => $discountData['discount_code'],
                                'discount_amount' => $discountData['discount_amount'],
                            ]),
                        ]);

                        $this->recordDiscountUsage($discountData['discount']);
                        $this->ensureUserPackageAccess($payment, 'Access activated by full discount');

                        return response()->json([
                            'success' => true,
                            'message' => ($discountData['source'] === 'voucher' ? 'Kode diskon berhasil digunakan.' : 'Diskon berhasil diterapkan.') . ' Paket sudah aktif.',
                            'redirect_url' => route('user.package.my'),
                        ]);
                    }

                    $paymentMode = strtolower((string) config('client.branding.payment_mode', 'gateway'));
                    if ($paymentMode === 'manual') {
                        $pendingManualPayment = $this->pendingPackagePayment($package, ['manual']);
                        if ($pendingManualPayment) {
                            $message = 'Bukti pembayaran untuk paket ini masih menunggu verifikasi admin.';

                            if ($request->expectsJson()) {
                                return response()->json([
                                    'success' => true,
                                    'message' => $message,
                                    'redirect_url' => route('user.package.riwayatPembelian'),
                                ]);
                            }

                            return redirect()
                                ->route('user.package.riwayatPembelian')
                                ->with('info', $message);
                        }

                        $paymentUniqueCodeEnabled = (bool) config('client.branding.payment_unique_code_enabled', true);
                        $rules = [
                            'payment_proof' => 'required|file|mimes:jpg,jpeg,png,pdf|max:20480',
                        ];

                        if ($paymentUniqueCodeEnabled) {
                            $rules['payment_unique_code'] = 'required|integer|min:1|max:999';
                        }

                        $validated = $request->validate($rules, [
                            'payment_proof.required' => 'Bukti pembayaran wajib diunggah.',
                            'payment_proof.mimes' => 'Format bukti harus berupa JPG, PNG, atau PDF.',
                            'payment_proof.max' => 'Ukuran bukti maksimal 20MB.',
                            'payment_unique_code.required' => 'Kode unik pembayaran tidak valid. Silakan buka ulang modal pembayaran.',
                        ]);

                        $uniqueCode = $paymentUniqueCodeEnabled
                            ? (int) $validated['payment_unique_code']
                            : 0;

                        if ($paymentUniqueCodeEnabled && !Payment::isManualUniqueCodeAvailable($uniqueCode)) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Kode unik pembayaran sudah dipakai. Silakan buka ulang modal pembayaran.'
                            ], 409);
                        }

                        $proofPath = $validated['payment_proof']->store('payment-proofs', 'public');
                        $transactionId = 'MANUAL-' . $package->package_id . '-' . Auth::id() . '-' . time();
                        $totalAmount = $payableAmount + $uniqueCode;

                        Payment::create([
                            'transaction_id' => $transactionId,
                            'user_id' => Auth::id(),
                            'package_id' => $package->package_id,
                            'discount_id' => $discountData['discount']?->id,
                            'discount_code' => $discountData['discount_code'],
                            'original_amount' => (int) $package->price,
                            'discount_amount' => $discountData['discount_amount'],
                            'amount' => $payableAmount,
                            'admin_fee' => 0,
                            'unique_code' => $paymentUniqueCodeEnabled ? $uniqueCode : null,
                            'unique_code_date' => $paymentUniqueCodeEnabled ? now()->toDateString() : null,
                            'total_amount' => $totalAmount,
                            'status' => Payment::STATUS_PENDING,
                            'payment_method' => 'manual',
                            'payment_details' => json_encode([
                                'proof_path' => $proofPath,
                                'proof_name' => $validated['payment_proof']->getClientOriginalName(),
                                'base_amount' => (int) $package->price,
                                'discount_code' => $discountData['discount_code'],
                                'discount_amount' => $discountData['discount_amount'],
                                'payable_amount' => $payableAmount,
                                'unique_code' => $paymentUniqueCodeEnabled ? $uniqueCode : null,
                            ]),
                        ]);

                        $this->recordDiscountUsage($discountData['discount']);

                        return response()->json([
                            'success' => true,
                            'message' => 'Bukti pembayaran berhasil dikirim. Mohon tunggu verifikasi admin.'
                        ]);
                    }

                    $pendingGatewayPayment = $this->reusablePendingPackageGatewayPayment($package);
                    if ($pendingGatewayPayment) {
                        $redirectUrl = $this->pendingGatewayPaymentRedirectUrl($pendingGatewayPayment);

                        if ($redirectUrl) {
                            if ($request->expectsJson()) {
                                return response()->json([
                                    'success' => true,
                                    'message' => 'Anda masih memiliki tagihan pending untuk paket ini. Silakan lanjutkan pembayaran sebelumnya.',
                                    'redirect_url' => $redirectUrl,
                                ]);
                            }

                            return redirect()->away($redirectUrl);
                        }
                    }

                    $paymentResponse = $this->createPayment($package, $discountData);

                    if ($paymentResponse['success']) {
                        // For AJAX requests, return JSON; for native form submit, redirect directly
                        if ($request->expectsJson()) {
                            return response()->json([
                                'success' => true,
                                'redirect_url' => $paymentResponse['redirect_url']
                            ]);
                        }
                        return redirect()->away($paymentResponse['redirect_url']);
                    }

                    if ($request->expectsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => $paymentResponse['message']
                        ], 500);
                    }
                    return redirect()->back()->with('error', $paymentResponse['message']);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function previewDiscount(Request $request, $package_id)
    {
        $package = Package::where('status', 'active')
            ->where('is_displayed', true)
            ->findOrFail($package_id);

        if ($package->type_price !== 'paid' || $package->price <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Diskon hanya berlaku untuk paket berbayar.',
            ], 422);
        }

        $discountData = $this->resolveDiscount($request, $package);
        if ($discountData['error']) {
            return response()->json([
                'success' => false,
                'message' => $discountData['error'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'code' => $discountData['discount_code'],
            'source' => $discountData['source'],
            'label' => $discountData['label'],
            'original_amount' => (int) $package->price,
            'discount_amount' => $discountData['discount_amount'],
            'payable_amount' => $discountData['payable_amount'],
        ]);
    }

    private function createPayment(Package $package, array $discountData)
    {
        $gateway = strtolower((string) config('services.payment_gateway', 'xendit'));

        if ($gateway === 'midtrans') {
            return $this->createMidtransPayment($package, $discountData);
        }

        $handler = config("payment_gateways.gateways.{$gateway}.handler");
        if ($handler && method_exists($handler, 'createPackagePayment')) {
            $paymentResponse = app($handler)->createPackagePayment($package, $discountData);

            if ($paymentResponse['success'] ?? false) {
                $this->recordDiscountUsage($discountData['discount']);
            }

            return $paymentResponse;
        }

        return $this->createXenditPayment($package, $discountData);
    }

    private function pendingPackagePayment(Package $package, array $methods): ?Payment
    {
        return Payment::query()
            ->where('user_id', Auth::id())
            ->where('package_id', $package->package_id)
            ->where('status', Payment::STATUS_PENDING)
            ->whereIn('payment_method', $methods)
            ->latest()
            ->first();
    }

    private function pendingPackagePaymentsForPackages(array $packageIds)
    {
        if (empty($packageIds)) {
            return collect();
        }

        return Payment::query()
            ->where('user_id', Auth::id())
            ->whereIn('package_id', $packageIds)
            ->where('status', Payment::STATUS_PENDING)
            ->whereIn('payment_method', ['manual', 'xendit', 'midtrans', 'ipaymu', 'interactive_qris'])
            ->latest()
            ->get()
            ->filter(function (Payment $payment) {
                if ($payment->payment_method === 'manual') {
                    return true;
                }

                if ($this->pendingGatewayPaymentIsExpired($payment)) {
                    $payment->update(['status' => Payment::STATUS_EXPIRED]);
                    return false;
                }

                return (bool) $this->pendingGatewayPaymentRedirectUrl($payment);
            })
            ->unique('package_id')
            ->keyBy('package_id');
    }

    private function reusablePendingPackageGatewayPayment(Package $package): ?Payment
    {
        $pendingPayments = Payment::query()
            ->where('user_id', Auth::id())
            ->where('package_id', $package->package_id)
            ->where('status', Payment::STATUS_PENDING)
            ->whereIn('payment_method', ['xendit', 'midtrans', 'ipaymu', 'interactive_qris'])
            ->latest()
            ->get();

        foreach ($pendingPayments as $payment) {
            if ($this->pendingGatewayPaymentIsExpired($payment)) {
                $payment->update(['status' => Payment::STATUS_EXPIRED]);
                continue;
            }

            if ($this->pendingGatewayPaymentRedirectUrl($payment)) {
                return $payment;
            }
        }

        return null;
    }

    private function pendingGatewayPaymentRedirectUrl(Payment $payment): ?string
    {
        $details = $payment->paymentDetailsArray();

        return match ($payment->payment_method) {
            'interactive_qris' => route('user.package.payment.qris.show', $payment->transaction_id),
            'xendit' => $details['invoice_url'] ?? null,
            'midtrans', 'ipaymu' => $details['redirect_url'] ?? null,
            default => null,
        };
    }

    private function pendingGatewayPaymentIsExpired(Payment $payment): bool
    {
        $details = $payment->paymentDetailsArray();
        $expiresAt = $details['expires_at']
            ?? $details['expiry_date']
            ?? $details['expiration_date']
            ?? null;

        if ($expiresAt) {
            return Carbon::parse($expiresAt)->isPast();
        }

        $createdAt = $payment->created_at ?: Carbon::now();

        if ($payment->payment_method === 'interactive_qris') {
            return $createdAt->copy()->addMinutes(30)->isPast();
        }

        return $createdAt->copy()->addDay()->isPast();
    }

    private function resolveDiscount(Request $request, Package $package): array
    {
        $amount = (int) $package->price;
        $code = Discount::normalizeCode($request->input('discount_code'));

        if (!$code) {
            $automaticDiscount = $this->automaticDiscountForPackage($package);
            $automaticDiscountAmount = $automaticDiscount?->calculateDiscountAmount($amount) ?? 0;
            $user = Auth::user();
            $setting = AffiliateSetting::current();
            $hasPackagePayment = Payment::query()
                ->where('user_id', Auth::id())
                ->whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_SUCCESS])
                ->exists();

            $affiliateDiscountAmount = 0;
            if ($user?->referred_by_user_id && !$hasPackagePayment && $setting->invitee_discount_enabled) {
                $affiliateDiscountAmount = $setting->calculateInviteeDiscount($amount);
            }

            if ($automaticDiscount && $automaticDiscountAmount >= $affiliateDiscountAmount && $automaticDiscountAmount > 0) {
                return [
                    'discount' => $automaticDiscount,
                    'discount_code' => null,
                    'discount_amount' => $automaticDiscountAmount,
                    'payable_amount' => max(0, $amount - $automaticDiscountAmount),
                    'source' => 'automatic',
                    'label' => $automaticDiscount->name ?: 'Diskon otomatis',
                    'error' => null,
                ];
            }

            if ($affiliateDiscountAmount > 0) {
                return [
                    'discount' => null,
                    'discount_code' => 'REFERRAL',
                    'discount_amount' => $affiliateDiscountAmount,
                    'payable_amount' => max(0, $amount - $affiliateDiscountAmount),
                    'source' => 'referral',
                    'label' => 'Diskon referral',
                    'error' => null,
                ];
            }

            return [
                'discount' => null,
                'discount_code' => null,
                'discount_amount' => 0,
                'payable_amount' => $amount,
                'source' => null,
                'label' => null,
                'error' => null,
            ];
        }

        $discount = Discount::query()->voucher()->where('code', $code)->first();
        if (!$discount) {
            return [
                'discount' => null,
                'discount_code' => null,
                'discount_amount' => 0,
                'payable_amount' => $amount,
                'source' => null,
                'label' => null,
                'error' => 'Kode diskon tidak ditemukan.',
            ];
        }

        $error = $discount->validationErrorFor($amount, Auth::id(), $package->package_id, 'package');
        if ($error) {
            return [
                'discount' => $discount,
                'discount_code' => null,
                'discount_amount' => 0,
                'payable_amount' => $amount,
                'source' => null,
                'label' => null,
                'error' => $error,
            ];
        }

        $discountAmount = $discount->calculateDiscountAmount($amount);

        return [
            'discount' => $discount,
            'discount_code' => $discount->code,
            'discount_amount' => $discountAmount,
            'payable_amount' => max(0, $amount - $discountAmount),
            'source' => 'voucher',
            'label' => $discount->name ?: $discount->code,
            'error' => null,
        ];
    }

    private function automaticDiscountForPackage(Package $package): ?Discount
    {
        $tryoutIds = $this->packageTryoutIds($package);

        if (empty($tryoutIds)) {
            return null;
        }

        $discounts = Discount::query()
            ->automaticAvailable()
            ->whereIn('tryout_id', $tryoutIds)
            ->with('tryout:tryout_id,name')
            ->get();

        return $this->bestAutomaticDiscount($package, $discounts);
    }

    private function automaticDiscountsForPackages($packages, $automaticDiscounts): array
    {
        $result = [];

        foreach ($packages as $package) {
            $discount = $this->bestAutomaticDiscount(
                $package,
                $automaticDiscounts->whereIn('tryout_id', $this->packageTryoutIds($package))->values()
            );

            if (!$discount) {
                continue;
            }

            $discountAmount = $discount->calculateDiscountAmount((int) $package->price);
            if ($discountAmount <= 0) {
                continue;
            }

            $result[$package->package_id] = [
                'id' => $discount->id,
                'name' => $discount->name ?: 'Diskon otomatis',
                'description' => $discount->description,
                'tryout_title' => $discount->tryout?->name,
                'formatted_value' => $discount->formatted_value,
                'discount_type' => $discount->discount_type,
                'discount_value' => (float) $discount->discount_value,
                'max_discount_amount' => $discount->max_discount_amount !== null ? (float) $discount->max_discount_amount : null,
                'discount_amount' => $discountAmount,
                'final_price' => max(0, (int) $package->price - $discountAmount),
                'ends_at' => $discount->ends_at ? $discount->ends_at->toIso8601String() : null,
            ];
        }

        return $result;
    }

    private function bestAutomaticDiscount(Package $package, $discounts): ?Discount
    {
        $amount = (int) $package->price;
        $userId = (int) (Auth::id() ?? 0);

        return $discounts
            ->filter(fn (Discount $discount) => $discount->validationErrorFor($amount, $userId, $package->package_id, 'package') === null)
            ->sortByDesc(fn (Discount $discount) => $discount->calculateDiscountAmount($amount))
            ->first();
    }

    private function packageTryoutIds(Package $package): array
    {
        $tryoutIds = collect();

        if ($package->relationLoaded('detailPackages')) {
            $tryoutIds = $tryoutIds->merge(
                $package->detailPackages
                    ->where('detailable_type', Tryout::class)
                    ->pluck('detailable_id')
            );
        } else {
            $tryoutIds = $tryoutIds->merge(
                $package->detailPackages()
                    ->where('detailable_type', Tryout::class)
                    ->pluck('detailable_id')
            );
        }

        return $tryoutIds->map(fn ($id) => (int) $id)->filter()->unique()->values()->all();
    }

    private function affiliateDiscountPreview(?int $amount = null): ?array
    {
        if (!config('client.branding.affiliate_menu_enabled', false)) {
            return null;
        }

        if (!Auth::check()) {
            return null;
        }

        $user = Auth::user();
        if (!$user?->referred_by_user_id) {
            return null;
        }

        $hasPackagePayment = Payment::query()
            ->where('user_id', Auth::id())
            ->whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_SUCCESS])
            ->exists();

        if ($hasPackagePayment) {
            return null;
        }

        $setting = AffiliateSetting::current();
        if (!$setting->is_active || !$setting->invitee_discount_enabled || (float) $setting->invitee_discount_value <= 0) {
            return null;
        }

        $discountLabel = $setting->invitee_discount_type === 'fixed'
            ? 'Rp ' . number_format((float) $setting->invitee_discount_value, 0, ',', '.')
            : rtrim(rtrim(number_format((float) $setting->invitee_discount_value, 2, ',', '.'), '0'), ',') . '%';

        return [
            'code' => 'REFERRAL',
            'label' => $discountLabel,
            'amount' => $amount !== null ? $setting->calculateInviteeDiscount($amount) : null,
            'payable_amount' => $amount !== null ? max(0, $amount - $setting->calculateInviteeDiscount($amount)) : null,
            'max_discount_amount' => $setting->invitee_max_discount_amount,
        ];
    }

    private function recordDiscountUsage(?Discount $discount): void
    {
        if (!$discount) {
            return;
        }

        $discount->increment('used_count');
    }

    private function createXenditPayment(Package $package, array $discountData)
    {
        $secretKey = config('services.xendit.secret_key');

        if (!$secretKey) {
            return [
                'success' => false,
                'message' => 'Xendit secret key tidak dikonfigurasi'
            ];
        }

        $transactionId = 'PKG-' . $package->package_id . '-' . Auth::id() . '-' . time();
        $baseUrl = rtrim(config('services.xendit.base_url', 'https://api.xendit.co'), '/');
        $amount = (int) round($discountData['payable_amount']);
        $uniqueCode = $this->paymentUniqueCodeFor($amount);
        $totalAmount = $amount + ($uniqueCode ?? 0);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode($secretKey . ':'),
                'Content-Type' => 'application/json',
            ])->post($baseUrl . '/v2/invoices', [
                        'external_id' => $transactionId,
                        'amount' => $totalAmount,
                        'description' => 'Pembelian ' . $package->name,
                        'invoice_duration' => 86400, // 24 hours
                        'customer' => [
                            'given_names' => Auth::user()->name,
                            'email' => Auth::user()->email,
                        ],
                        'customer_notification_preference' => [
                            'invoice_created' => ['email'],
                            'invoice_reminder' => ['email'],
                            'invoice_paid' => ['email'],
                        ],
                        'success_redirect_url' => route('user.package.payment.success'),
                        'failure_redirect_url' => route('user.package.payment.failed'),
                    ]);

            if ($response->successful()) {
                $invoiceData = $response->json();

                // Save payment record
                Payment::create([
                    'transaction_id' => $transactionId,
                    'user_id' => Auth::id(),
                    'package_id' => $package->package_id,
                    'discount_id' => $discountData['discount']?->id,
                    'discount_code' => $discountData['discount_code'],
                    'original_amount' => (int) $package->price,
                    'discount_amount' => $discountData['discount_amount'],
                    'amount' => $amount,
                    'admin_fee' => 0,
                    'unique_code' => $uniqueCode,
                    'unique_code_date' => $uniqueCode ? now()->toDateString() : null,
                    'total_amount' => $totalAmount,
                    'status' => Payment::STATUS_PENDING,
                    'payment_method' => 'xendit',
                    'payment_details' => json_encode([
                        'invoice_id' => $invoiceData['id'],
                        'invoice_url' => $invoiceData['invoice_url'],
                        'external_id' => $transactionId,
                        'base_amount' => (int) $package->price,
                        'payable_amount' => $amount,
                        'unique_code' => $uniqueCode,
                        'discount_code' => $discountData['discount_code'],
                        'discount_amount' => $discountData['discount_amount'],
                    ]),
                ]);

                $this->recordDiscountUsage($discountData['discount']);

                return [
                    'success' => true,
                    'redirect_url' => $invoiceData['invoice_url']
                ];
            } else {
                $errorMessage = 'Gagal membuat pembayaran';
                if ($response->json() && isset($response->json()['message'])) {
                    $errorMessage = $response->json()['message'];
                }

                return [
                    'success' => false,
                    'message' => $errorMessage
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error koneksi ke Xendit: ' . $e->getMessage()
            ];
        }
    }

    private function createMidtransPayment(Package $package, array $discountData)
    {
        $serverKey = config('services.midtrans.server_key');
        $snapUrl = config('services.midtrans.snap_url', 'https://app.sandbox.midtrans.com/snap/v1/transactions');

        if (!$serverKey) {
            return [
                'success' => false,
                'message' => 'Midtrans server key tidak dikonfigurasi'
            ];
        }

        $transactionId = 'PKG-' . $package->package_id . '-' . Auth::id() . '-' . time();
        $amount = (int) round($discountData['payable_amount']);
        $uniqueCode = $this->paymentUniqueCodeFor($amount);
        $totalAmount = $amount + ($uniqueCode ?? 0);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode($serverKey . ':'),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post($snapUrl, [
                        'transaction_details' => [
                            'order_id' => $transactionId,
                            'gross_amount' => $totalAmount,
                        ],
                        'item_details' => [
                            [
                                'id' => (string) $package->package_id,
                                'price' => $totalAmount,
                                'quantity' => 1,
                                'name' => Str::limit($package->name, 50, ''),
                            ],
                        ],
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

            if ($response->successful()) {
                $data = $response->json();

                Payment::create([
                    'transaction_id' => $transactionId,
                    'user_id' => Auth::id(),
                    'package_id' => $package->package_id,
                    'discount_id' => $discountData['discount']?->id,
                    'discount_code' => $discountData['discount_code'],
                    'original_amount' => (int) $package->price,
                    'discount_amount' => $discountData['discount_amount'],
                    'amount' => $amount,
                    'admin_fee' => 0,
                    'unique_code' => $uniqueCode,
                    'unique_code_date' => $uniqueCode ? now()->toDateString() : null,
                    'total_amount' => $totalAmount,
                    'status' => Payment::STATUS_PENDING,
                    'payment_method' => 'midtrans',
                    'payment_details' => json_encode([
                        'snap_token' => $data['token'] ?? null,
                        'redirect_url' => $data['redirect_url'] ?? null,
                        'external_id' => $transactionId,
                        'base_amount' => (int) $package->price,
                        'payable_amount' => $amount,
                        'unique_code' => $uniqueCode,
                        'discount_code' => $discountData['discount_code'],
                        'discount_amount' => $discountData['discount_amount'],
                    ]),
                ]);

                $this->recordDiscountUsage($discountData['discount']);

                return [
                    'success' => true,
                    'redirect_url' => $data['redirect_url'] ?? null,
                ];
            }

            $errorMessage = 'Gagal membuat pembayaran';
            if ($response->json() && isset($response->json()['status_message'])) {
                $errorMessage = $response->json()['status_message'];
            }

            return [
                'success' => false,
                'message' => $errorMessage,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error koneksi ke Midtrans: ' . $e->getMessage(),
            ];
        }
    }

    private function paymentUniqueCodeFor(int $amount): ?int
    {
        if ($amount <= 0 || !(bool) config('client.branding.payment_unique_code_enabled', true)) {
            return null;
        }

        return Payment::generateManualUniqueCode();
    }

    private function grantFreeAccess(Package $package): void
    {
        $startDate = Carbon::now();

        UserPackageAcces::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'package_id' => $package->package_id,
            ],
            [
                'start_date' => $startDate,
                'end_date' => PurchaseAccessDuration::expiresAt($package, $startDate),
                'status' => 'active',
                'payment_amount' => 0,
                'payment_status' => 'free',
                'notes' => 'Free package access',
                'created_by' => Auth::id(),
                'requirement_proof_path' => null,
                'requirement_review_notes' => null,
                'requirement_status' => 'none',
            ]
        );
    }

    private function saveConditionalRequest(Package $package, ?UserPackageAcces $existingAccess, array $proofs, ?string $userNotes = null): void
    {
        $proofPaths = collect($proofs)
            ->map(fn (\Illuminate\Http\UploadedFile $proof) => $proof->store('conditional-proofs', 'public'))
            ->values()
            ->all();

        $access = $existingAccess ?: new UserPackageAcces([
            'user_id' => Auth::id(),
            'package_id' => $package->package_id,
        ]);

        $oldProofPaths = collect($access->requirement_proof_paths ?? [])
            ->when($access->requirement_proof_path, fn ($paths) => $paths->push($access->requirement_proof_path))
            ->filter()
            ->unique();

        foreach ($oldProofPaths as $oldProofPath) {
            if (Storage::disk('public')->exists($oldProofPath)) {
                Storage::disk('public')->delete($oldProofPath);
            }
        }

        $access->fill([
            'start_date' => null,
            'end_date' => null,
            'status' => 'pending',
            'payment_amount' => 0,
            'payment_status' => 'conditional',
            'notes' => $package->conditional_requirement,
            'requirement_proof_path' => $proofPaths[0] ?? null,
            'requirement_proof_paths' => $proofPaths,
            'requirement_user_notes' => $userNotes ? trim($userNotes) : null,
            'requirement_review_notes' => null,
            'requirement_status' => 'pending',
            'created_by' => Auth::id(),
        ]);

        $access->save();
    }

    public function riwayatPembelian(Request $request)
    {
        $payments = Payment::where('user_id', Auth::id())
            ->with('package')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function (Payment $payment) {
                $actionUrl = null;
                $actionLabel = null;

                if ($payment->status === Payment::STATUS_PENDING && $payment->payment_method !== 'manual') {
                    if ($this->pendingGatewayPaymentIsExpired($payment)) {
                        $payment->update(['status' => Payment::STATUS_EXPIRED]);
                        $payment->refresh();
                    } else {
                        $actionUrl = $this->pendingGatewayPaymentRedirectUrl($payment);
                        $actionLabel = 'Lanjutkan Pembayaran';
                    }
                }

                if (
                    $payment->status === Payment::STATUS_EXPIRED
                    && $payment->package
                    && $payment->package->status === 'active'
                    && $payment->package->is_displayed
                ) {
                    $actionUrl = route('user.package.detail', $payment->package_id);
                    $actionLabel = 'Checkout Ulang';
                }

                return (object) [
                    'type' => 'package',
                    'title' => $payment->package->name ?? 'Paket',
                    'subtitle' => 'Paket',
                    'transaction_id' => $payment->transaction_id,
                    'amount' => (float) $payment->total_amount,
                    'status' => $payment->status,
                    'payment_method' => $payment->payment_method,
                    'created_at' => $payment->created_at,
                    'action_url' => $actionUrl,
                    'action_label' => $actionLabel,
                ];
            });

        $individualPurchases = IndividualPurchase::where('user_id', Auth::id())
            ->with('purchasable')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function (IndividualPurchase $purchase) {
                $item = $purchase->purchasable;
                $subtitle = match (class_basename($purchase->purchasable_type)) {
                    'Material' => 'Materi',
                    'Tryout' => 'Tryout',
                    'TesKoran' => 'Tes Koran',
                    default => 'Item',
                };
                $actionUrl = null;
                $actionLabel = null;

                if ($purchase->status === IndividualPurchase::STATUS_PENDING && !in_array($purchase->payment_method, ['manual', 'free_conditional'], true)) {
                    if ($this->pendingIndividualPurchaseIsExpired($purchase)) {
                        $details = is_array($purchase->payment_details) ? $purchase->payment_details : [];
                        $details['auto_rejected_reason'] = 'Gateway payment expired before completion.';
                        $details['auto_rejected_at'] = now()->toDateTimeString();
                        $purchase->update([
                            'status' => IndividualPurchase::STATUS_REJECTED,
                            'payment_details' => $details,
                        ]);
                        $purchase->refresh();
                    } else {
                        $actionUrl = $this->pendingIndividualPurchaseRedirectUrl($purchase);
                        $actionLabel = 'Lanjutkan Pembayaran';
                    }
                }

                $isAutoExpired = $purchase->status === IndividualPurchase::STATUS_REJECTED && $this->individualPurchaseWasAutoExpired($purchase);

                if ($isAutoExpired) {
                    $actionUrl = $this->individualPurchaseRetryUrl($purchase);
                    $actionLabel = 'Checkout Ulang';
                }

                return (object) [
                    'type' => 'individual',
                    'title' => $item?->title ?? $item?->name ?? 'Item',
                    'subtitle' => $subtitle,
                    'transaction_id' => $purchase->transaction_id,
                    'amount' => (float) $purchase->total_amount,
                    'status' => $isAutoExpired ? 'expired' : $purchase->status,
                    'payment_method' => $purchase->payment_method,
                    'created_at' => $purchase->created_at,
                    'action_url' => $actionUrl,
                    'action_label' => $actionLabel,
                ];
            });

        $items = $payments
            ->merge($individualPurchases)
            ->sortByDesc('created_at')
            ->values();

        $perPage = 10;
        $page = (int) $request->get('page', 1);
        $histories = new \Illuminate\Pagination\LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('user.pages.package.riwayat-pembelian', compact('histories'));
    }

    private function pendingIndividualPurchaseRedirectUrl(IndividualPurchase $purchase): ?string
    {
        $details = $this->paymentDetailsArray($purchase->payment_details);

        return match ($purchase->payment_method) {
            'interactive_qris' => route('user.package.payment.qris.show', $purchase->transaction_id),
            'xendit' => $details['invoice_url'] ?? null,
            'midtrans', 'ipaymu' => $details['redirect_url'] ?? null,
            default => null,
        };
    }

    private function pendingIndividualPurchaseIsExpired(IndividualPurchase $purchase): bool
    {
        $details = $this->paymentDetailsArray($purchase->payment_details);
        $expiresAt = $details['expires_at']
            ?? $details['expired_at']
            ?? $details['expiry_date']
            ?? $details['expiration_date']
            ?? null;

        if ($expiresAt) {
            return Carbon::parse($expiresAt)->isPast();
        }

        if ($purchase->payment_method === 'interactive_qris') {
            return ($purchase->created_at ?: now())->copy()->addMinutes(30)->isPast();
        }

        if (in_array($purchase->payment_method, ['xendit', 'midtrans', 'ipaymu'], true)) {
            return ($purchase->created_at ?: now())->copy()->addDay()->isPast();
        }

        return false;
    }

    private function individualPurchaseWasAutoExpired(IndividualPurchase $purchase): bool
    {
        $details = $this->paymentDetailsArray($purchase->payment_details);
        $reason = strtolower((string) ($details['auto_rejected_reason'] ?? ''));

        return str_contains($reason, 'expired');
    }

    private function individualPurchaseRetryUrl(IndividualPurchase $purchase): ?string
    {
        return match ($purchase->purchasable_type) {
            Material::class => route('user.material.index'),
            Tryout::class => route('user.package.tryout.list'),
            TesKoran::class => route('user.tes-koran.index'),
            default => null,
        };
    }

    private function paymentDetailsArray(mixed $details): array
    {
        if (is_array($details)) {
            return $details;
        }

        return $details ? (json_decode($details, true) ?: []) : [];
    }

    private function checkIndividualQrisPurchase(IndividualPurchase $purchase): array
    {
        $apiKey = config('services.interactive_qris.api_key');
        $merchantId = config('services.interactive_qris.mid');
        $details = $this->paymentDetailsArray($purchase->payment_details);
        $invoiceId = $details['qris_invoiceid'] ?? null;
        $transactionDate = Carbon::parse($details['qris_request_date'] ?? $purchase->created_at)->toDateString();

        if (!$apiKey || !$merchantId) {
            return [
                'success' => false,
                'message' => 'Credential InterActive QRIS belum dikonfigurasi.',
            ];
        }

        if (!$invoiceId) {
            return [
                'success' => false,
                'message' => 'Invoice ID QRIS tidak ditemukan.',
            ];
        }

        $response = Http::timeout(20)->get(rtrim(config('services.interactive_qris.base_url', 'https://qris.interactive.co.id/restapi/qris'), '/') . '/checkpaid_qris.php', [
            'do' => 'checkStatus',
            'apikey' => $apiKey,
            'mID' => $merchantId,
            'invid' => $invoiceId,
            'trxvalue' => (int) $purchase->total_amount,
            'trxdate' => $transactionDate,
        ]);

        if (!$response->successful()) {
            return [
                'success' => false,
                'message' => 'Gagal mengecek status InterActive QRIS.',
            ];
        }

        $payload = $response->json();
        $status = $payload['data']['qris_status'] ?? null;

        if (($payload['status'] ?? null) === 'success' && $status === 'paid') {
            $details['qris_paid_status'] = $payload['data'] ?? [];
            $details['qris_api_version_code'] = $payload['qris_api_version_code'] ?? null;

            $purchase->update([
                'status' => IndividualPurchase::STATUS_APPROVED,
                'approved_at' => now(),
                'payment_details' => $details,
            ]);

            return [
                'success' => true,
                'paid' => true,
                'message' => 'Pembayaran QRIS berhasil dikonfirmasi.',
                'data' => $payload,
            ];
        }

        if ($this->pendingIndividualPurchaseIsExpired($purchase)) {
            $details['auto_rejected_reason'] = 'Gateway payment expired before completion.';
            $details['auto_rejected_at'] = now()->toDateTimeString();
            $purchase->update([
                'status' => IndividualPurchase::STATUS_REJECTED,
                'payment_details' => $details,
            ]);

            return [
                'success' => true,
                'paid' => false,
                'expired' => true,
                'message' => 'QRIS sudah kedaluwarsa. Silakan buat pembayaran ulang.',
                'data' => $payload,
            ];
        }

        return [
            'success' => true,
            'paid' => false,
            'expired' => false,
            'message' => 'Pembayaran belum ditemukan. Coba cek lagi setelah beberapa saat.',
            'data' => $payload,
        ];
    }

    private function ensureIndividualPurchaseAccess(IndividualPurchase $purchase, string $notes): void
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

        $details = $this->paymentDetailsArray($purchase->payment_details);
        $details['access_activation_notes'] = $notes;

        $purchase->update([
            'status' => IndividualPurchase::STATUS_APPROVED,
            'approved_at' => $approvedAt,
            'access_expires_at' => $accessExpiresAt,
            'payment_details' => $details,
        ]);
    }

    public function riwayatPembelianAktif()
    {
        $activePackages = UserPackageAcces::where('user_id', Auth::id())
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>', Carbon::now());
            })
            ->with('package')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('user.pages.package.riwayat-pembelian-aktif', compact('activePackages'));
    }

    public function showQrisPayment(string $transactionId, InteractiveQrisGateway $gateway)
    {
        $payment = Payment::with('package')
            ->where('transaction_id', $transactionId)
            ->where('user_id', Auth::id())
            ->where('payment_method', 'interactive_qris')
            ->first();

        $individualPurchase = null;
        if (!$payment) {
            $individualPurchase = IndividualPurchase::with('purchasable')
                ->where('transaction_id', $transactionId)
                ->where('user_id', Auth::id())
                ->where('payment_method', 'interactive_qris')
                ->firstOrFail();
        }

        $payable = $payment ?: $individualPurchase;
        $paymentDetails = $this->paymentDetailsArray($payable->payment_details);
        $qrisContent = $paymentDetails['qris_content'] ?? null;

        if (!$qrisContent) {
            return redirect()
                ->route('user.package.riwayatPembelian')
                ->with('error', 'Konten QRIS tidak ditemukan.');
        }

        if ($payment && $payment->status === Payment::STATUS_PENDING && $gateway->isExpired($payment)) {
            $payment->update(['status' => Payment::STATUS_EXPIRED]);
            $payment->refresh();
        }

        if ($individualPurchase && $individualPurchase->status === IndividualPurchase::STATUS_PENDING && $this->pendingIndividualPurchaseIsExpired($individualPurchase)) {
            $paymentDetails['auto_rejected_reason'] = 'Gateway payment expired before completion.';
            $paymentDetails['auto_rejected_at'] = now()->toDateTimeString();
            $individualPurchase->update([
                'status' => IndividualPurchase::STATUS_REJECTED,
                'payment_details' => $paymentDetails,
            ]);
            $individualPurchase->refresh();
            $payable = $individualPurchase;
        }

        $qrisImage = base64_encode(QrCode::format('png')->size(280)->margin(1)->generate($qrisContent));
        $payment = $payable;
        $paymentTitle = $payable instanceof Payment
            ? ($payable->package->name ?? 'Paket')
            : ($payable->purchasable?->title ?? $payable->purchasable?->name ?? 'Item');

        return view('user.pages.package.payment-qris', compact('payment', 'paymentDetails', 'qrisImage', 'paymentTitle'));
    }

    public function checkQrisPayment(string $transactionId, InteractiveQrisGateway $gateway)
    {
        $payment = Payment::with('package')
            ->where('transaction_id', $transactionId)
            ->where('user_id', Auth::id())
            ->where('payment_method', 'interactive_qris')
            ->first();

        if (!$payment) {
            $individualPurchase = IndividualPurchase::with('purchasable')
                ->where('transaction_id', $transactionId)
                ->where('user_id', Auth::id())
                ->where('payment_method', 'interactive_qris')
                ->firstOrFail();

            if ($individualPurchase->status === IndividualPurchase::STATUS_APPROVED) {
                $this->ensureIndividualPurchaseAccess($individualPurchase, 'Payment confirmed via InterActive QRIS');

                return response()->json([
                    'success' => true,
                    'paid' => true,
                    'message' => 'Pembayaran sudah dikonfirmasi.',
                    'redirect_url' => route('user.package.my'),
                ]);
            }

            $result = $this->checkIndividualQrisPurchase($individualPurchase);
            $individualPurchase->refresh();

            if (($result['paid'] ?? false) && $individualPurchase->status === IndividualPurchase::STATUS_APPROVED) {
                $this->ensureIndividualPurchaseAccess($individualPurchase, 'Payment confirmed via InterActive QRIS');

                return response()->json([
                    ...$result,
                    'redirect_url' => route('user.package.my'),
                ]);
            }

            return response()->json($result, ($result['success'] ?? false) ? 200 : 422);
        }

        if ($payment->status === Payment::STATUS_SUCCESS) {
            $this->ensureUserPackageAccess($payment, 'Payment confirmed via InterActive QRIS');

            return response()->json([
                'success' => true,
                'paid' => true,
                'message' => 'Pembayaran sudah dikonfirmasi.',
                'redirect_url' => route('user.package.my'),
            ]);
        }

        $result = $gateway->checkPayment($payment);
        $payment->refresh();

        if (($result['paid'] ?? false) && $payment->status === Payment::STATUS_SUCCESS) {
            $this->ensureUserPackageAccess($payment, 'Payment confirmed via InterActive QRIS');

            return response()->json([
                ...$result,
                'redirect_url' => route('user.package.my'),
            ]);
        }

        return response()->json($result, ($result['success'] ?? false) ? 200 : 422);
    }

    public function indexBimbel($id_package)
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return redirect()->route('login')
                ->with('error', 'Silakan login terlebih dahulu untuk mengakses kelas.');
        }
        
        $tesKoranEnabled = config('client.branding.tes_koran_enabled', true);
        $package = $tesKoranEnabled
            ? Package::with('tesKorans')->findOrFail($id_package)
            : Package::findOrFail($id_package);

        // Check if user has access - perbaiki query akses
        $hasAccess = UserPackageAcces::where('user_id', Auth::id())
            ->where('package_id', $id_package)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>', Carbon::now());
            })
            ->exists();

        if (!$hasAccess) {
            return redirect()->route('user.package.index')
                ->with('error', 'Anda tidak memiliki akses ke paket ini');
        }

        // Get classes for this package
        $classes = ClassModel::with('tentor')
            ->whereHas('detailPackages', function ($query) use ($id_package) {
            $query->where('package_id', $id_package);
        })->orderBy('schedule_time', 'desc')->get();

        $tesKorans = $tesKoranEnabled
            ? $package->tesKorans()
                ->where('is_active', true)
                ->where('is_displayed', true)
                ->get()
            : collect();

        ActivityLogger::log('class_list_opened', 'success', Auth::user(), [
            'package_id' => $id_package,
        ]);

        return view('user.pages.package.bimbel', compact('package', 'classes', 'tesKorans'));
    }

    public function indexTryout($id_package)
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return redirect()->route('login')
                ->with('error', 'Silakan login terlebih dahulu untuk mengakses tryout.');
        }
        
        $package = Package::findOrFail($id_package);

        // Check if user has access - perbaiki query akses
        $hasAccess = UserPackageAcces::where('user_id', Auth::id())
            ->where('package_id', $id_package)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>', Carbon::now());
            })
            ->exists();

        if (!$hasAccess) {
            return redirect()->route('user.package.index')
                ->with('error', 'Anda tidak memiliki akses ke paket ini');
        }

        // Get tryouts for this package with user attempts
        $tryouts = $package->tryouts()
            ->where('tryouts.is_active', true)
            ->where('tryouts.is_displayed', true)
            ->with([
                'tryoutDetails.questions',
                'userAnswers' => function ($query) {
                    $query->where('user_id', Auth::id());
                }
            ])->get();

        ActivityLogger::log('tryout_list_opened', 'success', Auth::user(), [
            'package_id' => $id_package,
        ]);

        return view('user.pages.package.tryout', compact('package', 'tryouts'));
    }

    public function openClassZoom(ClassModel $class)
    {
        $packageIds = $class->packages()->pluck('packages.package_id')->all();
        if (!empty($class->package_id)) {
            $packageIds[] = $class->package_id;
        }
        $packageIds = array_unique($packageIds);

        $hasAccess = UserPackageAcces::where('user_id', Auth::id())
            ->whereIn('package_id', $packageIds)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>', Carbon::now());
            })
            ->exists();

        if (!$hasAccess) {
            return redirect()->route('user.package.index')
                ->with('error', 'Anda tidak memiliki akses ke kelas ini');
        }

        ActivityLogger::log('class_zoom_opened', 'success', Auth::user(), [
            'class_id' => $class->class_id,
            'package_ids' => $packageIds,
        ]);

        if (empty($class->zoom_link)) {
            return redirect()->back()->with('error', 'Link zoom tidak tersedia.');
        }

        return redirect()->away($class->zoom_link);
    }

    public function openClassMaterial(ClassModel $class)
    {
        $packageIds = $class->packages()->pluck('packages.package_id')->all();
        if (!empty($class->package_id)) {
            $packageIds[] = $class->package_id;
        }
        $packageIds = array_unique($packageIds);

        $hasAccess = UserPackageAcces::where('user_id', Auth::id())
            ->whereIn('package_id', $packageIds)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>', Carbon::now());
            })
            ->exists();

        if (!$hasAccess) {
            return redirect()->route('user.package.index')
                ->with('error', 'Anda tidak memiliki akses ke materi ini');
        }

        ActivityLogger::log('class_material_opened', 'success', Auth::user(), [
            'class_id' => $class->class_id,
            'package_ids' => $packageIds,
        ]);

        if (empty($class->drive_link)) {
            return redirect()->back()->with('error', 'Link materi tidak tersedia.');
        }

        return redirect()->away($class->drive_link);
    }

    public function riwayatTryout($id_package, $id_tryout)
    {
        $tryout = \App\Models\Tryout::with('tryoutDetails')->findOrFail($id_tryout);
        $package = null;
        $packageRouteId = $id_package;

        if ($id_package === 'free') {
            $hasAccess = UserTryoutAccess::where('user_id', Auth::id())
                ->where('tryout_id', $id_tryout)
                ->where(function ($query) {
                    $query->whereNull('expires_at')
                        ->orWhere('expires_at', '>', Carbon::now());
                })
                ->exists();

            $hasCompletedAttempt = \App\Models\UserAnswer::where('user_id', Auth::id())
                ->where('tryout_id', $id_tryout)
                ->where('status', 'completed')
                ->exists();

            if (!$hasAccess && !$hasCompletedAttempt) {
                return redirect()->route('user.package.my', ['tab' => 'tryouts'])
                    ->with('error', 'Anda tidak memiliki akses ke tryout ini');
            }
        } else {
            $package = Package::findOrFail($id_package);

            // Check access - perbaiki query akses
            $hasAccess = UserPackageAcces::where('user_id', Auth::id())
                ->where('package_id', $id_package)
                ->where('status', 'active')
                ->where(function ($query) {
                    $query->whereNull('end_date')
                        ->orWhere('end_date', '>', Carbon::now());
                })
                ->exists();

            if (!$hasAccess) {
                return redirect()->route('user.package.index')
                    ->with('error', 'Anda tidak memiliki akses ke paket ini');
            }
        }

        // Get user attempts for this tryout dengan data yang lebih lengkap
        $attempts = \App\Models\UserAnswer::where('user_id', Auth::id())
            ->where('tryout_id', $id_tryout)
            ->where('status', 'completed')
            ->with(['tryout', 'tryoutDetail'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Group attempts by attempt_token untuk SKD Full
        $groupedAttempts = $attempts->groupBy('attempt_token');

        // Prepare attempt data dengan perhitungan yang benar
        $attemptHistory = [];
        foreach ($groupedAttempts as $token => $userAnswers) {
            $firstAnswer = $userAnswers->first();
            $lastAnswer = $userAnswers->sortByDesc('finished_at')->first();

            if ($tryout->requiresIrtScoring()) {
                $userAnswers->loadMissing(['userAnswerDetails', 'tryoutDetail']);
                $questionCounts = \App\Models\Question::whereIn('tryout_detail_id', $userAnswers->pluck('tryout_detail_id'))
                    ->select('tryout_detail_id', \DB::raw('count(*) as total'))
                    ->groupBy('tryout_detail_id')
                    ->pluck('total', 'tryout_detail_id');

                $totalCorrect = 0;
                $totalWrong = 0;
                $totalUnanswered = 0;
                $isPassed = $userAnswers->every(function ($ua) {
                    return $this->isUtbkSubtestPassed($ua->tryoutDetail, (float) ($ua->score ?? 0));
                });

                foreach ($userAnswers as $ua) {
                    $answeredCount = $ua->userAnswerDetails->count();
                    $correctCount = $ua->userAnswerDetails->where('is_correct', true)->count();
                    $totalQuestions = (int) ($questionCounts[$ua->tryout_detail_id] ?? 0);

                    $totalCorrect += $correctCount;
                    $totalWrong += max(0, $answeredCount - $correctCount);
                    $totalUnanswered += max(0, $totalQuestions - $answeredCount);
                }

                $totalScore = (float) ($firstAnswer->utbk_total_score ?? 0);
                $finalPercentage = $totalScore / 10;
            } else
                // Calculate total score untuk attempt ini
                if ($userAnswers->count() > 1) {
                    // SKD Full - hitung total dari semua subtest
                    $totalScore = 0;
                    $totalMaxScore = 0;
                    $totalCorrect = 0;
                    $totalWrong = 0;
                    $totalUnanswered = 0;

                    foreach ($userAnswers as $ua) {
                        $subtestScore = $this->calculateTotalScore($ua, $ua->tryoutDetail->type_subtest);
                        $maxSubtestScore = $this->getMaxPossibleScoreForDetail(
                            $ua->tryout_detail_id,
                            $ua->tryoutDetail->type_subtest
                        );

                        $totalScore += $subtestScore;
                        $totalMaxScore += $maxSubtestScore;
                        $totalCorrect += $ua->correct_answers ?? 0;
                        $totalWrong += $ua->wrong_answers ?? 0;
                        $totalUnanswered += $ua->unanswered ?? 0;
                    }

                    $finalPercentage = $totalMaxScore > 0 ? ($totalScore / $totalMaxScore) * 100 : 0;
                    $isPassed = $this->isAttemptPassed($userAnswers, $tryout->tryoutDetails->count());
                } else {
                    // Single subtest
                    $singleAnswer = $userAnswers->first();
                    $rawScore = $this->calculateTotalScore($singleAnswer, $singleAnswer->tryoutDetail->type_subtest);
                    $maxScore = $this->getMaxPossibleScoreForDetail(
                        $singleAnswer->tryout_detail_id,
                        $singleAnswer->tryoutDetail->type_subtest
                    );
                    $finalPercentage = $maxScore > 0 ? ($rawScore / $maxScore) * 100 : 0;
                    $isPassed = $this->isAttemptPassed($userAnswers, 1);
                    $totalCorrect = $singleAnswer->correct_answers ?? 0;
                    $totalWrong = $singleAnswer->wrong_answers ?? 0;
                    $totalUnanswered = $singleAnswer->unanswered ?? 0;
                }

            // Calculate duration
            $startTime = Carbon::parse($firstAnswer->started_at);
            $endTime = Carbon::parse($lastAnswer->finished_at);
            $duration = $endTime->diff($startTime);

            $attemptHistory[] = [
                'id' => $token,
                'created_at' => $firstAnswer->created_at,
                'started_at' => $firstAnswer->started_at,
                'finished_at' => $lastAnswer->finished_at,
                'score' => $userAnswers->count() > 1 ? round($totalScore, 0) : round($rawScore, 0),
                'is_passed' => $isPassed,
                'duration' => $duration->format('%H:%I:%S'),
                'correct_answers' => $totalCorrect,
                'wrong_answers' => $totalWrong,
                'unanswered' => $totalUnanswered,
                'attempt_token' => $token
            ];
        }

        // Sort by newest first
        $attemptHistory = collect($attemptHistory)->sortByDesc('created_at')->values();

        return view('user.pages.package.tryout-riwayat', compact('package', 'tryout', 'attemptHistory', 'packageRouteId'));
    }

    // Helper methods untuk calculation (tambahkan jika belum ada)
    private function calculateTotalScore($userAnswer, $type_subtest)
    {
        $totalScore = 0;

        $userAnswerDetails = \App\Models\UserAnswerDetail::where('user_answer_id', $userAnswer->user_answer_id)
            ->with(['questionOption', 'question'])
            ->get();

        foreach ($userAnswerDetails as $detail) {
            $question = $detail->question;
            if (!$question) {
                continue;
            }

            $questionType = $question->question_type ?? 'multiple_choice';
            $answerMeta = is_array($detail->answer_json) ? $detail->answer_json : [];
            $pendingReview = (bool) ($answerMeta['pending_review'] ?? false);

            switch ($questionType) {
                case 'multiple_answer':
                    $totalScore += $this->resolveMultipleAnswerAwardedScore($question, $detail);
                    break;
                case 'multiple_true_false':
                    $totalScore += $this->resolveMultipleTrueFalseAwardedScore($question, $detail);
                    break;

                case 'matching':
                    $totalScore += $this->resolveMatchingAwardedScore($question, $detail);
                    break;

                case 'short_answer':
                case 'essay':
                    if ($pendingReview) {
                        continue 2;
                    }
                    // Gunakan score_obtained dari answer_json (hasil koreksi AI/manual)
                    $scoreObtained = isset($answerMeta['score_obtained']) ? (float) $answerMeta['score_obtained'] : null;
                    if ($scoreObtained !== null) {
                        $totalScore += $scoreObtained;
                    } else {
                        // Fallback: gunakan essay_score_correct atau default_weight
                        $weight = (float) ($question->getEssayScoreCorrect() ?? $question->default_weight ?? 1);
                        $totalScore += $detail->is_correct ? ($weight > 0 ? $weight : 1) : 0;
                    }
                    break;

                case 'audio':
                    continue 2;

                default:
                    if ($detail->questionOption) {
                        switch ($type_subtest) {
                            case 'twk':
                            case 'tiu':
                                $w = (float) ($detail->questionOption->weight ?? 0);
                                $totalScore += $detail->is_correct ? ($w > 0 ? $w : 5) : 0;
                                break;
                            case 'tkp':
                                $w = (float) ($detail->questionOption->weight ?? 0);
                                $totalScore += $w > 0 ? $w : 1;
                                break;
                            case 'writing':
                            case 'reading':
                            case 'listening':
                                $w = (float) ($detail->questionOption->weight ?? 0);
                                $totalScore += $detail->is_correct ? ($w > 0 ? $w : 10) : 0;
                                break;
                            default:
                                $w = (float) ($detail->questionOption->weight ?? 0);
                                $totalScore += $detail->is_correct ? ($w > 0 ? $w : 1) : 0;
                                break;
                        }
                    }
                    break;
            }
        }

        return $totalScore;
    }

    private function resolveMultipleAnswerAwardedScore($question, $detail): float
    {
        $defaultWeight = (float) ($question->default_weight ?? 1);
        $maxWeight = $defaultWeight > 0 ? $defaultWeight : 1;
        $meta = is_array($detail->answer_json) ? $detail->answer_json : [];
        $selectedIds = collect($meta['selected_option_ids'] ?? [])
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if (!empty($selectedIds)) {
            $correctIds = $question->questionOptions()
                ->where('is_correct', true)
                ->pluck('question_option_id')
                ->map(fn($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            sort($selectedIds);
            sort($correctIds);
            $multipleAnswerMeta = is_array($question->metadata) ? ($question->metadata['multiple_answer'] ?? []) : [];
            $matchedCorrect = count(array_intersect($selectedIds, $correctIds));
            $wrongSelected = max(0, count($selectedIds) - $matchedCorrect);
            $scoreCorrect = (float) ($multipleAnswerMeta['score_correct'] ?? (($maxWeight > 0 && count($correctIds) > 0) ? ($maxWeight / count($correctIds)) : 1));
            $scoreWrong = (float) ($multipleAnswerMeta['score_wrong'] ?? 0);
            $scoringMode = in_array(($multipleAnswerMeta['scoring_mode'] ?? null), ['fullscore', 'partial'], true)
                ? $multipleAnswerMeta['scoring_mode']
                : 'fullscore';
            $totalCorrectCount = max(1, count($correctIds));
            $missedCorrect = max(0, $totalCorrectCount - $matchedCorrect);
            $wrongCount = $missedCorrect + $wrongSelected;
            $isExactCorrect = ($selectedIds === $correctIds);
            $fullScore = $scoreCorrect;
            $score = 0.0;

            if ($scoringMode === 'partial') {
                $score = $matchedCorrect > 0
                    ? ($matchedCorrect / $totalCorrectCount) * $fullScore
                    : $scoreWrong;
            } else {
                $score = $isExactCorrect ? $scoreCorrect : $scoreWrong;
            }

            return max(0, $score);
        }

        $storedScore = $meta['score_obtained'] ?? null;
        if (is_numeric($storedScore)) {
            return max(0, min((float) $storedScore, $maxWeight));
        }

        return $detail->is_correct ? $maxWeight : 0;
    }

    private function resolveMatchingAwardedScore($question, $detail): float
    {
        $meta = is_array($detail->answer_json) ? $detail->answer_json : [];
        $questionMeta = is_array($question->metadata) ? $question->metadata : [];
        $matchingMeta = is_array($questionMeta['matching_scores'] ?? null) ? $questionMeta['matching_scores'] : [];
        $scoreCorrect = (float) ($matchingMeta['score_correct'] ?? 1);
        $scoreWrong = (float) ($matchingMeta['score_wrong'] ?? 0);
        $scoringMode = in_array(($matchingMeta['scoring_mode'] ?? null), ['fullscore', 'partial'], true)
            ? $matchingMeta['scoring_mode']
            : 'fullscore';

        $summary = is_array($meta['summary'] ?? null) ? $meta['summary'] : [];
        $correctCount = (int) ($summary['correct'] ?? 0);
        $totalCount = (int) ($summary['total'] ?? 0);
        $wrongCount = max(0, $totalCount - $correctCount);

        if ($totalCount > 0) {
            $fullScore = max(0, $scoreCorrect);
            $isExactCorrect = ($correctCount === $totalCount);
            $score = 0.0;
            if ($scoringMode === 'partial') {
                $score = $correctCount > 0
                    ? ($correctCount / $totalCount) * $fullScore
                    : $scoreWrong;
            } else {
                $score = $isExactCorrect ? $fullScore : $scoreWrong;
            }

            return max(0, $score);
        }

        $storedScore = $meta['score_obtained'] ?? null;
        if (is_numeric($storedScore)) {
            return max(0, (float) $storedScore);
        }

        $weight = (float) ($question->default_weight ?? 1);
        return $detail->is_correct ? max(0, $weight) : 0;
    }

    private function resolveMultipleTrueFalseAwardedScore($question, $detail): float
    {
        $meta = is_array($detail->answer_json) ? $detail->answer_json : [];
        $questionMeta = is_array($question->metadata) ? ($question->metadata['multiple_true_false'] ?? []) : [];
        $scoreCorrect = (float) ($questionMeta['score_correct'] ?? ($question->default_weight ?? 1));
        $scoreWrong = (float) ($questionMeta['score_wrong'] ?? 0);
        $scoringMode = in_array(($questionMeta['scoring_mode'] ?? null), ['fullscore', 'partial'], true)
            ? $questionMeta['scoring_mode']
            : 'fullscore';

        $summary = is_array($meta['summary'] ?? null) ? $meta['summary'] : [];
        $correctCount = (int) ($summary['correct'] ?? 0);
        $totalCount = (int) ($summary['total'] ?? 0);

        if ($totalCount > 0) {
            $fullScore = max(0, $scoreCorrect);
            $isExactCorrect = ($correctCount === $totalCount);
            if ($scoringMode === 'partial') {
                return max(0, $correctCount > 0 ? ($correctCount / $totalCount) * $fullScore : $scoreWrong);
            }

            return max(0, $isExactCorrect ? $fullScore : $scoreWrong);
        }

        $storedScore = $meta['score_obtained'] ?? null;
        if (is_numeric($storedScore)) {
            return max(0, (float) $storedScore);
        }

        $weight = (float) ($question->default_weight ?? 1);
        return $detail->is_correct ? max(0, $weight) : 0;
    }

    private function isUtbkSubtestPassed($detail, float $score): bool
    {
        if (!$detail) {
            return false;
        }

        $passingScore = $detail->passing_score;
        if ($passingScore === null) {
            return false;
        }

        $passingType = $detail->passing_type ?? 'score';
        if ($passingType === 'percentage') {
            $percentage = ($score / 1000) * 100;
            return $percentage >= $passingScore;
        }

        return $score >= $passingScore;
    }

    private function getMaxPossibleScore($type_subtest, $totalQuestions)
    {
        switch ($type_subtest) {
            case 'twk':
            case 'tiu':
                return $totalQuestions * 5;
            case 'tkp':
                return $totalQuestions * 5;
            case 'writing':
            case 'reading':
            case 'listening':
                return $totalQuestions * 10; // 10 poin per soal untuk certification
            default:
                return $totalQuestions;
        }
    }

    // Versi dinamis: hitung maksimum skor berdasarkan bobot pada template
    private function getMaxPossibleScoreForDetail(int $tryoutDetailId, string $type_subtest)
    {
        $questions = \App\Models\Question::where('tryout_detail_id', $tryoutDetailId)
            ->with('questionOptions')
            ->get();

        if ($questions->isEmpty()) {
            return 0;
        }

        $total = 0;

        foreach ($questions as $question) {
            $questionType = $question->question_type ?? 'multiple_choice';

            switch ($questionType) {
                case 'multiple_answer':
                    $weight = (float) ($question->default_weight ?? 1);
                    $total += $weight > 0 ? $weight : 1;
                    break;
                case 'multiple_true_false':
                    $mtfMeta = is_array($question->metadata['multiple_true_false'] ?? null) ? $question->metadata['multiple_true_false'] : [];
                    $weight = (float) ($mtfMeta['score_correct'] ?? ($question->default_weight ?? 0));
                    $total += $weight > 0 ? $weight : 1;
                    break;

                case 'matching':
                    $matchingMeta = is_array($question->metadata['matching_scores'] ?? null) ? $question->metadata['matching_scores'] : [];
                    $weight = (float) ($matchingMeta['score_correct'] ?? ($question->default_weight ?? 0));
                    if ($weight <= 0) {
                        $weight = 1;
                    }
                    if ($type_subtest === 'tkp') {
                        $weight = $weight > 0 ? $weight : 1;
                    }
                    $total += $weight;
                    break;

                case 'short_answer':
                case 'essay':
                    // Gunakan essay_score_correct (field "Benar") untuk max score
                    $weight = (float) ($question->getEssayScoreCorrect() ?? $question->default_weight ?? 1);
                    $total += $weight > 0 ? $weight : 1;
                    break;

                case 'audio':
                    break;

                default:
                    $options = $question->questionOptions;
                    switch ($type_subtest) {
                        case 'tkp':
                            $maxWeight = (float) ($options->max('weight') ?? 0);
                            $total += $maxWeight > 0 ? $maxWeight : 1;
                            break;
                        case 'twk':
                        case 'tiu':
                            $weight = $options->where('is_correct', true)->pluck('weight')->first();
                            $weightValue = (float) ($weight ?? 0);
                            $total += $weightValue > 0 ? $weightValue : 5;
                            break;
                        case 'writing':
                        case 'reading':
                        case 'listening':
                            $weight = $options->where('is_correct', true)->pluck('weight')->first();
                            $weightValue = (float) ($weight ?? 0);
                            $total += $weightValue > 0 ? $weightValue : 10;
                            break;
                        default:
                            $weight = $options->where('is_correct', true)->pluck('weight')->first();
                            $weightValue = (float) ($weight ?? 0);
                            $total += $weightValue > 0 ? $weightValue : 1;
                            break;
                    }
                    break;
            }
        }

        return $total;
    }

    private function getDefaultPassingScore($type_subtest)
    {
        switch ($type_subtest) {
            case 'word':
            case 'excel':
            case 'ppt':
                return 70;
            case 'teknis':
            case 'social culture':
            case 'management':
            case 'interview':
                return 65;
            default:
                return 60;
        }
    }

    private function isSubtestPassed($detail, float $rawScore, float $maxScore, string $type): bool
    {
        $passingScore = $detail?->passing_score ?? $this->getDefaultPassingScore($type);
        if (is_null($passingScore)) {
            return false;
        }

        $passingType = $detail?->passing_type ?? 'score';
        if ($passingType === 'percentage') {
            $percentage = $maxScore > 0 ? ($rawScore / $maxScore) * 100 : 0;
            return $percentage >= $passingScore;
        }

        return $rawScore >= $passingScore;
    }

    private function isToeflPassed(int $score): bool
    {
        return $score >= 217;
    }

    private function isAttemptPassed($userAnswers, int $expectedSubtests = 0): bool
    {
        if ($expectedSubtests > 0 && $userAnswers->count() < $expectedSubtests) {
            return false;
        }

        return $userAnswers->every(function ($userAnswer) {
            $detail = $userAnswer->tryoutDetail;
            $type = $detail->type_subtest;
            $rawScore = $this->calculateTotalScore($userAnswer, $type);
            $maxScore = $this->getMaxPossibleScoreForDetail($userAnswer->tryout_detail_id, $type);

            return $this->isSubtestPassed($detail, $rawScore, $maxScore, $type);
        });
    }

    public function paymentSuccess(Request $request, IpaymuGateway $ipaymuGateway)
    {
        $payment = $this->ipaymuReturnPayment($request);

        if ($payment) {
            try {
                if ($payment->status === Payment::STATUS_PENDING) {
                    $ipaymuGateway->checkTransaction($payment);
                    $payment->refresh();
                }

                if ($payment->status === Payment::STATUS_SUCCESS) {
                    $this->ensureUserPackageAccess($payment, 'Payment confirmed via iPaymu return');

                    return redirect()->route('user.package.my')
                        ->with('success', 'Pembayaran berhasil. Paket sudah aktif.');
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $individualPurchase = $this->ipaymuReturnIndividualPurchase($request);

        if ($individualPurchase) {
            try {
                if ($individualPurchase->status === IndividualPurchase::STATUS_PENDING) {
                    $ipaymuGateway->checkIndividualTransaction($individualPurchase);
                    $individualPurchase->refresh();
                }

                if ($individualPurchase->status === IndividualPurchase::STATUS_APPROVED) {
                    $this->ensureIndividualPurchaseAccess($individualPurchase, 'Payment confirmed via iPaymu return');

                    return redirect()->route('user.package.my')
                        ->with('success', 'Pembayaran berhasil. Akses sudah aktif.');
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return redirect()->route('user.package.riwayatPembelian')
            ->with('success', 'Terima kasih. Akses akan aktif setelah pembayaran dikonfirmasi oleh payment gateway.');
    }

    private function ipaymuReturnPayment(Request $request): ?Payment
    {
        if (!Auth::check()) {
            return null;
        }

        $referenceId = (string) ($request->input('transaction_id')
            ?: $request->input('reference_id')
            ?: $request->input('referenceId')
            ?: $request->input('reference')
            ?: $request->input('merchant_ref')
            ?: '');

        if ($referenceId !== '') {
            $payment = Payment::query()
                ->where('user_id', Auth::id())
                ->where('payment_method', 'ipaymu')
                ->where('transaction_id', $referenceId)
                ->first();

            if ($payment) {
                return $payment;
            }
        }

        $gatewayTransactionId = (string) ($request->input('trx_id')
            ?: $request->input('ipaymu_transaction_id')
            ?: $request->input('sid')
            ?: '');

        if ($gatewayTransactionId !== '') {
            $payment = Payment::query()
                ->where('user_id', Auth::id())
                ->where('payment_method', 'ipaymu')
                ->where('payment_details', 'like', '%' . $gatewayTransactionId . '%')
                ->latest()
                ->first();

            if ($payment) {
                return $payment;
            }
        }

        if (strtolower((string) config('services.payment_gateway', '')) !== 'ipaymu') {
            return null;
        }

        return Payment::query()
            ->where('user_id', Auth::id())
            ->where('payment_method', 'ipaymu')
            ->where('status', Payment::STATUS_PENDING)
            ->latest()
            ->first();
    }

    private function ipaymuReturnIndividualPurchase(Request $request): ?IndividualPurchase
    {
        if (!Auth::check()) {
            return null;
        }

        $referenceId = (string) ($request->input('transaction_id')
            ?: $request->input('reference_id')
            ?: $request->input('referenceId')
            ?: $request->input('reference')
            ?: $request->input('merchant_ref')
            ?: '');

        if ($referenceId !== '') {
            $purchase = IndividualPurchase::query()
                ->where('user_id', Auth::id())
                ->where('payment_method', 'ipaymu')
                ->where('transaction_id', $referenceId)
                ->first();

            if ($purchase) {
                return $purchase;
            }
        }

        $gatewayTransactionId = (string) ($request->input('trx_id')
            ?: $request->input('ipaymu_transaction_id')
            ?: $request->input('sid')
            ?: '');

        if ($gatewayTransactionId !== '') {
            return IndividualPurchase::query()
                ->where('user_id', Auth::id())
                ->where('payment_method', 'ipaymu')
                ->where('payment_details', 'like', '%' . $gatewayTransactionId . '%')
                ->latest()
                ->first();
        }

        if (strtolower((string) config('services.payment_gateway', '')) !== 'ipaymu') {
            return null;
        }

        return IndividualPurchase::query()
            ->where('user_id', Auth::id())
            ->where('payment_method', 'ipaymu')
            ->where('status', IndividualPurchase::STATUS_PENDING)
            ->latest()
            ->first();
    }

    public function paymentFailed()
    {
        return redirect()->route('user.package.index')
            ->with('error', 'Pembayaran gagal atau dibatalkan.');
    }

    // Webhook for Xendit payment callback
    public function xenditWebhook(Request $request)
    {

        $callbackToken = $request->header('X-CALLBACK-TOKEN');
        $expectedToken = config('services.xendit.webhook_token');

        if ($callbackToken !== $expectedToken) {
            return response()->json(['message' => 'Invalid callback token'], 401);
        }

        $payment = Payment::where('transaction_id', $request->external_id)->first();

        if (!$payment) {
            $purchase = IndividualPurchase::where('transaction_id', $request->external_id)->first();

            if (!$purchase) {
                return response()->json(['message' => 'Payment not found'], 404);
            }

            if ($request->status === 'PAID') {
                $purchase->update([
                    'status' => IndividualPurchase::STATUS_APPROVED,
                    'approved_at' => Carbon::now(),
                ]);
                $this->ensureIndividualPurchaseAccess($purchase, 'Payment confirmed via Xendit');
            } elseif (in_array($request->status, ['EXPIRED', 'FAILED'], true)) {
                $details = $this->paymentDetailsArray($purchase->payment_details);
                $details['auto_rejected_reason'] = $request->status === 'EXPIRED'
                    ? 'Gateway payment expired before completion.'
                    : 'Gateway payment failed.';
                $details['auto_rejected_at'] = now()->toDateTimeString();
                $purchase->update([
                    'status' => IndividualPurchase::STATUS_REJECTED,
                    'payment_details' => $details,
                ]);
            }

            return response()->json(['message' => 'OK']);
        }

        if ($request->status === 'PAID') {
            $payment->update([
                'status' => Payment::STATUS_SUCCESS,
                'paid_at' => Carbon::now()
            ]);

                $this->ensureUserPackageAccess($payment, 'Payment confirmed via Xendit');
        } elseif ($request->status === 'EXPIRED') {
            $payment->update(['status' => Payment::STATUS_EXPIRED]);
        } elseif ($request->status === 'FAILED') {
            $payment->update(['status' => Payment::STATUS_FAILED]);
        }

        return response()->json(['message' => 'OK']);
    }

    public function midtransWebhook(Request $request)
    {
        $serverKey = config('services.midtrans.server_key');

        if (!$serverKey) {
            return response()->json(['message' => 'Midtrans server key tidak dikonfigurasi'], 500);
        }

        $orderId = (string) $request->input('order_id', '');
        $signature = (string) $request->input('signature_key', '');
        $statusCode = (string) $request->input('status_code', '');
        $grossAmount = (string) $request->input('gross_amount', '');
        $expectedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        if (!$signature || !hash_equals($expectedSignature, $signature)) {
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        if (!$orderId) {
            return response()->json(['message' => 'Invalid payload'], 422);
        }

        $payment = Payment::where('transaction_id', $orderId)->first();

        if (!$payment) {
            $purchase = IndividualPurchase::where('transaction_id', $orderId)->first();

            if (!$purchase) {
                return response()->json(['message' => 'Payment not found'], 404);
            }

            $transactionStatus = $request->input('transaction_status');
            $fraudStatus = $request->input('fraud_status');

            if (in_array($transactionStatus, ['capture', 'settlement'], true)) {
                if ($transactionStatus === 'capture' && $fraudStatus === 'challenge') {
                    $purchase->update(['status' => IndividualPurchase::STATUS_PENDING]);
                } else {
                    $purchase->update([
                        'status' => IndividualPurchase::STATUS_APPROVED,
                        'approved_at' => Carbon::now(),
                    ]);
                    $this->ensureIndividualPurchaseAccess($purchase, 'Payment confirmed via Midtrans');
                }
            } elseif ($transactionStatus === 'pending') {
                $purchase->update(['status' => IndividualPurchase::STATUS_PENDING]);
            } elseif (in_array($transactionStatus, ['expire', 'cancel', 'deny', 'failure'], true)) {
                $details = $this->paymentDetailsArray($purchase->payment_details);
                $details['auto_rejected_reason'] = $transactionStatus === 'expire'
                    ? 'Gateway payment expired before completion.'
                    : 'Gateway payment failed or cancelled.';
                $details['auto_rejected_at'] = now()->toDateTimeString();
                $purchase->update([
                    'status' => IndividualPurchase::STATUS_REJECTED,
                    'payment_details' => $details,
                ]);
            }

            return response()->json(['message' => 'OK']);
        }

        $transactionStatus = $request->input('transaction_status');
        $fraudStatus = $request->input('fraud_status');

        if (in_array($transactionStatus, ['capture', 'settlement'])) {
            if ($transactionStatus === 'capture' && $fraudStatus === 'challenge') {
                $payment->update(['status' => Payment::STATUS_PENDING]);
            } else {
                $payment->update([
                    'status' => Payment::STATUS_SUCCESS,
                    'paid_at' => Carbon::now(),
                ]);

            $this->ensureUserPackageAccess($payment, 'Payment confirmed via Midtrans');
            }
        } elseif ($transactionStatus === 'pending') {
            $payment->update(['status' => Payment::STATUS_PENDING]);
        } elseif ($transactionStatus === 'expire') {
            $payment->update(['status' => Payment::STATUS_EXPIRED]);
        } elseif (in_array($transactionStatus, ['cancel', 'deny', 'failure'])) {
            $payment->update(['status' => Payment::STATUS_FAILED]);
        }

        return response()->json(['message' => 'OK']);
    }

    public function ipaymuWebhook(Request $request, IpaymuGateway $gateway)
    {
        $payable = $gateway->handleWebhook($request);

        if (!$payable) {
            return response()->json(['message' => 'Payment not found'], 404);
        }

        if ($payable instanceof Payment && $payable->status === Payment::STATUS_SUCCESS) {
            $this->ensureUserPackageAccess($payable, 'Payment confirmed via iPaymu');
        }

        if ($payable instanceof IndividualPurchase && $payable->status === IndividualPurchase::STATUS_APPROVED) {
            $this->ensureIndividualPurchaseAccess($payable, 'Payment confirmed via iPaymu');
        }

        return response()->json(['message' => 'OK']);
    }

    private function ensureUserPackageAccess(Payment $payment, string $notes): void
    {
        if ($payment->status !== Payment::STATUS_SUCCESS) {
            throw new \RuntimeException('Cannot grant package access before payment is successful.');
        }

        $existingAccess = UserPackageAcces::where('user_id', $payment->user_id)
            ->where('package_id', $payment->package_id)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('end_date')->orWhere('end_date', '>', Carbon::now());
            })
            ->first();

        if (!$existingAccess) {
            $payment->loadMissing('package');
            $startDate = Carbon::now();

            UserPackageAcces::create([
                'user_id' => $payment->user_id,
                'package_id' => $payment->package_id,
                'start_date' => $startDate,
                'end_date' => $payment->package
                    ? PurchaseAccessDuration::expiresAt($payment->package, $startDate)
                    : $startDate->copy()->addYear(),
                'status' => 'active',
                'payment_amount' => $payment->total_amount,
                'payment_status' => 'paid',
                'notes' => $notes,
                'created_by' => $payment->user_id
            ]);
        }

        app(AffiliateService::class)->recordCommission($payment);
    }

    // Add method to manually check payment status (for testing)
    public function checkPaymentStatus($paymentId)
    {
        // Use 'role' instead of 'is_admin' based on migration
        if (!Auth::user()->isAdmin()) {
            abort(403);
        }

        $payment = Payment::findOrFail($paymentId);

        if (!$payment->payment_details && $payment->payment_method === 'xendit') {
            return response()->json(['error' => 'No payment details found']);
        }

        $paymentDetails = json_decode($payment->payment_details ?? '{}', true);

        if ($payment->payment_method === 'interactive_qris') {
            try {
                $result = app(InteractiveQrisGateway::class)->checkPayment($payment);
                $payment->refresh();

                if (($result['paid'] ?? false) && $payment->status === Payment::STATUS_SUCCESS) {
                    $this->ensureUserPackageAccess($payment, 'Payment confirmed via InterActive QRIS - admin check');
                }

                return response()->json([
                    'payment_status' => $payment->status,
                    'interactive_qris_result' => $result,
                ]);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()]);
            }
        }

        if ($payment->payment_method === 'midtrans') {
            $orderId = $payment->transaction_id;
            if (!$orderId) {
                return response()->json(['error' => 'No order ID found']);
            }

            try {
                $serverKey = config('services.midtrans.server_key');

                if (!$serverKey) {
                    return response()->json(['error' => 'Midtrans server key is not configured']);
                }

                $statusUrl = rtrim(config('services.midtrans.status_url', 'https://api.sandbox.midtrans.com/v2'), '/');
                $response = Http::withHeaders([
                    'Authorization' => 'Basic ' . base64_encode($serverKey . ':'),
                    'Accept' => 'application/json',
                ])->get("{$statusUrl}/{$orderId}/status");

                if ($response->successful()) {
                    $midtransData = $response->json();

                    return response()->json([
                        'payment_status' => $payment->status,
                        'midtrans_status' => $midtransData['transaction_status'] ?? null,
                        'midtrans_data' => $midtransData,
                    ]);
                }

                return response()->json(['error' => 'Failed to fetch from Midtrans']);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()]);
            }
        }

        if ($payment->payment_method === 'ipaymu') {
            try {
                $result = app(IpaymuGateway::class)->checkTransaction($payment);
                $payment->refresh();

                if (($result['paid'] ?? false) && $payment->status === Payment::STATUS_SUCCESS) {
                    $this->ensureUserPackageAccess($payment, 'Payment confirmed via iPaymu - admin check');
                }

                return response()->json([
                    'payment_status' => $payment->status,
                    'gateway_confirmation' => $payment->gatewayConfirmationLabel(),
                    'ipaymu_result' => $result,
                ]);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()]);
            }
        }

        $invoiceId = $paymentDetails['invoice_id'] ?? null;

        if (!$invoiceId) {
            return response()->json(['error' => 'No invoice ID found']);
        }

        try {
            $secretKey = config('services.xendit.secret_key');

            if (!$secretKey) {
                return response()->json(['error' => 'Xendit secret key is not configured']);
            }

            $baseUrl = rtrim(config('services.xendit.base_url', 'https://api.xendit.co'), '/');
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode($secretKey . ':'),
            ])->get("{$baseUrl}/v2/invoices/{$invoiceId}");

            if ($response->successful()) {
                $invoiceData = $response->json();

                return response()->json([
                    'payment_status' => $payment->status,
                    'xendit_status' => $invoiceData['status'],
                    'xendit_data' => $invoiceData
                ]);
            }

            return response()->json(['error' => 'Failed to fetch from Xendit']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function getUserActivePackagesForSidebar()
    {
        // Get user's active packages for sidebar
        $activePackages = UserPackageAcces::where('user_id', Auth::id())
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>', Carbon::now());
            })
            ->with([
                'package' => function ($query) {
                    $query->where('status', 'active');
                }
            ])
            ->get()
            ->filter(function ($access) {
                return $access->package !== null;
            });

        return $activePackages;
    }

    // Add method for manual payment activation (admin only)
    public function manualActivatePayment(Request $request, $paymentId)
    {
        // Use 'role' instead of 'is_admin' based on migration
        if (!Auth::user()->isAdmin()) {
            abort(403, 'Only admin can manually activate payments');
        }

        try {
            $payment = Payment::findOrFail($paymentId);

            if ($payment->status !== 'pending') {
                return response()->json([
                    'error' => 'Payment is not in pending status',
                    'current_status' => $payment->status
                ], 400);
            }

            // Update payment status
            $payment->update([
                'status' => 'success',
                'paid_at' => Carbon::now()
            ]);

            // Check if user already has access
            $this->ensureUserPackageAccess($payment, 'Manually activated by admin: ' . Auth::user()->name);

            return response()->json([
                'success' => true,
                'message' => 'Payment activated successfully',
                'payment_id' => $payment->payment_id,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to activate payment',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function rankingTryout($id_package, $id_tryout)
    {
        $tryout = \App\Models\Tryout::with('tryoutDetails')->findOrFail($id_tryout);
        if (! $tryout->show_leaderboard) {
            return redirect()->route('user.package.my', ['tab' => 'tryouts'])
                ->with('error', 'Leaderboard tryout ini tidak tersedia.');
        }

        $package = null;
        $packageRouteId = $id_package;

        if ($id_package === 'free') {
            $hasAccess = UserTryoutAccess::where('user_id', Auth::id())
                ->where('tryout_id', $id_tryout)
                ->where(function ($query) {
                    $query->whereNull('expires_at')
                        ->orWhere('expires_at', '>', Carbon::now());
                })
                ->exists();

            $hasCompletedAttempt = \App\Models\UserAnswer::where('user_id', Auth::id())
                ->where('tryout_id', $id_tryout)
                ->where('status', 'completed')
                ->exists();

            if (!$hasAccess && !$hasCompletedAttempt) {
                return redirect()->route('user.package.my', ['tab' => 'tryouts'])
                    ->with('error', 'Anda tidak memiliki akses ke tryout ini');
            }
        } else {
            $package = Package::findOrFail($id_package);

            // Check access - perbaiki query akses
            $hasAccess = UserPackageAcces::where('user_id', Auth::id())
                ->where('package_id', $id_package)
                ->where('status', 'active')
                ->where(function ($query) {
                    $query->whereNull('end_date')
                        ->orWhere('end_date', '>', Carbon::now());
                })
                ->exists();

            if (!$hasAccess) {
                return redirect()->route('user.package.index')
                    ->with('error', 'Anda tidak memiliki akses ke paket ini');
            }
        }

        $currentUser = Auth::user();
        $currentUser->loadMissing([
            'participantDestinationCategory.parent',
            'participantDestinationCategory.activeChildren',
        ]);
        $activeRankingTab = request('tab', 'all') === 'profile' ? 'profile' : 'all';
        $profileCategory = $currentUser->participantDestinationCategory;
        $profileOfficialInstitution = trim((string) ($currentUser->participant_destination_institution_name ?? ''));
        $profileOfficialProgram = trim((string) ($currentUser->participant_destination_program_name ?? ''));
        $hasOfficialDestination = $currentUser->participant_destination_source === 'snpmb'
            && $profileOfficialInstitution !== '';
        $profileNeedsCompletion = !$hasOfficialDestination
            && (
                !$profileCategory
                || (!$profileCategory->parent_id && $profileCategory->activeChildren()->exists())
            );

        if ($activeRankingTab === 'profile' && $profileNeedsCompletion) {
            return redirect()->route('user.profile.index')
                ->with('error', 'Lengkapi instansi/prodi tujuan di profil terlebih dahulu untuk melihat ranking berdasarkan profil.');
        }

        $profileDestinationIds = [];
        $profileDestinationLabel = null;
        $profileDestinationSnapshot = null;
        if ($profileCategory) {
            $profileDestinationLabel = $profileCategory->display_name;
            $profileDestinationIds = $profileCategory->parent_id
                ? [$profileCategory->id]
                : $profileCategory->activeChildren
                    ->pluck('id')
                    ->prepend($profileCategory->id)
                    ->unique()
                    ->values()
                    ->all();
        } elseif ($hasOfficialDestination) {
            $profileDestinationLabel = $currentUser->participant_destination_display_name;
            $profileDestinationSnapshot = [
                'source' => 'snpmb',
                'institution_name' => $profileOfficialInstitution,
                'program_name' => $profileOfficialProgram,
            ];
        }

        $buildRankings = function (array $destinationIds = [], ?array $destinationSnapshot = null) use ($id_tryout, $tryout) {
            return \App\Models\UserAnswer::where('tryout_id', $id_tryout)
                ->where('status', 'completed')
                ->when(!empty($destinationIds), function ($query) use ($destinationIds) {
                    $query->whereHas('user', function ($userQuery) use ($destinationIds) {
                        $userQuery->whereIn('participant_destination_category_id', $destinationIds);
                    });
                })
                ->when(!empty($destinationSnapshot), function ($query) use ($destinationSnapshot) {
                    $query->whereHas('user', function ($userQuery) use ($destinationSnapshot) {
                        $userQuery
                            ->where('participant_destination_source', $destinationSnapshot['source'])
                            ->where('participant_destination_institution_name', $destinationSnapshot['institution_name']);

                        if (!empty($destinationSnapshot['program_name'])) {
                            $userQuery->where('participant_destination_program_name', $destinationSnapshot['program_name']);
                        }
                    });
                })
                ->with(['user.participantDestinationCategory.parent', 'tryoutDetail'])
                ->get()
                ->groupBy('user_id')
                ->map(function ($userAnswers) use ($tryout) {
                $usesUtbkIrt = method_exists($tryout, 'requiresIrtScoring') && $tryout->requiresIrtScoring();

                if ($usesUtbkIrt) {
                    $attemptGroups = $userAnswers->groupBy('attempt_token');

                    $bestAttempt = $attemptGroups->map(function ($attempt) {
                        $representative = $attempt->first();
                        $score = (float) ($representative->utbk_total_score ?? 0);
                        $attempt->loadMissing('tryoutDetail');
                        $subtestScores = [];
                        $allPassed = $attempt->every(function ($ua) {
                            return $this->isUtbkSubtestPassed($ua->tryoutDetail, (float) ($ua->score ?? 0));
                        });

                        foreach ($attempt as $userAnswer) {
                            $subtestScores[$userAnswer->tryout_detail_id] = (float) ($userAnswer->score ?? 0);
                        }

                        return [
                            'user' => $representative->user,
                            'raw_score' => $score,
                            'max_score' => 1000,
                            'percentage' => $score / 10,
                            'finished_at' => $attempt->max('finished_at'),
                            'correct_answers' => $attempt->sum('correct_answers'),
                            'wrong_answers' => $attempt->sum('wrong_answers'),
                            'unanswered' => $attempt->sum('unanswered'),
                            'is_passed' => $allPassed,
                            'subtest_scores' => $subtestScores,
                        ];
                    })->filter()->sortByDesc('raw_score')->values()->first();

                    return $bestAttempt;
                }

                // Gabungkan skor dari semua subtest dengan group_by attempt_token
                $attemptGroups = $userAnswers->groupBy('attempt_token');

                $bestAttempt = $attemptGroups->map(function ($attempt) use ($tryout) {
                    if ($tryout->is_toefl == 1) {
                        // For TOEFL, use the actual TOEFL total score
                        $toeflScore = $attempt->first()->toefl_total_score ?? $attempt->first()->score;

                        return [
                            'user' => $attempt->first()->user,
                            'raw_score' => $toeflScore,
                            'max_score' => 677, // TOEFL max score
                            'percentage' => $toeflScore,
                            'finished_at' => $attempt->max('finished_at'),
                            'started_at' => $attempt->min('started_at'),
                            'correct_answers' => $attempt->sum('correct_answers'),
                            'wrong_answers' => $attempt->sum('wrong_answers'),
                            'unanswered' => $attempt->sum('unanswered'),
                            'is_passed' => $this->isToeflPassed((int) $toeflScore),
                            'subtest_scores' => []
                        ];
                    } else {
                        // Regular scoring
                        $totalScore = 0;
                        $totalMaxScore = 0;
                        $allSubtestsPassed = true;
                        $subtestScores = [];

                        foreach ($attempt as $userAnswer) {
                            $subtestScore = $this->calculateTotalScore($userAnswer, $userAnswer->tryoutDetail->type_subtest);
                            $maxSubtestScore = $this->getMaxPossibleScoreForDetail(
                                $userAnswer->tryout_detail_id,
                                $userAnswer->tryoutDetail->type_subtest
                            );

                            $totalScore += $subtestScore;
                            $totalMaxScore += $maxSubtestScore;
                            $subtestScores[$userAnswer->tryout_detail_id] = $subtestScore;

                            $detail = $userAnswer->tryoutDetail;
                            if (!$this->isSubtestPassed($detail, $subtestScore, $maxSubtestScore, $detail->type_subtest)) {
                                $allSubtestsPassed = false;
                            }
                        }

                        $percentage = $totalMaxScore > 0 ? ($totalScore / $totalMaxScore) * 100 : 0;

                        return [
                            'user' => $attempt->first()->user,
                            'raw_score' => $totalScore,
                            'max_score' => $totalMaxScore,
                            'percentage' => $percentage,
                            'finished_at' => $attempt->max('finished_at'),
                            'started_at' => $attempt->min('started_at'),
                            'correct_answers' => $attempt->sum('correct_answers'),
                            'wrong_answers' => $attempt->sum('wrong_answers'),
                            'unanswered' => $attempt->sum('unanswered'),
                            'is_passed' => $allSubtestsPassed,
                            'subtest_scores' => $subtestScores
                        ];
                    }
                })->filter()->sortByDesc('raw_score')->values()->first();

                return $bestAttempt;
            })
                ->filter() // Remove null values
                ->sortByDesc('raw_score')
                ->values();
        };

        $allRankings = $buildRankings();
        $profileRankings = !$profileNeedsCompletion
            ? $buildRankings($profileDestinationIds, $profileDestinationSnapshot)
            : collect();
        $rankings = $activeRankingTab === 'profile' ? $profileRankings : $allRankings;

        return view('user.pages.package.tryout-rank', compact(
            'package',
            'tryout',
            'rankings',
            'allRankings',
            'profileRankings',
            'activeRankingTab',
            'profileDestinationLabel',
            'profileNeedsCompletion',
            'packageRouteId'
        ));
    }

    public function pembahasanTryout($id_package, $id_tryout, $token)
    {
        $isFreeTryout = $id_package === 'free';
        $package = $isFreeTryout ? null : Package::findOrFail($id_package);
        $tryout = \App\Models\Tryout::findOrFail($id_tryout);
        if (! $tryout->show_discussion) {
            $redirectRoute = $isFreeTryout
                ? route('user.tryout.result', [$id_package, $id_tryout, 'attempt' => $token])
                : route('user.package.tryout.riwayat', [$id_package, $id_tryout]);

            return redirect($redirectRoute)
                ->with('error', 'Pembahasan tryout ini tidak tersedia.');
        }

        // Check access
        if (!$isFreeTryout) {
            $hasAccess = UserPackageAcces::where('user_id', Auth::id())
                ->where('package_id', $id_package)
                ->where('status', 'active')
                ->where(function ($query) {
                    $query->whereNull('end_date')
                        ->orWhere('end_date', '>', Carbon::now());
                })
                ->exists();

            if (!$hasAccess) {
                return redirect()->route('user.package.index')
                    ->with('error', 'Anda tidak memiliki akses ke paket ini');
            }
        }

        // Get user's latest completed answers for this tryout
        $userAnswers = \App\Models\UserAnswer::where('user_id', Auth::id())
            ->where('tryout_id', $id_tryout)
            ->where('status', 'completed')
            ->where('attempt_token', $token)
            ->with(['tryout.tryoutDetails', 'userAnswerDetails.question.questionOptions', 'tryoutDetail'])
            ->orderBy('created_at', 'desc')
            ->get();

        if ($userAnswers->isEmpty()) {
            return redirect()->route('user.package.tryout', $id_package)
                ->with('error', 'Anda belum mengerjakan tryout ini');
        }

        // Group by attempt_token and get latest attempt
        $latestAttemptToken = $userAnswers->first()->attempt_token;
        $latestUserAnswers = $userAnswers->where('attempt_token', $latestAttemptToken);

        $tryoutDetails = $tryout->tryoutDetails;

        $answeredDetailsByQuestionId = collect();
        foreach ($latestUserAnswers as $userAnswer) {
            $answerDetails = $userAnswer->userAnswerDetails()->with([
                'question.questionOptions',
                'questionOption'
            ])->get();

            foreach ($answerDetails as $detail) {
                $detail->subtest_type = $userAnswer->tryoutDetail->type_subtest;
                $detail->subtest_name = $this->getSubtestName($userAnswer->tryoutDetail->type_subtest);
            }

            foreach ($answerDetails as $detail) {
                $answeredDetailsByQuestionId->put($detail->question_id, $detail);
            }
        }

        $userAnswersByTryoutDetailId = $latestUserAnswers->keyBy('tryout_detail_id');
        $questions = \App\Models\Question::with('questionOptions')
            ->whereIn('tryout_detail_id', $latestUserAnswers->pluck('tryout_detail_id'))
            ->orderBy('tryout_detail_id')
            ->orderBy('question_id')
            ->get();

        $allAnswerDetails = $questions->map(function ($question) use ($answeredDetailsByQuestionId, $userAnswersByTryoutDetailId) {
            $userAnswer = $userAnswersByTryoutDetailId->get($question->tryout_detail_id);
            $detail = $answeredDetailsByQuestionId->get($question->question_id);

            if (!$detail) {
                $detail = new \App\Models\UserAnswerDetail([
                    'user_answer_id' => $userAnswer?->user_answer_id,
                    'question_id' => $question->question_id,
                    'question_option_id' => null,
                    'answer_text' => null,
                    'answer_json' => [],
                    'is_correct' => false,
                    'answered_at' => null,
                ]);
                $detail->exists = false;
                $detail->setRelation('questionOption', null);
            }

            $detail->setRelation('question', $question);
            $detail->subtest_type = $userAnswer?->tryoutDetail?->type_subtest ?? $question->tryoutDetail?->type_subtest;
            $detail->subtest_name = $this->getSubtestName($detail->subtest_type);
            $detail->is_unanswered = !$detail->exists || (
                !$detail->question_option_id
                && blank($detail->answer_text)
                && empty($detail->answer_json)
            );

            return $detail;
        });

        $pendingReviewCount = $allAnswerDetails->filter(function ($detail) {
            $meta = is_array($detail->answer_json) ? $detail->answer_json : [];
            return !empty($meta['pending_review']);
        })->count();

        $latestUserAnswers->loadMissing(['userAnswerDetails', 'tryoutDetail']);
        $questionCounts = \App\Models\Question::whereIn('tryout_detail_id', $latestUserAnswers->pluck('tryout_detail_id'))
            ->select('tryout_detail_id', \DB::raw('count(*) as total'))
            ->groupBy('tryout_detail_id')
            ->pluck('total', 'tryout_detail_id');

        if ($tryout->requiresIrtScoring()) {
            $totalQuestions = $questionCounts->sum();
            $answeredCount = $latestUserAnswers->sum(fn($ua) => $ua->userAnswerDetails->count());
            $correctAnswers = $latestUserAnswers->sum(fn($ua) => $ua->userAnswerDetails->where('is_correct', true)->count());
            $wrongAnswers = max(0, $answeredCount - $correctAnswers);
            $unanswered = max(0, $totalQuestions - $answeredCount);

            $totalScore = (float) ($latestUserAnswers->first()->utbk_total_score ?? 0);
            $maxScore = 1000;
            $percentage = $totalScore / 10;
            $isPassed = $latestUserAnswers->every(function ($ua) {
                return $this->isUtbkSubtestPassed($ua->tryoutDetail, (float) ($ua->score ?? 0));
            });
        } else {
            // Calculate overall statistics
            $totalQuestions = $latestUserAnswers->sum(function ($ua) use ($questionCounts) {
                return (int) ($questionCounts[$ua->tryout_detail_id] ?? 0);
            });
            $answeredCount = 0;
            $correctAnswers = 0;
            $wrongAnswers = 0;
            foreach ($latestUserAnswers as $userAnswer) {
                $details = $userAnswer->userAnswerDetails;
                $answeredCount += $details->count();
                foreach ($details as $detail) {
                    $meta = is_array($detail->answer_json) ? $detail->answer_json : [];
                    if (!empty($meta['pending_review'])) {
                        continue;
                    }

                    if ($detail->is_correct) {
                        $correctAnswers++;
                    } else {
                        $wrongAnswers++;
                    }
                }
            }
            $unanswered = max(0, $totalQuestions - $answeredCount);

            // Calculate total score
            if ($tryoutDetails->count() > 1) {
                // SKD Full calculation
                $totalScore = 0;
                $maxScore = 0;

                foreach ($latestUserAnswers as $userAnswer) {
                    $subtestScore = $this->calculateTotalScore($userAnswer, $userAnswer->tryoutDetail->type_subtest);
                    $maxSubtestScore = $this->getMaxPossibleScoreForDetail(
                        $userAnswer->tryout_detail_id,
                        $userAnswer->tryoutDetail->type_subtest
                    );

                    $totalScore += $subtestScore;
                    $maxScore += $maxSubtestScore;
                }
            } else {
                // Single subtest
                $singleUserAnswer = $latestUserAnswers->first();
                $totalScore = $this->calculateTotalScore($singleUserAnswer, $singleUserAnswer->tryoutDetail->type_subtest);
                $maxScore = $this->getMaxPossibleScoreForDetail(
                    $singleUserAnswer->tryout_detail_id,
                    $singleUserAnswer->tryoutDetail->type_subtest
                );
            }

            $percentage = $maxScore > 0 ? ($totalScore / $maxScore) * 100 : 0;
            $isPassed = $this->isAttemptPassed($latestUserAnswers, $tryoutDetails->count());
        }

        $overallStats = [
            'total_questions' => $totalQuestions,
            'correct_answers' => $correctAnswers,
            'wrong_answers' => $wrongAnswers,
            'unanswered' => $unanswered,
            'pending_review' => $pendingReviewCount,
            'total_score' => $totalScore,
            'max_score' => $maxScore,
            'percentage' => $percentage,
            'is_passed' => $isPassed
        ];

        $subtestSummaries = [];
        if ($tryoutDetails->count() > 1) {
            if ($tryout->requiresIrtScoring()) {
                $subtestSummaries = $latestUserAnswers->map(function ($userAnswer) {
                    $detail = $userAnswer->tryoutDetail;
                    $type = $detail->type_subtest;
                    $score = (float) ($userAnswer->score ?? 0);
                    $max = 1000;
                    $percentage = $score / 10;
                    $passingScore = $detail->passing_score ?? null;
                    $passingType = $detail->passing_type ?? 'score';

                    return [
                        'type' => $type,
                        'name' => $this->getSubtestName($type),
                        'score' => $score,
                        'max_score' => $max,
                        'percentage' => $percentage,
                        'passing_score' => $passingScore,
                        'passing_type' => $passingType,
                        'passing_percentage' => $passingType === 'percentage' ? $passingScore : null,
                        'is_passed' => $this->isUtbkSubtestPassed($detail, $score),
                        'correct_answers' => $userAnswer->userAnswerDetails->where('is_correct', true)->count(),
                        'wrong_answers' => max(0, $userAnswer->userAnswerDetails->count() - $userAnswer->userAnswerDetails->where('is_correct', true)->count()),
                    ];
                })->values();
            } else {
                $subtestSummaries = $latestUserAnswers->map(function ($userAnswer) {
                    $type = $userAnswer->tryoutDetail->type_subtest;
                    $score = $this->calculateTotalScore($userAnswer, $type);
                    $max = $this->getMaxPossibleScoreForDetail($userAnswer->tryout_detail_id, $type);
                    $percentage = $max > 0 ? ($score / $max) * 100 : 0;
                    $detail = $userAnswer->tryoutDetail;
                    $passingScore = $detail->passing_score ?? $this->getDefaultPassingScore($type);
                    $passingType = $detail->passing_type ?? 'score';
                    $passingPercentage = $passingType === 'percentage'
                        ? $passingScore
                        : ($max > 0 ? ($passingScore / $max) * 100 : null);
                    $correctCount = $userAnswer->userAnswerDetails->filter(function ($detail) {
                        $meta = is_array($detail->answer_json) ? $detail->answer_json : [];
                        return empty($meta['pending_review']) && $detail->is_correct;
                    })->count();
                    $wrongCount = $userAnswer->userAnswerDetails->filter(function ($detail) {
                        $meta = is_array($detail->answer_json) ? $detail->answer_json : [];
                        return empty($meta['pending_review']) && !$detail->is_correct;
                    })->count();

                    return [
                        'type' => $type,
                        'name' => $this->getSubtestName($type),
                        'score' => $score,
                        'max_score' => $max,
                        'percentage' => $percentage,
                        'passing_score' => $passingScore,
                        'passing_type' => $passingType,
                        'passing_percentage' => $passingPercentage,
                        'is_passed' => $this->isSubtestPassed($detail, $score, $max, $type),
                        'correct_answers' => $correctCount,
                        'wrong_answers' => $wrongCount,
                    ];
                })->values();
            }
        }

        $token = $token;
        $packageRouteId = $isFreeTryout ? 'free' : $package->package_id;
        return view('user.pages.package.tryout-pembahasan', compact(
            'package',
            'packageRouteId',
            'tryout',
            'tryoutDetails',
            'latestUserAnswers',
            'token',
            'allAnswerDetails',
            'overallStats',
            'subtestSummaries'
        ));
    }

    public function chatPembahasanAi(Request $request, $id_package, $id_tryout, $token, AiDiscussionService $aiDiscussionService)
    {
        $validated = $request->validate([
            'question_id' => ['required', 'integer'],
            'message' => ['required', 'string', 'max:1200'],
        ]);

        if (!$aiDiscussionService->isEnabled()) {
            return response()->json([
                'message' => 'Diskusi AI belum diaktifkan admin.',
            ], 403);
        }

        $isFreeTryout = $id_package === 'free';
        $package = $isFreeTryout ? null : Package::findOrFail($id_package);
        $tryout = \App\Models\Tryout::with('tryoutDetails')->findOrFail($id_tryout);

        if (!$tryout->show_discussion) {
            return response()->json([
                'message' => 'Pembahasan tryout ini tidak tersedia.',
            ], 403);
        }

        if (!$isFreeTryout) {
            $hasAccess = UserPackageAcces::where('user_id', Auth::id())
                ->where('package_id', $package->package_id)
                ->where('status', 'active')
                ->where(function ($query) {
                    $query->whereNull('end_date')
                        ->orWhere('end_date', '>', Carbon::now());
                })
                ->exists();

            if (!$hasAccess) {
                return response()->json([
                    'message' => 'Anda tidak memiliki akses ke paket ini.',
                ], 403);
            }
        }

        $userAnswers = \App\Models\UserAnswer::where('user_id', Auth::id())
            ->where('tryout_id', $tryout->tryout_id)
            ->where('status', 'completed')
            ->where('attempt_token', $token)
            ->with('tryoutDetail')
            ->latest()
            ->get();

        if ($userAnswers->isEmpty()) {
            return response()->json([
                'message' => 'Data pengerjaan tidak ditemukan.',
            ], 404);
        }

        $question = \App\Models\Question::with(['questionOptions', 'tryoutDetail'])
            ->where('question_id', $validated['question_id'])
            ->whereIn('tryout_detail_id', $userAnswers->pluck('tryout_detail_id'))
            ->firstOrFail();

        $answerDetail = \App\Models\UserAnswerDetail::with('questionOption')
            ->whereIn('user_answer_id', $userAnswers->pluck('user_answer_id'))
            ->where('question_id', $question->question_id)
            ->first();

        try {
            $result = $aiDiscussionService->chat($validated['message'], [
                'tryout_name' => $tryout->name,
                'subtest_name' => $this->getSubtestName($question->tryoutDetail?->type_subtest),
                'question' => $question,
                'answer_detail' => $answerDetail,
            ]);
        } catch (\RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json($result);
    }

    private function getSubtestName($type)
    {
        switch ($type) {
            case 'twk':
                return 'Tes Wawasan Kebangsaan';
            case 'tiu':
                return 'Tes Intelegensi Umum';
            case 'tkp':
                return 'Tes Karakteristik Pribadi';
            case 'writing':
                return 'Writing Test';
            case 'reading':
                return 'Reading Comprehension';
            case 'listening':
                return 'Listening Test';
            case 'word':
                return 'Microsoft Word';
            case 'excel':
                return 'Microsoft Excel';
            case 'ppt':
                return 'Microsoft PowerPoint';
            default:
                return ucfirst($type);
        }
    }

    /**
     * Display user's active packages with step by step view
     */
    public function myPackages(Request $request)
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return redirect()->route('login')
                ->with('error', 'Silakan login terlebih dahulu untuk melihat paket Anda.');
        }
        
        $user = Auth::user();
        $search = trim((string) $request->get('search', ''));
        $sort = $request->get('sort', 'latest');
        $tesKoranEnabled = config('client.branding.tes_koran_enabled', true);
        
        $packageRelations = [
            'package.materialsThroughDetail' => fn ($query) => $query->where('materials.is_active', true)->where('materials.is_displayed', true),
            'package.tryouts' => fn ($query) => $query->where('tryouts.is_active', true)->where('tryouts.is_displayed', true),
        ];
        if ($tesKoranEnabled) {
            $packageRelations['package.tesKorans'] = fn ($query) => $query->where('tes_korans.is_active', true)->where('tes_korans.is_displayed', true);
        }

        $activePackages = UserPackageAcces::where('user_id', $user->id)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>', Carbon::now());
            })
            ->with($packageRelations)
            ->get();

        $accessiblePackageIds = $activePackages
            ->pluck('package_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $packageMaterialIds = \DB::table('detail_packages')
            ->join('materials', 'detail_packages.detailable_id', '=', 'materials.material_id')
            ->whereIn('package_id', $accessiblePackageIds)
            ->where('detailable_type', \App\Models\Material::class)
            ->where('materials.is_active', true)
            ->where('materials.is_displayed', true)
            ->pluck('detail_packages.detailable_id')
            ->toArray();

        $directMaterialIds = UserMaterialAccess::where('user_id', $user->id)
            ->where('access_source', 'direct')
            ->whereIn('access_type', ['free', 'purchased', 'paid'])
            ->where('status', '!=', 'not_started')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->pluck('material_id')
            ->toArray();

        $accessibleMaterialIds = array_values(array_unique(array_merge($packageMaterialIds, $directMaterialIds)));

        $myMaterials = \App\Models\Material::whereIn('material_id', $accessibleMaterialIds)
            ->where('is_active', true)
            ->where('is_displayed', true)
            ->with(['userAccess' => function ($query) use ($user) {
                $query->where('user_id', $user->id);
            }])
            ->get();

        $directTryoutIds = UserTryoutAccess::where('user_id', $user->id)
            ->where('access_source', 'direct')
            ->whereIn('access_type', ['free', 'purchased', 'paid'])
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->pluck('tryout_id')
            ->toArray();

        $myTryouts = Tryout::where(function ($query) use ($accessiblePackageIds, $directTryoutIds) {
            $query->whereHas('packages', function ($packageQuery) use ($accessiblePackageIds) {
                $packageQuery->whereIn('packages.package_id', $accessiblePackageIds);
            })->orWhereIn('tryout_id', $directTryoutIds);
        })
            ->where('is_active', true)
            ->where('is_displayed', true)
            ->with([
                'packages' => function ($query) use ($accessiblePackageIds) {
                    $query->whereIn('packages.package_id', $accessiblePackageIds);
                },
                'userAnswers' => function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                },
            ])
            ->get();

        $myTesKorans = $tesKoranEnabled
            ? \App\Models\TesKoran::where(function ($query) use ($accessiblePackageIds, $user) {
                $query->whereHas('packages', function ($packageQuery) use ($accessiblePackageIds) {
                    $packageQuery->whereIn('packages.package_id', $accessiblePackageIds);
                })->orWhereHas('individualPurchases', function ($purchaseQuery) use ($user) {
                    $purchaseQuery->where('user_id', $user->id)
                        ->where('status', \App\Models\IndividualPurchase::STATUS_APPROVED)
                        ->where(function ($query) {
                            $query->whereNull('access_expires_at')
                                ->orWhere('access_expires_at', '>', now());
                        });
                });
            })
                ->where('is_active', true)
                ->where('is_displayed', true)
                ->with(['results' => function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                }])
                ->get()
            : collect();

        $activePackages = $this->filterAndSortUserCollection(
            $activePackages,
            $search,
            $sort,
            fn ($access) => (string) ($access->package?->name ?? ''),
            fn ($access) => $access->created_at
        );

        $myMaterials = $this->filterAndSortUserCollection(
            $myMaterials,
            $search,
            $sort,
            fn ($material) => (string) $material->title,
            fn ($material) => $material->created_at
        );

        $myTryouts = $this->filterAndSortUserCollection(
            $myTryouts,
            $search,
            $sort,
            fn ($tryout) => (string) $tryout->name,
            fn ($tryout) => $tryout->created_at
        );

        $myTesKorans = $this->filterAndSortUserCollection(
            $myTesKorans,
            $search,
            $sort,
            fn ($tesKoran) => (string) $tesKoran->name,
            fn ($tesKoran) => $tesKoran->created_at
        );

        $videoMaterials = $myMaterials->where('type', 'video')->values();
        $documentMaterials = $myMaterials->where('type', 'document')->values();

        $completedMaterialIds = UserMaterialAccess::where('user_id', $user->id)
            ->where('status', 'completed')
            ->pluck('material_id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $completedTryoutIds = UserAnswer::where('user_id', $user->id)
            ->where('status', 'completed')
            ->pluck('tryout_id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $completedTesKoranIds = $tesKoranEnabled
            ? TesKoranResult::where('user_id', $user->id)
                ->where('status', 'completed')
                ->pluck('tes_koran_id')
                ->map(fn ($id) => (int) $id)
                ->all()
            : [];

        $packageProgress = [];
        foreach ($activePackages as $access) {
            $package = $access->package;
            $materials = $package?->materialsThroughDetail ?? collect();
            $tryouts = $package?->tryouts ?? collect();
            $tesKorans = $tesKoranEnabled ? ($package?->tesKorans ?? collect()) : collect();
            $totalItems = $materials->count() + $tryouts->count() + $tesKorans->count();
            $completedCount = $materials->whereIn('material_id', $completedMaterialIds)->count()
                + $tryouts->whereIn('tryout_id', $completedTryoutIds)->count()
                + $tesKorans->whereIn('id', $completedTesKoranIds)->count();

            $packageProgress[$access->package_id] = [
                'total_items' => $totalItems,
                'completed_count' => $completedCount,
                'percent' => $totalItems > 0 ? round(($completedCount / $totalItems) * 100) : 0,
            ];
        }

        $tryoutPackageIds = \DB::table('detail_packages')
            ->join('user_package_access', 'detail_packages.package_id', '=', 'user_package_access.package_id')
            ->where('detail_packages.detailable_type', Tryout::class)
            ->where('user_package_access.user_id', $user->id)
            ->where('user_package_access.status', 'active')
            ->where(function ($query) {
                $query->whereNull('user_package_access.end_date')
                    ->orWhere('user_package_access.end_date', '>', now());
            })
            ->pluck('detail_packages.package_id', 'detail_packages.detailable_id')
            ->all();

        return view('user.pages.package.new-my-packages', compact(
            'activePackages',
            'myMaterials',
            'videoMaterials',
            'documentMaterials',
            'myTryouts',
            'myTesKorans',
            'packageProgress',
            'tryoutPackageIds',
            'search',
            'sort'
        ));
    }
    
    /**
     * Show package roadmap (new gamified view)
     */
    public function showPackage($packageId)
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return redirect()->route('login')
                ->with('error', 'Silakan login terlebih dahulu untuk mengakses paket.');
        }
        
        $user = Auth::user();
        $tesKoranEnabled = config('client.branding.tes_koran_enabled', true);
        $relations = $tesKoranEnabled
            ? [
                'materialsThroughDetail' => fn ($query) => $query->where('materials.is_active', true)->where('materials.is_displayed', true),
                'materialsThroughDetail.categories',
                'tryouts' => fn ($query) => $query->where('tryouts.is_active', true)->where('tryouts.is_displayed', true),
                'tesKorans' => fn ($query) => $query->where('tes_korans.is_active', true)->where('tes_korans.is_displayed', true),
            ]
            : [
                'materialsThroughDetail' => fn ($query) => $query->where('materials.is_active', true)->where('materials.is_displayed', true),
                'materialsThroughDetail.categories',
                'tryouts' => fn ($query) => $query->where('tryouts.is_active', true)->where('tryouts.is_displayed', true),
            ];

        $package = Package::with($relations)->findOrFail($packageId);
        
        // Check access
        $hasAccess = UserPackageAcces::where('user_id', $user->id)
            ->where('package_id', $packageId)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>', Carbon::now());
            })
            ->exists();
        
        if (!$hasAccess) {
            return redirect()->route('user.package.my')
                ->with('error', 'Anda tidak memiliki akses ke paket ini.');
        }
        
        // Prepare roadmap items with status
        $roadmapItems = collect();
        $completedCount = 0;
        $orderCounter = 1;
        
        // Process materials first. Package content uses detail_packages as the single source of truth.
        $sortedMaterials = $package->materialsThroughDetail->sortBy(function ($material) {
            return $material->order_number ?? $material->material_id;
        });
        
        foreach ($sortedMaterials as $material) {
            $progress = UserMaterialAccess::where('user_id', $user->id)
                ->where('material_id', $material->material_id)
                ->first();
            $isCompleted = $progress && $progress->is_completed;
            $isInProgress = $progress && $progress->is_in_progress;
            $itemProgress = $progress ? (int) ($progress->progress_percentage ?? 0) : 0;
            
            if ($isCompleted) {
                $completedCount++;
            }
            
            $roadmapItems->push([
                'order' => $orderCounter,
                'type' => 'material',
                'material_type' => $material->type,
                'title' => $material->title,
                'subtitle' => $material->categories->first()?->name ?? $material->type_label,
                'icon' => $material->type === 'video' ? 'ri-video-line' : ($material->type === 'document' ? 'ri-file-text-line' : 'ri-live-line'),
                'route' => route('user.material.show', $material->material_id),
                'is_completed' => $isCompleted,
                'is_in_progress' => $isInProgress,
                'progress_percent' => $itemProgress,
                'status_text' => $isCompleted ? 'Selesai' : ($isInProgress ? 'Berlangsung' : 'Mulai'),
                'is_left' => $orderCounter % 2 === 1,
            ]);
            
            $orderCounter++;
        }
        
        // Process tryouts (append at the end)
        foreach ($package->tryouts as $tryout) {
            $attempts = UserAnswer::where('user_id', $user->id)
                ->where('tryout_id', $tryout->tryout_id)
                ->get();
            $isCompleted = $attempts->where('status', 'completed')->isNotEmpty();
            $isInProgress = $attempts->where('status', 'in_progress')->isNotEmpty();
            
            if ($isCompleted) {
                $completedCount++;
            }
            
            $roadmapItems->push([
                'order' => $orderCounter,
                'type' => 'tryout',
                'title' => $tryout->name,
                'subtitle' => 'Tryout Latihan',
                'icon' => 'ri-file-list-3-line',
                'route' => route('user.tryout.lobby', ['id_package' => $package->package_id, 'id_tryout' => $tryout->tryout_id]),
                'is_completed' => $isCompleted,
                'is_in_progress' => $isInProgress,
                'progress_percent' => 0,
                'status_text' => $isCompleted ? 'Selesai' : ($isInProgress ? 'Berlangsung' : 'Mulai'),
                'is_left' => $orderCounter % 2 === 1,
            ]);
            
            $orderCounter++;
        }

        if ($tesKoranEnabled) {
            foreach ($package->tesKorans as $tesKoran) {
                $attempt = TesKoranResult::where('user_id', $user->id)
                    ->where('tes_koran_id', $tesKoran->id)
                    ->where('status', 'completed')
                    ->first();
                $isCompleted = $attempt !== null;

                if ($isCompleted) {
                    $completedCount++;
                }

                $roadmapItems->push([
                    'order' => $orderCounter,
                    'type' => 'tes_koran',
                    'title' => $tesKoran->name,
                    'subtitle' => 'Tes Koran',
                    'icon' => 'ri-file-edit-line',
                    'route' => route('user.tes-koran.show', $tesKoran),
                    'is_completed' => $isCompleted,
                    'is_in_progress' => false,
                    'progress_percent' => 0,
                    'status_text' => $isCompleted ? 'Selesai' : 'Mulai',
                    'is_left' => $orderCounter % 2 === 1,
                ]);

                $orderCounter++;
            }
        }
        
        $totalItems = $roadmapItems->count();
        $progressPercent = $totalItems > 0 ? round(($completedCount / $totalItems) * 100) : 0;
        
        // Find next item to start
        $nextItem = $roadmapItems->first(fn($item) => !$item['is_completed']) ?? $roadmapItems->first();
        
        return view('user.pages.package.roadmap', compact(
            'package',
            'roadmapItems',
            'completedCount',
            'totalItems',
            'progressPercent',
            'nextItem'
        ));
    }

    private function filterAndSortUserCollection($items, string $search, string $sort, callable $nameResolver, callable $dateResolver)
    {
        if ($search !== '') {
            $needle = Str::lower($search);
            $items = $items->filter(fn ($item) => Str::contains(Str::lower($nameResolver($item)), $needle));
        }

        $items = match ($sort) {
            'oldest' => $items->sortBy(fn ($item) => $dateResolver($item)),
            'name_asc' => $items->sortBy(fn ($item) => Str::lower($nameResolver($item))),
            'name_desc' => $items->sortByDesc(fn ($item) => Str::lower($nameResolver($item))),
            default => $items->sortByDesc(fn ($item) => $dateResolver($item)),
        };

        return $items->values();
    }

    /**
     * List all tryouts - tampilkan SEMUA dengan status akses user
     * BISA diakses oleh GUEST
     */
    public function listTryout(Request $request)
    {
        $user = Auth::user();
        $search = trim((string) $request->get('search', ''));
        $sort = $request->get('sort', 'latest');
        
        // Get packages that user has access to (empty for guest)
        $accessiblePackageIds = $user ? $user->userPackageAccess()
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>', Carbon::now());
            })
            ->pluck('package_id')
            ->toArray() : [];
        
        // Get ALL active tryouts with their packages (only displayed)
        $tryoutsQuery = \App\Models\Tryout::with(['tryoutDetails', 'packages', 'userAnswers' => function ($query) use ($user) {
            if ($user) {
                $query->where('user_id', $user->id);
            }
        }])
        ->where('is_active', true)
        ->where('is_displayed', true);

        if ($search !== '') {
            $tryoutsQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        match ($sort) {
            'oldest' => $tryoutsQuery->orderBy('created_at', 'asc'),
            'name_asc' => $tryoutsQuery->orderBy('name', 'asc'),
            'name_desc' => $tryoutsQuery->orderBy('name', 'desc'),
            default => $tryoutsQuery->orderBy('created_at', 'desc'),
        };

        $tryouts = $tryoutsQuery->get();
        $pendingIndividualTryoutIds = $user
            ? \App\Models\IndividualPurchase::query()
                ->where('user_id', $user->id)
                ->where('purchasable_type', \App\Models\Tryout::class)
                ->where('status', \App\Models\IndividualPurchase::STATUS_PENDING)
                ->pluck('purchasable_id')
                ->map(fn ($id) => (int) $id)
                ->all()
            : [];

        // Mark each tryout with access status
        foreach ($tryouts as $tryout) {
            $tryoutPackageIds = $tryout->packages->pluck('package_id')->toArray();
            $tryout->has_package_access = $user && !empty(array_intersect($tryoutPackageIds, $accessiblePackageIds));
            $tryout->has_access = $user && ($tryout->has_package_access || $tryout->canUserAccess($user->id));
            $tryout->is_pending_individual = $user && in_array((int) $tryout->tryout_id, $pendingIndividualTryoutIds, true);
            $tryout->route_package_id = $tryout->has_package_access
                ? collect($tryoutPackageIds)->first(fn ($packageId) => in_array($packageId, $accessiblePackageIds))
                : 'free';
            $tryout->access_via_package = $tryout->packages->first();
        }
        
        return view('user.pages.tryout.new-list', compact('tryouts', 'accessiblePackageIds', 'search', 'sort'));
    }

    /**
     * Show public package detail (accessible by guest)
     */
    public function detail($package_id)
    {
        $tesKoranEnabled = config('client.branding.tes_koran_enabled', true);
        $relations = $tesKoranEnabled
            ? [
                'materialsThroughDetail' => fn ($query) => $query->where('materials.is_active', true)->where('materials.is_displayed', true),
                'tryouts' => fn ($query) => $query->where('tryouts.is_active', true)->where('tryouts.is_displayed', true),
                'tryouts.tryoutDetails',
                'classes',
                'tesKorans' => fn ($query) => $query->where('tes_korans.is_active', true)->where('tes_korans.is_displayed', true),
                'detailPackages',
            ]
            : [
                'materialsThroughDetail' => fn ($query) => $query->where('materials.is_active', true)->where('materials.is_displayed', true),
                'tryouts' => fn ($query) => $query->where('tryouts.is_active', true)->where('tryouts.is_displayed', true),
                'tryouts.tryoutDetails',
                'classes',
                'detailPackages',
            ];

        $package = Package::with($relations)
            ->where('status', 'active')
            ->where('is_displayed', true)
            ->findOrFail($package_id);
        
        // Check if user is logged in and has access
        $hasAccess = false;
        $isOwned = false;
        $isPendingConditional = false;
        $pendingPackagePayment = null;
        
        if (Auth::check()) {
            $hasAccess = UserPackageAcces::where('user_id', Auth::id())
                ->where('package_id', $package_id)
                ->where('status', 'active')
                ->where(function ($query) {
                    $query->whereNull('end_date')
                        ->orWhere('end_date', '>', Carbon::now());
                })
                ->exists();
            $isOwned = $hasAccess;
            $isPendingConditional = UserPackageAcces::where('user_id', Auth::id())
                ->where('package_id', $package_id)
                ->where('requirement_status', 'pending')
                ->exists();
            $pendingPackagePayment = $this->pendingPackagePaymentsForPackages([(int) $package_id])->get((int) $package_id);
        }
        
        // Calculate stats counts from materialsThroughDetail
        $totalVideos = 0;
        $totalDocuments = 0;
        $totalLiveSessions = 0;
        $totalMaterials = $package->materialsThroughDetail->count();

        foreach ($package->materialsThroughDetail as $material) {
            switch ($material->type) {
                case 'video':
                    $totalVideos++;
                    break;
                case 'document':
                    $totalDocuments++;
                    break;
                case 'live_session':
                    $totalLiveSessions++;
                    break;
            }
        }

        $publicDiscounts = Discount::query()
            ->publicAvailable()
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->filter(fn (Discount $discount) => $discount->appliesToPurchaseType('package')
                && $discount->appliesToPackage($package->package_id)
            )
            ->values();
        $packageAutomaticDiscounts = $package->type_price === 'paid'
            ? $this->automaticDiscountsForPackages(collect([$package]), Discount::query()
                ->automaticAvailable()
                ->with('tryout:tryout_id,name')
                ->orderBy('created_at', 'desc')
                ->get())
            : null;
        $packageAutomaticDiscount = $packageAutomaticDiscounts[$package->package_id] ?? null;
        $affiliateDiscountPreview = $package->type_price === 'paid'
            ? $this->affiliateDiscountPreview((int) $package->price)
            : null;

        return view('user.pages.package.detail-public', compact(
            'package',
            'hasAccess',
            'isOwned',
            'isPendingConditional',
            'pendingPackagePayment',
            'totalVideos',
            'totalDocuments',
            'totalLiveSessions',
            'totalMaterials',
            'publicDiscounts',
            'packageAutomaticDiscount',
            'affiliateDiscountPreview'
        ));
    }
}
