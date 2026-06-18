<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\IndividualPurchase;
use App\Models\Kecermatan;
use App\Models\Material;
use App\Models\Payment;
use App\Models\TesKoran;
use App\Models\Tryout;
use App\Models\UserPackageAcces;
use App\Models\User;
use App\Models\Package;
use App\Models\UserMaterialAccess;
use App\Models\UserTryoutAccess;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Services\AffiliateService;
use App\Services\PurchaseAccessDuration;
use Illuminate\Pagination\LengthAwarePaginator;

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

        $status = $request->get('status', Payment::STATUS_PENDING);
        $method = $request->get('method');
        $productType = $request->get('product_type', 'all');
        $summaryMetric = $request->get('summary_metric', 'count');
        $search = trim((string) $request->get('search', ''));
        $allowedProductTypes = ['all', 'package', 'material', 'tryout', 'kecermatan'];
        $canManageTesKoran = $request->user()?->hasPermission('tes_koran', 'view') ?? false;

        if ($canManageTesKoran) {
            $allowedProductTypes[] = 'tes_koran';
        }

        if (!in_array($productType, $allowedProductTypes, true)) {
            $productType = 'all';
        }

        if (!in_array($summaryMetric, ['count', 'amount'], true)) {
            $summaryMetric = 'count';
        }

        $paymentsQuery = Payment::with(['user', 'package'])
            ->when($status && $status !== 'all', function ($query) use ($status) {
                if ($status === 'failed') {
                    $query->whereIn('status', ['failed', 'expired']);
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

        if (!in_array($productType, ['all', 'package'], true)) {
            $paymentsQuery->whereRaw('1 = 0');
        }

        $individualTypes = [
            Material::class => 'material',
            Tryout::class => 'tryout',
            Kecermatan::class => 'kecermatan',
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
                            if ($type === Material::class) {
                                $itemQuery->where('title', 'like', "%{$search}%");
                                return;
                            }

                            $itemQuery->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('created_at', 'desc');

        $paymentRows = $paymentsQuery->get()->map(fn (Payment $payment) => [
            'id' => $payment->payment_id,
            'source' => 'package',
            'transaction_id' => $payment->transaction_id,
            'user_name' => $payment->user->name ?? 'Unknown User',
            'user_email' => $payment->user->email ?? 'No email',
            'item_name' => $payment->package->name ?? 'Unknown Package',
            'item_type' => 'Paket',
            'amount' => (int) $payment->total_amount,
            'payment_method' => $payment->payment_method ?? 'Unknown',
            'status' => $payment->status,
            'status_label' => match ($payment->status) {
                'success' => 'Berhasil',
                'pending' => 'Pending',
                'failed' => 'Gagal',
                'expired' => 'Expired',
                default => ucfirst((string) $payment->status),
            },
            'status_class' => match ($payment->status) {
                'success' => 'bg-green-100 text-green-700',
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
                    'Tryout' => 'Tryout',
                    'TesKoran' => 'Tes Koran',
                    'Kecermatan' => 'Kecermatan',
                    default => 'Materi',
                },
                'amount' => (int) $purchase->total_amount,
                'payment_method' => $purchase->payment_method ?? 'Unknown',
                'status' => $purchase->status,
                'status_label' => match ($purchase->status) {
                    IndividualPurchase::STATUS_APPROVED => 'Berhasil',
                    IndividualPurchase::STATUS_PENDING => 'Pending',
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

        $paymentRows = $paymentRows
            ->concat($individualRows)
            ->sortByDesc('created_at')
            ->values();
        $perPage = 15;
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
            ->filter()
            ->unique()
            ->sort()
            ->values();
        $productTypeOptions = [
            'all' => 'Semua Produk',
            'package' => 'Paket',
            'material' => 'Materi',
            'tryout' => 'Tryout',
            'kecermatan' => 'Kecermatan',
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
            'pending' => ['pending'],
            'failed' => ['failed', 'expired'],
        ];
        $individualStatuses = [
            'total' => null,
            'success' => [IndividualPurchase::STATUS_APPROVED],
            'pending' => [IndividualPurchase::STATUS_PENDING],
            'failed' => [IndividualPurchase::STATUS_REJECTED],
        ];

        return collect(['total', 'success', 'pending', 'failed'])
            ->mapWithKeys(function (string $key) use ($summaryMetric, $packageStatuses, $individualStatuses, $individualTypeClasses) {
                $packageQuery = Payment::query();
                $individualQuery = IndividualPurchase::query()->whereIn('purchasable_type', $individualTypeClasses);

                if ($packageStatuses[$key] !== null) {
                    $packageQuery->whereIn('status', $packageStatuses[$key]);
                }

                if ($individualStatuses[$key] !== null) {
                    $individualQuery->whereIn('status', $individualStatuses[$key]);
                }

                $value = $summaryMetric === 'amount'
                    ? (int) $packageQuery->sum('total_amount') + (int) $individualQuery->sum('total_amount')
                    : $packageQuery->count() + $individualQuery->count();

                return [$key => $value];
            })
            ->all();
    }

    public function createManual()
    {
        $packages = Package::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('admin.pages.pembayaran.manual-create', compact('packages'));
    }

    public function storeManual(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
            'package_id' => 'required|exists:packages,package_id',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string|max:50',
            'notes' => 'nullable|string|max:500',
        ], [
            'email.exists' => 'Email user tidak ditemukan.',
            'package_id.exists' => 'Paket tidak valid.',
        ]);

        $user = User::where('email', $validated['email'])->first();
        $package = Package::where('package_id', $validated['package_id'])->first();

        if (!$user || !$package) {
            return redirect()->route('admin.pembayaran.index')
                ->with('error', 'User atau paket tidak ditemukan.');
        }

        $transactionId = 'MANUAL-' . Str::upper(Str::random(10));
        while (Payment::where('transaction_id', $transactionId)->exists()) {
            $transactionId = 'MANUAL-' . Str::upper(Str::random(10));
        }

        Payment::create([
            'transaction_id' => $transactionId,
            'user_id' => $user->id,
            'package_id' => $package->package_id,
            'amount' => $validated['amount'],
            'admin_fee' => 0,
            'total_amount' => $validated['amount'],
            'status' => 'pending',
            'payment_method' => $validated['payment_method'],
            'payment_details' => json_encode(['manual' => true]),
            'notes' => $validated['notes'] ?? ('Manual entry by ' . Auth::user()->name),
        ]);

        return redirect()->route('admin.pembayaran.index')
            ->with('success', 'Pembayaran manual berhasil ditambahkan.');
    }

    public function show($id)
    {
        $payment = Payment::with(['user', 'package'])->findOrFail($id);

        // Get payment details from JSON
        $paymentDetails = $payment->payment_details ? json_decode($payment->payment_details, true) : [];

        // Get user's total transactions
        $userTotalTransactions = Payment::where('user_id', $payment->user_id)->count();

        // Get user package access info
        $userAccess = UserPackageAcces::where('user_id', $payment->user_id)
            ->where('package_id', $payment->package_id)
            ->first();

        // Ensure dates are properly formatted
        if ($payment->paid_at && !($payment->paid_at instanceof \Carbon\Carbon)) {
            $payment->paid_at = \Carbon\Carbon::parse($payment->paid_at);
        }

        return view('admin.pages.pembayaran.show', compact(
            'payment',
            'paymentDetails',
            'userTotalTransactions',
            'userAccess'
        ));
    }

    public function confirm(Request $request, $id)
    {
        $payment = Payment::findOrFail($id);

        if ($payment->status !== 'pending') {
            return redirect()->route('admin.pembayaran.show', $id)
                ->with('error', 'Pembayaran sudah diproses sebelumnya');
        }

        try {
            // Update payment status
            $payment->update([
                'status' => 'success',
                'paid_at' => Carbon::now()
            ]);

            // Check if user already has access
            $existingAccess = UserPackageAcces::where('user_id', $payment->user_id)
                ->where('package_id', $payment->package_id)
                ->where('status', 'active')
                ->where(function ($query) {
                    $query->whereNull('end_date')->orWhere('end_date', '>', Carbon::now());
                })
                ->first();

            if (!$existingAccess) {
                $package = $payment->package ?: Package::find($payment->package_id);
                $startDate = Carbon::now();

                // Give user access to package
                UserPackageAcces::create([
                    'user_id' => $payment->user_id,
                    'package_id' => $payment->package_id,
                    'start_date' => $startDate,
                    'end_date' => $package ? PurchaseAccessDuration::expiresAt($package, $startDate) : $startDate->copy()->addYear(),
                    'status' => 'active',
                    'payment_amount' => $payment->total_amount,
                    'payment_status' => 'paid',
                    'notes' => 'Manually confirmed by admin: ' . Auth::user()->name,
                    'created_by' => Auth::user()->id
                ]);
            }

            app(AffiliateService::class)->recordCommission($payment);

            return redirect()->route('admin.pembayaran.show', $id)
                ->with('success', 'Pembayaran berhasil dikonfirmasi dan akses user telah diaktifkan');
        } catch (\Exception $e) {
            return redirect()->route('admin.pembayaran.show', $id)
                ->with('error', 'Gagal mengkonfirmasi pembayaran: ' . $e->getMessage());
        }
    }

    public function reject(Request $request, $id)
    {
        $payment = Payment::findOrFail($id);

        if ($payment->status !== 'pending') {
            return redirect()->route('admin.pembayaran.show', $id)
                ->with('error', 'Pembayaran sudah diproses sebelumnya');
        }

        $request->validate([
            'rejection_reason' => 'nullable|string|max:500'
        ]);

        try {
            // Update payment status
            $payment->update([
                'status' => 'failed',
                'notes' => 'Rejected by admin: ' . ($request->rejection_reason ?? 'No reason provided')
            ]);

            return redirect()->route('admin.pembayaran.show', $id)
                ->with('success', 'Pembayaran berhasil ditolak');
        } catch (\Exception $e) {
            return redirect()->route('admin.pembayaran.show', $id)
                ->with('error', 'Gagal menolak pembayaran: ' . $e->getMessage());
        }
    }

    public function showIndividual($id)
    {
        $purchase = IndividualPurchase::with(['user', 'purchasable', 'approver'])->findOrFail($id);

        $itemType = class_basename($purchase->purchasable_type);
        $itemTitle = $purchase->purchasable?->title
            ?? $purchase->purchasable?->name
            ?? 'N/A';

        $paymentDetails = $purchase->payment_details ? json_decode($purchase->payment_details, true) : [];
        $proofPath = $paymentDetails['proof_path'] ?? null;

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
                'approved_by' => Auth::id(),
            ]);

            return redirect()->route('admin.pembayaran.item.show', $id)
                ->with('success', 'Pembelian berhasil disetujui. User mendapat akses.');
        } catch (\Exception $e) {
            return redirect()->route('admin.pembayaran.item.show', $id)
                ->with('error', 'Gagal mengkonfirmasi: ' . $e->getMessage());
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
                ->with('error', 'Gagal menolak: ' . $e->getMessage());
        }
    }
}
