<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\UserPackageAcces;
use App\Models\User;
use App\Models\Package;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Services\AffiliateService;
use App\Services\PurchaseAccessDuration;

class PembayaranController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status', Payment::STATUS_PENDING);
        $method = $request->get('method');
        $search = trim((string) $request->get('search', ''));

        $paymentsQuery = Payment::with(['user', 'package'])
            ->when($status && $status !== 'all', fn ($query) => $query->where('status', $status))
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

        $payments = $paymentsQuery->paginate(15)->withQueryString();

        // Get summary statistics
        $totalPayments = Payment::count();
        $successPayments = Payment::where('status', 'success')->count();
        $pendingPayments = Payment::where('status', 'pending')->count();
        $failedOnlyPayments = Payment::where('status', 'failed')->count();
        $expiredPayments = Payment::where('status', 'expired')->count();
        $failedPayments = $failedOnlyPayments + $expiredPayments;
        $paymentMethods = Payment::query()
            ->whereNotNull('payment_method')
            ->select('payment_method')
            ->distinct()
            ->orderBy('payment_method')
            ->pluck('payment_method');

        return view('admin.pages.pembayaran.index', compact(
            'payments',
            'totalPayments',
            'successPayments',
            'pendingPayments',
            'failedPayments',
            'failedOnlyPayments',
            'expiredPayments',
            'paymentMethods',
            'status',
            'method',
            'search'
        ));
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
}
