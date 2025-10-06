<?php

namespace App\Http\Controllers;

use App\Models\UserPackageAcces;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

abstract class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function __construct()
    {
        // Share sidebar data with all views
        View::composer('*', function ($view) {
            if (Auth::check()) {
                $sidebarPackages = UserPackageAcces::where('user_id', Auth::id())
                    ->where('status', 'active')
                    ->where(function ($query) {
                        $query->whereNull('end_date')
                            ->orWhere('end_date', '>', Carbon::now());
                    })
                    // Add additional check to ensure payment is valid
                    ->whereIn('payment_status', ['paid', 'free']) // Only show paid or free packages
                    ->with(['package' => function ($query) {
                        $query->where('status', 'active');
                    }])
                    ->get()
                    ->filter(function ($access) {
                        // Double check that package exists and payment amount makes sense
                        return $access->package !== null &&
                            ($access->payment_status === 'free' || $access->payment_amount > 0);
                    });

                $view->with('sidebarPackages', $sidebarPackages);
            }
        });
    }
}
