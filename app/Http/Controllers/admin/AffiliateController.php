<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\AffiliateCommission;
use App\Models\AffiliateSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AffiliateController extends Controller
{
    public function index()
    {
        $setting = AffiliateSetting::current();
        $commissions = AffiliateCommission::with(['affiliateUser', 'referredUser', 'package', 'payment'])
            ->latest()
            ->paginate(15);
        $referrals = User::with('referredBy:id,name,email,affiliate_code')
            ->whereNotNull('referred_by_user_id')
            ->latest('referred_at')
            ->paginate(15, ['id', 'name', 'email', 'referred_by_user_id', 'referred_at'], 'referrals_page');

        $summary = [
            'pending' => AffiliateCommission::where('status', AffiliateCommission::STATUS_PENDING)->sum('commission_amount'),
            'approved' => AffiliateCommission::where('status', AffiliateCommission::STATUS_APPROVED)->sum('commission_amount'),
            'paid' => AffiliateCommission::where('status', AffiliateCommission::STATUS_PAID)->sum('commission_amount'),
            'total' => AffiliateCommission::sum('commission_amount'),
        ];

        return view('admin.pages.affiliate.index', compact('setting', 'commissions', 'referrals', 'summary'));
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'is_active' => ['nullable', 'boolean'],
            'commission_type' => ['required', Rule::in(['percent', 'fixed'])],
            'commission_value' => ['required', 'numeric', 'min:0'],
            'invitee_discount_enabled' => ['nullable', 'boolean'],
            'invitee_discount_type' => ['required', Rule::in(['percent', 'fixed'])],
            'invitee_discount_value' => ['required', 'numeric', 'min:0'],
            'invitee_max_discount_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $setting = AffiliateSetting::current();
        $setting->update([
            ...$validated,
            'is_active' => $request->boolean('is_active'),
            'invitee_discount_enabled' => $request->boolean('invitee_discount_enabled'),
        ]);

        return redirect()
            ->route('admin.affiliate.index')
            ->with('success', 'Pengaturan affiliate berhasil disimpan.');
    }

    public function approve(AffiliateCommission $commission)
    {
        if ($commission->status !== AffiliateCommission::STATUS_PENDING) {
            return redirect()->route('admin.affiliate.index')->with('error', 'Komisi sudah diproses sebelumnya.');
        }

        $commission->update([
            'status' => AffiliateCommission::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by' => Auth::id(),
        ]);

        return redirect()->route('admin.affiliate.index')->with('success', 'Komisi berhasil disetujui.');
    }

    public function markPaid(AffiliateCommission $commission)
    {
        if (!in_array($commission->status, [AffiliateCommission::STATUS_PENDING, AffiliateCommission::STATUS_APPROVED], true)) {
            return redirect()->route('admin.affiliate.index')->with('error', 'Komisi sudah dibayar atau dibatalkan.');
        }

        $commission->update([
            'status' => AffiliateCommission::STATUS_PAID,
            'approved_at' => $commission->approved_at ?: now(),
            'approved_by' => $commission->approved_by ?: Auth::id(),
            'paid_at' => now(),
            'paid_by' => Auth::id(),
        ]);

        return redirect()->route('admin.affiliate.index')->with('success', 'Komisi ditandai sudah dibayar.');
    }

    public function cancel(AffiliateCommission $commission)
    {
        if ($commission->status === AffiliateCommission::STATUS_PAID) {
            return redirect()->route('admin.affiliate.index')->with('error', 'Komisi yang sudah dibayar tidak bisa dibatalkan.');
        }

        $commission->update([
            'status' => AffiliateCommission::STATUS_CANCELLED,
        ]);

        return redirect()->route('admin.affiliate.index')->with('success', 'Komisi berhasil dibatalkan.');
    }
}
