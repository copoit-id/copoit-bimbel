<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use App\Models\AffiliateCommission;
use App\Services\AffiliateService;
use Illuminate\Support\Facades\Auth;

class AffiliateController extends Controller
{
    public function index(AffiliateService $affiliateService)
    {
        $user = Auth::user();
        $code = $affiliateService->ensureCode($user);
        $referralLink = route('register', ['ref' => $code]);

        $commissions = AffiliateCommission::with(['referredUser', 'package'])
            ->where('affiliate_user_id', $user->id)
            ->latest()
            ->paginate(\App\Support\Pagination::perPage(10));

        $summary = [
            'pending' => AffiliateCommission::where('affiliate_user_id', $user->id)
                ->where('status', AffiliateCommission::STATUS_PENDING)
                ->sum('commission_amount'),
            'approved' => AffiliateCommission::where('affiliate_user_id', $user->id)
                ->where('status', AffiliateCommission::STATUS_APPROVED)
                ->sum('commission_amount'),
            'paid' => AffiliateCommission::where('affiliate_user_id', $user->id)
                ->where('status', AffiliateCommission::STATUS_PAID)
                ->sum('commission_amount'),
            'total' => AffiliateCommission::where('affiliate_user_id', $user->id)->sum('commission_amount'),
            'referrals_count' => $user->referrals()->count(),
        ];

        $referrals = $user->referrals()
            ->latest('referred_at')
            ->limit(10)
            ->get(['id', 'name', 'email', 'referred_at']);

        return view('user.pages.affiliate.index', compact('code', 'referralLink', 'commissions', 'summary', 'referrals'));
    }
}
