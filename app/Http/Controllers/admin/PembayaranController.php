<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\UserPackageAcces;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PembayaranController extends Controller
{
    public function index()
    {
        // Get all payments with user and package info
        $payments = Payment::with(['user', 'package'])
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
