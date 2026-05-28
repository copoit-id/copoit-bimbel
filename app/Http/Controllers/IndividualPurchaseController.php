<?php

namespace App\Http\Controllers;

use App\Models\IndividualPurchase;
use App\Models\Material;
use App\Models\Tryout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
            'type' => 'required|in:material,tryout',
            'id' => 'required|integer',
        ]);

        $type = $request->type;
        $id = $request->id;
        $userId = Auth::id();

        // Get the item
        if ($type === 'material') {
            $item = Material::find($id);
            $purchasableType = Material::class;
        } else {
            $item = Tryout::find($id);
            $purchasableType = Tryout::class;
        }

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Item tidak ditemukan.'
            ], 404);
        }

        // Check if item has price and is for sale (can be sold individually)
        if (!$item->is_for_sale || !$item->price || $item->price <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Item ini tidak dijual terpisah.'
            ], 400);
        }

        // Check if already purchased
        $existingPurchase = IndividualPurchase::where('user_id', $userId)
            ->where('purchasable_type', $purchasableType)
            ->where('purchasable_id', $id)
            ->where('status', 'approved')
            ->first();

        if ($existingPurchase) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah memiliki item ini.'
            ], 400);
        }

        // Check if already has pending purchase
        $pendingPurchase = IndividualPurchase::where('user_id', $userId)
            ->where('purchasable_type', $purchasableType)
            ->where('purchasable_id', $id)
            ->where('status', 'pending')
            ->first();

        if ($pendingPurchase) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah memiliki permintaan pembelian yang pending.'
            ], 400);
        }

        // Check access via package
        if ($type === 'material') {
            if ($item->canUserAccess($userId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda sudah memiliki akses ke materi ini.'
                ], 400);
            }
        } else {
            if ($item->canUserAccess($userId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda sudah memiliki akses ke tryout ini.'
                ], 400);
            }
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
            $transactionId = 'IND-' . strtoupper($type) . '-' . $id . '-' . $userId . '-' . time();

            IndividualPurchase::create([
                'user_id' => $userId,
                'purchasable_type' => $purchasableType,
                'purchasable_id' => $id,
                'price' => $item->price,
                'admin_fee' => 0,
                'total_amount' => $item->price,
                'payment_method' => 'manual',
                'status' => IndividualPurchase::STATUS_PENDING,
                'transaction_id' => $transactionId,
                'payment_details' => json_encode([
                    'proof_path' => $proofPath,
                    'proof_name' => $request->file('payment_proof')->getClientOriginalName(),
                ]),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Bukti pembayaran berhasil dikirim. Mohon tunggu verifikasi admin.'
            ]);
        }

        // For gateway payment, redirect to payment
        return response()->json([
            'success' => true,
            'redirect_url' => route('user.individual-purchase.gateway', ['type' => $type, 'id' => $id]),
        ]);
    }

    public function gatewayRedirect(Request $request, string $type, int $id)
    {
        // Placeholder for gateway integration
        // For now, create pending purchase and redirect to history
        $userId = Auth::id();

        if ($type === 'material') {
            $item = Material::find($id);
            $purchasableType = Material::class;
        } else {
            $item = Tryout::find($id);
            $purchasableType = Tryout::class;
        }

        if (!$item) {
            return redirect()->back()->with('error', 'Item tidak ditemukan.');
        }

        $transactionId = 'IND-' . strtoupper($type) . '-' . $id . '-' . $userId . '-' . time();

        IndividualPurchase::create([
            'user_id' => $userId,
            'purchasable_type' => $purchasableType,
            'purchasable_id' => $id,
            'price' => $item->price,
            'admin_fee' => 0,
            'total_amount' => $item->price,
            'payment_method' => 'gateway',
            'status' => IndividualPurchase::STATUS_PENDING,
            'transaction_id' => $transactionId,
        ]);

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
}
