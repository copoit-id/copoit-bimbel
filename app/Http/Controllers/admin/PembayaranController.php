<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\BillInvoicePayment;
use App\Models\ClassModel;
use App\Models\IndividualPurchase;
use App\Models\Material;
use App\Models\Package;
use App\Models\Payment;
use App\Models\TesKoran;
use App\Models\Tryout;
use App\Models\User;
use App\Models\UserClassAccess;
use App\Models\UserMaterialAccess;
use App\Models\UserPackageAcces;
use App\Models\UserTryoutAccess;
use App\Services\PackagePaymentInstallmentService;
use App\Services\PurchaseAccessDuration;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PembayaranController extends Controller
{
    public function index(Request $request)
    {
        if ($request->has('tab')) {
            $query = $request->except(['tab', 'type']);
            if ($request->get('tab') === 'individual' && $request->filled('type')) {
                $query['product_type'] = $request->get('type');
            }

            return redirect()->route('admin.pembayaran.index', $query);
        }

        $status = $request->get('status', 'unpaid');
        $method = $request->get('method');
        $productType = $request->get('product_type', 'all');
        $summaryMetric = $request->get('summary_metric', 'count');
        $search = trim((string) $request->get('search', ''));
        $allowedProductTypes = ['all', 'package', 'material', 'class', 'tryout', 'recurring_bill'];
        $canManageTesKoran = $request->user()?->hasPermission('tes_koran', 'view') ?? false;

        if ($canManageTesKoran) {
            $allowedProductTypes[] = 'tes_koran';
        }

        if (! in_array($productType, $allowedProductTypes, true)) {
            $productType = 'all';
        }

        if (! in_array($summaryMetric, ['count', 'amount'], true)) {
            $summaryMetric = 'count';
        }

        $paymentsQuery = Payment::with(['user', 'package'])
            ->withCount('installments')
            ->withSum('installments as installments_paid_amount', 'amount')
            ->when($status && $status !== 'all', function ($query) use ($status) {
                if ($status === 'failed') {
                    $query->whereIn('status', ['failed', 'expired']);

                    return;
                }

                if ($status === 'unpaid') {
                    $query->whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_PARTIAL]);

                    return;
                }

                $query->where('status', $status);
            })
            ->when($method, fn ($query) => $query->where('payment_method', $method))
            ->when($search !== '', fn ($query) => $query->where(function ($searchQuery) use ($search) {
                $searchQuery->where('transaction_id', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($userQuery) => $userQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                    )
                    ->orWhereHas('package', fn ($packageQuery) => $packageQuery
                        ->where('name', 'like', "%{$search}%")
                    );
            }))
            ->orderBy('created_at', 'desc');

        if (! in_array($productType, ['all', 'package'], true)) {
            $paymentsQuery->whereRaw('1 = 0');
        }

        $individualTypes = [
            Material::class => 'material',
            ClassModel::class => 'class',
            Tryout::class => 'tryout',
        ];

        if ($canManageTesKoran) {
            $individualTypes[TesKoran::class] = 'tes_koran';
        }

        $individualQuery = IndividualPurchase::with(['user', 'purchasable'])
            ->whereIn('purchasable_type', array_keys($individualTypes))
            ->when($productType !== 'all' && $productType !== 'package', function ($query) use ($individualTypes, $productType) {
                $typeClass = array_search($productType, $individualTypes, true);
                $query->where('purchasable_type', $typeClass ?: Material::class);
            })
            ->when($productType === 'package', fn ($query) => $query->whereRaw('1 = 0'))
            ->when($status && $status !== 'all', function ($query) use ($status) {
                $individualStatus = match ($status) {
                    'success' => IndividualPurchase::STATUS_APPROVED,
                    'failed' => IndividualPurchase::STATUS_REJECTED,
                    'unpaid' => IndividualPurchase::STATUS_PENDING,
                    default => $status,
                };

                $query->where('status', $individualStatus);
            })
            ->when($method, fn ($query) => $query->where('payment_method', $method))
            ->when($search !== '', function ($query) use ($search, $individualTypes) {
                $query->where(function ($searchQuery) use ($search, $individualTypes) {
                    $searchQuery->where('transaction_id', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($userQuery) => $userQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                        )
                        ->orWhereHasMorph('purchasable', array_keys($individualTypes), function ($itemQuery, string $type) use ($search) {
                            if ($type === Material::class || $type === ClassModel::class) {
                                $itemQuery->where('title', 'like', "%{$search}%");

                                return;
                            }

                            $itemQuery->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('created_at', 'desc');

        $billPaymentsQuery = BillInvoicePayment::with(['invoice:id,recurring_bill_id,user_id,title,invoice_number', 'invoice.user:id,name,email'])
            ->when($productType !== 'all' && $productType !== 'recurring_bill', fn ($query) => $query->whereRaw('1 = 0'))
            ->when($status && $status !== 'all' && $status !== 'success', fn ($query) => $query->whereRaw('1 = 0'))
            ->when($method, fn ($query) => $query->where('payment_method', $method))
            ->when($search !== '', fn ($query) => $query->where(function ($searchQuery) use ($search) {
                $searchQuery->where('receipt_number', 'like', "%{$search}%")
                    ->orWhereHas('invoice', fn ($invoiceQuery) => $invoiceQuery
                        ->where('title', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($userQuery) => $userQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                        )
                    );
            }))
            ->orderByDesc('paid_at');

        $paymentRows = $paymentsQuery->get()->map(fn (Payment $payment) => [
            'id' => $payment->payment_id,
            'source' => 'package',
            'transaction_id' => $payment->transaction_id,
            'user_name' => $payment->user->name ?? 'Unknown User',
            'user_email' => $payment->user->email ?? 'No email',
            'item_name' => $payment->package->name ?? 'Unknown Package',
            'item_type' => 'Paket',
            'amount' => (int) $payment->total_amount,
            'paid_amount' => $payment->paid_amount,
            'remaining_amount' => $payment->remaining_amount,
            'installments_count' => (int) ($payment->installments_count ?? 0),
            'payment_method' => $payment->payment_method ?? 'Unknown',
            'status' => $payment->status,
            'status_label' => match ($payment->status) {
                'success' => 'Lunas',
                'partial' => 'Belum Lunas',
                'pending' => 'Belum Lunas',
                'failed' => 'Gagal',
                'expired' => 'Expired',
                default => ucfirst((string) $payment->status),
            },
            'status_class' => match ($payment->status) {
                'success' => 'bg-green-100 text-green-700',
                'partial' => 'bg-blue-100 text-blue-700',
                'pending' => 'bg-yellow-100 text-yellow-700',
                'failed' => 'bg-red-100 text-red-700',
                'expired' => 'bg-gray-100 text-gray-700',
                default => 'bg-gray-100 text-gray-700',
            },
            'detail_route' => route('admin.pembayaran.show', $payment->payment_id),
            'confirm_route' => route('admin.pembayaran.confirm', $payment->payment_id),
            'created_at' => $payment->created_at,
        ]);

        $individualRows = $individualQuery->get()->map(function (IndividualPurchase $purchase) {
            $itemType = class_basename($purchase->purchasable_type ?? '');

            return [
                'id' => $purchase->id,
                'source' => 'individual',
                'transaction_id' => $purchase->transaction_id,
                'user_name' => $purchase->user->name ?? 'Unknown User',
                'user_email' => $purchase->user->email ?? 'No email',
                'item_name' => $purchase->purchasable?->title ?? $purchase->purchasable?->name ?? 'N/A',
                'item_type' => match ($itemType) {
                    'ClassModel' => 'Kelas Zoom',
                    'Tryout' => 'Tryout',
                    'TesKoran' => 'Tes Koran',
                    default => 'Materi',
                },
                'amount' => (int) $purchase->total_amount,
                'paid_amount' => $purchase->status === IndividualPurchase::STATUS_APPROVED ? (int) $purchase->total_amount : 0,
                'remaining_amount' => $purchase->status === IndividualPurchase::STATUS_APPROVED ? 0 : (int) $purchase->total_amount,
                'installments_count' => 0,
                'payment_method' => $purchase->payment_method ?? 'Unknown',
                'status' => $purchase->status,
                'status_label' => match ($purchase->status) {
                    IndividualPurchase::STATUS_APPROVED => 'Lunas',
                    IndividualPurchase::STATUS_PENDING => 'Belum Lunas',
                    IndividualPurchase::STATUS_REJECTED => 'Gagal',
                    default => ucfirst((string) $purchase->status),
                },
                'status_class' => match ($purchase->status) {
                    IndividualPurchase::STATUS_APPROVED => 'bg-green-100 text-green-700',
                    IndividualPurchase::STATUS_PENDING => 'bg-yellow-100 text-yellow-700',
                    IndividualPurchase::STATUS_REJECTED => 'bg-red-100 text-red-700',
                    default => 'bg-gray-100 text-gray-700',
                },
                'detail_route' => route('admin.pembayaran.item.show', $purchase->id),
                'confirm_route' => route('admin.pembayaran.item.confirm', $purchase->id),
                'created_at' => $purchase->created_at,
            ];
        });

        $billPaymentRows = $billPaymentsQuery->get()->map(fn (BillInvoicePayment $payment): array => [
            'id' => $payment->id,
            'source' => 'recurring_bill',
            'transaction_id' => $payment->receipt_number,
            'user_name' => $payment->invoice?->user?->name ?? 'Unknown User',
            'user_email' => $payment->invoice?->user?->email ?? 'No email',
            'item_name' => $payment->invoice?->title ?? 'Tagihan Rutin',
            'item_type' => 'Tagihan Rutin',
            'amount' => (int) $payment->amount,
            'paid_amount' => (int) $payment->amount,
            'remaining_amount' => 0,
            'installments_count' => 1,
            'payment_method' => $payment->payment_method,
            'status' => 'success',
            'status_label' => 'Lunas',
            'status_class' => 'bg-green-100 text-green-700',
            'detail_route' => $payment->invoice?->recurring_bill_id
                ? route('admin.recurring-bills.show', $payment->invoice->recurring_bill_id)
                : route('admin.pembayaran.index'),
            'confirm_route' => null,
            'created_at' => $payment->paid_at,
        ]);

        $paymentRows = $paymentRows
            ->concat($individualRows)
            ->concat($billPaymentRows)
            ->sortByDesc('created_at')
            ->values();
        $perPage = \App\Support\Pagination::perPage(15);
        $page = LengthAwarePaginator::resolveCurrentPage();
        $payments = new LengthAwarePaginator(
            $paymentRows->forPage($page, $perPage)->values(),
            $paymentRows->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
        $payments->withQueryString();

        $summary = $this->paymentSummary(array_keys($individualTypes), $summaryMetric);
        $paymentMethods = Payment::query()
            ->whereNotNull('payment_method')
            ->select('payment_method')
            ->distinct()
            ->pluck('payment_method')
            ->merge(
                IndividualPurchase::query()
                    ->whereIn('purchasable_type', array_keys($individualTypes))
                    ->whereNotNull('payment_method')
                    ->select('payment_method')
                    ->distinct()
                    ->pluck('payment_method')
            )
            ->merge(
                BillInvoicePayment::query()
                    ->whereNotNull('payment_method')
                    ->select('payment_method')
                    ->distinct()
                    ->pluck('payment_method')
            )
            ->filter()
            ->unique()
            ->sort()
            ->values();
        $productTypeOptions = [
            'all' => 'Semua Produk',
            'package' => 'Paket',
            'material' => 'Materi',
            'class' => 'Kelas Zoom',
            'tryout' => 'Tryout',
            'recurring_bill' => 'Tagihan Rutin',
        ];

        if ($canManageTesKoran) {
            $productTypeOptions['tes_koran'] = 'Tes Koran';
        }

        return view('admin.pages.pembayaran.index', compact(
            'payments',
            'summary',
            'paymentMethods',
            'productTypeOptions',
            'status',
            'method',
            'productType',
            'summaryMetric',
            'search',
            'canManageTesKoran'
        ));
    }

    private function paymentSummary(array $individualTypeClasses, string $summaryMetric): array
    {
        $packageStatuses = [
            'total' => null,
            'success' => ['success'],
            'partial' => ['partial'],
            'pending' => ['pending'],
            'failed' => ['failed', 'expired'],
        ];
        $individualStatuses = [
            'total' => null,
            'success' => [IndividualPurchase::STATUS_APPROVED],
            'partial' => [],
            'pending' => [IndividualPurchase::STATUS_PENDING],
            'failed' => [IndividualPurchase::STATUS_REJECTED],
        ];

        return collect(['total', 'success', 'partial', 'pending', 'failed'])
            ->mapWithKeys(function (string $key) use ($summaryMetric, $packageStatuses, $individualStatuses, $individualTypeClasses) {
                $packageQuery = Payment::query();
                $individualQuery = IndividualPurchase::query()->whereIn('purchasable_type', $individualTypeClasses);
                $billPaymentQuery = BillInvoicePayment::query();

                if ($packageStatuses[$key] !== null) {
                    $packageQuery->whereIn('status', $packageStatuses[$key]);
                }

                if ($individualStatuses[$key] !== null) {
                    $individualQuery->whereIn('status', $individualStatuses[$key]);
                }

                if (! in_array($key, ['total', 'success'], true)) {
                    $billPaymentQuery->whereRaw('1 = 0');
                }

                $value = $summaryMetric === 'amount'
                    ? (int) $packageQuery->sum('total_amount') + (int) $individualQuery->sum('total_amount') + (int) $billPaymentQuery->sum('amount')
                    : $packageQuery->count() + $individualQuery->count() + $billPaymentQuery->count();

                return [$key => $value];
            })
            ->all();
    }

    public function createManual()
    {
        $users = User::query()
            ->where('role', 'user')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
        $packages = Package::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
        $paymentUniqueCodeEnabled = (bool) config('client.branding.payment_unique_code_enabled', true);
        $manualPaymentUniqueCode = $paymentUniqueCodeEnabled
            ? Payment::generateManualUniqueCode()
            : null;

        return view('admin.pages.pembayaran.manual-create', compact(
            'users',
            'packages',
            'paymentUniqueCodeEnabled',
            'manualPaymentUniqueCode'
        ));
    }

    public function storeManual(Request $request, PackagePaymentInstallmentService $installmentService)
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'package_id' => 'required|exists:packages,package_id',
            'amount' => 'required|numeric|min:0',
            'payment_status_choice' => 'required|in:paid,unpaid',
            'initial_payment_amount' => 'nullable|integer|min:0',
            'payment_method' => 'required|string|max:50',
            'payment_unique_code' => 'nullable|integer|min:1|max:999',
            'notes' => 'nullable|string|max:500',
        ], [
            'user_id.exists' => 'Peserta tidak ditemukan.',
            'package_id.exists' => 'Paket tidak valid.',
        ]);

        $user = User::query()
            ->whereKey($validated['user_id'])
            ->where('role', 'user')
            ->first();
        $package = Package::where('package_id', $validated['package_id'])->first();

        if (! $user || ! $package) {
            return redirect()->route('admin.pembayaran.index')
                ->with('error', 'User atau paket tidak ditemukan.');
        }

        $transactionId = 'MANUAL-'.Str::upper(Str::random(10));
        while (Payment::where('transaction_id', $transactionId)->exists()) {
            $transactionId = 'MANUAL-'.Str::upper(Str::random(10));
        }

        $amount = (int) $validated['amount'];
        $useUniqueCode = (bool) config('client.branding.payment_unique_code_enabled', true)
            && $amount > 0
            && strtolower((string) $validated['payment_method']) === 'manual';
        $uniqueCode = $useUniqueCode ? (int) ($validated['payment_unique_code'] ?? 0) : 0;

        if ($useUniqueCode && $uniqueCode <= 0) {
            $uniqueCode = Payment::generateManualUniqueCode();
        }

        if ($useUniqueCode && ! Payment::isManualUniqueCodeAvailable($uniqueCode)) {
            return redirect()->route('admin.pembayaran.manual.create')
                ->withInput()
                ->with('error', 'Kode unik sudah dipakai hari ini. Silakan buka ulang halaman tambah pembayaran.');
        }

        $totalAmount = $amount + ($useUniqueCode ? $uniqueCode : 0);

        $initialPaymentAmount = $validated['payment_status_choice'] === 'paid'
            ? $totalAmount
            : (int) ($validated['initial_payment_amount'] ?? 0);
        if ($initialPaymentAmount > $totalAmount) {
            return redirect()->route('admin.pembayaran.manual.create')
                ->withInput()
                ->withErrors([
                    'initial_payment_amount' => 'Cicilan pertama tidak boleh melebihi total tagihan Rp '.number_format($totalAmount, 0, ',', '.').'.',
                ]);
        }

        $payment = DB::transaction(function () use ($transactionId, $user, $package, $amount, $useUniqueCode, $uniqueCode, $totalAmount, $validated, $initialPaymentAmount, $request, $installmentService): Payment {
            $payment = Payment::create([
                'transaction_id' => $transactionId,
                'user_id' => $user->id,
                'package_id' => $package->package_id,
                'amount' => $amount,
                'admin_fee' => 0,
                'unique_code' => $useUniqueCode ? $uniqueCode : null,
                'unique_code_date' => $useUniqueCode ? now()->toDateString() : null,
                'total_amount' => $totalAmount,
                'status' => Payment::STATUS_PENDING,
                'payment_method' => $validated['payment_method'],
                'payment_details' => json_encode([
                    'manual' => true,
                    'base_amount' => $amount,
                    'unique_code' => $useUniqueCode ? $uniqueCode : null,
                ]),
                'notes' => $validated['notes'] ?? ('Manual entry by '.Auth::user()->name),
            ]);

            if ($initialPaymentAmount > 0) {
                return $installmentService->record(
                    $payment,
                    $initialPaymentAmount,
                    $validated['payment_method'],
                    $validated['notes'] ?? null,
                    $request->user(),
                );
            }

            return $payment;
        });

        return redirect()->route('admin.pembayaran.index')
            ->with('success', $payment->isSuccess()
                ? 'Pembayaran manual disimpan sebagai lunas dan akses paket telah diaktifkan.'
                : ($payment->isPartial()
                    ? 'Pembayaran manual dibuat. Uang diterima tercatat dan status masih belum lunas.'
                    : 'Pembayaran manual dibuat dengan status belum lunas.'));
    }

    public function show($id)
    {
        $payment = Payment::with(['user', 'package', 'installments.paidBy'])->findOrFail($id);

        // Get payment details from JSON
        $paymentDetails = $payment->payment_details ? json_decode($payment->payment_details, true) : [];

        // Get user's total transactions
        $userTotalTransactions = Payment::where('user_id', $payment->user_id)->count();

        // Get user package access info
        $userAccess = UserPackageAcces::where('user_id', $payment->user_id)
            ->where('package_id', $payment->package_id)
            ->first();

        // Ensure dates are properly formatted
        if ($payment->paid_at && ! ($payment->paid_at instanceof Carbon)) {
            $payment->paid_at = Carbon::parse($payment->paid_at);
        }

        return view('admin.pages.pembayaran.show', compact(
            'payment',
            'paymentDetails',
            'userTotalTransactions',
            'userAccess'
        ));
    }

    public function confirm(Request $request, $id, PackagePaymentInstallmentService $installmentService)
    {
        $payment = Payment::findOrFail($id);

        if ($payment->status !== Payment::STATUS_PENDING) {
            return redirect()->route('admin.pembayaran.show', $id)
                ->with('error', 'Pembayaran sudah diproses sebelumnya.');
        }

        try {
            $installmentService->record(
                $payment,
                $payment->remaining_amount,
                'manual',
                'Pembayaran dilunasi melalui konfirmasi admin.',
                $request->user(),
            );

            return redirect()->route('admin.pembayaran.show', $id)
                ->with('success', 'Pembayaran berhasil dilunasi dan akses paket telah diaktifkan.');
        } catch (\Throwable $e) {
            return redirect()->route('admin.pembayaran.show', $id)
                ->with('error', 'Gagal mengkonfirmasi pembayaran: '.$e->getMessage());
        }
    }

    public function recordInstallment(Request $request, Payment $payment, PackagePaymentInstallmentService $installmentService)
    {
        if (! $payment->isManualEntry()) {
            abort(403, 'Cicilan hanya dapat dicatat untuk pembayaran manual.');
        }

        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'payment_method' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $payment = $installmentService->record(
                $payment,
                (int) $validated['amount'],
                $validated['payment_method'],
                $validated['notes'] ?? null,
                $request->user(),
            );

            return back()->with('success', $payment->isSuccess()
                ? 'Cicilan terakhir tercatat. Pembayaran lunas dan akses paket telah diaktifkan.'
                : 'Cicilan tercatat. Sisa pembayaran: Rp '.number_format($payment->remaining_amount, 0, ',', '.').'.');
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors([
                'amount' => $e->getMessage(),
            ]);
        }
    }

    public function reject(Request $request, $id)
    {
        $payment = Payment::findOrFail($id);

        if ($payment->status !== Payment::STATUS_PENDING || $payment->installments()->exists()) {
            return redirect()->route('admin.pembayaran.show', $id)
                ->with('error', 'Pembayaran yang sudah memiliki cicilan tidak dapat ditolak.');
        }

        $request->validate([
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        try {
            // Update payment status
            $payment->update([
                'status' => 'failed',
                'notes' => 'Rejected by admin: '.($request->rejection_reason ?? 'No reason provided'),
            ]);

            return redirect()->route('admin.pembayaran.show', $id)
                ->with('success', 'Pembayaran berhasil ditolak');
        } catch (\Exception $e) {
            return redirect()->route('admin.pembayaran.show', $id)
                ->with('error', 'Gagal menolak pembayaran: '.$e->getMessage());
        }
    }

    public function showIndividual($id)
    {
        $purchase = IndividualPurchase::with(['user', 'purchasable', 'approver'])->findOrFail($id);

        $itemType = class_basename($purchase->purchasable_type);
        $itemTitle = $purchase->purchasable?->title
            ?? $purchase->purchasable?->name
            ?? 'N/A';

        $paymentDetails = is_array($purchase->payment_details)
            ? $purchase->payment_details
            : ($purchase->payment_details ? json_decode($purchase->payment_details, true) : []);
        $proofPaths = collect($paymentDetails['requirement_proof_paths'] ?? [])
            ->when($paymentDetails['proof_path'] ?? null, fn ($paths, $proofPath) => $paths->push($proofPath))
            ->filter()
            ->unique()
            ->values();
        $proofPath = $proofPaths->first();

        return view('admin.pages.individual-purchase.show', compact(
            'purchase',
            'itemType',
            'itemTitle',
            'paymentDetails',
            'proofPath'
        ));
    }

    public function confirmIndividual(Request $request, $id)
    {
        $purchase = IndividualPurchase::findOrFail($id);

        if ($purchase->status !== IndividualPurchase::STATUS_PENDING) {
            return redirect()->route('admin.pembayaran.item.show', $id)
                ->with('error', 'Pembelian sudah diproses sebelumnya.');
        }

        try {
            $purchase->loadMissing('purchasable');
            $approvedAt = Carbon::now();
            $accessExpiresAt = $purchase->purchasable
                ? PurchaseAccessDuration::expiresAt($purchase->purchasable, $approvedAt)
                : null;

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
                'approved_by' => Auth::id(),
            ]);

            return redirect()->route('admin.pembayaran.item.show', $id)
                ->with('success', 'Pembelian berhasil disetujui. User mendapat akses.');
        } catch (\Exception $e) {
            return redirect()->route('admin.pembayaran.item.show', $id)
                ->with('error', 'Gagal mengkonfirmasi: '.$e->getMessage());
        }
    }

    public function rejectIndividual(Request $request, $id)
    {
        $purchase = IndividualPurchase::findOrFail($id);

        if ($purchase->status !== IndividualPurchase::STATUS_PENDING) {
            return redirect()->route('admin.pembayaran.item.show', $id)
                ->with('error', 'Pembelian sudah diproses sebelumnya.');
        }

        try {
            $purchase->update([
                'status' => IndividualPurchase::STATUS_REJECTED,
                'approved_at' => Carbon::now(),
                'approved_by' => Auth::id(),
            ]);

            return redirect()->route('admin.pembayaran.item.show', $id)
                ->with('success', 'Pembelian berhasil ditolak.');
        } catch (\Exception $e) {
            return redirect()->route('admin.pembayaran.item.show', $id)
                ->with('error', 'Gagal menolak: '.$e->getMessage());
        }
    }
}
