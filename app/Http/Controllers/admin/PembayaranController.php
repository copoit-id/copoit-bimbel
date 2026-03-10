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

class PembayaranController extends Controller
{
    public function index()
    {
        // Get all payments with user and package info
        $payments = Payment::with(['user', 'package'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Get summary statistics
        $totalPayments = Payment::count();
        $successPayments = Payment::where('status', 'success')->count();
        $pendingPayments = Payment::where('status', 'pending')->count();
        $failedPayments = Payment::whereIn('status', ['failed', 'expired'])->count();

        return view('admin.pages.pembayaran.index', compact(
            'payments',
            'totalPayments',
            'successPayments',
            'pendingPayments',
            'failedPayments'
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
                ->where('end_date', '>', Carbon::now())
                ->first();

            if (!$existingAccess) {
                // Give user access to package
                UserPackageAcces::create([
                    'user_id' => $payment->user_id,
                    'package_id' => $payment->package_id,
                    'start_date' => Carbon::now(),
                    'end_date' => Carbon::now()->addYear(),
                    'status' => 'active',
                    'payment_amount' => $payment->amount,
                    'payment_status' => 'paid',
                    'notes' => 'Manually confirmed by admin: ' . Auth::user()->name,
                    'created_by' => Auth::user()->id
                ]);
            }

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
