<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\IndividualPurchase;
use App\Models\Material;
use App\Models\TesKoran;
use App\Models\Tryout;
use App\Models\User;
use App\Models\UserMaterialAccess;
use App\Models\UserTryoutAccess;
use App\Services\PurchaseAccessDuration;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IndividualPurchaseController extends Controller
{
    /**
     * List individual purchases (materials & tryouts) with tabs
     */
    public function index(Request $request)
    {
        $type = $request->get('type', 'material');
        if ($type === 'tes_koran' && !($request->user()?->hasPermission('tes_koran', 'view') ?? false)) {
            $type = 'material';
        }

        $status = $request->get('status', 'pending'); // 'pending', 'approved', 'rejected', 'all'

        $purchasableType = match ($type) {
            'tryout' => Tryout::class,
            'tes_koran' => TesKoran::class,
            default => Material::class,
        };

        $query = IndividualPurchase::with(['user', 'purchasable'])
            ->where('purchasable_type', $purchasableType)
            ->orderBy('created_at', 'desc');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $purchases = $query->paginate(15)->withQueryString();

        // Stats
        $stats = [
            'total' => IndividualPurchase::where('purchasable_type', $purchasableType)->count(),
            'pending' => IndividualPurchase::where('purchasable_type', $purchasableType)->where('status', 'pending')->count(),
            'approved' => IndividualPurchase::where('purchasable_type', $purchasableType)->where('status', 'approved')->count(),
            'rejected' => IndividualPurchase::where('purchasable_type', $purchasableType)->where('status', 'rejected')->count(),
        ];

        return view('admin.pages.individual-purchase.index', compact(
            'purchases',
            'stats',
            'type',
            'status'
        ));
    }

    /**
     * Show purchase detail
     */
    public function show($id)
    {
        $purchase = IndividualPurchase::with(['user', 'purchasable', 'approver'])->findOrFail($id);

        // Get purchasable display info
        $itemType = class_basename($purchase->purchasable_type);
        $itemTitle = $purchase->purchasable?->title
            ?? $purchase->purchasable?->name
            ?? 'N/A';

        // Decode payment proof
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

    /**
     * Approve purchase & grant access
     */
    public function confirm(Request $request, $id)
    {
        $purchase = IndividualPurchase::findOrFail($id);

        if ($purchase->status !== 'pending') {
            return redirect()->route('admin.pembayaran.item.show', $id)
                ->with('error', 'Pembelian sudah diproses sebelumnya.');
        }

        try {
            $purchase->loadMissing('purchasable');
            $approvedAt = Carbon::now();
            $accessExpiresAt = $purchase->purchasable
                ? PurchaseAccessDuration::expiresAt($purchase->purchasable, $approvedAt)
                : null;

            // Grant access based on purchasable type
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

    /**
     * Reject purchase
     */
    public function reject(Request $request, $id)
    {
        $purchase = IndividualPurchase::findOrFail($id);

        if ($purchase->status !== 'pending') {
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
